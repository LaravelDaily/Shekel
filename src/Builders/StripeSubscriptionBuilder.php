<?php

namespace Shekel\Builders;

use Carbon\Carbon;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;
use Shekel\Tests\Fixtures\User;
use Stripe\Customer;

class StripeSubscriptionBuilder implements SubscriptionBuilderContract
{
    /** @var \Shekel\Tests\Fixtures\User DONT TYPEHINT THIS PROPERTY!! */
    private $user;

    private Plan $plan;

    private \Stripe\Plan $stripePlan;

    private int $plan_id;

    private string $paymentMethod;

    private Customer $stripeCustomer;

    private int $quantity = 1;

    public function __construct($user, int $plan_id, string $paymentMethod)
    {
        $this->user = $user;
        $this->plan_id = $plan_id;
        $this->paymentMethod = $paymentMethod;
        $this->plan = Plan::findOrFail($plan_id);

        $this->stripePlan = \Stripe\Plan::retrieve($this->plan->getMeta('stripe.plan_id'));
    }

    public function quantity(int $quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    private function getQuantity(): int
    {
        return $this->quantity;
    }

    public function create(): Subscription
    {
        $this->stripeCustomer = $this->getStripeCustomer();

        $stripeSubscription = $this->stripeCustomer->subscriptions->create([
//            'coupon' => $this->coupon,
            'expand' => ['latest_invoice.payment_intent'],
            'plan' => $this->stripePlan->id,
            'quantity' => $this->getQuantity(),
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
            'trial_ends_at' => $stripeSubscription->trial_end ? Carbon::createFromFormat('U', $stripeSubscription->trial_end) : null,
            'ends_at' => Carbon::createFromFormat('U', $stripeSubscription->current_period_end),
        ]);

        $subscription->save();

        return $subscription;
    }

    private function getStripeCustomer()
    {
        $customer_id = $this->user->getMeta('stripe.customer_id');
        if (!$customer_id || !$stripeCustomer = \Stripe\Customer::retrieve($customer_id)) {
            $stripeCustomer = \Stripe\Customer::create([
                'email' => $this->user->email,
                'payment_method' => $this->paymentMethod,
                'invoice_settings' => [
                    'default_payment_method' => $this->paymentMethod,
                ],
            ]);

            $this->user->setMeta('stripe.customer_id', $stripeCustomer->id)->save();
        }

        return $stripeCustomer;
    }
}