<?php


namespace Shekel\Traits;


use Carbon\Carbon;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Shekel;

/**
 * Trait HandlesSubscriptions
 * @package Shekel\Traits
 */
trait HandlesSubscription
{
    /** @var SubscriptionHandlerContract */
    private $subscriptionHandler;

    /**
     * @return SubscriptionHandlerContract
     * @throws \Exception
     */
    public function handler(): SubscriptionHandlerContract
    {
        if (!$this->subscriptionHandler) {
            $provider = Shekel::getPaymentProvider($this->payment_provider);
            $this->subscriptionHandler = $provider->getSubscriptionHandler($this);
        }

        return $this->subscriptionHandler;
    }

    /**
     * @throws \Exception
     */
    public function cancel(): void
    {
        $this->handler()->cancel();
    }

    /**
     * @throws \Exception
     */
    public function cancelNow(): void
    {
        $this->handler()->cancelNow();
    }

    /**
     * @return bool
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at ? $this->trial_ends_at->gte(now()) : false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function incomplete(): bool
    {
        return (bool)$this->handler()->incomplete();
    }

    /**
     * @param int $quantity
     * @return HandlesSubscription
     * @throws \Exception
     */
    public function changeQuantity(int $quantity): self
    {
        $this->handler()->changeQuantity($quantity);

        return $this;
    }

    /**
     * @param int $plan_id
     * @return HandlesSubscription
     * @throws \Exception
     */
    public function changePlan(int $plan_id): self
    {
        $this->handler()->changePlan($plan_id);

        return $this;
    }

    /**
     * @return HandlesSubscription
     * @throws \Exception
     */
    public function dontProrate(): self
    {
        $this->handler()->dontProrate();

        return $this;
    }

    /**
     * @return HandlesSubscription
     * @throws \Exception
     */
    public function markAsCancelled(): self
    {
        $this->handler()->markAsCancelled();

        return $this;
    }

}