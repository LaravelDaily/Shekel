<?php


use Carbon\Carbon;
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

        dd($response->content());
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

    public function test_customer_subscription_deleted_webhook()
    {
        $user = $this->makeUser(['meta' => []]);
        $plan = $this->makePlan(['meta' => []]);
        $paymentMethod = $this->makePaymentMethod();

        $subscription = $user->newSubscription('stripe', $plan->id, $paymentMethod->id)->create();

        $payload = $this->getStripeDeletePayload();
        $payload['data']['object']['customer'] = $user->getMeta('stripe.customer_id');
        $payload['data']['object']['id'] = $subscription->getMeta('stripe.subscription_id');
        $response = $this->postJson('/stripe/webhook', $payload);

        $response->assertStatus(200);

        $subscription->refresh();
        $this->assertTrue(Carbon::now()->gte($subscription->ends_at));

    }

    public function getStripeDeletePayload()
    {
        return json_decode('{
            "created": 1326853478,
            "livemode": false,
            "id": "evt_00000000000000",
            "type": "customer.subscription.deleted",
            "object": "event",
            "request": null,
            "pending_webhooks": 1,
            "api_version": "2019-11-05",
            "data": {
              "object": {
                "id": "sub_00000000000000",
                "object": "subscription",
                "application_fee_percent": null,
                "billing_cycle_anchor": 1575033073,
                "billing_thresholds": null,
                "cancel_at_period_end": false,
                "canceled_at": null,
                "collection_method": "charge_automatically",
                "created": 1575033071,
                "current_period_end": 1606655473,
                "current_period_start": 1575033073,
                "customer": "cus_00000000000000",
                "days_until_due": null,
                "default_payment_method": null,
                "default_source": null,
                "default_tax_rates": [
                ],
                "discount": null,
                "ended_at": 1575101281,
                "invoice_customer_balance_settings": {
                  "consume_applied_balance_on_void": true
                },
                "items": {
                  "object": "list",
                  "data": [
                    {
                      "id": "si_00000000000000",
                      "object": "subscription_item",
                      "billing_thresholds": null,
                      "created": 1575033071,
                      "metadata": {
                      },
                      "plan": {
                        "id": "plan_00000000000000",
                        "object": "plan",
                        "active": true,
                        "aggregate_usage": null,
                        "amount": 9999,
                        "amount_decimal": "9999",
                        "billing_scheme": "per_unit",
                        "created": 1575033069,
                        "currency": "usd",
                        "interval": "year",
                        "interval_count": 1,
                        "livemode": false,
                        "metadata": {
                        },
                        "nickname": null,
                        "product": "prod_00000000000000",
                        "tiers": null,
                        "tiers_mode": null,
                        "transform_usage": null,
                        "trial_period_days": null,
                        "usage_type": "licensed"
                      },
                      "quantity": 1,
                      "subscription": "sub_00000000000000",
                      "tax_rates": [
                      ]
                    }
                  ],
                  "has_more": false,
                  "url": "/v1/subscription_items?subscription=sub_GGgcFFN73Re8lJ"
                },
                "latest_invoice": "in_1Fk9FdAcYFSu03UMbbzMAzka",
                "livemode": false,
                "metadata": {
                },
                "next_pending_invoice_item_invoice": null,
                "pending_invoice_item_interval": null,
                "pending_setup_intent": null,
                "plan": {
                  "id": "plan_00000000000000",
                  "object": "plan",
                  "active": true,
                  "aggregate_usage": null,
                  "amount": 9999,
                  "amount_decimal": "9999",
                  "billing_scheme": "per_unit",
                  "created": 1575033069,
                  "currency": "usd",
                  "interval": "year",
                  "interval_count": 1,
                  "livemode": false,
                  "metadata": {
                  },
                  "nickname": null,
                  "product": "prod_00000000000000",
                  "tiers": null,
                  "tiers_mode": null,
                  "transform_usage": null,
                  "trial_period_days": null,
                  "usage_type": "licensed"
                },
                "quantity": 1,
                "start_date": 1575033071,
                "status": "canceled",
                "tax_percent": null,
                "trial_end": null,
                "trial_start": null
              }
            }
        }', true);
    }


    public function getStripeUpdatePayload()
    {
        return json_decode('{
            "created": 1326853478,
            "livemode": false,
            "id": "evt_00000000000000",
            "type": "customer.subscription.updated",
            "object": "event",
            "request": null,
            "pending_webhooks": 1,
            "api_version": "2019-11-05",
            "data": {
              "object": {
                "id": "sub_00000000000000",
                "object": "subscription",
                "application_fee_percent": null,
                "billing_cycle_anchor": 1576746278,
                "billing_thresholds": null,
                "cancel_at_period_end": false,
                "canceled_at": null,
                "collection_method": "charge_automatically",
                "created": 1574672678,
                "current_period_end": 1576746278,
                "current_period_start": 1574672678,
                "customer": "cus_00000000000000",
                "days_until_due": null,
                "default_payment_method": null,
                "default_source": null,
                "default_tax_rates": [
                  {
                    "id": "txr_00000000000000",
                    "object": "tax_rate",
                    "active": true,
                    "created": 1574426582,
                    "description": null,
                    "display_name": "Tax",
                    "inclusive": false,
                    "jurisdiction": null,
                    "livemode": false,
                    "metadata": {
                    },
                    "percentage": 0
                  }
                ],
                "discount": null,
                "ended_at": null,
                "invoice_customer_balance_settings": {
                  "consume_applied_balance_on_void": true
                },
                "items": {
                  "object": "list",
                  "data": [
                    {
                      "id": "si_00000000000000",
                      "object": "subscription_item",
                      "billing_thresholds": null,
                      "created": 1574672679,
                      "metadata": {
                      },
                      "plan": {
                        "id": "plan_00000000000000",
                        "object": "plan",
                        "active": true,
                        "aggregate_usage": null,
                        "amount": 870,
                        "amount_decimal": "870",
                        "billing_scheme": "per_unit",
                        "created": 1574672628,
                        "currency": "usd",
                        "interval": "year",
                        "interval_count": 1,
                        "livemode": false,
                        "metadata": {
                        },
                        "nickname": null,
                        "product": "prod_00000000000000",
                        "tiers": null,
                        "tiers_mode": null,
                        "transform_usage": null,
                        "trial_period_days": 24,
                        "usage_type": "licensed"
                      },
                      "quantity": 1,
                      "subscription": "sub_00000000000000",
                      "tax_rates": [
                      ]
                    }
                  ],
                  "has_more": false,
                  "url": "/v1/subscription_items?subscription=sub_GF7kbbrjJ1nTdy"
                },
                "latest_invoice": "in_1FidUoAcYFSu03UMcoGc9vQv",
                "livemode": false,
                "metadata": {
                },
                "next_pending_invoice_item_invoice": null,
                "pending_invoice_item_interval": null,
                "pending_setup_intent": null,
                "plan": {
                  "id": "plan_00000000000000",
                  "object": "plan",
                  "active": true,
                  "aggregate_usage": null,
                  "amount": 870,
                  "amount_decimal": "870",
                  "billing_scheme": "per_unit",
                  "created": 1574672628,
                  "currency": "usd",
                  "interval": "year",
                  "interval_count": 1,
                  "livemode": false,
                  "metadata": {
                  },
                  "nickname": null,
                  "product": "prod_00000000000000",
                  "tiers": null,
                  "tiers_mode": null,
                  "transform_usage": null,
                  "trial_period_days": 24,
                  "usage_type": "licensed"
                },
                "quantity": 1,
                "start_date": 1574672678,
                "status": "trialing",
                "tax_percent": 0,
                "trial_end": 1574672628,
                "trial_start": 1574672678
              },
              "previous_attributes": {
                "plan": {
                  "id": "OLD_00000000000000",
                  "object": "plan",
                  "active": true,
                  "aggregate_usage": null,
                  "amount": 870,
                  "amount_decimal": "870",
                  "billing_scheme": "per_unit",
                  "created": 1574672628,
                  "currency": "usd",
                  "interval": "year",
                  "interval_count": 1,
                  "livemode": false,
                  "metadata": {
                  },
                  "nickname": null,
                  "product": "prod_00000000000000",
                  "tiers": null,
                  "tiers_mode": null,
                  "transform_usage": null,
                  "trial_period_days": 24,
                  "usage_type": "licensed",
                  "name": "Old plan"
                }
              }
            }
        }', true);
    }

}