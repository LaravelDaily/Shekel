<?php


namespace Shekel\Providers;


use Shekel\Contracts\PaymentProviderContract;
use Shekel\Contracts\SubscriptionBuilderContract;
use Shekel\Contracts\SubscriptionHandlerContract;
use Shekel\Exceptions\PaymentProviderConstructException;
use Shekel\Models\Subscription;

class PaypalPaymentProvider implements PaymentProviderContract
{

    private $secretKey = null;
    private $clientKey = null;

    /** @var \PayPal\Rest\ApiContext */
    private $apiContext;

    public function __construct()
    {
        $this->secretKey = config('shekel.paypal.secret_key');
        $this->clientKey = config('shekel.paypal.client_key');

        if (!$this->secretKey || !$this->clientKey) {
            throw new PaymentProviderConstructException('Can\'t construct PaypalPaymentProvider - PAYPAL_SECRET or STRIPE_CLIENT_KEY not set in env.');
        }

        $this->apiContext = new \PayPal\Rest\ApiContext(new \PayPal\Auth\OAuthTokenCredential($this->clientKey, $this->secretKey));

    }

    public static function key(): string
    {
        return 'paypal';
    }

    public function getSubscriptionBuilder($user, $plan_id, $paymentMethod): SubscriptionBuilderContract
    {
        // TODO: Implement getSubscriptionBuilder() method.
    }

    public function getSubscriptionHandler(Subscription $subscription): SubscriptionHandlerContract
    {
        // TODO: Implement getSubscriptionHandler() method.
    }

    public function updateDefaultPaymentMethod(string $paymentMethod)
    {
        // TODO: Implement updateDefaultPaymentMethod() method.
    }

    public function getDefaultPaymentMethod()
    {
        // TODO: Implement getDefaultPaymentMethod() method.
    }

    public function getApiContext(): \PayPal\Rest\ApiContext
    {
        return $this->apiContext;
    }
}