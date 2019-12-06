<?php


namespace Shekel\Traits;


use Illuminate\Database\Query\Builder;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Shekel;

/**
 * Trait HandlesSubscription
 * @package Shekel\Traits
 */
trait HandlesSubscription
{
    /** @var SubscriptionHandlerContract|null */
    private $subscriptionHandler;

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

    public function changePlan(int $plan_id): self
    {
        $this->handler()->changePlan($plan_id);

        return $this;
    }

    public function changeQuantity(int $quantity): self
    {
        $this->handler()->changeQuantity($quantity);

        return $this;
    }

    public function markAsCancelled(): self
    {
        $this->handler()->markAsCancelled();

        return $this;
    }

    /** SETTINGS */

    public function dontProrate(): self
    {
        $this->handler()->dontProrate();

        return $this;
    }

    /** STATUSES */

    public function active(): bool
    {
        return $this->handler()->active();
    }

    public function onTrial(): bool
    {
        return $this->handler()->onTrial();
    }

    public function incomplete(): bool
    {
        return $this->handler()->incomplete();
    }

    public function onGracePeriod(): bool
    {
        return $this->handler()->onGracePeriod();
    }

    public function canceled(): bool
    {
        return $this->handler()->canceled();
    }

}