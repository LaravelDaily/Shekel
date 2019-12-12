<?php


namespace Shekel\Contracts;

use Shekel\Models\Subscription;

interface PaymentProviderContract
{
    public static function key(): string;

    public function getSubscriptionBuilder($user, $plan_id, $paymentMethod): SubscriptionBuilderContract;

    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract;

    public function updateDefaultPaymentMethod(string $paymentMethod);

    public function getDefaultPaymentMethod();
}