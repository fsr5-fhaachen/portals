<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Grouphasstation;
use App\Models\Station;
use App\Models\Student;
use App\Models\Timeslot;
use App\Models\Tutor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class AdminController extends Controller
{
    public function getStats()
    {
        $stats = [
            [
                'title' => 'Anmeldungen (Insgesamt)',
                'value' => Student::where('attended', true)->count() . '/' . Student::count(),
            ]
        ];

        foreach ($this->courseNames as $courseName) {
            $stats[] = [
                'title' => 'Anmeldungen (' . $courseName . ')',
                'value' => Student::where('course', $courseName)->where('attended', true)->count() . '/' . Student::where('course', $courseName)->count(),
            ];
        }

        $stats[] = [
            'title' => 'Tutoren',
            'value' => Tutor::count(),
        ];

        return $stats;
    }
    public function index()
    {
        return Inertia::render('Admin/Index', [
            'stats' => $this->getStats(),
        ]);
    }

    public function getGroupsCount($withKeys = false)
    {
        $groupsCount = [
            'all' => Group::count(),
            'courses' => [],
        ];

        foreach ($this->courseNames as $courseName) {
            $count = Group::where('course', $courseName)->count();
            if ($count > 0) {
                if ($withKeys) {
                    $groupsCount['courses'][$courseName] = [
                        'title' => $courseName,
                        'count' => $count,
                    ];
                } else {
                    $groupsCount['courses'][] = [
                        'title' => $courseName,
                        'count' => $count,
                    ];
                }
            }
        }

        return $groupsCount;
    }

    public function start()
    {
        return Inertia::render('Admin/Start', [
            'stats' => $this->getStats(),
            'groupsCount' => $this->getGroupsCount(),
        ]);
    }

    public function startGrouping(Request $request)
    {
        $groupsCount = $this->getGroupsCount(true);

        if ($groupsCount['all'] > 0) {

            if (!empty($groupsCount['courses'])) {
                foreach ($this->courseNames as $course) {
                    if (!\key_exists($course, $groupsCount['courses'])) {
                        continue;
                    }
                    $groupSize = ceil(Student::where('course', $course)->count() / $groupsCount['courses'][$course]['count']);
                    if ($groupSize > 0) {
                        $this->randAssignmentFhTour($groupSize, $course);
                    }
                }
            } else {
                $groupSize = ceil(Student::count() / $groupsCount['all']);
                if ($groupSize > 0) {
                    $this->randAssignmentGroupPhase($groupSize, $groupsCount['all']);
                }
            }
        } else {
            $request->validate([
                'groupCount' => ['bail', 'required', 'integer', 'min:1'],
            ]);
            for ($i = 0; $i < $request->input('groupCount'); $i++) {
                (new Group())->save();
            }
            $groupSize = ceil(Student::count() / Group::count());
            if ($groupSize > 0) {
                $this->randAssignmentGroupPhase($groupSize, $request->input('groupCount'));
            }
        }

        // redirect to admin result page
        return Redirect::route('admin.result');
    }

    public function result()
    {
        return Inertia::render('Admin/Result', []);
    }

    /**
     * Names of all supported courses.
     *
     * @var array
     */
    private $courseNames = [
        'ET',
        'INF',
        'MCD',
        'WI'
    ];


    public function getAdminGroupInfo()
    {
        // TODO implement retrieving all groups and necessary information
    }

    /**
     * Creates a Tutor model from provided data and persists it in the database.
     * If the database contains an entry with the same email, the entry gets updated instead.
     *
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $course
     */
    public function updateOrCreateTutor($firstName, $lastName, $email, $course)
    {
        Tutor::query()->updateOrCreate(
            ['email' => $email],
            [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'course' => $course,
                'group_id' => null,
                'station_id' => null
            ]
        );
    }

    /**
     * Creates a Group model from provided data and persists it in the database.
     *
     * @param string $name
     */
    public function createGroup($name)
    {
        Group::query()->create([
            'name' => $name,
            'course' => null,
            'station_id' => null,
            'timeslot_id' => null
        ]);
    }

    /**
     * Creates a Station model from provided data and persists it in the database.
     *
     * @param string $name
     */
    public function createStation($name)
    {
        Station::query()->create([
            'name' => $name
        ]);
    }

    /**
     * Creates a Timeslot model from provided data and persists it in the database.
     * If the database contains an entry with the same name, the entry gets updated instead.
     *
     * @param string $name
     * @param string $time
     */
    public function updateOrCreateTimeslot($name, $time)
    {
        Timeslot::query()->updateOrCreate(
            ['name' => $name],
            [
                'time' => $time
            ]
        );
    }

    /**
     * Creates a Grouphasstation model from provided data and persists it in the database.
     *
     * @param mixed $groupId
     * @param mixed $stationId
     * @param mixed $step
     */
    public function createTourStep($groupId, $stationId, $step)
    {
        Grouphasstation::query()->create([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'step' => $step
        ]);
    }

    /**
     * Gets a Grouphasstation model from the database by its ID and updates it.
     *
     * @param mixed $groupHasStationId
     * @param mixed $groupId
     * @param mixed $stationId
     * @param mixed $step
     */
    public function updateTourStep($groupHasStationId, $groupId, $stationId, $step)
    {
        Grouphasstation::query()->find($groupHasStationId)->update([
            'group_id' => $groupId,
            'station_id' => $stationId,
            'step' => $step
        ]);
    }


    /**
     * Gets a Group model from the database by its ID and updates the timeslot_id.
     *
     * @param mixed $timeslotId
     * @param mixed $groupId
     */
    public function assignTimeslotToGroup($timeslotId, $groupId)
    {
        Group::query()->find($groupId)->update(['timeslot_id' => $timeslotId]);
    }

    /**
     * Gets a Tutor model from the database by its ID and updates the group_id.
     *
     * @param mixed $groupId
     * @param mixed $tutorId
     */
    public function assignGroupToTutor($groupId, $tutorId)
    {
        Tutor::query()->find($tutorId)->update(['group_id' => $groupId]);
    }

    /**
     * Gets a Tutor model from the database by its ID and updates the station_id.
     *
     * @param mixed $stationId
     * @param mixed $tutorId
     */
    public function assignStationToTutor($stationId, $tutorId)
    {
        Tutor::query()->find($tutorId)->update(['station_id' => $stationId]);
    }

    /**
     * Gets a Group model from the database by its ID and updates the course.
     *
     * @param mixed $groupId
     * @param string $course
     */
    public function setGroupCourse($groupId, $course)
    {
        Group::query()->find($groupId)->update(['course' => $course]);
    }


    /**
     * Returns the minimum amount of groups of given group size necessary to fit all students, or students of specified course.
     *
     * @param int $groupSize
     * @param string $course
     *
     * @return float
     */
    public function calculateMinAmountGroups($groupSize, $course = '')
    {
        $totalStudents = Student::getByCourse($course)->count();
        return ceil($totalStudents / $groupSize);
    }

    /**
     * Calculates what percentage of the total amount of students each course makes up.
     *
     * Returns it as an array with course names as keys and the calculated percentages as values.
     *
     * @return array
     */
    public function calculateCoursePercentages()
    {
        $totalStudents = Student::query()->count();
        $coursePercentages = [];
        foreach ($this->courseNames as $course) {
            $coursePercentages[$course] = (Student::query()
                ->where('course', 'LIKE', $course)
                ->count()) / $totalStudents;
        }
        return $coursePercentages;
    }

    /**
     * Calculates how many students of each course should be in each group of given group size by using calculated course percentages.
     * Returns it as an array with course names as keys and the calculated amount of students as values.
     *
     * @param int $groupSize
     *
     * @return array
     *
     * @see AdminController::calculateCoursePercentages() Used to calculate the percentages necessary for even distribution.
     */
    public function calculateCourseDistribution($groupSize)
    {
        $coursePercentages = $this->calculateCoursePercentages();
        $courseDistribution = [];
        foreach ($this->courseNames as $course) {
            $courseDistribution[$course] = floor($groupSize * $coursePercentages[$course]);
        }
        return $courseDistribution;
    }

    /**
     * Assigns the exact amount of students of specified course to each group up to the specified amount of groups
     * by using calculated course distribution. This way a mostly even spread of courses over the groups is guaranteed.
     *
     * Returns a collection of remaining students of the course, that could not be assigned in this manner and need to be
     * handled differently.
     *
     * @param string $course
     * @param int $amountGroups
     * @param int $coursePerGroup How many students of specified course should be in each group at least
     *
     * @return Collection
     */
    public function distributedAssignCourse($course, $amountGroups, $coursePerGroup)
    {
        if ($coursePerGroup < 1) return Student::getByCourse($course);
        $studentsOfCourse = Student::getByCourse($course)->shuffle();
        $groups = Group::all();

        $i = 1;
        foreach ($groups as $group) {
            $chunk = $studentsOfCourse->forPage($i, $coursePerGroup);
            foreach ($chunk as $student) {
                $student->group_id = $group->id;
                $student->save();
            }
            $i++;
            if ($i > $amountGroups) break;
        }

        return $studentsOfCourse->where('group_id', '=', null);
    }

    /**
     * Handles remaining unassigned students after the majority got assigned by, for instance, distributedAssignCourse.
     * Assigns students from shuffled list one at a time, thus distributing them evenly over groups.
     *
     * @param Collection $unassignedStudents
     * @param int $amountGroups
     */
    public function handleUnassignedStudentsGroupPhase($unassignedStudents, $amountGroups)
    {
        $groups = Group::all();
        $i = 0;

        foreach ($unassignedStudents as $student) {
            if ($i >= $amountGroups) $i = 0;
            $student->group_id = $groups[$i]->id;
            $student->save();
            $i++;
        }
    }

    /**
     * Assigns students of all courses to groups of specified group size by calculating the minimum amount of students of each course
     * that should be in each group and then utilizing distributedAssignCourse for the actual assignment.
     * Handles students that could not be assigned in that manner by using handleUnassignedStudentsGroupPhase.
     *
     * @param int $groupSize
     *
     * @see AdminController::calculateCourseDistribution() Used to decide how many students of each course should be at least in each group
     * @see AdminController::distributedAssignCourse() Used to distribute students of each course evenly.
     * @see AdminController::handleUnassignedStudentsGroupPhase() Used to distribute the rest afterwards.
     */
    public function randAssignmentGroupPhase($groupSize, $amountGroups)
    {
        // TODO TEST the random assignment of students for regular Group Phases which uses calculated distribution of courses
        $courseDistribution = $this->calculateCourseDistribution($groupSize);
        if ($amountGroups > Group::all()->count()) return; //Assignment can't work if there aren't enough groups of that size to fit all students. Duh!

        $unassignedStudents = [];

        foreach ($this->courseNames as $course) {
            $unassignedStudents[$course] = $this->distributedAssignCourse($course, $amountGroups, $courseDistribution[$course]);
        }

        $unassignedStudents = collect($unassignedStudents)->collapse()->shuffle();
        $this->handleUnassignedStudentsGroupPhase($unassignedStudents, $amountGroups);
    }

    /**
     * Assigns students of specified course and preferred timeslot to a group with matching course and timeslot.
     * Does this until either the groups are full, or all of these students are assigned to a group.
     * Works in a "first come, first serve" manner, where students who registered earlier get prioritized.
     *
     * @param int $groupSize
     * @param int $timeslotId
     * @param string $course
     */
    public function timeslotAssignCourse($groupSize, $timeslotId, $course)
    {
        $groups = Group::getByTimeslotAndCourse($timeslotId, $course);
        $students = Student::getByTimeslotAndCourse($timeslotId, $course)->reverse();

        foreach ($groups as $group) {
            $groupId = $group->id;
            for ($i = 0; $i < $groupSize; $i++) {
                if ($students->isEmpty()) return;
                $student = $students->pop();
                $student->group_id = $groupId;
                $student->save();
            }
        }
    }

    /**
     * Checks each group of specified course for open slots and calculated how many there are per group.
     *
     * Returns an array with group ids as keys and the amount of open slots as values of all not yet fully filled groups in descending order of open slots.
     *
     * @param int $groupSize
     * @param string $course
     *
     * @return array
     */
    public function calculateFillableGroups($groupSize, $course = '')
    {
        $groups = Group::getByCourse($course);
        $remainingSlotsPerGroup = [];

        foreach ($groups as $group) {
            $groupId = $group->id;
            $amountStudentsOfGroup = Student::getByGroup($groupId)->count();
            $remainingSlots = $groupSize - $amountStudentsOfGroup;
            if ($remainingSlots > 0) $remainingSlotsPerGroup[$groupId] = $remainingSlots;
        }

        arsort($remainingSlotsPerGroup);
        return $remainingSlotsPerGroup;
    }

    /**
     * Calculates what percentage of the total open slots of all groups every single group has to facilitate an even distribution.
     *
     * Returns an array with group ids as key and its percentage of the total open slots of all groups as values.
     *
     * @param array $remainingSlotsPerGroup Keys need to be the groups id, values the amount of the groups open slots
     *
     * @return array
     */
    public function calculateFillablePercentages($remainingSlotsPerGroup)
    {
        $totalRemainingSlots = array_sum($remainingSlotsPerGroup);
        $fillablePercentagePerGroup = [];

        foreach ($remainingSlotsPerGroup as $groupId => $remainingSlots) {
            $fillablePercentagePerGroup[$groupId] = $remainingSlots / $totalRemainingSlots;
        }

        return $fillablePercentagePerGroup;
    }

    /**
     * Decides how many yet unassigned students should be assigned to each group with open slots by using the provided percentages of total open slots of all groups.
     *
     * Returns an array with group ids as key and amount of unassigned students it should be assigned as values.
     *
     * @param int $amountUnassigned Amount of yet unassigned students
     * @param array $fillablePercentages Keys need to be the groups id, the value its percentage of the total open slots of all groups
     *
     * @return array
     */
    public function calculateFillAmount($amountUnassigned, $fillablePercentages)
    {
        $fillAmountPerGroup = [];
        foreach ($fillablePercentages as $groupId => $fillablePercentage) {
            $fillAmountPerGroup[$groupId] = ceil($amountUnassigned * $fillablePercentage);
        }
        arsort($fillAmountPerGroup);
        return $fillAmountPerGroup;
    }

    /**
     * Fills groups that have open slots with unassigned students evenly by calculating what percentage of the total open slots of all groups they make up and then deciding
     * how many unassigned students each should receive.
     *
     * @param int $groupSize
     * @param string $course
     *
     * @see AdminController::calculateFillableGroups() Used to calculate amount of open slots per group.
     * @see AdminController::calculateFillablePercentages() Used to calculate what percentage of total open slots each group makes up.
     * @see AdminController::calculateFillAmount() Used to calculate how many unassigned students each group with open slots should receive.
     */
    public function balancedFillFillableGroups($groupSize, $course)
    {
        $unassignedStudents = Student::getByGroupAndCourse(null, $course);

        $remainingSlotsPerGroup = $this->calculateFillableGroups($groupSize, $course);
        $fillablePercentagesPerGroup = $this->calculateFillablePercentages($remainingSlotsPerGroup);
        $fillAmountPerGroup = $this->calculateFillAmount($unassignedStudents->count(), $fillablePercentagesPerGroup);

        foreach ($fillAmountPerGroup as $groupId => $fillAmount) {
            for ($i = 0; $i < $fillAmount; $i++) {
                if ($unassignedStudents->isEmpty()) return;
                $student = $unassignedStudents->pop();
                $student->group_id = $groupId;
                $student->save();
            }
        }
    }

    /**
     * Assigns students of specified course to groups with matching course up to group size while trying to maximize the amount of students that get their preferred timeslot.
     * After assignment by matching timeslot the yet unassigned students get distributed evenly to groups with open slots.
     *
     * @param int $groupSize
     * @param string $course
     *
     * @see AdminController::timeslotAssignCourse() Used to distribute students of certain course and preferred timeslot to matching groups.
     * @see AdminController::balancedFillFillableGroups() Used to distribute unassigned students evenly to groups with open slots.
     */
    public function randAssignmentFhTour($groupSize, $course = '')
    {
        // TODO TEST the random assignment of students for the FH Tour which takes course and timeslots into account
        $timeslots = Timeslot::all();

        foreach ($timeslots as $timeslot) {
            $timeslotId = $timeslot->id;
            $this->timeslotAssignCourse($groupSize, $timeslotId, $course);
        }

        $this->balancedFillFillableGroups($groupSize, $course);
    }


    /**
     * Resets attended for each Student to its default value.
     */
    public function resetStudentAttendance()
    {
        Student::query()->each->update(['attended' => False]);
    }

    /**
     * Resets course for each group to its default value.
     */
    public function resetGroupCourse()
    {
        Group::query()->each->update(['course' => null]);
    }

    /**
     * Resets group_id for each student of specified course to its default value.
     *
     * @param string $course
     */
    public function resetGroupAssignment($course = '')
    {
        Student::getByCourse()->each->update(['group_id' => null]);
    }
}
