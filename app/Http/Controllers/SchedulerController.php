<?php

namespace App\Http\Controllers;

use App\Exceptions\SchedulerException;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Employee;
use App\Models\Schedule;

class SchedulerController extends Controller
{
    const MAX_BOOKING = 2;
    const SCHEDULE_NOT_FOUND = 10;
    private $dates;


    /**
     * Returns current schedule
     * @return string
     */
    public function getCurrentSchedule()
    {
       return $this->getSchedule(date('Y-m-d',time()));
    }

    /**
     * Returns schedule for the date requested
     * @param $date   string
     * @return string
     */
    public function getSchedule($date = null)
    {
        // current schedule from the db
        try {
            $currentSchedule = [];
            $scheduledDates = [];
            $scheduledUsers = [];

            // do date conversion, we accept strings too
            if (!is_object($date) || get_class($date) !== 'DateTime') {
                $inputdate = ($date) ? $date : date();
                $date = new \DateTime($inputdate);
            }

            $period = $this->getWeekDates($date, true);
            $schedules = Schedule::where([
                ['schedule_date', '>=', $period['start']],
                ['schedule_date', '<=', $period['end']]
            ])->get();

            sort($period['calendar']);
            foreach ($period['calendar'] as $calendar) {
                $scheduledDates[$calendar] = 0;
                $scheduledDates[$calendar] = 0;
            }

            foreach ($schedules as $schedule) {

                (isset($scheduledUsers[$schedule->getAttribute('employee_id')])) ?
                    $scheduledUsers[$schedule->getAttribute('employee_id')] ++ :
                    $scheduledUsers[$schedule->getAttribute('employee_id')] = 1;
                (isset($scheduledDates[$schedule->getAttribute('schedule_date')])) ?
                    $scheduledDates[$schedule->getAttribute('schedule_date')] ++ :
                    $scheduledDates[$schedule->getAttribute('schedule_date')] = 1;
                $currentSchedule[] = new ScheduleResource($schedule);
            }
            $data = [
                'period' => $period,
                'used' => $scheduledDates,
                'schedules' => $currentSchedule,
                'user_shifts' => $scheduledUsers
            ];
        } catch (\Exception $e) {
            return $this->responseError($e->getCode(),$e->getMessage());
        }
        return $this->responseSuccess($data);
    }

    /**
     * Helper, returns true if shift is scheduled at certain date
     * @param $schedules    array
     * @param $date         string
     * @param $shift        integer
     * @return bool
     */
    private function isScheduled($schedules, $date, $shift)
    {
        foreach ($schedules as $schedule) {
            if ($schedule['schedule_date'] == $date && $schedule['shift'] == $shift) {
                return true;
            }
        }
    }
    /**
     * Returns array of dates
     * @param $startDate    string
     * @param $endDate      string
     * @return              array
     */
    private function getDateRange($startDate, $endDate)
    {
        $dateArray = [];
        $dateStart = new \DateTime($startDate);
        $dateEnd = new \DateTime($endDate);
        $dateArray[] = $dateStart->format('Y-m-d');
        while ($dateStart < $dateEnd) {
            $dateStart->modify("+1 day");
            if (!in_array($dateStart->format('w'),[0,6])) {
                $dateArray[] = $dateStart->format('Y-m-d');
            }
        }
        return $dateArray;
    }
    /**
     * Calculate start and end dates of week based on input date
     * 2 week period always starts with odd week number
     *
     * @param $datein       string|\DateTime    date within period
     * @param $createRange  bool                add a list of dates to the response
     * @return array
     */
    public function getWeekDates($datein, $createRange = false)
    {
        $date = (is_object($datein)) ? $datein : new \DateTime($datein);
        $week = $date->format("W");
        $month = $date->format("m");
        $year = ($month == 1 && $week > 50) ? $date->format("Y")-1 : $date->format("Y");
        $week = ($week % 2 == 0) ? $week - 1 : $week;

        $date->setISODate($year, $week);
        $from = $date->format('Y-m-d');
        $date->modify('+13 days');
        $to = $date->format('Y-m-d');
        //if flag is set, return calendar entries
        $range = ($createRange) ? $this->getDateRange($from, $to) : null;
        $response = ['start' => $from,  'end' => $to, 'week' => $week, 'year' => $year];
        if ($range !== null) {
            $response['calendar'] = $range;
        }
        return $response;
    }

    /**
     * Fetch schedules from database from the DB for specified period
     *
     * @param $dateStart    string
     * @param $dateEnd      string
     * @param $employee_id  integer optional, if null fetches all schedules
     * @return mixed
     */
    public function getEmployeeSchedule($dateStart, $dateEnd, $employee_id = null)
    {
        if ($employee_id !== null) {
            $condition = [
                ['schedule_date', '>=', $dateStart ],
                ['schedule_date', '<=', $dateEnd ],
                ['employee_id', '=', $employee_id]
            ];
        } else {
            $condition = [
                ['schedule_date', '>=', $dateStart ],
                ['schedule_date', '<=', $dateEnd ]
            ];
        }
        return Schedule::where($condition)->get();
    }

