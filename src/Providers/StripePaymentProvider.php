<?php

namespace Shekel\Providers;

use Shekel\Contracts\PaymentProviderContract;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Handlers\StripeSubscriptionHandler;
use Shekel\Models\Subscription;

/**
 * Class Stripe
 * @package Shekel\PaymentVendors
 */
class StripePaymentProvider implements PaymentProviderContract
{
    /** @var string */
    static $key = 'stripe';

    /** @var string|null */
    private $secretKey, $publicKey = null;

    /**
     * StripePaymentProvider constructor.
     */
    public function __construct()
    {
        $this->secretKey = config('shekel.stripe.secret_key');
        $this->publicKey = config('shekel.stipre.public_key');
        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    /**
     * @param Subscription $subscription
     * @return SubscriptionHandlerContract
     */
    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract
    {
        return new StripeSubscriptionHandler($subscription);
    }

}