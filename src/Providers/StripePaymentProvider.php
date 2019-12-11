<?php

namespace Shekel\Providers;

use Shekel\Builders\StripeSubscriptionBuilder;
use Shekel\Contracts\PaymentProviderContract;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Exceptions\InvalidStripeCustomerException;
use Shekel\Exceptions\PaymentProviderConstructException;
use Shekel\Handlers\StripeSubscriptionHandler;
use Shekel\Models\Subscription;
use Shekel\Tests\Fixtures\User;

/**
 * Class Stripe
 * @package Shekel\PaymentVendors
 */
class StripePaymentProvider implements PaymentProviderContract
{
    /** @var \Illuminate\Config\Repository|mixed|string|null */
    private $secretKey = null;
    /** @var \Illuminate\Config\Repository|mixed|string|null */
    private $publicKey = null;

    /** @var User|null */
    private $user = null;

    public function __construct()
    {
        $this->secretKey = config('shekel.stripe.secret_key');
        $this->publicKey = config('shekel.stripe.public_key');

        if (!$this->secretKey || !$this->publicKey) {
            throw new PaymentProviderConstructException('Can\'t construct StripePaymentProvider - STRIPE_PUBLIC or STRIPE_SECRET key not set in env.');
        }

        \Stripe\Stripe::setApiKey($this->secretKey);
    }

    public function setUser($user)
    {
        $this->user = $user;
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

    public function getDefaultPaymentMethod()
    {
        $this->assertCustomerExists();
        $customer_id = $this->user->getMeta('stripe.customer_id');
        if (!$customer_id) {
            return null;
        }

        $customer = \Stripe\Customer::retrieve($customer_id);
        if (!$customer->invoice_settings->default_payment_method) {
            return null;
        }

        return \Stripe\PaymentMethod::retrieve($customer->invoice_settings->default_payment_method);
    }

    public function updateDefaultPaymentMethod(string $paymentMethod)
    {
        $this->assertCustomerExists();
        $stripePaymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethod);

        $customer_id = $this->user->getMeta('stripe.customer_id');
        if (!$customer_id) {
            return null;
        }
        $customer = \Stripe\Customer::retrieve($customer_id);

        if ($customer->invoice_settings->default_payment_method === $stripePaymentMethod->id) {
            return;
        }

        $stripePaymentMethod->attach(['customer' => $customer_id]);
        $customer->invoice_settings = ['default_payment_method' => $stripePaymentMethod->id];
        $customer->save();
    }

    private function assertCustomerExists()
    {
        if (!$this->user->getMeta('stripe.customer_id')) {
            throw new InvalidStripeCustomerException();
        }
    }
}