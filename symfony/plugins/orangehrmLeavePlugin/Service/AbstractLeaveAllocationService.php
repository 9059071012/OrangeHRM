<?php
/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software; you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with this program;
 * if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor,
 * Boston, MA  02110-1301, USA
 */

namespace OrangeHRM\Leave\Service;

use DateTime;
use OrangeHRM\Core\Traits\LoggerTrait;
use OrangeHRM\Core\Traits\Service\DateTimeHelperTrait;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Entity\Leave;
use OrangeHRM\Entity\LeaveRequest;
use OrangeHRM\Entity\LeaveType;
use OrangeHRM\Leave\Dto\LeaveDuration;
use OrangeHRM\Leave\Dto\LeaveParameterObject;
use OrangeHRM\Leave\Traits\Service\LeavePeriodServiceTrait;
use OrangeHRM\Leave\Traits\Service\LeaveTypeServiceTrait;
use OrangeHRM\Leave\Traits\Service\WorkScheduleServiceTrait;
use OrangeHRM\Pim\Traits\Service\EmployeeServiceTrait;

abstract class AbstractLeaveAllocationService
{
    use DateTimeHelperTrait;
    use UserRoleManagerTrait;
    use EmployeeServiceTrait;
    use LeaveTypeServiceTrait;
    use LeavePeriodServiceTrait;
    use WorkScheduleServiceTrait;
    use LoggerTrait;

    protected ?LeaveRequestService $leaveRequestService = null;
    /**
     * @var array<string, Leave[]>
     */
    protected array $overlapLeaves = [];
    protected $workflowService;

    public function getWorkflowService() {
        // TODO
        if (empty($this->workflowService)) {
            $this->workflowService = new AccessFlowStateMachineService();
        }
        return $this->workflowService;
    }

    /**
     *
     * Saves Leave Request and Sends Notification
     * @param LeaveParameterObject $leaveAssignmentData
     */
    abstract protected function saveLeaveRequest(LeaveParameterObject $leaveAssignmentData);

    /**
     * @param bool $isWeekend
     * @param bool $isHoliday
     * @param DateTime $leaveDate
     * @param LeaveParameterObject $leaveAssignmentData
     * @return int
     */
    abstract protected function getLeaveRequestStatus(
        bool $isWeekend,
        bool $isHoliday,
        DateTime $leaveDate,
        LeaveParameterObject $leaveAssignmentData
    ): int;

    /**
     * @return bool
     */
    abstract protected function allowToExceedLeaveBalance(): bool;

    /**
     *
     * @return LeaveRequestService
     */
    public function getLeaveRequestService(): LeaveRequestService
    {
        if (!($this->leaveRequestService instanceof LeaveRequestService)) {
            $this->leaveRequestService = new LeaveRequestService();
        }
        return $this->leaveRequestService;
    }

    /**
     *
     * @param LeaveRequestService $service
     */
    public function setLeaveRequestService(LeaveRequestService $service): void
    {
        $this->leaveRequestService = $service;
    }

    /**
     * @param LeaveParameterObject $leaveParameterObject
     * @return string
     */
    private function getOverlapLeaveArrayKey(LeaveParameterObject $leaveParameterObject): string
    {
        return md5(serialize($leaveParameterObject));
    }

