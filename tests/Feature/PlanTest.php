<?php


namespace Shekel\Tests\Feature;

use Illuminate\Validation\ValidationException;
use Shekel\Exceptions\UpdatingRestrictedPlanFieldException;
use Shekel\Models\Plan;
use Shekel\Shekel;
use Shekel\Tests\TestCase;
use Stripe\Exception\InvalidRequestException;

class PlanTest extends TestCase
{

    private function makePlan(array $fields = []): Plan
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
        $this->makePlan(['trial_period_days' => 800]);
    }

    public function test_plan_creation()
    {
        /** @var Plan $plan */
        $plan = $this->makePlan();

        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
        $this->assertNotNull($plan->getMeta('stripe.plan_id'));

        $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
        $this->assertNotNull($stripePlan);
        $this->assertEquals($stripePlan->amount, 999);
        $this->assertEquals($stripePlan->interval, 'month');
        $this->assertEquals($stripePlan->currency, 'usd');
        $this->assertEquals($stripePlan->trial_period_days, 30);

        //CLEANUP THE CREATE PLAN AND PRODUCT IN STRIPE
        $product_id = $stripePlan->product;
        $stripePlan->delete();
        \Stripe\Product::retrieve($product_id)->delete();
    }

    public function test_restricted_fields_cant_be_updated()
    {
        Shekel::$disableAllProviders = true;

        $plan = $this->makePlan();
        $this->expectException(UpdatingRestrictedPlanFieldException::class);
        $plan->update(['title' => 'test-title']);
    }

    public function test_that_trial_cant_exceed_730_days_while_updating()
    {
        Shekel::$disableAllProviders = true;

        $plan = $this->makePlan();
        $this->expectException(ValidationException::class);
        $plan->update(['trial_period_days' => 800]);
    }

    public function test_plan_updating()
    {
        $plan = $this->makePlan();
        $plan->update(['trial_period_days' => 100]);

        $stripePlan = \Stripe\Plan::retrieve($plan->getMeta('stripe.plan_id'));
        $this->assertEquals($stripePlan->trial_period_days, 100);

        //CLEANUP THE CREATE PLAN AND PRODUCT IN STRIPE
        $product_id = $stripePlan->product;
        $stripePlan->delete();
        \Stripe\Product::retrieve($product_id)->delete();
    }

    public function test_plan_deletion()
    {
        $plan = $this->makePlan();
        $planCopy = $plan;
        $stripePlanId = $plan->getMeta('stripe.plan_id');

        $plan->delete();

        $this->assertDatabaseMissing('plans', ['id' => $planCopy->id]);

        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('No such plan: ' . $stripePlanId);
        \Stripe\Plan::retrieve($stripePlanId);
    }

}