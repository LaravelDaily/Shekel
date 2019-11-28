<?php


namespace Shekel\Exceptions;


class StripePlanNotFoundWhileUpdatingException extends \Exception
{

    public function __construct()
    {
        parent::__construct();
        $this->message = 'Plan not found while updating subscription.';
    }

}