<?php


namespace Shekel\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Shekel\Exceptions\PlanHasActiveSubscriptionsException;
use Shekel\Exceptions\UpdatingRestrictedPlanFieldException;
use Shekel\Models\Plan;
use Shekel\Shekel;
use Shekel\Tests\TestCase;
use Stripe\Exception\InvalidRequestException;

class PlanTest extends TestCase
{

    private function newPlan(array $fields = []): Plan
    {
        return Plan::create(array_merge([
            'title' => 'basic',
            'price' => 999,
            'billing_period' => 'month',
            'trial_period_days' => 30,
        ], $fields));
    }

    public function test_that_trial_cant_exceed_730_days()
    {
        Shekel::$disableAllProviders = true;
        $this->expectException(ValidationException::class);
        $this->newPlan(['trial_period_days' => 800]);
    }

    public function test_plan_creation()
    {
        /** @var Plan $plan */
        $plan = $this->newPlan();

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertNotNull($plan->getMeta('stripe.plan_id'));
        $this->assertNotNull($plan->getMeta('paypal.plan_id'));

        if (Shekel::paymentProviderActive('stripe')) {
            //STRIPE
            $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
            $this->assertNotNull($stripePlan);
            $this->assertEquals($stripePlan->amount, 999);
            $this->assertEquals($stripePlan->interval, 'month');
            $this->assertEquals($stripePlan->currency, 'usd');
            $this->assertEquals($stripePlan->trial_period_days, 30);
        }

        if (Shekel::paymentProviderActive('paypal')) {

            //PAYPAL
            $paypalPlan = \PayPal\Api\Plan::get($plan->getMeta('paypal.plan_id'), Shekel::paypal()->getApiContext());
            $this->assertEquals('CREATED', $paypalPlan->state);
            $this->assertEquals($plan->title, $paypalPlan->name);
            $this->assertEquals('INFINITE', $paypalPlan->type);
            $paymentDefinitions = collect($paypalPlan->payment_definitions);
            $this->assertEquals(2, $paymentDefinitions->count());

            //PAYMENT DEFINITION
            $paymentDefinition = $paymentDefinitions->where('name', 'Regular Payments')->first();
            $this->assertNotNull($paymentDefinition);
            $this->assertEquals('REGULAR', $paymentDefinition->type);
            $this->assertEquals(ucfirst($plan->billing_period), $paymentDefinition->frequency);
            $this->assertEquals(strtoupper(Shekel::getCurrency()), $paymentDefinition->amount->currency);
            $this->assertEquals($plan->price, $paymentDefinition->amount->value);
            $this->assertEquals(0, $paymentDefinition->cycles);
            $this->assertEquals(1, $paymentDefinition->frequency_interval);

            //TRIAL DEFINITION
            $trialDefinition = $paymentDefinitions->where('name', 'Trial period')->first();
            $this->assertNotNull($trialDefinition);
            $this->assertEquals('TRIAL', $trialDefinition->type);
            $this->assertEquals('Day', $trialDefinition->frequency);
            $this->assertEquals(strtoupper(Shekel::getCurrency()), $trialDefinition->amount->currency);
            $this->assertEquals(0, $trialDefinition->amount->value);
            $this->assertEquals(1, $trialDefinition->cycles);
            $this->assertEquals($plan->trial_period_days, $trialDefinition->frequency_interval);
        }


        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'price' => 999, 'billing_period' => 'month', 'trial_period_days' => 30]);

        $plan->delete();
    }

    public function test_restricted_fields_cant_be_updated()
    {
        Shekel::$disableAllProviders = true;

        $plan = $this->newPlan();
        $this->expectException(UpdatingRestrictedPlanFieldException::class);
        $plan->update(['title' => 'test-title']);

        $this->assertDatabaseMissing('plans', ['title' => 'test-title']);

        $plan->delete();
    }

    public function test_that_trial_cant_exceed_730_days_while_updating()
    {
        Shekel::$disableAllProviders = true;

        $plan = $this->newPlan();
        $this->expectException(ValidationException::class);
        $plan->update(['trial_period_days' => 800]);

        $this->assertDatabaseMissing('plans', ['trial_period_days' => 800]);

        $plan->delete();
    }

    public function test_plan_updating()
    {
        $plan = $this->newPlan();
        $plan->update(['trial_period_days' => 100]);

        $this->assertEquals(100, $plan->trial_period_days);

        if (Shekel::paymentProviderActive('stripe')) {
            $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
            $this->assertEquals($plan->trial_period_days, $stripePlan->trial_period_days);
        }

        if (Shekel::paymentProviderActive('paypal')) {
            $paypalPlan = \PayPal\Api\Plan::get($plan->getMeta('paypal.plan_id'), Shekel::paypal()->getApiContext());
            $paymentDefinitions = collect($paypalPlan->payment_definitions);
            $trialDefinition = $paymentDefinitions->where('name', 'Trial period')->first();
            $this->assertEquals('Day', $trialDefinition->frequency);
            $this->assertEquals(1, $trialDefinition->cycles);
            $this->assertEquals($plan->trial_period_days, $trialDefinition->frequency_interval);
        }

        $plan->delete();
    }

    public function test_plan_updated_to_no_trial()
    {
        $plan = $this->newPlan(['trial_period_days' => 30]);
        $plan->update(['trial_period_days' => null]);

        $this->assertEquals(null, $plan->trial_period_days);

        if (Shekel::paymentProviderActive('stripe')) {
            $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
            $this->assertEquals(null, $stripePlan->trial_period_days);
        }

        if (Shekel::paymentProviderActive('paypal')) {
            $paypalPlan = \PayPal\Api\Plan::get($plan->getMeta('paypal.plan_id'), Shekel::paypal()->getApiContext());
            $trialDefinition = collect($paypalPlan->getPaymentDefinitions())->where('type', 'TRIAL')->first();
            dd($paypalPlan);
            $this->assertNull($trialDefinition);
        }

        $plan->delete();
    }

    public function test_plan_deletion_stripe()
    {
        $plan = $this->newPlan();
        $planCopy = $plan;
        $stripePlanId = $plan->getMeta('stripe.plan_id');

        $plan->delete();

        $this->assertDatabaseMissing('plans', ['id' => $planCopy->id]);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No such plan: ' . $stripePlanId);
        \Stripe\Plan::retrieve($stripePlanId);
    }

    public function test_plan_deletion_paypal()
    {
        $plan = $this->newPlan();
        $planCopy = $plan;

        $plan->delete();

        $this->assertDatabaseMissing('plans', ['id' => $planCopy->id]);

        $paypalPlan = \PayPal\Api\Plan::get($planCopy->getMeta('paypal.plan_id'), Shekel::paypal()->getApiContext());
        $this->assertEquals('DELETED', $paypalPlan->state);
    }

    public function test_plan_delete_while_having_subscriptions()
    {
        $this->makeSubscription();
        /** @var Plan $plan */
        $plan = Plan::first();

        try {
            $plan->delete();
        } catch (\Exception $e) {
            $this->assertInstanceOf(PlanHasActiveSubscriptionsException::class, $e);
        } finally {
            $this->assertDatabaseHas('plans', ['id' => $plan->id]);

            if (Shekel::paymentProviderActive('stripe')) {
                $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
                $this->assertNotNull($stripePlan);
            }

            if (Shekel::paymentProviderActive('paypal')) {
                $paypalPlan = \PayPal\Api\Plan::get($plan->getMeta('paypal.plan_id'), Shekel::paypal()->getApiContext());
                $this->assertEquals('CREATED', $paypalPlan->state);
            }
        }


    }

}