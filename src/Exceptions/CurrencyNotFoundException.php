<?php

namespace Shekel\Exceptions;

class CurrencyNotFoundException extends \Exception
{

    /**
     * IncompleteSubscriptionException constructor.
     */
    public function __construct($currency)
    {
        parent::__construct();
        $this->message = 'Currency ' . $currency . ' not found.';
    }

}