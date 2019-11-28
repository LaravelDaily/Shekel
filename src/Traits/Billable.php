<?php


namespace Shekel\Traits;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Shekel\Builders\StripeSubscriptionBuilder;
use Shekel\Models\Subscription;

/**
 * Trait Billable
 * @package Shekel\Traits
 *
 * @property object $stripe
 * @property Collection $subscriptions
 */
trait Billable
{
    use HasMetaField;

    /**
     * @param int $plan_id
     * @param string $paymentMethod
     * @return StripeSubscriptionBuilder
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function stripeSubscription(int $plan_id, string $paymentMethod): StripeSubscriptionBuilder
    {
        return new StripeSubscriptionBuilder($this, $plan_id, $paymentMethod);
    }

    /**
     * @param string $paymentProvider
     * @param int $plan_id
     * @param string $paymentMethod
     * @return StripeSubscriptionBuilder
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function newSubscription(string $paymentProvider, int $plan_id, string $paymentMethod)
    {
        switch ($paymentProvider) {
            case 'stripe':
                return new StripeSubscriptionBuilder($this, $plan_id, $paymentMethod);
                break;
        }
    }

    /**
     * @return HasMany
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * @return Subscription
     */
    public function subscription(): Subscription
    {
        return $this->subscriptions->sortByDesc(function (Subscription $subscription) {
            return $subscription->created_at->getTimestamp();
        })->first();
    }

}