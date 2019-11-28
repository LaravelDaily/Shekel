<?php


namespace Shekel\Exceptions;


use Throwable;

class PaymentProviderNotFoundException extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}