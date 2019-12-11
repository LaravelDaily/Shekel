<?php


namespace Shekel\Exceptions;


class PaymentProviderConstructException extends \Exception
{

    public function __construct($message)
    {
        parent::__construct($message);
    }

}