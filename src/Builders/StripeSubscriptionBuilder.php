<?php

namespace Shekel\Builders;

use Carbon\Carbon;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;

class StripeSubscriptionBuilder implements SubscriptionBuilderContract
{
    private $user;

    /** @var Plan */
    private $plan;

    /** @var \Stripe\Plan */
    private $stripePlan;

    /** @var int */
    private $plan_id;

    /** @var string */
    private $paymentMethod;

    /** @var \Stripe\Customer */
    private $stripeCustomer;

    /** @var int */
    private $quantity = 1;

    /**
     * StripeSubscriptionBuilder constructor.
     * @param $user
     * @param int $plan_id
     * @param string $paymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function __construct($user, int $plan_id, string $paymentMethod)
    {
        $this->user = $user;
        $this->plan_id = $plan_id;
        $this->paymentMethod = $paymentMethod;
        $this->plan = Plan::findOrFail($plan_id);

        $this->stripePlan = \Stripe\Plan::retrieve($this->plan->getMeta('stripe.plan_id'));
    }

    /**
     * @param int $quantity
     * @return StripeSubscriptionBuilder
     */
    public function quantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return int
     */
    private function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return $this
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function create(): Subscription
    {
        $this->stripeCustomer = $this->getStripeCustomer();

        $stripeSubscription = $this->stripeCustomer->subscriptions->create([
            'billing_cycle_anchor' => null,
//            'coupon' => $this->coupon,
            'expand' => ['latest_invoice.payment_intent'],
            'metadata' => [],
            'plan' => $this->stripePlan->id,
            'quantity' => $this->getQuantity(),
            'tax_percent' => 0,
            'off_session' => true,
        ]);


        $subscription = new Subscription([
            'plan_id' => $this->plan->id,
            'user_id' => $this->user->id,
            'payment_provider' => 'stripe',
            'meta' => [
                'stripe' => [
                    'subscription_id' => $stripeSubscription->id,
                    'plan_id' => $stripeSubscription->plan->id,
                    'status' => $stripeSubscription->status,
                    'quantity' => $stripeSubscription->quantity,
                ],
            ],
            'trial_ends_at' => Carbon::createFromFormat('U', $stripeSubscription->trial_end),
            'ends_at' => Carbon::createFromFormat('U', $stripeSubscription->current_period_end),
        ]);
        $subscription->save();

        return $subscription;
    }

    /**
     * @return \Stripe\Customer
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function getStripeCustomer()
    {
        $customer_id = $this->user->getMeta('stripe.customer_id');
        if (!$customer_id || !$stripeCustomer = \Stripe\Customer::retrieve($customer_id)) {
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $this->user->email,
                'payment_method' => $this->paymentMethod,
            ]);

            $this->user->setMeta('stripe.customer_id', $stripeCustomer->id)->save();
        }

        return $stripeCustomer;
    }
}