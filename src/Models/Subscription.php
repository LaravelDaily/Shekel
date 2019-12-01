<?php


namespace Shekel\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Shekel\Traits\HandlesSubscription;
use Shekel\Traits\HasMetaField;

/**
 * Class Subscription
 * @package Shekel\Models
 *
 * @property integer $plan_id
 * @property integer $user_id
 * @property string $payment_provider
 * @property array $meta
 * @property Carbon|null $trial_ends_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Plan $plan
 */
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

}