    /**
     * Returns true if shift for the date is not scheduled yet
     * @param $date
     * @param $shift
     * @return bool
     */
    public function shiftNotTaken($date, $shift)
    {
        $count = Schedule::where([['schedule_date', '=', $date], ['shift', '=', $shift]])->count();
        return ($count > 0) ? false : true;
    }

    /**
     * Returne shift id of Employee is scheduled on a specific date
     * @param $schedule
     * @param $date
     * @return bool
     */
    public function isInSchedule($schedule, $date) {

        foreach ($schedule as $day) {
            if ($day->schedule_date == $date) {
                return $day->shift;
            }
        }
        return false;
    }

    /**
     * Check if Employee can accept shift
     * This is the constraint checking method, extend in case of additional or changed constraints
     *
     * @param $date             string      Shift date
     * @param $shift            integer     Shift id (1 or 2)
     * @param $employee_id      integer     Emplyoee id
     * @param $ignoreMaxShift   boolean     true if we do not check maximum of used shifts
     * @return bool
     * @throws SchedulerException
     */
    public function canAcceptShift($date, $shift, $employee_id, $ignoreMaxShift = false)
    {
        //fetch date information
        $dateData = $this->getWeekDates($date);

        //fetch selected employee schedule
        if (!$this->shiftNotTaken($date, $shift)) {
            throw new SchedulerException(
                'Shift already taken',
                SchedulerException::SHIFT_TAKEN
            );
        }
        $currentSchedule = $this->getEmployeeSchedule($dateData['start'], $dateData['end'], $employee_id);
        //fetch selected employee schedule, ignore for randomJustice
        if (!$ignoreMaxShift) {
            if ($currentSchedule->count() == 2) {
                throw new SchedulerException(
                    sprintf('Max bookings reached', $shift),
                    SchedulerException::EMPLOYEE_OVERBOOKING
                );
            }
        }

        // let's check if we already booked Employee for the date
        if ($shift = $this->isInSchedule($currentSchedule,$date)) {

            throw new SchedulerException(
                sprintf('Employee already scheduled for %s on the same date', $shift),
                SchedulerException::EMPLOYEE_OVERBOOKING
            );

        }
        // is employee booked for previous day?
        if ($date != $dateData['start']) {
            $previousDate = new \DateTime($date);
            $previousDate->modify("-1 day");
            if ($shift = $this->isInSchedule($currentSchedule, $previousDate->format('Y-m-d'))) {
                throw new SchedulerException(
                    sprintf("Employee cannot work on two consecutive days", $shift),
                    SchedulerException::EMPLOYEE_OVERBOOKING
                );
            }
        }
        // or maybe boooked for the next day
        if ($date != $dateData['end']) {
            $nextDate = new \DateTime($date);
            $nextDate->modify("+1 day");
            if ($shift = $this->isInSchedule($currentSchedule, $nextDate->format('Y-m-d'))) {

                throw new SchedulerException(
                    sprintf("Employee cannot work on two consecutive days", $shift),
                    SchedulerException::EMPLOYEE_OVERBOOKING
                );
            }
        }
        return true;
    }

    /**
     * Schedules employee for the date
     * @param $date
     * @param $shift
     * @param $employee_id
     * @return string
     */
    public function scheduleEmployee($date, $shift, $employee_id)
    {
        try {
            // lets check if we can accept the shift
            $this->canAcceptShift($date,$shift,$employee_id);

            // let's make new schedule and save it
            $schedule = new Schedule();
            $schedule->employee_id = $employee_id;
            $schedule->schedule_date = $date;
            $schedule->shift = $shift;
            $schedule->save();
            return $this->responseSuccess([]);
        } catch (\Exception $e) {
            $response = (get_class($e) === SchedulerException::class) ?
                $this->responseError($e->getCode(),$e->getMessage()) :
                $this->responseError(0,'Invalid parameters');
            return $response;
        }
    }

    /**
     * Remove schedule for an employee
     * @param $date
     * @param $shift
     * @param $employee_id
     * @return array
     */
    public function unScheduleEmployee($date, $shift, $employee_id)
    {
        try {
            $schedule = Schedule::where(
                [
                    ['employee_id', '=', $employee_id],
                    ['schedule_date', '=', $date],
                    ['shift', '=', $shift]
                ]
            )->first();
            if ($schedule) {
                Schedule::find($schedule->id)->delete();
                return $this->responseSuccess([]);
            }
            return $this->responseError(self::SCHEDULE_NOT_FOUND, 'Employee schedule not found');
        } catch (\Exception $e) {
                return $this->responseError(0,'Invalid parameters');
        }
    }

