<?php


namespace Shekel\Tests\Feature;


use Shekel\Exceptions\PaymentProviderNotFoundException;
use Shekel\Providers\StripePaymentProvider;
use Shekel\Shekel;
use Shekel\Tests\TestCase;

class ShekelProviderResolvingTest extends TestCase
{

    public function test_ensure_providers_are_resolved_correctly()
    {
        Shekel::activatePaymentProvider(StripePaymentProvider::class);
        $stripePaymentProvider = Shekel::getPaymentProvider('stripe');
        $stripePaymentProviderByClass = Shekel::getPaymentProvider(StripePaymentProvider::class);

        $this->assertInstanceOf(StripePaymentProvider::class, $stripePaymentProvider);
        $this->assertInstanceOf(StripePaymentProvider::class, $stripePaymentProviderByClass);
    }

    public function test_resolving_unknown_provider_throws_exception()
    {
        $this->expectException(PaymentProviderNotFoundException::class);
        Shekel::getPaymentProvider('random-provider');
    }

    public function test_ensure_that_payment_provider_active_returns_correct_boolean()
    {
        Shekel::activatePaymentProvider(StripePaymentProvider::class);

        $this->assertTrue(Shekel::paymentProviderActive('stripe'));
        $this->assertTrue(Shekel::paymentProviderActive(StripePaymentProvider::class));
        $this->assertFalse(Shekel::paymentProviderActive('random-payment-provider'));
    }

    public function test_ensure_that_shekel_can_disable_all_providers()
    {
        Shekel::activatePaymentProvider(StripePaymentProvider::class);

        Shekel::$disableAllProviders = true;

        $this->assertFalse(Shekel::paymentProviderActive('stripe'));
        $this->assertFalse(Shekel::paymentProviderActive(StripePaymentProvider::class));
        $this->assertFalse(Shekel::paymentProviderActive('random-payment-provider'));
    }

}