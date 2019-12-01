<?php

namespace Shekel\Providers;

use Shekel\Builders\StripeSubscriptionBuilder;
use Shekel\Contracts\PaymentProviderContract;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Exceptions\PaymentProviderConstructExcelption;
use Shekel\Handlers\StripeSubscriptionHandler;
use Shekel\Models\Subscription;

/**
 * Class Stripe
 * @package Shekel\PaymentVendors
 */
class StripePaymentProvider implements PaymentProviderContract
{
    private ?string $secretKey = null;
    private ?string $publicKey = null;

    public function __construct()
    {
        $this->secretKey = config('shekel.stripe.secret_key');
        $this->publicKey = config('shekel.stripe.public_key');


        if (!$this->secretKey || !$this->publicKey) {
            throw new PaymentProviderConstructExcelption('Can\'t construct StripePaymentProvider - secret or public key not set.');
        }

        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    public static function key(): string
    {
        return 'stripe';
    }

    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract
    {
        return new StripeSubscriptionHandler($subscription);
    }

    public function getSubscriptionBuilder($user, $plan_id, $paymentMethod): SubscriptionBuilderContract
    {
        return new StripeSubscriptionBuilder($user, $plan_id, $paymentMethod);
    }

}