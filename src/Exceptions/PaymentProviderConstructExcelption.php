<?php


namespace Shekel\Exceptions;


class PaymentProviderConstructExcelption extends \Exception
{

    public function __construct($message)
    {
        parent::__construct($message);
    }

}