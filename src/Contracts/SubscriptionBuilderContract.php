<?php


namespace Shekel\Contracts;

interface SubscriptionBuilderContract
{
    public function __construct($user, int $plan_id, string $paymentMethod);

    public function create();
}