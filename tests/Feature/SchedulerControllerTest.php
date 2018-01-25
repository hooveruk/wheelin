<?php

namespace Tests\Feature;

use App\Http\Controllers\SchedulerController;
use App\Models\Employee;
use App\Models\Schedule;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SchedulerControllerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testScheduleNoDate()
    {
        $date = new \DateTime();
        $today = $date->format('Y-m-d');
        $response = $this->get('/api/schedule');
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);
        $response->assertSeeText($today);
        $response->assertJsonCount(10, ['data', 'period', 'calendar']);
    }

    public function testScheduleDate()
    {
        $date = new \DateTime();
        $today = $date->format('Y-m-d');
        $response = $this->get(sprintf('/api/schedule/%s',$today));
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(10, ['data', 'period', 'calendar']);
    }
    public function testScheduleDate19()
    {
        $this->setupDemoData();
        $response = $this->get('/api/schedule/2019-06-18');
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(10, ['data', 'period', 'calendar']);
        $response->assertSeeText('2019-06-17');
        $response->assertDontSeeText('2019-06-16');
        $response->assertJsonCount(19, ['data', 'schedules']);
    }

    /**
     * Test generating full random schedule without any existing schedule
     * white canvas
     */
    public function testGenerateSchedule()
    {
        $this->deleteSchedule('2019-06-17','2019-06-28');
        $response = $this->get('/api/random_schedule/2019-06-17');
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(10, ['data', 'period', 'calendar']);
        $response->assertSeeText('2019-06-17');
        $response->assertDontSeeText('2019-06-16');
        $response->assertJsonCount(20, ['data', 'schedules']);
        $response->assertStatus(200);
    }

    /**
     * Test generation of missing schedule in a scenario where randomJustice
     * must be triggered as and multiple function calls done to randomly select
     * full schedule table
     */
    public function testGenerateScheduleRandomJustice()
    {
        $this->setupDemoData();
        $response = $this->get('/api/random_schedule/2019-06-17');
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(10, ['data', 'period', 'calendar']);
        $response->assertSeeText('2019-06-17');
        $response->assertDontSeeText('2019-06-16');
        $response->assertJsonCount(20, ['data', 'schedules']);
        $response->assertStatus(200);
    }

    /**
     * tests for scheduling of single employee
     * triggering error messages
     */
    public function testScheduleAPICallsSingleEmployee() {
        $scheduler = new SchedulerController();

        // get a test employee
        $emp = Employee::all()->first();

        // clear the schedule
        $scheduler->deleteSchedule('2018-05-08');

        // successful first schedule
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-08/1/%s',$emp->id));
        $response->assertJson([ 'status' => "ok"]);
        $response->assertStatus(200);

        //failure on retry shift already taken
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-08/1/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('Shift already taken');
        $response->assertStatus(200);

        //failure on retry, already scheduled for that day, different shift
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-08/2/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('already scheduled');
        $response->assertStatus(200);

        //failure on booking for previous day
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-07/1/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('consecutive');
        $response->assertStatus(200);

        //failure on booking for next day
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-09/1/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('consecutive');
        $response->assertStatus(200);

        //success on booking second shift
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-10/2/%s',$emp->id));
        $response->assertJson([ 'status' => "ok"]);
        $response->assertStatus(200);

        //failure for overbooking (max shift allowed reached
        $response = $this->get(sprintf('/api/schedule_employee/2018-05-14/2/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('Max bookings reached');
        $response->assertStatus(200);

        //successfully remove
        $response = $this->get(sprintf('/api/un_schedule_employee/2018-05-10/2/%s',$emp->id));
        $response->assertJson([ 'status' => "ok"]);
        $response->assertStatus(200);

        //failure
        $response = $this->get(sprintf('/api/un_schedule_employee/2018-05-10/2/%s',$emp->id));
        $response->assertJson([ 'status' => "error"]);
        $response->assertSeeText('schedule not found');
        $response->assertStatus(200);
    }

    /**
     * Helper: delete range of schedules
     * @param $startDate
     * @param $endDate
     */
    private function deleteSchedule($startDate, $endDate)
    {
        //remove test schedule
        Schedule::where([
            ['schedule_date', '>=', $startDate],
            ['schedule_date', '<=', $endDate]])->delete();
    }

    /**
     * Add schedule
     * @param $date
     * @param $shift
     * @param $employeeId
     */
    private function insertSchedule($date, $shift, $employeeID)
    {//remove test schedule
        $schedule = new Schedule();
        $schedule->schedule_date = $date;
        $schedule->employee_id = $employeeID;
        $schedule->shift = $shift;
        $schedule->save();
    }

    /**
     * Get schedule from DB
     * @param $startDate
     * @param $endDate
     * @return mixed
     */
    private function getSchedules($startDate, $endDate)
    {
        return Schedule::where(
            [
                ['schedule_date', '>=', $startDate],
                ['schedule_date', '<=', $endDate]
            ]
        )->get();
    }

    /**
     * Fake demo data setup
     */
    private function setupDemoData()
    {
        //a never possible scenario of emplyees in sequence
        //but fir purpose of testing ideal
        $this->deleteSchedule('2019-06-17','2019-06-28');
        $this->insertSchedule('2019-06-17', 1, 1);
        $this->insertSchedule('2019-06-17', 2, 1);
        $this->insertSchedule('2019-06-18', 1, 2);
        $this->insertSchedule('2019-06-19', 1, 3);
        $this->insertSchedule('2019-06-19', 2, 3);
        $this->insertSchedule('2019-06-20', 1, 4);
        $this->insertSchedule('2019-06-20', 2, 4);
        $this->insertSchedule('2019-06-21', 1, 5);
        $this->insertSchedule('2019-06-21', 2, 5);
        $this->insertSchedule('2019-06-24', 1, 6);
        $this->insertSchedule('2019-06-24', 2, 6);
        $this->insertSchedule('2019-06-25', 1, 7);
        $this->insertSchedule('2019-06-25', 2, 7);
        $this->insertSchedule('2019-06-26', 1, 8);
        $this->insertSchedule('2019-06-26', 2, 8);
        $this->insertSchedule('2019-06-27', 1, 9);
        $this->insertSchedule('2019-06-27', 2, 9);
        $this->insertSchedule('2019-06-28', 1, 10);
        $this->insertSchedule('2019-06-28', 2, 10);

    }

    /**
     * Various schedule counters go here
     * @param $schedule
     * @return array
     */
    private function parseSchedules($schedule)
    {
        $employee = [];
        foreach ($schedule as $item) {
            $employee[$item['employee_id']] =+ 1;
        }
        return ['employees' => $employee];
    }

    /**
     * getAvailableEmployees API call
     * @throws \Exception
     */
    public function testGetAvailableEmployees() {
        $this->setupDemoData();
        $this->assertEquals(19, count($this->getSchedules('2019-06-17','2019-06-28')));

        // emoty list of employees, because we want to draw for already booked shift

        $response = $this->get('/api/get_available_employees/2019-06-18/1/');
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);
        $response->assertJsonCount(0,['data','employees']);

        // number of schedules did not change
        $this->assertEquals(19, count($this->getSchedules('2019-06-17','2019-06-28')));

        // looking to book employee for free shift, the only availabke employee
        // is also the employee who was on the shift the day before
        // in order to enable all constrains, randomizer randomJustice with blindfold on
        // selects a random Employee who was already booked to and offers him for the draw.

        $response = $this->get('/api/get_available_employees/2019-06-18/2/');
        $response->assertStatus(200);
        $response->assertJson([ 'status' => "ok"]);

        // we have one employee to draw !
        $response->assertJsonCount(1,['data','employees']);

        $data = $response->decodeResponseJson();

        $schedules = $this->getSchedules('2019-06-17','2019-06-28');
        $count = $this->parseSchedules($schedules);
        // we have one less schedule
        $this->assertEquals(18, count($schedules));
        // and we are missing a schedule for employee we got to draw for
        $this->assertEquals(1, $count['employees'][$data['data']['employees'][0]['id']]);
    }



}
