<?php


namespace Shekel\Traits;


use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Shekel;

/**
 * Trait HandlesSubscriptions
 * @package Shekel\Traits
 */
trait HandlesSubscription
{
    private ?SubscriptionHandlerContract $subscriptionHandler;

    public function handler(): SubscriptionHandlerContract
    {
        if (!$this->subscriptionHandler) {
            $provider = Shekel::getPaymentProvider($this->payment_provider);
            $this->subscriptionHandler = $provider->getSubscriptionHandler($this);
        }

        return $this->subscriptionHandler;
    }

    public function cancel(): void
    {
        $this->handler()->cancel();
    }

    public function cancelNow(): void
    {
        $this->handler()->cancelNow();
    }

    public function onTrial(): bool
    {
        return $this->trial_ends_at ? $this->trial_ends_at->gte(now()) : false;
    }

    public function incomplete(): bool
    {
        return (bool)$this->handler()->incomplete();
    }

    public function changeQuantity(int $quantity): self
    {
        $this->handler()->changeQuantity($quantity);

        return $this;
    }

    public function changePlan(int $plan_id): self
    {
        $this->handler()->changePlan($plan_id);

        return $this;
    }

    public function dontProrate(): self
    {
        $this->handler()->dontProrate();

        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->handler()->markAsCancelled();

        return $this;
    }

}