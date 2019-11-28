<?php


namespace Shekel\Exceptions;


class PlanHasActiveSubscriptionsException extends \Exception
{

    public function __construct()
    {
        parent::__construct();
        $this->message = 'Can\'t delete plan because there are active subscriptions using it.';
    }

}