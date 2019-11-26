<?php


namespace Shekel\Contracts;


interface SubscriptionHandlerContract
{
    public function cancel(): void;

    public function cancelNow(): void;

    public function changePlan(int $plan_id): void;

    public function incomplete(): bool;

    public function changeQuantity(int $quantity): void;
}