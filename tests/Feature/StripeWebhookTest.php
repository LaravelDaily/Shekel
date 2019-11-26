<?php


use Shekel\Tests\TestCase;

class StripeWebhookTest extends TestCase
{

    public function test_customer_subscription_updated()
    {

        $payload = '{
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
                "trial_end": 1576746278,
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
        }';

        $response = $this->postJson('/stripe/webhook', (array)json_decode($payload));
        dd($response->content());


    }

}