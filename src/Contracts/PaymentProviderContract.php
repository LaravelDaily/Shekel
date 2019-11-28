<?php


namespace Shekel\Contracts;


use Shekel\Models\Subscription;

interface PaymentProviderContract
{
    public function getSubscriptionBuilder($user, $plan_id, $paymentMethod): SubscriptionBuilderContract;

    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract;

    public static function key(): string;
}