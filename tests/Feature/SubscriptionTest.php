<?php


namespace Shekel\Tests\Feature;


use Shekel\Tests\TestCase;

class SubscriptionTest extends TestCase
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

        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $this->assertDatabaseHas('subscriptions', ['id' => $subscription->id]);

        //TODO CONTINUE TESTING
    }

}