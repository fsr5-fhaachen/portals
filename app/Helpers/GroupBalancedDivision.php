<?php


namespace App\Helpers;


use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class GroupBalancedDivision extends GroupDivision
{
  public function __construct(Event $event, bool $assignByAlc, int $minNonDrinkers = 3) {
    parent::__construct($event, $assignByAlc, $minNonDrinkers);
  }

  protected function calcOptFill(Collection $registrations)
  {
    $optFill = array();

    foreach (Course::all() as $course){
      $registrationsOfCourse = $registrations->toQuery()
        ->join('users', 'registrations.user_id', '=', 'users.id')
        ->where('users.course_id', '=', $course->id)
        ->get();

      $optFill[$course->id] = floor($registrationsOfCourse->count() / $this->groups->count() );
    }

    return $optFill;
  }

  protected function calcCurrFill()
  {
    $currFill = array();

    foreach ($this->groups as $group){
      foreach (Course::all() as $course){
        $registrationsOfGroupAndCourse = $this->registrations->toQuery()
          ->join('users', 'registrations.user_id', '=', 'users.id')
          ->where([
            ['users.course_id', '=', $course->id],
            ['registrations.group_id', '=', $group->id]
            ])
          ->get();
        $currFill[$group->id][$course->id] = $registrationsOfGroupAndCourse->count();
      }
    }

    return $currFill;
  }

  protected function calcFillRate(Collection $registrations)
  {
    $fillRate = array();
    $optFill = $this->calcOptFill($registrations);
    $currFill = $this->calcCurrFill();

    foreach (Course::all() as $course){
      $optFillForCourse = $optFill[$course->id];
      foreach ($this->groups as $group){
        $fillRate[$group->id][$course->id] = $optFillForCourse - $currFill[$group->id][$course->id];
      }
    }

    return $fillRate;
  }

  protected function assignBalanced(Collection $toBeAssignedRegs, Collection $totalRegs)
  {
    $fillRate = $this->calcFillRate($totalRegs);

    foreach (Course::all() as $course){
      $regsOfCourse = $toBeAssignedRegs->toQuery()
        ->join('users', 'registrations.user_id', '=', 'users.id')
        ->where('users.course_id', '=', $course->id)
        ->get()
        ->shuffle();

      foreach ($this->groups as $group){

        // Assign registrations of given course to a group until fill rate of course for that group is hit
        for ($i = 0; $i < $fillRate[$group->id][$course->id]; $i++){

          // Stop if no more registrations of given course are unassigned
          if ($regsOfCourse->count() <= 0) break;

          $registration = $regsOfCourse->pop();
          $registration->group_id = $group->id;
          $registration->save();
        }
      }
    }
  }

  /**
   * @inheritDoc
   */
  public function getUnassignedRegs()
  {
    return $this->registrations->toQuery()
      ->where('group_id', '=', null)
      ->get();
  }

  /**
   * @inheritDoc
   */
  protected function assignNonDrinkers()
  {
    $nonDrinkerRegs = $this->registrations->toQuery()
      ->where('registrations.drinks_alcohol', '=', false)
      ->get();

    $nonDrinkerFillRates = $this->calcFillRate($nonDrinkerRegs);

    // If you can satisfy minNonDrinkers requirement with a balanced fill, do it first
    if (array_sum($nonDrinkerFillRates[1]) > $this->minNonDrinkers){
      $this->assignBalanced($nonDrinkerRegs, $nonDrinkerRegs);
    }

    // Assign yet unassigned non-drinkers
    $nonDrinkerRegs = $nonDrinkerRegs->toQuery()
      ->where('group_id', '=', null)
      ->get()
      ->shuffle();

    // If chunking by minNonDrinkers would give more chunks than groups, increase chunk size by 1 until it fits
    $nonDrinkersPerGroup = $this->minNonDrinkers;
    while ( ($nonDrinkerRegs->count() / $nonDrinkersPerGroup) > $this->groups->count() ){
      $nonDrinkersPerGroup++;
    }
    $chunks = $nonDrinkerRegs->chunk($nonDrinkersPerGroup);

    // Assign each chunk to a group
    foreach ($this->groups as $group){
      if ($chunks->count() == 0) return;
      $chunk = $chunks->pop();

      foreach ($chunk as $registration){
        $registration->group_id = $group->id;
        $registration->save();
      }
    }
  }

  /**
   * @inheritDoc
   */
  protected function assignUntilSatisfies()
  {
    // Get only registrations that have yet to be assigned a group
    $unassignedRegs = $this->getUnassignedRegs();

    $this->assignBalanced($unassignedRegs, $this->registrations);
  }

  // TODO Handle duplicate code fragment and add phpdoc
  protected function assignLeftoverTo(Collection $leftoverRegs, Collection $groups)
  {
    // Sort groups by how many registrations are assigned to it
    $sortedGroups = $groups->sortBy(function($group)
    {
      return $group->registrations()->count();
    });

    // Assign registrations to the groups
    $i = 0;
    $amountOfGroups = $sortedGroups->count();
    foreach ($leftoverRegs as $registration){
      if ($i >= $amountOfGroups) {
        $i = 0;
      }

      $registration->group_id = $sortedGroups[$i]->id;
      $registration->save();
      $i++;
    }
  }

  // TODO Handle duplicate code fragment
  /**
   * @inheritDoc
   */
  public function assignLeftover()
  {
    // Get only registrations that have yet to be assigned a group
    $unassignedRegs = $this->getUnassignedRegs();

    if ($this->assignByAlc){
      $unassignedNonDrinkers = $unassignedRegs->where('drinks_alcohol', '=', false);
      $groupsWithNonDrinkers = $this->groups->filter(function ($val, $key) {
        return $val->registrations()
            ->where('drinks_alcohol', '=', false)
            ->count() > 0;
      });

      $this->assignLeftoverTo($unassignedNonDrinkers, $groupsWithNonDrinkers);
    }

    // Refresh in case non-drinkers got assigned
    $unassignedRegs = $unassignedRegs->where('group_id', '=', null);

    $this->assignLeftoverTo($unassignedRegs, $this->groups);
  }

  /**
   * @inheritDoc
   */
  public function assign()
  {
    if ($this->assignByAlc){
      $this->assignNonDrinkers();
    }
    $this->assignUntilSatisfies();
    $this->assignLeftover();
  }
}
