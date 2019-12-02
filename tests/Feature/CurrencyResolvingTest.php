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

        config(['shekel.billable_currency' => 'eur']);
        $this->assertEquals('eur', Shekel::getCurrency());

        //check if currency is converted to lower case
        config(['shekel.billable_currency' => 'EUR']);
        $this->assertEquals('eur', Shekel::getCurrency());
    }
}