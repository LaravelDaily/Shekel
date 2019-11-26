<?php

namespace Shekel\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Shekel\Models\Plan;
use Shekel\Shekel;

/**
 * Class CreatePlan
 * @package Shekel\Actions
 */
class CreatePlan
{

    /**
     * @param array $data
     * @throws ValidationException
     */
    public function __invoke(array $data): void
    {

        $data = $this::validate($data);

        DB::transaction(function () use ($data) {

            $plan = new Plan($data);
            $plan->save();

            //TODO THIS SHOULD PREVENT CREATING A PLAN IN STRIPE IN CASE OF ERRORS SOMEWHERE ELSE IN THE CODE
            if (Shekel::stripeActive()) {

                $stripePlan = \Stripe\Plan::create([
                    'amount' => $data['price'],
                    'currency' => Shekel::getCurrency(),
                    'interval' => $data['billing_period'],
                    'product' => ['name' => $data['title']],
                    'trial_period_days' => $data['trial_period_days'] ?? null,
                ]);

                $plan->setMeta('stripe.id', $stripePlan->id)->save();
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
            'title' => ['required', 'string'],
            'price' => ['required', 'integer'],
            'billing_period' => [
                'required',
                Rule::in(Plan::BILLING_PERIODS),
            ],
            'trial_period_days' => ['nullable', 'integer', 'max:730'],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

}