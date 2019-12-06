<?php


namespace Shekel\Contracts;


use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

interface SubscriptionHandlerContract
{
    /** ACTIONS */

    public function cancel(): void;

    public function cancelNow(): void;

    public function changePlan(int $plan_id): void;

    public function changeQuantity(int $quantity): void;

    public function markAsCancelled(): void;

    /** ATTRIBUTES */

    public function currentPeriodEndsAt(): Carbon;
    
    /** SETTINGS */

    public function dontProrate(): void;

    /** STATUSES */

    public function active(): bool;

    public function onTrial(): bool;

    public function incomplete(): bool;

    public function onGracePeriod(): bool;

    public function canceled(): bool;

}