<?php


namespace Shekel\Exceptions;


use Shekel\Models\Plan;

class UpdatingRestrictedPlanFieldException extends \Exception
{

    public function __construct()
    {
        parent::__construct();
        $this->message = 'Tried updating a restricted field. These fields should not be updated: ' . implode(', ', Plan::RESTRICTED_FIELDS);
    }

}