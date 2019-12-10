<?php


namespace Shekel\Exceptions;


use Throwable;

class PaymentProviderNotInConfigException extends \Exception
{
    public function __construct(string $provider)
    {
        parent::__construct();
        $this->message = 'Payment provider ' . $provider . ' not set in config file.';
    }
}