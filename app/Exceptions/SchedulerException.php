<?php

namespace App\Exceptions;

use Exception;

class SchedulerException extends Exception
{
    const INVALID_DATE_FORMAT = 1;
    const EMPLOYEE_NOT_FOUND = 2;
    const EMPLOYEE_OVERBOOKING = 3;
    const SHIFT_NOT_FOUND = 4;
    const SHIFT_TAKEN = 5;
}
