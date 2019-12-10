<?php


namespace Shekel\Exceptions;


class InvalidStripeCustomerException extends \Exception
{
    public function __construct()
    {
        parent::__construct();
        $this->message = 'No stripe customer setup on user.';
    }
}