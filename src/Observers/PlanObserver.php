<?php


namespace Shekel\Observers;


use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Shekel\Exceptions\PlanHasActiveSubscriptionsException;
use Shekel\Exceptions\UpdatingRestrictedPlanFieldException;
use Shekel\Models\Plan;
use Shekel\Shekel;

class PlanObserver
{
    /**
     * NEED THIS SO WE COULD CREATE A PLAN WITH ALL THE FIELDS
     * WHEN A PLAN IS CREATED FULL VALIDATION IS REENACTED AGAIN
     * @var bool
     */
    static bool $disableRestrictedFieldValidation = false;

    public function created(Plan $plan)
    {
        self::$disableRestrictedFieldValidation = true;

        $validator = Validator::make($plan->toArray(), [
            'title' => ['required', 'string'],
            'price' => ['required', 'integer'],
            'billing_period' => [
                'required',
                Rule::in(Plan::BILLING_PERIODS),
            ],
            'trial_period_days' => ['nullable', 'integer', 'max:730'], //STRIPE ONLY ALLOWS MAXIMUM 730 DAYS FOR THE TRIAL PERIOD
        ]);

        if ($validator->fails()) {
            $plan->delete();
            throw new ValidationException($validator);
        }

        try {

            if (Shekel::paymentProviderActive('stripe')) {

                $stripePlan = \Stripe\Plan::create([
                    'amount' => $plan->price,
                    'currency' => Shekel::getCurrency(),
                    'interval' => $plan->billing_period,
                    'product' => ['name' => $plan->title],
                    'trial_period_days' => $plan->trial_period_days ?? null,
                ]);

                $plan->setMeta('stripe.plan_id', $stripePlan->id)->save();
            }

        } catch (\Exception $e) {
            $plan->delete();
            throw $e;
        } finally {
            self::$disableRestrictedFieldValidation = false;
        }
    }


    public function updating(Plan $plan)
    {
        //STRIPE DOES NOT ALLOW UPDATING ANY FIELDS EXCEPT trial_period_days AFTER CREATION.
        if (!self::$disableRestrictedFieldValidation) {
            $intersection = array_intersect(array_keys($plan->getDirty()), Plan::RESTRICTED_FIELDS);

            if (count($intersection) > 0) {
                throw new UpdatingRestrictedPlanFieldException();
            }
        }

        $validator = Validator::make($plan->getDirty(), [
            'trial_period_days' => ['nullable', 'integer', 'max:730'], //STRIPE ONLY ALLOWS MAXIMUM 730 DAYS FOR THE TRIAL PERIOD
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        if (Shekel::paymentProviderActive('stripe') && $stripePlanId = $plan->getMeta('stripe.plan_id')) {

            \Stripe\Plan::update($stripePlanId, [
                'trial_period_days' => $plan->trial_period_days ?? null,
            ]);

        }
    }


    public function deleting(Plan $plan)
    {
        if ($plan->subscriptions()->count() > 0) {
            throw new PlanHasActiveSubscriptionsException();
        }

        if (Shekel::paymentProviderActive('stripe')) {
            $stripePlanId = $plan->getMeta('stripe.plan_id');
            if ($stripePlanId) {
                //TODO SHOULD DELETE THE PRODUCT ALSO???
                \Stripe\Plan::retrieve($stripePlanId)->delete();
            }

        }
    }

}