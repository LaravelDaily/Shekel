<?php

namespace Shekel\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Model;
use Illuminate\Notifications\Notifiable;
use Shekel\Traits\Billable;

class User extends Model
{
    use Notifiable, Billable;
}