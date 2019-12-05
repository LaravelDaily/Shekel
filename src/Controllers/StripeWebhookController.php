<?php

namespace Shekel\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Shekel\Exceptions\StripePlanNotFoundWhileUpdatingException;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;
use Shekel\Shekel;

/**
 * ADD MIDDLEWARE TO PROTECT THESE ROUTES
 * ADD A TEST ROUTE TO ENSURE THAT STRIPE RECEIVES THE CORRECT URL
 */
class StripeWebhookController
{
    public function handleWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $method = 'handle' . Str::studly(str_replace('.', '_', $payload['type']));
        if (method_exists($this, $method)) {
            $response = $this->{$method}($payload);

            return $response;
        }

        return response('Method not found.', 404);
    }

    protected function handleCustomerSubscriptionUpdated(array $payload)
    {
        $model = Shekel::userModelClass();

        $customer_id = $payload['data']['object']['customer'];
        $user = $model::with('subscriptions')->where('meta->stripe->customer_id', $customer_id)->first();

        $data = $payload['data']['object'];
        $subscription_id = $data['id'];

        /** @var Subscription $subscription */
        $subscription = $user->subscriptions->filter(function (Subscription $subscription) use ($subscription_id) {
            return $subscription->getMeta('stripe.subscription_id') === $subscription_id;
        })->first();


        if (isset($data['status']) && $data['status'] === 'incomplete_expired') {
            $subscription->delete();

            return response('OK', 200);
        }

        //Quantity...
        if (isset($data['quantity'])) {
            $subscription->setMeta('stripe.quantity', $data['quantity']);
        }
        // Plan...
        if (isset($data['plan']['id'])) {
            $subscription->setMeta('stripe.plan_id', $data['plan']['id']);

            //IF PLAN IS CHANGED IN STRIPE DASHBOARD WE NEED TO CHANGE IT ALSO IN OUR DATABASE
            $plan = Plan::where('meta->stripe->plan_id', $data['plan']['id'])->first();
            if (!$plan) {
                throw new StripePlanNotFoundWhileUpdatingException();
            }

            $subscription->plan_id = $plan->id;

        }
        // Trial ending date...
        if (isset($data['trial_end'])) {
            $trial_ends = Carbon::createFromTimestamp($data['trial_end']);
            if (!$subscription->trial_ends_at || $subscription->trial_ends_at->ne($trial_ends)) {
                $subscription->trial_ends_at = $trial_ends;
            }
        }
        // Cancellation date...
        if (isset($data['cancel_at_period_end'])) {
            if ($data['cancel_at_period_end']) {
                $subscription->ends_at = $subscription->onTrial() ? $subscription->trial_ends_at : Carbon::createFromTimestamp($data['current_period_end']);
            } else {
                $subscription->ends_at = null;
            }
        }
        // Status...
        if (isset($data['status'])) {
            $subscription->setMeta('stripe.status', $data['status']);
        }

        $subscription->save();

        return response('OK', 200);
    }

    protected function handleCustomerSubscriptionDeleted(array $payload)
    {
        $model = Shekel::userModelClass();

        $customer_id = $payload['data']['object']['customer'];
        $user = $model::with('subscriptions')->where('meta->stripe->customer_id', $customer_id)->first();

        if ($user) {
            $user->subscriptions
                ->filter(function (Subscription $subscription) use ($payload) {
                    return $subscription->getMeta('stripe.subscription_id') === $payload['data']['object']['id'];
                })
                ->each(function (Subscription $subscription) {
                    $subscription->markAsCancelled();
                });
        }

        return response('OK', 200);
    }
}