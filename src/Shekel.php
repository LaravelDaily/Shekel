<?php


namespace Shekel;


use Shekel\Contracts\PaymentProviderContract;
use Shekel\Exceptions\CurrencyNotFoundException;
use Shekel\Exceptions\PaymentProviderNotFoundException;

class Shekel
{
    static bool $disableAllProviders = false;

    /** @var PaymentProviderContract[] */
    static $activePaymentProviders = [];

    static bool $disableMigrations = false;

    public static function activatePaymentProvider(string $provider)
    {
        if (class_exists($provider)) {
            $provider = new $provider();
        } else {
            throw new \Exception('Payment provider "' . $provider . '" not found.');
        }
        if (!$provider instanceof PaymentProviderContract) {
            throw new \Exception('Payment provider has to implement "' . PaymentProviderContract::class . '" in order to be active');
        }

        self::$activePaymentProviders[$provider::key()] = $provider;
    }

    public static function paymentProviderActive(string $provider): bool
    {
        if (self::$disableAllProviders) {
            return false;
        }

        try {
            self::getPaymentProvider($provider);

            return true;
        } catch (PaymentProviderNotFoundException $e) {
            return false;
        }
    }

    public static function getPaymentProvider(string $provider): PaymentProviderContract
    {
        $key = class_exists($provider) ? $provider::key() : $provider;

        //If resolving by key we can return the value straight from the array
        if (isset(self::$activePaymentProviders[$key])) {
            return self::$activePaymentProviders[$key];
        }

        throw new PaymentProviderNotFoundException('Payment provider : ' . $provider . ' not found.');
    }

    public static function getCurrency(): string
    {
        $currencies = [
            "USD",
            "AED",
            "AFN",
            "ALL",
            "AMD",
            "ANG",
            "AOA",
            "ARS",
            "AUD",
            "AWG",
            "AZN",
            "BAM",
            "BBD",
            "BDT",
            "BGN",
            "BIF",
            "BMD",
            "BND",
            "BOB",
            "BRL",
            "BSD",
            "BWP",
            "BZD",
            "CAD",
            "CDF",
            "CHF",
            "CLP",
            "CNY",
            "COP",
            "CRC",
            "CVE",
            "CZK",
            "DJF",
            "DKK",
            "DOP",
            "DZD",
            "EGP",
            "ETB",
            "EUR",
            "FJD",
            "FKP",
            "GBP",
            "GEL",
            "GIP",
            "GMD",
            "GNF",
            "GTQ",
            "GYD",
            "HKD",
            "HNL",
            "HRK",
            "HTG",
            "HUF",
            "IDR",
            "ILS",
            "INR",
            "ISK",
            "JMD",
            "JPY",
            "KES",
            "KGS",
            "KHR",
            "KMF",
            "KRW",
            "KYD",
            "KZT",
            "LAK",
            "LBP",
            "LKR",
            "LRD",
            "LSL",
            "MAD",
            "MDL",
            "MGA",
            "MKD",
            "MMK",
            "MNT",
            "MOP",
            "MRO",
            "MUR",
            "MVR",
            "MWK",
            "MXN",
            "MYR",
            "MZN",
            "NAD",
            "NGN",
            "NIO",
            "NOK",
            "NPR",
            "NZD",
            "PAB",
            "PEN",
            "PGK",
            "PHP",
            "PKR",
            "PLN",
            "PYG",
            "QAR",
            "RON",
            "RSD",
            "RUB",
            "RWF",
            "SAR",
            "SBD",
            "SCR",
            "SEK",
            "SGD",
            "SHP",
            "SLL",
            "SOS",
            "SRD",
            "STD",
            "SZL",
            "THB",
            "TJS",
            "TOP",
            "TRY",
            "TTD",
            "TWD",
            "TZS",
            "UAH",
            "UGX",
            "UYU",
            "UZS",
            "VND",
            "VUV",
            "WST",
            "XAF",
            "XCD",
            "XOF",
            "XPF",
            "YER",
            "ZAR",
            "ZMW",
        ];

        $currency = config('shekel.billable_currency');

        if (!in_array(strtoupper($currency), $currencies)) {
            throw new CurrencyNotFoundException($currency);
        }

        return strtolower($currency);
    }

}