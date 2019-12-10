<?php


namespace Shekel\Tests\Feature;


use Shekel\Exceptions\InvalidStripeCustomerException;
use Shekel\Tests\Fixtures\User;
use Shekel\Tests\TestCase;

class PaymentMethodTest extends TestCase
{

    public function test_updating_default_payment_method_throws_an_exception()
    {
        $user = $this->makeUser(['meta' => []]);
        $this->expectException(InvalidStripeCustomerException::class);
        $user->stripe()->updateDefaultPaymentMethod('my-id');
    }

    public function test_getting_default_payment_method_throws_an_exception()
    {
        $user = $this->makeUser(['meta' => []]);
        $this->expectException(InvalidStripeCustomerException::class);
        $user->stripe()->getDefaultPaymentMethod();
    }

    public function test_updating_default_payment_method()
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

        $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $this->assertEquals($paymentMethod->id, $user->stripe()->getDefaultPaymentMethod()->id);


        $paymentMethod2 = \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4000004840000008',
                'exp_month' => 12,
                'exp_year' => now()->addYear()->year,
                'cvc' => '123',
            ],
        ]);

        $user->stripe()->updateDefaultPaymentMethod($paymentMethod2->id);

        $this->assertEquals($paymentMethod2->id, $user->stripe()->getDefaultPaymentMethod()->id);
    }

}