    /**
     * Make a list of available employees for the date
     * For each employee constraints are checked to allow/disallow selection for the specific shift
     *
     * @param   $date
     * @return  string
     */
    public function getAvailableEmployees($date, $shift)
    {
        try {
            $response = [];
            $errors = [];
            $forceReload = false;
            foreach (Employee::all() as $employee) {
                try {
                    $this->canAcceptShift($date, $shift, $employee->id);
                    $response[] = new EmployeeResource($employee);
                } catch (SchedulerException $e) {
                    $errors[$employee->id] = sprintf('%s, %s', $e->getCode(), $e->getMessage());
                }
            }


            //run random vigilante free one shift that is ok to be freed
            if (count($response) == 0) {
                $response = $this->randomJustice($date, $shift);
                $forceReload = true;
            }
            return $this->responseSuccess(['employees' => $response, 'force_reload' => $forceReload]);
        } catch (\Exception $e) {
            return $this->responseError($e->getCode(),$e->getMessage());
        }
    }

    /**
     * server side automatic random procedure to fill in the full schedule it will through schedule calendar day by day
     * and randomly select an employee, with luck all will be sucessfully selected in one run.
     *
     * Input parameter is any valid date, used to select proper 2 week period.
     *
     * @param   $date
     * @return  string
     */
    public function createRandomSchedule($date)
    {
        try {
            //lets get period information
            $schedule = $this->getSchedule($date);
            $dates = $schedule['data']['period'];
            foreach ($dates['calendar'] as $calendardate) {
                // skip scheduled calendars
                if (!$this->isScheduled($schedule['data']['schedules'], $calendardate, 1)) {
                    $data = $this->getAvailableEmployees($calendardate, 1);
                    if (count($data['data']['employees']) > 0) {
                        $numEmployees = count($data['data']['employees']);
                        $used = [];
                        while (count($used) < $numEmployees) {
                            $random = rand(0, $numEmployees - 1);
                            $used[$random] = 1;
                            $scheduleResult = $this->scheduleEmployee(
                                $calendardate,
                                1,
                                $data['data']['employees'][$random]->id
                            );
                            if ($scheduleResult['status'] == 'ok') {
                                break;
                            }

                        }
                    }
                }

                if (!$this->isScheduled($schedule['data']['schedules'], $calendardate, 2)) {
                    $data = $this->getAvailableEmployees($calendardate, 2);
                    if (count($data['data']['employees']) > 0) {
                        $numEmployees = count($data['data']['employees']);
                        $used = [];
                        while (count($used) < $numEmployees) {
                            $random = rand(0, $numEmployees - 1);
                            $used[$random] = 1;
                            $scheduleResult = $this->scheduleEmployee(
                                $calendardate,
                                2,
                                $data['data']['employees'][$random]->id
                            );
                            if ($scheduleResult['status'] == 'ok') {
                                break;
                            }
                        }
                    }
                }
            }
            return $this->getSchedule($date);
        } catch (\Exception $e) {
            return $this->responseError($e->getCode(),$e->getMessage());
        }
    }

    /**
     * Wrapper for autogenerated random schedule, when more than one iteration is needed
     * @param $date
     * @return string
     * @throws \Exception
     */
    public function createRandomScheduleWrapper($date)
    {
        /**
         * Because of the nature of true random selection there is a possibility that due to constraints some of
         * the schedules were not se with a sigle walkthru, so one or more random iterations are needed to generate
         * 20 random selections based on constraints.
         */
        while (true) {
            $return = $this->createRandomSchedule($date);
            if (($return['status'] == 'ok' && count($return['data']['schedules']) >= 20)
            || $return['status'] != 'ok') {
                return $return;
            }
        }
    }

    /**
     * Justice vigilante - when randomizer made a decision that makes impossible to select
     * a shift for a specific day we need reset one random shift to enable process to move on
     *
     * @param $date     \DateTime   date for when we are
     * @param $shift    integer     shift number
     * @return mixed
     */
    public function randomJustice($date, $shift)
    {
        $checkedItems = [];
        $schedule = $this->getSchedule($date);
        $allSchedules = $schedule['data']['schedules'];
        // no need to loop as there is an empty schedule
        if (count($schedule['data']['schedules'])>0) {
            // indefinte look until all options are checked
            while (1) {
                $item_no = array_rand($schedule['data']['schedules']);
                // we dont want to check the same random item, but wnat to get all items a chance
                if (isset($checkedItems[$item_no])) {
                    if (count($checkedItems) < count($allSchedules)) {
                        continue;
                    }

                    break;
                }
                $checkedItems[$item_no] = 1;
                $item =  $allSchedules[$item_no];
                //ignore employees with schedule on the same date
                if ($item['schedule_date'] == $date) continue;

                try {
                    $this->canAcceptShift($date, $shift, $item->employee_id, true);
                    $this->unScheduleEmployee($item->schedule_date, $item->shift, $item->employee_id);
                    return [$item['employee']];

                } catch (\Exception $e) {
                    if ($e->getCode() == SchedulerException::SHIFT_TAKEN) {
                        return [];
                    }
                    //canAcceptShift throws exceptions for non matches
                    $this->doNothing();
                }
            }
        }
        return [];
    }

    //placeholder function
    public function doNothing($param = null)
    {
    }

    /**
     * Empty database for selected period, internal use only
     * @param $date
     */
    public function deleteSchedule($date)
    {
        $dates = $this->getWeekDates($date);
        Schedule::where([['schedule_date', '>=', $dates['start']],['schedule_date', '<=', $dates['end']]])->delete();
    }
}
