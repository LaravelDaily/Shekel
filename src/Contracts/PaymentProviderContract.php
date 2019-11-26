<?php


namespace Shekel\Contracts;


use Shekel\Models\Subscription;

interface PaymentProviderContract
{
    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract;
}