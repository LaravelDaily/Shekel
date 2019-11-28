<?php


use Shekel\Exceptions\StripePlanNotFoundWhileUpdatingException;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;
use Shekel\Tests\TestCase;

class StripeWebhookTest extends TestCase
{

    public function test_customer_subscription_updated()
    {

        $this->makeSubscription();

        $newQuantity = 123;

        /** @var Plan $newPlan */
        $newPlan = Plan::create([
            'title' => 'plan1',
            'price' => 999,
            'billing_period' => 'month',
            'trial_period_days' => 30,
            'meta' => ['stripe' => ['plan_id' => 'plan123']],
        ]);

        $newTrialEnd = now()->addDays(15)->timestamp;

        $payload = $this->getStripeUpdatePayload();
        $payload['data']['object']['quantity'] = $newQuantity;
        $payload['data']['object']['plan']['id'] = $newPlan->getMeta('stripe.plan_id');
        $payload['data']['object']['trial_end'] = $newTrialEnd;
        $payload['data']['object']['status'] = 'trialing';

        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertStatus(200);

        /** @var Subscription $subscription */
        $subscription = Subscription::where('meta->stripe->subscription_id', 'sub_00000000000000')->first();

        $this->assertEquals($subscription->getMeta('stripe.quantity'), $newQuantity);
        $this->assertEquals($subscription->getMeta('stripe.plan_id'), $newPlan->getMeta('stripe.plan_id'));
        $this->assertEquals($subscription->plan_id, $newPlan->id);
        $this->assertEquals($subscription->trial_ends_at->timestamp, $newTrialEnd);
        $this->assertEquals($subscription->ends_at, null);
        $this->assertEquals($subscription->getMeta('stripe.status'), 'trialing');

    }

    public function test_customer_subscription_incomplete_expired()
    {
        $this->makeSubscription();
        $payload = $this->getStripeUpdatePayload();
        $payload['data']['object']['status'] = 'incomplete_expired';

        $response = $this->postJson('/stripe/webhook', $payload);
        $response->assertStatus(200);

        $this->assertNull(Subscription::where('meta->stripe->subscription_id', 'sub_00000000000000')->first());

    }

    public function test_customer_subscription_update_to_not_existing_plan()
    {
        $this->makeSubscription();
        $payload = $this->getStripeUpdatePayload();
        $payload['data']['object']['plan']['id'] = 'random_id';

        $response = $this->postJson('/stripe/webhook', $payload);
        $response->assertStatus(500);
        $response->withException(new StripePlanNotFoundWhileUpdatingException());
    }

}