<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Resources\Json\Resource;

class ScheduleResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'schedule_date' => $this->schedule_date,
            'shift' => $this->shift,
            'employee_id' => $this->employee_id,
            'employee' => new EmployeeResource(Employee::find($this->employee_id))
        ];
    }
}
