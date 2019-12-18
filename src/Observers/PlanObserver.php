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
    static $disableRestrictedFieldValidation = false;

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

            if (Shekel::paymentProviderActive('paypal')) {

                $paypalPlan = new \PayPal\Api\Plan();
                $paypalPlan->setDescription('Payment')->setState('ACTIVE')->setType('infinite')->setName($plan->title);

                $paypalAmount = new \PayPal\Api\Currency(['value' => $plan->price, 'currency' => strtoupper(Shekel::getCurrency())]);

                $paymentDefinitions = [];

                if ($plan->trial_period_days) {
                    $trial = new \PayPal\Api\PaymentDefinition();
                    $trial->setName('Trial period');
                    $trial->setType('TRIAL');
                    $trial->setCycles(1);
                    $trial->setFrequency('Day');
                    $trial->setFrequencyInterval($plan->trial_period_days);
                    $trial->setAmount(new \PayPal\Api\Currency(['value' => 0, 'currency' => strtoupper(Shekel::getCurrency())]));

                    $paymentDefinitions[] = $trial;
                }


                $paymentDefinition = new \PayPal\Api\PaymentDefinition();
                $paymentDefinition->setName('Regular Payments');
                $paymentDefinition->setType('REGULAR');
                $paymentDefinition->setFrequency($plan->billing_period);
                $paymentDefinition->setFrequencyInterval(1);
                $paymentDefinition->setCycles(0);
                $paymentDefinition->setAmount($paypalAmount);

                $paymentDefinitions[] = $paymentDefinition;

                $paypalPlan->setPaymentDefinitions($paymentDefinitions);

                $merchantPreferences = new \PayPal\Api\MerchantPreferences();
                $baseUrl = env('APP_URL', 'http://localhost');
                $merchantPreferences->setReturnUrl("$baseUrl/paypal/success")
                    ->setCancelUrl("$baseUrl/paypal/cancel")
                    ->setAutoBillAmount("yes")
                    ->setInitialFailAmountAction("CONTINUE")
                    ->setMaxFailAttempts("0")
                    ->setSetupFee($paypalAmount);

                $paypalPlan->setMerchantPreferences($merchantPreferences);

                $paypalPlan->create(Shekel::paypal()->getApiContext());

                $plan->setMeta('paypal.plan_id', $paypalPlan->id)->save();
            }

        } catch (\Exception $e) {
            $plan->delete();

//            dd($e->getCode(), $e->getData());
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
                'trial_period_days' => $plan->trial_period_days ?? 0,
            ]);
        }

        if (Shekel::paymentProviderActive('paypal') && $paypalPlanId = $plan->getMeta('paypal.plan_id')) {
            $paypalPlan = \PayPal\Api\Plan::get($paypalPlanId, Shekel::paypal()->getApiContext());
            $paymentDefinitions = collect($paypalPlan->getPaymentDefinitions());
            $trialDefinition = $paymentDefinitions->where('type', 'TRIAL')->first();

            //CREATE
            if (!$trialDefinition && $plan->trial_period_days) {
                $trial = new \PayPal\Api\PaymentDefinition();
                $trial->setName('Trial period');
                $trial->setType('TRIAL');
                $trial->setCycles(1);
                $trial->setFrequency('Day');
                $trial->setFrequencyInterval($plan->trial_period_days);
                $trial->setAmount(new \PayPal\Api\Currency(['value' => 0, 'currency' => strtoupper(Shekel::getCurrency())]));

                $patch = (new \PayPal\Api\Patch())
                    ->setOp('add')
                    ->setPath('/payment-definitions/')
                    ->setValue($trial);
            }

            //UPDATE
            if ($trialDefinition && $plan->trial_period_days) {
                $patch = (new \PayPal\Api\Patch())
                    ->setOp('replace')
                    ->setPath('/payment-definitions/' . $trialDefinition->getId())
                    ->setValue(['frequency_interval' => $plan->trial_period_days]);
            }

            //DELETE
            if ($trialDefinition && !$plan->trial_period_days) {
                $patch = (new \PayPal\Api\Patch())
                    ->setOp('remove')
                    ->setPath('/payment-definitions/' . $trialDefinition->getId());
            }

            if (isset($patch)) {
                $patchRequest = new \PayPal\Api\PatchRequest();
                $patchRequest->addPatch($patch);
                dump($patch);
                try {
                    $paypalPlan->update($patchRequest, Shekel::paypal()->getApiContext());
                } catch (\Exception $e) {
//                    dd($e->getData(), $paypalPlan);
                    throw $e;
                }
            }
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
                \Stripe\Plan::retrieve($stripePlanId)->delete();
            }

        }

        if (Shekel::paymentProviderActive('paypal')) {
            $paypalPlanId = $plan->getMeta('paypal.plan_id');
            if ($paypalPlanId) {
                \PayPal\Api\Plan::get($paypalPlanId, Shekel::paypal()->getApiContext())->delete(Shekel::paypal()->getApiContext());
            }
        }
    }

}