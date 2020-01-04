<?php

namespace Shekel\Commands;

use Illuminate\Console\Command;
use Shekel\Models\Plan;

class CreatePlanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:plan-create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive wizard to create a SaaS Payment Plan in database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $plan = [];
        $plan['title']                     = $this->ask('[Question 1/5]: Plan name? (ex. Premium plan)', 'Premium plan');
        $plan['billing_period']            = $this->choice('[Question 2/5]: Billing period?',
            ['day', 'week', 'month', 'year'], 2);
        $plan['price']                     = round($this->ask('[Question 3/5]: Price per ' . $plan['billing_period'] . '? (ex. 29.99)') * 100);
        $plan['meta']['stripe']['plan_id'] = $this->ask('[Question 4/5]: Plan ID from Stripe? (ex. plan_xxxxxxxxxx)');
        $plan['trial_period_days']         = $this->ask('[Question 5/5]: Days of free trial? (empty for 0)');

        Plan::create($plan);

        $this->info('Plan created successfully!');
    }
}
