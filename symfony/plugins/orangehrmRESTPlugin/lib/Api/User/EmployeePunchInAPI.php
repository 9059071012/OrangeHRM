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

namespace Orangehrm\Rest\Api\User;

use Orangehrm\Rest\Api\Exception\InvalidParamException;
use Orangehrm\Rest\Api\Exception\RecordNotFoundException;
use Orangehrm\Rest\Api\Exception\BadRequestException;
use Orangehrm\Rest\Http\Response;
use Orangehrm\Rest\Api\Attendance\PunchInAPI;
use \PluginAttendanceRecord;
use \sfContext;
use \DateTimeZone;
use \DateTime;
use \AttendanceRecord;


class EmployeePunchInAPI extends PunchInAPI
{
    /**
     * @return Response
     * @throws InvalidParamException
     * @throws RecordNotFoundException
     */
    public function savePunchIn()
    {
        $timeZone = $this->getRequestParams()->getPostParam(parent::PARAMETER_TIME_ZONE);
        $punchInNote = $this->getRequestParams()->getPostParam(parent::PARAMETER_NOTE);
        $dateTime = $this->getRequestParams()->getPostParam(parent::PARAMETER_DATE_TIME);

        $editable = $this->getAttendanceService()->getDateTimeEditable();
        if ($editable && empty($dateTime)) {
            throw new InvalidParamException('Datetime Cannot Be Empty');
        } else {
            if (!$editable && !empty($dateTime)) {
                throw new InvalidParamException('You Are Not Allowed To Change Current Date & Time');
            }
        }
        $empNumber = $this->getAttendanceService()->GetLoggedInEmployeeNumber();

        if (!$this->checkValidEmployee($empNumber)) {
            throw new RecordNotFoundException('Employee Id ' . $empNumber . ' Not Found');
        }
        $actionableStatesList = array(PluginAttendanceRecord::STATE_PUNCHED_IN);
        $attendanceRecord = $this->getAttendanceService()->getLastPunchRecord(
            $empNumber,
            $actionableStatesList
        );
        if ($attendanceRecord) {
            throw new InvalidParamException('Cannot Proceed Punch In Employee Already Punched In');
        }
        $attendanceRecord = new AttendanceRecord();
        $attendanceRecord->setEmployeeId($empNumber);

        $nextState = PluginAttendanceRecord::STATE_PUNCHED_IN;
        if (empty($timeZone)) {
            throw new InvalidParamException('Datetime Cannot Be Empty');
        }
        if (!$this->getAttendanceService()->validateTimezone($timeZone)) {
            throw new InvalidParamException('Invalid Time Zone');
        }
        $timeZoneDTZ = new DateTimeZone($timeZone);
        $originDateTime = new DateTime($dateTime, $timeZoneDTZ);
        $punchIndateTime = $originDateTime->format('Y-m-d H:i');
        $timeZoneOffset = $this->getTimezoneOffset('UTC', $timeZone);

        //check overlapping
        $punchInUtcTime = $this->getAttendanceService()->getCalculatedPunchInUtcTime($punchIndateTime, $timeZoneOffset);
        $isValid = $this->getAttendanceService()->checkForPunchInOverLappingRecords(
            $punchInUtcTime,
            $empNumber
        );
        if (!$isValid) {
            throw new InvalidParamException('Overlapping Records Found');
        }

        try {
            $attendanceRecord = $this->setPunchInRecord(
                $attendanceRecord,
                $nextState,
                $punchInUtcTime,
                $punchIndateTime,
                $timeZoneOffset / 3600,
                $punchInNote
            );

            $displayTimeZoneOffset = $this->getAttendanceService()->getOriginDisplayTimeZoneOffset($timeZoneOffset);

            return new Response(
                array(
                    'success' => 'Successfully Punched In',
                    'id' => $attendanceRecord->getId(),
                    'datetime' => $attendanceRecord->getPunchInUserTime(),
                    'timezone' => $displayTimeZoneOffset,
                    'note' => $attendanceRecord->getPunchInNote()
                )
            );
        } catch (Exception $e) {
            new BadRequestException($e->getMessage());
        }
    }


    /**
     * @return array
     */
    public function getValidationRules()
    {
        return array(
            self::PARAMETER_NOTE => array('NotEmpty' => true, 'StringType' => true, 'Length' => array(1, 250)),
            self::PARAMETER_DATE_TIME => array('NotEmpty' => true, 'Date' => array('Y-m-d H:i'))
        );
    }

    public function getDetailsForPunchIn()
    {
        $empNumber = $this->getAttendanceService()->GetLoggedInEmployeeNumber();
        if (!$this->checkValidEmployee($empNumber)) {
            throw new RecordNotFoundException('Employee Id' . $empNumber . ' Not Found');
        }
        $actionableStatesList = array(PluginAttendanceRecord::STATE_PUNCHED_IN);
        $attendanceRecord = $this->getAttendanceService()->getLastPunchRecord($empNumber, $actionableStatesList);
        if ($attendanceRecord) {
            throw new InvalidParamException('Cannot Proceed Punch In Employee Already Punched In');
        }
        $lastRecord = $this->getAttendanceService()->getLatestPunchInRecord(
            $empNumber,
            PluginAttendanceRecord::STATE_PUNCHED_OUT
        );
        $lastRecordId = null;
        $displayTimeZoneOffset = null;
        if ($lastRecord) {
            $lastRecordId = $lastRecord->getId();
            $lastRecordPunchOutTime = $lastRecord->getPunchOutUserTime();
            $punchOutTimeOffset = $lastRecord->getPunchOutTimeOffset();
            $displayTimeZoneOffset = $this->getAttendanceService()->getOriginDisplayTimeZoneOffset($punchOutTimeOffset);
        }


        $punchTimeEditableDetails = $this->getPunchTimeEditable();
        return new Response(
            array(
                'id' => $lastRecordId,
                'punchOutTime' => $lastRecordPunchOutTime,
                'punchOutTimezone' => $displayTimeZoneOffset,
                'dateTimeEditable' => $punchTimeEditableDetails['editable'],
                'currentUtcDateTime' => $punchTimeEditableDetails['serverUtcTime']
            )
        );
    }
}
