<?php


namespace Shekel\Models;


use Illuminate\Database\Eloquent\Model;
use Shekel\Traits\HandlesSubscription;
use Shekel\Traits\HasMetaField;

class Subscription extends Model
{
    use HandlesSubscription, HasMetaField;

    protected $guarded = [];

    protected $dates = ['trial_ends_at', 'ends_at'];

    /**
     * SUBSCRIPTION STATUSES ON DIFFERENT PAYMENT PROVIDERS
     * STRIPE - 'incomplete', 'incomplete_expired', 'trialing', 'active', 'past_due', 'canceled', 'unpaid'
     * PAYPAL - 'APPROVAL_PENDING', 'APPROVED', 'ACTIVE', 'SUSPENDED', 'CANCELLED', 'EXPIRED'
     * BRAINTREE - 'Pending', 'Active', 'Past Due', 'Expired', 'Canceled'
     */

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

}