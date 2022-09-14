<?php


namespace App\Helpers;


use App\Models\Course;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

class GroupCourseDivision extends GroupDivision
{
  protected Course $course;

  public function __construct(Event $event, Course $course, bool $assignByAlc, int $minNonDrinkers = 3) {
    parent::__construct($event, $assignByAlc, $minNonDrinkers);
    $this->course = $course;
    $this->registrations = $this->registrations->toQuery()
      ->join('users', 'registrations.user_id', '=', 'users.id')
      ->where('users.course_id', '=', $course->id)
      ->get();
    $this->groups = $this->groups->toQuery()
      ->where('course_id', '=', $course->id)
      ->get();
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
      $chunk = $chunks->pop();
      if ($chunk == null) return;

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
    $groupMinSize = floor($this->registrations->count() / $this->groups->count());

    // Get only registrations that have yet to be assigned a group
    $unassignedRegs = $this->getUnassignedRegs()
      ->shuffle();

    // Assign registrations until groupMinSize is reached for every group
    foreach ($this->groups as $group){
      $assignAmount = $groupMinSize - $group->registrations()->count();

      for ($i = 0; $i < $assignAmount; $i++){
        $registration = $unassignedRegs->pop();
        $registration->group_id = $group->id;
        $registration->save();
      }
    }
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
