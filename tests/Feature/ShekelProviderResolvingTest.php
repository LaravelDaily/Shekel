<?php


namespace Shekel\Tests\Feature;


use Shekel\Exceptions\PaymentProviderConstructException;
use Shekel\Exceptions\PaymentProviderNotFoundException;
use Shekel\Exceptions\PaymentProviderNotInConfigException;
use Shekel\Providers\StripePaymentProvider;
use Shekel\Shekel;
use Shekel\Tests\TestCase;

class ShekelProviderResolvingTest extends TestCase
{

    public function test_ensure_providers_are_resolved_correctly()
    {
        $stripePaymentProvider = Shekel::getPaymentProvider('stripe');
        $stripePaymentProviderByClass = Shekel::getPaymentProvider(StripePaymentProvider::class);

        $this->assertInstanceOf(StripePaymentProvider::class, $stripePaymentProvider);
        $this->assertInstanceOf(StripePaymentProvider::class, $stripePaymentProviderByClass);
    }

    public function test_resolving_unconfigured_provider()
    {
        config(['shekel.providers' => []]);

        $this->expectException(PaymentProviderNotInConfigException::class);
        Shekel::getPaymentProvider('stripe');
    }

    public function test_resolving_stripe_provider_without_api_keys()
    {
        config(['shekel.stripe' => [
            'public_key' => null,
            'secret_key' => null,
        ]]);

        $this->expectException(PaymentProviderConstructException::class);
        Shekel::getPaymentProvider('stripe');

    }

    public function test_resolving_unknown_provider_throws_exception()
    {
        $this->expectException(PaymentProviderNotFoundException::class);
        Shekel::getPaymentProvider('random-provider');
    }

    public function test_ensure_that_payment_provider_active_returns_correct_boolean()
    {
        $this->assertTrue(Shekel::paymentProviderActive('stripe'));
        $this->assertTrue(Shekel::paymentProviderActive(StripePaymentProvider::class));
        $this->assertFalse(Shekel::paymentProviderActive('random-payment-provider'));
    }

    public function test_ensure_that_shekel_can_disable_all_providers()
    {
        Shekel::$disableAllProviders = true;

        $this->assertFalse(Shekel::paymentProviderActive('stripe'));
        $this->assertFalse(Shekel::paymentProviderActive(StripePaymentProvider::class));
        $this->assertFalse(Shekel::paymentProviderActive('random-payment-provider'));
    }

}