<?php


namespace Shekel;


use Shekel\Contracts\PaymentProviderContract;
use Shekel\Exceptions\CurrencyNotFoundException;
use Shekel\Exceptions\PaymentProviderNotFoundException;
use Shekel\Exceptions\PaymentProviderNotInConfigException;
use Shekel\Providers\StripePaymentProvider;

class Shekel
{
    static $disableAllProviders = false;

    /** @var PaymentProviderContract[] */
    static $activePaymentProviders = [];

    static $paymentProviders = [
        'stripe' => StripePaymentProvider::class,
    ];

    static $disableMigrations = false;

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

        //check if key is valid
        if (!isset(static::$paymentProviders[$key])) {
            throw new PaymentProviderNotFoundException($key);
        }

        $providerClass = static::$paymentProviders[$key];

        //check if selected provider is in config
        $configuredProviders = config('shekel.providers');
        if (!in_array($key, $configuredProviders) && !in_array($providerClass, $configuredProviders)) {
            throw new PaymentProviderNotInConfigException($key);
        }

        //if provider is not instantiated create a singleton
        if (!isset(static::$activePaymentProviders[$key])) {
            static::$activePaymentProviders[$providerClass::key()] = new $providerClass();
        }

        return static::$activePaymentProviders[$key];
    }

    public static function userModelClass()
    {
        return config('shekel.billable_model');
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