    /**
     * @param LeaveParameterObject $leaveAssignmentData
     * @return Leave[]
     */
    public function getOverlapLeaves(LeaveParameterObject $leaveAssignmentData): array
    {
        $overlapLeavesArrayKey = $this->getOverlapLeaveArrayKey($leaveAssignmentData);
        if (isset($this->overlapLeaves[$overlapLeavesArrayKey])) {
            return $this->overlapLeaves[$overlapLeavesArrayKey];
        }

        $startDayStartTime = null;
        $startDayEndTime = null;
        $endDayStartTime = null;
        $endDayEndTime = null;

        $startDuration = null;
        $endDuration = null;

        if ($leaveAssignmentData->isMultiDayLeave()) {
            $partialDayOption = $leaveAssignmentData->getMultiDayPartialOption();
            $allPartialDays = ($partialDayOption === LeaveParameterObject::PARTIAL_OPTION_ALL);

            if ($allPartialDays) {
                $startDuration = $leaveAssignmentData->getStartMultiDayDuration();
            } elseif ($partialDayOption === LeaveParameterObject::PARTIAL_OPTION_START) {
                $startDuration = $leaveAssignmentData->getStartMultiDayDuration();
            } elseif ($partialDayOption === LeaveParameterObject::PARTIAL_OPTION_END) {
                $endDuration = $leaveAssignmentData->getEndMultiDayDuration();
            } elseif ($partialDayOption === LeaveParameterObject::PARTIAL_OPTION_START_END) {
                $startDuration = $leaveAssignmentData->getStartMultiDayDuration();
                $endDuration = $leaveAssignmentData->getEndMultiDayDuration();
            }
        } else {
            $allPartialDays = false;
            $startDuration = $leaveAssignmentData->getSingleDayDuration();
        }

        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($leaveAssignmentData->getEmployeeNumber());
        $workScheduleStartEndTime = $workSchedule->getWorkShiftStartEndTime();
        $workScheduleDuration = $workSchedule->getWorkShiftLength();
        $midDay = $this->addHoursDuration($workScheduleStartEndTime->getStartTime(), $workScheduleDuration / 2);

        // set start times
        if (!is_null($startDuration)) {
            if ($startDuration->isTypeHalfDayMorning()) {
                $startDayStartTime = $workScheduleStartEndTime->getStartTime();
                $startDayEndTime = $midDay;
            } elseif ($startDuration->isTypeHalfDayAfternoon()) {
                $startDayStartTime = $midDay;
                $startDayEndTime = $workScheduleStartEndTime->getEndTime();
            } elseif ($startDuration->isTypeSpecifyTime()) {
                $startDayStartTime = $startDuration->getFromTime();
                $startDayEndTime = $startDuration->getToTime();
            }
        }

        // set end times
        if (!is_null($endDuration)) {
            if ($endDuration->isTypeHalfDayMorning()) {
                $endDayStartTime = $workScheduleStartEndTime->getStartTime();
                $endDayEndTime = $midDay;
            } elseif ($endDuration->isTypeHalfDayAfternoon()) {
                $endDayStartTime = $midDay;
                $endDayEndTime = $workScheduleStartEndTime->getEndTime();
            } elseif ($endDuration->isTypeSpecifyTime()) {
                $endDayStartTime = $endDuration->getFromTime();
                $endDayEndTime = $endDuration->getToTime();
            }
        }

        $overlapLeaves = $this->getLeaveRequestService()->getLeaveRequestDao()->getOverlappingLeave(
            $leaveAssignmentData->getFromDate(),
            $leaveAssignmentData->getToDate(),
            $leaveAssignmentData->getEmployeeNumber(),
            $startDayStartTime,
            $startDayEndTime,
            $allPartialDays,
            $endDayStartTime,
            $endDayEndTime
        );

        $this->overlapLeaves[$overlapLeavesArrayKey] = $overlapLeaves;
        return $this->overlapLeaves[$overlapLeavesArrayKey];
    }

    /**
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    public function hasOverlapLeaves(LeaveParameterObject $leaveAssignmentData): bool
    {
        return !empty($this->getOverlapLeaves($leaveAssignmentData));
    }

    /**
     * @param LeaveType $leaveType
     * @return bool
     */
    public function isEmployeeAllowedToApply(LeaveType $leaveType): bool
    {
        return true;
    }

