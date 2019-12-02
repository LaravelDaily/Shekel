<?php


namespace Shekel\Contracts;

use Shekel\Models\Subscription;

interface SubscriptionBuilderContract
{
    public function __construct($user, int $plan_id, string $paymentMethod);

    public function create(): Subscription;

    public function quantity(int $quantity): SubscriptionBuilderContract;
}