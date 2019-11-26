<?php

namespace Shekel\Exceptions;

class IncompleteSubscriptionException extends \Exception
{

    /**
     * IncompleteSubscriptionException constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->message = 'Can\'t change plan on incomplete subscriptions.';
    }

}