    /**
     * Check if user has exceeded the allowed hours per day in existing and current leave request.
     *
     * @param LeaveParameterObject $leaveAssignmentData Leave Parameters
     * @return bool True if user has exceeded limit, false if not
     */
    protected function applyMoreThanAllowedForADay(LeaveParameterObject $leaveAssignmentData): bool
    {
        $workShiftExceeded = false;
        $overlapLeave = [];
        $empNumber = $leaveAssignmentData->getEmployeeNumber();

        $workShiftLength = $this->getWorkShiftDurationForEmployee($empNumber);
        $dates = $this->getDateTimeHelper()->dateRange(
            $leaveAssignmentData->getFromDate(),
            $leaveAssignmentData->getToDate()
        );

        $firstDay = true;

        foreach ($dates as $date) {
            // TODO
            $existingDuration = $this->getLeaveRequestService()->getTotalLeaveDuration($empNumber, $date);

            $lastDay = $date == $leaveAssignmentData->getToDate();
            $duration = $this->getApplicableLeaveDuration($leaveAssignmentData, $firstDay, $lastDay);
            $firstDay = false;

            $workingDayLength = $workShiftLength;

            if ($this->isHoliday($date, $leaveAssignmentData) || $this->isWeekend($date, $leaveAssignmentData)) {
                $this->getLogger()->debug("Skipping ". $date->format('Y-m-d') ." since it is a weekend/holiday");
                continue;
            }

            // Reduce workShiftLength for half days
            $halfDay = $this->isHalfDay($date, $leaveAssignmentData);
            if ($halfDay) {
                $workingDayLength = $workShiftLength / 2;
            }

            if ($duration->isTypeFullDay()) {
                $leaveHours = $workingDayLength;
            } elseif ($duration->isTypeHalfDay()) {
                $leaveHours = $workShiftLength / 2;
            } elseif ($duration->isTypeSpecifyTime()) {
                $leaveHours = $this->getDurationInHours($duration->getFromTime(), $duration->getToTime());
            } else {
                $this->getLogger()->error(
                    'Unexpected duration type in applyMoreThanAllowedForADay(): ' . print_r($duration->getType(), true)
                );
                $leaveHours = 0;
            }

            $this->getLogger()->debug("date=".$date->format('Y-m-d').", existing leave duration=$existingDuration, " .
                                      "workShiftLength=$workShiftLength, totalLeaveTime=$leaveHours,workDayLength=$workingDayLength");

            // We only show work shift exceeded warning for partial leave days (length < work shift)
            if (($existingDuration + $leaveHours) > $workingDayLength) {

                $this->getLogger()->debug('Work shift length exceeded!');

                $parameter = new ParameterObject(
                    ['dateRange' => new DateRange($date, $date), 'employeeFilter' => $empNumber]
                );
                $leaveRequests = $this->getLeaveRequestService()->searchLeaveRequests($parameter);

                if (count($leaveRequests['list']) > 0) {

                    foreach ($leaveRequests['list'] as $leaveRequest) {
                        $leaveList = $leaveRequest->getLeave();
                        foreach ($leaveList as $leave) {
                            if ($leave->getDate() == $date) {
                                $overlapLeave[] = $leave;
                            }
                        }
                    }
                }

                $workShiftExceeded = true;
            }

        }

        $overlapLeavesArrayKey = $this->getOverlapLeaveArrayKey($leaveAssignmentData);
        if (!isset($this->overlapLeaves[$overlapLeavesArrayKey])) {
            $this->overlapLeaves[$overlapLeavesArrayKey] = $overlapLeave;
        }

        return $workShiftExceeded;
    }

    /**
     *
     * Checks overlapping leave request
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    public function isOverlapLeaveRequest(LeaveParameterObject $leaveAssignmentData) {

//        $leavePeriod = $this->getLeavePeriodService()->getLeavePeriod(strtotime($leaveAssignmentData->getFromDate()));
//
//        if (!is_null($leavePeriod) && ($leavePeriod instanceof LeavePeriod)) {
//            if ($leaveAssignmentData->getToDate() > $leavePeriod->getEndDate()) {
//                return true;
//            }
//        }

        return false;
    }

    /**
     * @param LeaveParameterObject $leaveAssignmentData
     * @return LeaveRequest
     */
    protected function generateLeaveRequest(LeaveParameterObject $leaveAssignmentData): LeaveRequest
    {
        $leaveRequest = new LeaveRequest();
        $leaveRequest->getDecorator()->setLeaveTypeById($leaveAssignmentData->getLeaveType());
        $leaveRequest->setDateApplied($leaveAssignmentData->getFromDate());
        $leaveRequest->getDecorator()->setEmployeeByEmpNumber($leaveAssignmentData->getEmployeeNumber());
        $leaveRequest->setComment($leaveAssignmentData->getComment());

        return $leaveRequest;
    }

