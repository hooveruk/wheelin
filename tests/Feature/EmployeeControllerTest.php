<?php

namespace Tests\Feature;

use App\Http\Controllers\SchedulerController;
use App\Models\Employee;
use App\Models\Schedule;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmployeeControllerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetEmployees()
    {
        $response = $this->get('/api/employees');
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(10, ['data', 'employees']);
    }
}
