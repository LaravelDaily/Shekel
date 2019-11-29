<?php


namespace Shekel\Tests\Feature;


use Shekel\Shekel;
use Shekel\Tests\TestCase;

class CurrencyResolvingTest extends TestCase
{
    public function test_returns_currency_correctly()
    {
        //default to usd
        $this->assertEquals('usd', Shekel::getCurrency());

        //check if eur is returned
        putenv('BILLABLE_CURRENCY=eur');
        $this->assertEquals('eur', Shekel::getCurrency());

        //check if currency is converted to lower case
        putenv('BILLABLE_CURRENCY=EUR');
        $this->assertEquals('eur', Shekel::getCurrency());
    }
}