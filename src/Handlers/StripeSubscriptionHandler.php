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
     * StripeSubscriptionHandler constructor.
     * @param Subscription $subscription
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
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

    /**
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelNow(): void
    {
        $this->cancel();
        $this->subscription->ends_at = now();
        $this->subscription->save();
    }

    /**
     * @param int $plan_id
     * @throws \Exception
     * TODO HANDLE LOCAL SUBSCRIPTION TRIAL END DATE AND END DATE
     */
    public function changePlan(int $plan_id): void
    {
        if ($this->incomplete()) {
            throw new IncompleteSubscriptionException();
        }

        /** @var Plan $plan */
        $plan = Plan::findOrFail($plan_id);

        $stripeSubscription = \Stripe\Subscription::retrieve($this->subscription->getMeta('stripe.subscription_id'));

        $stripePlanId = $plan->getMeta('stripe.id');

        $stripeSubscription->plan = $stripePlanId;
        $stripeSubscription->cancel_at_period_end = false;

        if ($this->subscription->onTrial()) {
            $stripeSubscription->trial_end = $this->subscription->trial_ends_at->getTimestamp();
        } else {
            $stripeSubscription->trial_end = 'now';
        }

        $stripeSubscription->save();

        $this->subscription->setMeta('stripe.plan_id', $stripePlanId)->save();

    }

    /**
     * @param int $quantity
     * @throws IncompleteSubscriptionException
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function changeQuantity(int $quantity): void
    {
        if ($this->incomplete()) {
            throw new IncompleteSubscriptionException();
        }

        $stripeSubscription = \Stripe\Subscription::retrieve($this->subscription->getMeta('stripe.subscription_id'));
        $stripeSubscription->quantity = $quantity;
        $stripeSubscription->save();

        $this->subscription->setMeta('stripe.quantity', $quantity)->save();
    }

    /**
     * @return bool
     */
    public function incomplete(): bool
    {
        return (bool)$this->subscription->getMeta('stripe.status') === \Stripe\Subscription::STATUS_INCOMPLETE;
    }

}