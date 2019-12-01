<?php

namespace Shekel\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Shekel\Traits\HasMetaField;

/**
 * Class Plan
 * @package Shekel\Models
 *
 * @property string $tile
 * @property integer $price
 * @property string $billing_period
 * @property integer $trial_period_days
 * @property array $meta
 * @property Collection subscriptions
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Plan extends Model
{
    use HasMetaField;

    protected $guarded = [];

    //Available plan billing periods (based on stripe billing periods)
    const BILLING_PERIODS = ['day', 'week', 'month', 'year'];

    //Stripe prevents updating any field except trial_period_days
    const RESTRICTED_FIELDS = ['title', 'price', 'billing_period'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

}