    /**
     * @param int $empNumber
     * @return float
     */
    protected function getWorkShiftDurationForEmployee(int $empNumber): float
    {
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);
        return $workSchedule->getWorkShiftLength();
    }

    /**
     * @param LeaveParameterObject $leaveAssignmentData
     * @param bool $firstDay
     * @param bool $lastDay
     * @return LeaveDuration
     */
    protected function getApplicableLeaveDuration(
        LeaveParameterObject $leaveAssignmentData,
        bool $firstDay,
        bool $lastDay
    ): LeaveDuration {
        // Default to full day
        $duration = new LeaveDuration(LeaveDuration::FULL_DAY);

        if ($leaveAssignmentData->isMultiDayLeave()) {
            $partialDayOption = $leaveAssignmentData->getMultiDayPartialOption();

            if (($partialDayOption == LeaveParameterObject::PARTIAL_OPTION_ALL) ||
                ($firstDay &&
                    ($partialDayOption == LeaveParameterObject::PARTIAL_OPTION_START ||
                        $partialDayOption == LeaveParameterObject::PARTIAL_OPTION_START_END))) {
                $duration = $leaveAssignmentData->getStartMultiDayDuration();
            } elseif ($lastDay &&
                ($partialDayOption == LeaveParameterObject::PARTIAL_OPTION_END ||
                    $partialDayOption == LeaveParameterObject::PARTIAL_OPTION_START_END)) {
                $duration = $leaveAssignmentData->getEndMultiDayDuration();
            }
        } else {
            // Single day leave:
            $duration = $leaveAssignmentData->getSingleDayDuration();
        }

        return $duration;
    }

    /**
     * @param Leave $leave
     * @param int $empNumber
     * @param LeaveDuration $duration
     * @param bool $isNonWorkingDay
     * @param bool $isHoliday
     * @param bool $isHalfDay
     * @param bool $isHalfDayHoliday
     */
    protected function updateLeaveDurationParameters(
        Leave $leave,
        int $empNumber,
        LeaveDuration $duration,
        bool $isNonWorkingDay,
        bool $isHoliday,
        bool $isHalfDay,
        bool $isHalfDayHoliday
    ): void {
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);
        $workScheduleStartEndTime = $workSchedule->getWorkShiftStartEndTime();
        $workScheduleDuration = $workSchedule->getWorkShiftLength();

        $midDay = $this->addHoursDuration($workScheduleStartEndTime->getStartTime(), $workScheduleDuration / 2);

        // set status
        switch ($duration->getType()) {
            case LeaveDuration::FULL_DAY:
                $leave->setDurationType(Leave::DURATION_TYPE_FULL_DAY);
                // For backwards compatibility, set to 00:00
                $leave->setStartTime(new DateTime('00:00'));
                $leave->setEndTime(new DateTime('00:00'));
                break;
            case LeaveDuration::HALF_DAY_MORNING:
                $leave->setDurationType(Leave::DURATION_TYPE_HALF_DAY_AM);
                $leave->setStartTime($workScheduleStartEndTime->getStartTime());
                $leave->setEndTime($midDay);
                break;
            case LeaveDuration::HALF_DAY_AFTERNOON:
                $leave->setDurationType(Leave::DURATION_TYPE_HALF_DAY_PM);
                $leave->setStartTime($midDay);
                $leave->setEndTime($workScheduleStartEndTime->getEndTime());
                break;
            case LeaveDuration::SPECIFY_TIME:
                $leave->setDurationType(Leave::DURATION_TYPE_SPECIFY_TIME);
                $leave->setStartTime($duration->getFromTime());
                $leave->setEndTime($duration->getToTime());
                break;
        }

        if ($isNonWorkingDay || $isHoliday) {
            // Full Day Off
            $durationInHours = 0;
        } elseif ($isHalfDay || $isHalfDayHoliday) {
            if ($duration->isTypeFullDay()) {
                $durationInHours = $workScheduleDuration;
            } elseif ($duration->isTypeHalfDay()) {
                $durationInHours = $workScheduleDuration / 2;
            } else {
                $durationInHours = $this->getDurationInHours($duration->getFromTime(), $duration->getToTime());
            }

            $halfDayHours = ($workScheduleDuration / 2);
            if ($durationInHours > $halfDayHours) {
                $durationInHours = $halfDayHours;
            }
            // Half Day Off
        } else {
            // Full Working Day
            if ($duration->isTypeFullDay()) {
                $durationInHours = $workScheduleDuration;
            } elseif ($duration->isTypeHalfDay()) {
                $durationInHours = $workScheduleDuration / 2;
            } else {
                $durationInHours = $this->getDurationInHours($duration->getFromTime(), $duration->getToTime());
            }
        }

        $leave->setLengthHours($durationInHours);
        $leave->setLengthDays($durationInHours / $workScheduleDuration);
    }

    /**
     * @param DateTime $time
     * @param float $hoursToAdd
     * @return DateTime
     */
    protected function addHoursDuration(DateTime $time, float $hoursToAdd): DateTime
    {
        $timeInMinutes = (intval($time->format('H')) * 60) + intval($time->format('i'));
        $minutesToAdd = 60 * $hoursToAdd;

        $newMinutes = $timeInMinutes + $minutesToAdd;
        $hoursPart = intval(floor($newMinutes / 60));
        $minutesPart = round($newMinutes) % 60;

        return new DateTime(sprintf("%02d:%02d", $hoursPart, $minutesPart));
    }

    /**
     * @param DateTime $fromTime
     * @param DateTime $toTime
     * @return float
     */
    protected function getDurationInHours(DateTime $fromTime, DateTime $toTime): float
    {
        return $this->getDateTimeHelper()->dateDiffInHours($fromTime, $toTime);
    }

    /**
     * @param LeaveParameterObject $leaveAssignmentData
     * @return Leave[]
     */
    public function createLeaveObjectListForAppliedRange(LeaveParameterObject $leaveAssignmentData): array
    {
        $leaveList = [];
        $firstDay = true;
        $dates = $this->getDateTimeHelper()->dateRange(
            $leaveAssignmentData->getFromDate(),
            $leaveAssignmentData->getToDate()
        );

        foreach ($dates as $leaveDate) {
            $leave = new Leave();

            $isWeekend = $this->isWeekend($leaveDate, $leaveAssignmentData);
            $isHoliday = $this->isHoliday($leaveDate, $leaveAssignmentData);
            $isHalfday = $this->isHalfDay($leaveDate, $leaveAssignmentData);
            $isHalfDayHoliday = $this->isHalfdayHoliday($leaveDate, $leaveAssignmentData);

            $leave->setDate($leaveDate);

            $lastDay = $leaveDate == $leaveAssignmentData->getToDate();
            $leaveDuration = $this->getApplicableLeaveDuration($leaveAssignmentData, $firstDay, $lastDay);
            $firstDay = false;

            $this->updateLeaveDurationParameters(
                $leave,
                $leaveAssignmentData->getEmployeeNumber(),
                $leaveDuration,
                $isWeekend,
                $isHoliday,
                $isHalfday,
                $isHalfDayHoliday
            );
            $leave->setStatus($this->getLeaveRequestStatus($isWeekend, $isHoliday, $leaveDate, $leaveAssignmentData));

            array_push($leaveList, $leave);
        }

        return $leaveList;
    }

    /**
     * Date increment
     * @param int $timestamp
     */
    final protected function incDate($timestamp) {
        return strtotime("+1 day", $timestamp);
    }

    /**
     * @param DateTime $day
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    public function isHalfDay(DateTime $day, LeaveParameterObject $leaveAssignmentData):bool
    {
        $empNumber = $leaveAssignmentData->getEmployeeNumber();
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);

        /* This is to check weekday half days */
        $isHalfDay = $workSchedule->isHalfDay($day);

        if (!$isHalfDay) {
            /* This checks for weekend half day */
            $isHalfDay = $workSchedule->isNonWorkingDay($day, false);
        }

        return $isHalfDay;
    }

    /**
     * @param DateTime $day
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    protected function isWeekend(DateTime $day, LeaveParameterObject $leaveAssignmentData):bool {
        $empNumber = $leaveAssignmentData->getEmployeeNumber();
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);
        return $workSchedule->isNonWorkingDay($day, true);
    }

    /**
     * @param DateTime $day
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    protected function isHoliday(DateTime $day, LeaveParameterObject $leaveAssignmentData):bool {
        $empNumber = $leaveAssignmentData->getEmployeeNumber();
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);
        return $workSchedule->isHoliday($day);
    }

    /**
     * @param DateTime $day
     * @param LeaveParameterObject $leaveAssignmentData
     * @return bool
     */
    protected function isHalfdayHoliday(DateTime $day, LeaveParameterObject $leaveAssignmentData):bool {
        $empNumber = $leaveAssignmentData->getEmployeeNumber();
        $workSchedule = $this->getWorkScheduleService()->getWorkSchedule($empNumber);
        return $workSchedule->isHalfDayHoliday($day);
    }

    /**
     * Calculate Date deference
     *
     * @param LeaveParameterObject $leaveAssignmentData
     * @param bool $isWeekend
     * @param bool $isHoliday
     * @param bool $isHalfday
     * @param bool $isHalfDayHoliday
     * @return int
     */
    public function calculateDateDeference(LeaveParameterObject $leaveAssignmentData, $isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday) {

        if ($isWeekend) {
            $dayDeference = 0;
        } elseif ($isHoliday) {
            if ($isHalfDayHoliday) {
                if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate()) {
                    if ($leaveAssignmentData->getWorkShiftLength() / 2 <= $leaveAssignmentData->getLeaveTotalTime()) {
                        $dayDeference = 0.5;
                    } else {
                        $dayDeference = number_format($leaveAssignmentData->getLeaveTotalTime() / $leaveAssignmentData->getWorkShiftLength(), 3);
                    }
                } else {
                    $dayDeference = 0.5;
                }
            } else {
                $dayDeference = 0;
            }
        } elseif ($isHalfday) {

            if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate()) {
                if ($leaveAssignmentData->getWorkShiftLength() / 2 <= $leaveAssignmentData->getLeaveTotalTime()) {
                    $dayDeference = 0.5;
                } else {
                    $dayDeference = number_format($leaveAssignmentData->getLeaveTotalTime() / $leaveAssignmentData->getWorkShiftLength(), 3);
                }
            } else {
                $dayDeference = 0.5;
            }
        } else {
            if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate()) {
                $dayDeference = number_format($leaveAssignmentData->getLeaveTotalTime() / $leaveAssignmentData->getWorkShiftLength(), 3);
            } else {
                //$dayDeference	=	floor((strtotime($posts['txtToDate'])-strtotime($posts['txtFromDate']))/86400)+1;
                $dayDeference = 1;
            }
        }

        return $dayDeference;
    }

    /**
     *
     * @param LeaveParameterObject $leaveAssignmentData
     * @param bool $isWeekend
     * @param bool $isHoliday
     * @param bool $isHalfday
     * @param bool $isHalfDayHoliday
     * @return int
     */
    public function calculateTimeDeference(LeaveParameterObject $leaveAssignmentData, $isWeekend, $isHoliday, $isHalfday, $isHalfDayHoliday) {

        if ($isWeekend) {
            $timeDeference = 0;
        } elseif ($isHoliday) {
            if ($isHalfDayHoliday) {
                if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate()) {
                    if ($leaveAssignmentData->getWorkShiftLength() / 2 <= $leaveAssignmentData->getLeaveTotalTime()) {
                        $timeDeference = number_format($leaveAssignmentData->getWorkShiftLength() / 2, 3);
                    } else {
                        $timeDeference = $leaveAssignmentData->getLeaveTotalTime();
                    }
                } else {
                    $timeDeference = number_format($leaveAssignmentData->getWorkShiftLength() / 2, 3);
                }
            } else {
                $timeDeference = 0;
            }
        } elseif ($isHalfday) {
            if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate() && $leaveAssignmentData->getLeaveTotalTime() > 0) {
                if ($leaveAssignmentData->getWorkShiftLength() / 2 <= $leaveAssignmentData->getLeaveTotalTime()) {
                    $timeDeference = number_format($leaveAssignmentData->getWorkShiftLength() / 2, 3);
                } else {
                    $timeDeference = $leaveAssignmentData->getLeaveTotalTime();
                }
            } else {
                $timeDeference = number_format($leaveAssignmentData->getWorkShiftLength() / 2, 3);
            }
        } else {
            if ($leaveAssignmentData->getToDate() == $leaveAssignmentData->getFromDate()) {
                $timeDeference = $leaveAssignmentData->getLeaveTotalTime();
            } else {
                $timeDeference = $this->getWorkShiftLengthOfEmployee($leaveAssignmentData->getEmployeeNumber());
            }
        }

        return $timeDeference;
    }

    /**
     * Get work shift length
     * @return int
     */
    protected function getWorkShiftLengthOfEmployee($employeeNumber) {

        $employeeWorkShift = $this->getEmployeeService()->getEmployeeWorkShift($employeeNumber);

        if (!is_null($employeeWorkShift) && ($employeeWorkShift instanceof EmployeeWorkShift)) {
            return $employeeWorkShift->getWorkShift()->getHoursPerDay();
        } else {
            return WorkShift::DEFAULT_WORK_SHIFT_LENGTH;
        }
    }

}
