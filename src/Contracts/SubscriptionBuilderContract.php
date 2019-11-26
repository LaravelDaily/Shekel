<?php


namespace Shekel\Contracts;


use App\User;

interface SubscriptionBuilderContract
{
    public function __construct(User $user, int $plan_id, string $paymentMethod);

    public function create();
}