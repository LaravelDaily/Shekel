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

    const BILLING_PERIODS = ['day', 'week', 'month', 'year'];

    protected $fillable = ['title', 'price', 'billing_period', 'trial_period_days', 'meta'];

}