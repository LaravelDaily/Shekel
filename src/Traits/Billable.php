<?php


namespace Shekel\Traits;


use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Shekel\Builders\StripeSubscriptionBuilder;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Models\Subscription;
use Shekel\Shekel;

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

    public function stripeSubscription(int $plan_id, string $paymentMethod): StripeSubscriptionBuilder
    {
        return new StripeSubscriptionBuilder($this, $plan_id, $paymentMethod);
    }

    public function newSubscription(string $paymentProvider, int $plan_id, string $paymentMethod): SubscriptionBuilderContract
    {
        return Shekel::getPaymentProvider($paymentProvider)->getSubscriptionBuilder($this, $plan_id, $paymentMethod);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    public function subscription(): Subscription
    {
        return $this->subscriptions->sortByDesc(function (Subscription $subscription) {
            return $subscription->created_at->getTimestamp();
        })->first();
    }

}