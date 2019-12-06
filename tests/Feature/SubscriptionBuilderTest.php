<?php


namespace Shekel\Tests\Feature;


use Shekel\Models\Subscription;
use Shekel\Tests\TestCase;

class SubscriptionBuilderTest extends TestCase
{

    public function test_user_can_subscribe_to_a_plan()
    {
        $user = $this->makeUser(['meta' => []]);
        $plan = $this->makePlan(['meta' => []]);

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
        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $stripeCustomer = \Stripe\Customer::retrieve($user->getMeta('stripe.customer_id'));
        $this->assertEquals($user->email, $stripeCustomer->email);

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);
        $this->assertNotNull($subscription->getMeta('stripe.subscription_id'));

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertEquals($stripeSubscription->plan->id, $plan->getMeta('stripe.plan_id'));
        $this->assertEquals($stripeSubscription->customer, $user->getMeta('stripe.customer_id'));
        $this->assertEquals($stripeSubscription->trial_end, $subscription->trial_ends_at->timestamp);
        $this->assertNull($subscription->ends_at);
        $this->assertEquals($stripeSubscription->status, 'trialing');
        $this->assertEquals($stripeSubscription->quantity, 1);
        $this->assertEquals($subscription->getMeta('stripe.quantity'), 1);

    }

    public function test_user_can_subscribe_to_a_plan_without_trial()
    {
        $user = $this->makeUser(['meta' => []]);
        $plan = $this->makePlan(['trial_period_days' => null]);

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
        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertNull($stripeSubscription->trial_end);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertNull($subscription->ends_at);
        $this->assertEquals($stripeSubscription->status, 'active');

    }

    public function test_subscription_quantity_change()
    {
        $user = $this->makeUser(['meta' => []]);
        $plan = $this->makePlan(['trial_period_days' => null, 'price' => 12]);

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
        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->quantity(10)->create();

        $stripeSubscription = \Stripe\Subscription::retrieve($subscription->getMeta('stripe.subscription_id'));
        $this->assertEquals(10, $stripeSubscription->quantity);
        $this->assertEquals(10, $subscription->getMeta('stripe.quantity'));

    }

}