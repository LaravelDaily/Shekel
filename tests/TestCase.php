<?php

namespace Shekel\Tests;

use Dotenv\Dotenv;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Shekel\Models\Plan;
use Shekel\Models\Subscription;
use Shekel\Shekel;
use Shekel\ShekelServiceProvider;
use Shekel\Tests\Fixtures\User;
use Stripe\PaymentMethod;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [ShekelServiceProvider::class];
    }

    public function setUp(): void
    {

        if (file_exists(__DIR__ . '/../.env')) {
            Dotenv::create(__DIR__ . '/..')->load();
        }

        parent::setUp();

        Model::unguard();

        $this->loadLaravelMigrations();
        $this->artisan('migrate')->run();

        Shekel::$disableAllProviders = false;
        putenv('BILLABLE_CURRENCY=usd');
    }

    public function makeUser($data = []): User
    {
        /** @var User $user */
        return User::create(array_merge([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'meta' => ['stripe' => ['customer_id' => 'cus_00000000000000']],
        ], $data));
    }

    public function makePlan($data = []): Plan
    {
        return Plan::create(array_merge([
            'title' => 'plan1',
            'price' => 999,
            'billing_period' => 'month',
            'trial_period_days' => 30,
            'meta' => ['stripe' => ['plan_id' => 'plan_00000000000000']],
        ], $data));
    }

    public function makeSubscription(): Subscription
    {
        $user = $this->makeUser();
        $plan = $this->makePlan();

        return Subscription::create([
            'plan_id' => $plan->id,
            'user_id' => $user->id,
            'payment_provider' => 'stripe',
            'meta' => '{"stripe": {"status": "trialing", "plan_id": "plan_00000000000000", "quantity": 1, "subscription_id": "sub_00000000000000"}}',
            'trial_ends_at' => now()->addDays(30),
            'ends_at' => now()->addDays(30),
        ]);
    }

    public function makePaymentMethod(): PaymentMethod
    {
        return \Stripe\PaymentMethod::create([
            'type' => 'card',
            'card' => [
                'number' => '4242424242424242',
                'exp_month' => 11,
                'exp_year' => now()->addYear()->year,
                'cvc' => '314',
            ],
        ]);
    }
}