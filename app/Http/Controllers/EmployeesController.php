<?php

namespace App\Http\Controllers;

use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    /**
     * Employees controller contains methods that handle employees data retrieval and manipulation
     */
    public function listAllEmployees()
    {
        foreach (Employee::all() as $employee) {
            $response[] = new EmployeeResource($employee);
        }
        return ['employees' => $response];
    }
}
