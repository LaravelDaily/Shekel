<?php


namespace Shekel\Actions;


use Shekel\Models\Plan;
use Shekel\Shekel;

class DeletePlan
{

    public function __invoke(int $id)
    {

        /** @var Plan $plan */
        $plan = Plan::findOrFail($id);

        //TODO SHOULD CHECK FOR ACTIVE SUBSCRIPTIONS HERE???
        if (Shekel::stripeActive()) {
            $stripePlanId = $plan->getMeta('stripe.id');
            if ($stripePlanId) {
                //TODO SHOULD DELETE THE PRODUCT ALSO???
                \Stripe\Plan::retrieve($stripePlanId)->delete();
            }

        }

        $plan->delete();
    }

}