<?php

namespace Shekel\Models;

use Illuminate\Database\Eloquent\Model;
use Shekel\Traits\HasMetaField;

/**
 * Class Plan
 * @package Shekel\Models
 *
 * @property string $tile
 * @property integer $price
 * @property string $billing_period
 * @property integer $trial_period_days
 */
class Plan extends Model
{
    use HasMetaField;

    protected $guarded = [];

    const BILLING_PERIODS = ['day', 'week', 'month', 'year'];

    //Stripe prevents updating any field except trial_period_days
    const RESTRICTED_FIELDS = ['title', 'price', 'billing_period'];


}