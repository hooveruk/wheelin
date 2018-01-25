<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/schedule/{week}', 'SchedulerController@getSchedule');
Route::get('/schedule', 'SchedulerController@getCurrentSchedule');
Route::get('/employees', 'EmployeesController@listAllEmployees');
Route::get('/get_available_employees/{date}/{shift}', 'SchedulerController@getAvailableEmployees');
Route::get('/schedule_employee/{date}/{shift}/{employee_id}', 'SchedulerController@scheduleEmployee');
Route::get('/un_schedule_employee/{date}/{shift}/{employee_id}', 'SchedulerController@unScheduleEmployee');
Route::get('/random_schedule/{date}', 'SchedulerController@createRandomScheduleWrapper');

