<?php


namespace Shekel\Handlers;


use Carbon\Carbon;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Exceptions\IncompleteSubscriptionException;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;

class StripeSubscriptionHandler implements SubscriptionHandlerContract
{

    /** @var Subscription */
    private $subscription;

    /**
     * If set to true will recalculate the cost of subscription
     * when changing plan so the user doesn't have to pay all the amount
     * @var bool
     */
    private $prorate = true;

    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /** ACTIONS */

    public function cancel(): void
    {
        $stripeSubscription = \Stripe\Subscription::retrieve($this->subscription->getMeta('stripe.subscription_id'));

        $stripeSubscription->cancel_at_period_end = true;
        $stripeSubscription = $stripeSubscription->save();
        $this->subscription->setMeta('stripe.status', $stripeSubscription->status);

        if ($this->subscription->onTrial()) {
            $this->subscription->ends_at = $this->subscription->trial_ends_at;
        } else {
            $this->subscription->ends_at = Carbon::createFromFormat('U', $stripeSubscription->current_period_end);
        }

        $this->subscription->save();
    }

    public function cancelNow(): void
    {
        $this->cancel();
        $this->markAsCancelled();
    }

    public function markAsCancelled(): void
    {
        $this->subscription->ends_at = Carbon::now();
        $this->subscription->setMeta('stripe.status', \Stripe\Subscription::STATUS_CANCELED);
        $this->subscription->save();
    }

    public function changePlan(int $plan_id): void
    {
        if ($this->incomplete()) {
            throw new IncompleteSubscriptionException();
        }

        /** @var Plan $plan */
        $plan = Plan::findOrFail($plan_id);

        $stripeSubscription = \Stripe\Subscription::retrieve($this->subscription->getMeta('stripe.subscription_id'));

        $stripePlanId = $plan->getMeta('stripe.plan_id');

        $stripeSubscription->plan = $stripePlanId;
        $stripeSubscription->cancel_at_period_end = false;
        $stripeSubscription->prorate = $this->prorate;

        if ($this->subscription->onTrial()) {
            $stripeSubscription->trial_end = $this->subscription->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->save();

        $this->subscription->setMeta('stripe.plan_id', $stripePlanId)->save();
    }

    public function changeQuantity(int $quantity): void
    {
        if ($this->incomplete()) {
            throw new IncompleteSubscriptionException();
        }

        $stripeSubscription = \Stripe\Subscription::retrieve($this->subscription->getMeta('stripe.subscription_id'));
        $stripeSubscription->quantity = $quantity;
        $stripeSubscription->prorate = $this->prorate;
        $stripeSubscription->save();

        $this->subscription->setMeta('stripe.quantity', $quantity)->save();
    }

    /** ATTRIBUTES */

    public function currentPeriodEndsAt(): Carbon
    {
        return Carbon::createFromTimestamp($this->subscription->getMeta('stripe.current_period_ends_at'));
    }

    /** SETTINGS */

    public function dontProrate(): void
    {
        $this->prorate = false;
    }

    /** STATUSES */

    public function active(): bool
    {
        return $this->onTrial() || $this->onGracePeriod() || $this->subscription->getMeta('stripe.status') === \Stripe\Subscription::STATUS_ACTIVE;
    }

    public function onTrial(): bool
    {
        return $this->subscription->trial_ends_at && $this->subscription->trial_ends_at->isFuture();
    }

    public function incomplete(): bool
    {
        return $this->subscription->getMeta('stripe.status') === \Stripe\Subscription::STATUS_INCOMPLETE;
    }

    public function onGracePeriod(): bool
    {
        return $this->subscription->ends_at && $this->subscription->ends_at->isFuture();
    }

    public function canceled(): bool
    {
        return (bool)$this->subscription->ends_at;
    }

}