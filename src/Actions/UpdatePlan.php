<?php


namespace Shekel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Shekel\Models\Plan;
use Shekel\Shekel;

/**
 * Class UpdatePlan
 * @package Shekel\Actions
 */
class UpdatePlan
{
    /**
     * @param Plan $plan
     * @param array $data
     * @throws ValidationException
     */
    public function __invoke(Plan $plan, array $data)
    {
        $data = $this::validate($data);

        DB::transaction(function () use ($plan, $data) {

            $plan->update($data);

            if (Shekel::stripeActive() && $stripePlanId = $plan->getMeta('stripe_id')) {

                \Stripe\Plan::update($stripePlanId, [
                    'trial_period_days' => $plan->trial_period_days ?? null,
                ]);

            }

        });


    }

    /**
     * @param array $data
     * @return array
     * @throws ValidationException
     */
    public static function validate(array $data)
    {
        $validator = Validator::make($data, [
            'trial_period_days' => ['nullable', 'integer', 'max:730'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}