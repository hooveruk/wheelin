<?php

namespace Tests\Unit;

use App\Http\Controllers\SchedulerController;
use App\Models\Schedule;
use Tests\TestCase;
//use Illuminate\Foundation\Testing\WithFaker;
//use Illuminate\Foundation\Testing\RefreshDatabase;

class ScheduleControllerTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    static $scheduler;

    public function setUp()
    {
        self::$scheduler = new SchedulerController();
    }
    public function testGetWeekDates()
    {
        $result = self::$scheduler->getWeekDates('2018-05-11');
        $this->assertEquals('2018-05-07',$result['start']);
        $this->assertEquals('2018-05-20',$result['end']);
        $this->assertNotContains('calendar', json_encode($result));
    }
    public function atestGetWeekDatesWithCalendar()
    {
        $result = self::$scheduler->getWeekDates('2018-05-11', true);
        $this->assertEquals('2018-05-07',$result['start']);
        $this->assertEquals('2018-05-20',$result['end']);
        $this->assertEquals(10, count($result['calendar']));
        $this->assertContains('calendar', json_encode($result));
    }
    public function atestIsInSchedule()
    {
        $schedule = new Schedule();
        $schedule->schedule_date = '2018-05-08';
        $schedule->shift = 1;
        $schedule->employee_id = 1;
        $schedule->save();
        $collection = Schedule::where(['schedule_date', '2018-05-08'])->get();
        $this->assertTrue(self::$scheduler->isInSchedule($collection, '2018-05-08'));
    }
}
