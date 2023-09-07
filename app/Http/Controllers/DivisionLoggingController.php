<?php

namespace App\Http\Controllers;

use App\Helpers\GroupBalancedDivision;
use App\Helpers\DivisionLogger;
use App\Models\Event;
use App\Models\Group;

class DivisionLoggingController extends Controller
{
    public function logGrp(int $groupId)
    {
        $logPath = 'logs/' . env('DIVISION_LOG_FILE_NAME', 'division.log');
        $logger = new DivisionLogger(storage_path($logPath));

        $group = Group::find($groupId);

        if (! $group) return sprintf('DIVISION_LOGGER_ERROR: Could not find group with ID %&d', $groupId);

        $logger->logGroup($group);

        return sprintf('DIVISION_LOGGER: Wrote group log (%s)', storage_path($logPath));
    }

    public function logEvt(int $eventId)
    {
        $logPath = 'logs/' . env('DIVISION_LOG_FILE_NAME', 'division.log');
        $logger = new DivisionLogger(storage_path($logPath));

        $event = Event::find($eventId);

        if (! $event) return sprintf('DIVISION_LOGGER_ERROR: Could not find event with ID %&d', $eventId);

        $logger->logEvent($event);

        return sprintf('DIVISION_LOGGER: Wrote event log (%s)', storage_path($logPath));
    }
}
