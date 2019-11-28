<?php


namespace Shekel;


use Shekel\Contracts\PaymentProviderContract;
use Shekel\Providers\StripePaymentProvider;

class Shekel
{
    static $disableAllProviders = false;

    /** @var PaymentProviderContract[] */
    static $paymentProviders = [
        'stripe' => StripePaymentProvider::class,
    ];

    /** @var PaymentProviderContract[] */
    static $activePaymentProviders = [];

    /**
     * @return bool
     * @throws \Exception
     */
    public static function stripeActive(): bool
    {
        return self::paymentProviderActive(StripePaymentProvider::class);
    }

    /**
     * @param string $provider
     * @throws \Exception
     */
    public static function activatePaymentProvider(string $provider)
    {
        if (class_exists($provider)) {
            $provider = new $provider();
        } elseif (isset(self::$paymentProviders[$provider])) {
            $provider = new self::$paymentProviders[$provider]();
        } else {
            throw new \Exception('Payment provider "' . $provider . '" not found.');
        }
        if (!$provider instanceof PaymentProviderContract) {
            throw new \Exception('Payment provider has to implement "' . PaymentProviderContract::class . '" in order to be active');
        }
        if (!isset($provider::$key)) {
            throw new \Exception('Payment provider needs to have a static key (static $key = "provider-name")');
        }

        self::$activePaymentProviders[$provider::$key] = $provider;
    }

    /**
     * @param string $provider
     * @return bool
     * TODO SHOULD CACHE THIS METHOD WITH CACHEABLE TRAIT
     */
    public static function paymentProviderActive(string $provider): bool
    {
        if (self::$disableAllProviders) {
            return false;
        }

        foreach (self::$activePaymentProviders as $paymentProvider) {
            if (get_class($paymentProvider) === $provider || $paymentProvider::$key === $provider) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $provider
     * @return PaymentProviderContract
     * @throws \Exception
     */
    public static function getPaymentProvider(string $provider): PaymentProviderContract
    {
        foreach (self::$activePaymentProviders as $paymentProvider) {
            if (get_class($paymentProvider) === $provider || $paymentProvider::$key === $provider) {
                return $paymentProvider;
            }
        }

        throw new \Exception('Payment provider : ' . $provider . ' not found.');
    }

    /**
     * @return string
     */
    public static function getCurrency(): string
    {
        return 'usd';
    }

}