<?php


namespace Shekel\Tests\Feature;


use Shekel\Models\Subscription;
use Shekel\Tests\TestCase;

class SubscriptionHandlerTest extends TestCase
{

    public function test_subscription_can_change_quantity()
    {
        $user = $this->makeUser(['meta' => []]);
        $plan = $this->makePlan(['meta' => []]);

        $paymentMethod = $this->makePaymentMethod();

        /** @var Subscription $subscription */
        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $subscription->changeQuantity(10);

        $subscription->fresh();

        $this->assertEquals(10, $subscription->getMeta('stripe.quantity'));

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertEquals(10, $stripeSubscription->quantity);
    }

    public function test_subscription_can_change_plan()
    {
        $user = $this->makeUser(['meta' => []]);

        $plan1 = $this->makePlan(['title' => 'plan1', 'price' => 999, 'billing_period' => 'month', 'meta' => []]);
        $plan2 = $this->makePlan(['title' => 'plan2', 'price' => 9999, 'billing_period' => 'year', 'meta' => []]);

        $paymentMethod = \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 11,
                'exp_year' => now()->addYear()->year,
                'cvc' => '314',
            ],
        ]);

        /** @var Subscription $subscription */
        $subscription = $user->newSubscription('stripe', $plan1->id, $paymentMethod->id)->create();

        $subscription->changePlan($plan2->id);

        $subscription->fresh();

        $this->assertEquals($plan2->id, $subscription->plan_id);
        $this->assertEquals($plan2->getMeta('stripe.plan_id'), $subscription->getMeta('stripe.plan_id'));

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertEquals($plan2->getMeta('stripe.plan_id'), $stripeSubscription->plan->id);

    }


    public function test_subscription_can_change_plan_without_trial()
    {
        $user = $this->makeUser(['meta' => []]);

        $plan1 = $this->makePlan(['title' => 'plan1', 'price' => 999, 'trial_period_days' => null, 'billing_period' => 'month', 'meta' => []]);
        $plan2 = $this->makePlan(['title' => 'plan2', 'price' => 9999, 'trial_period_days' => null, 'billing_period' => 'year', 'meta' => []]);

        $paymentMethod = \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 11,
                'exp_year' => now()->addYear()->year,
                'cvc' => '314',
            ],
        ]);

        /** @var Subscription $subscription */
        $subscription = $user->newSubscription('stripe', $plan1->id, $paymentMethod->id)->create();

        $subscription->changePlan($plan2->id);

        $subscription->fresh();

        $this->assertEquals($plan2->id, $subscription->plan_id);
        $this->assertEquals($plan2->getMeta('stripe.plan_id'), $subscription->getMeta('stripe.plan_id'));

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertEquals($plan2->getMeta('stripe.plan_id'), $stripeSubscription->plan->id);
    }

}