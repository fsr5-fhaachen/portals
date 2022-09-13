<?php


namespace App\Helpers;


use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;

abstract class GroupDivision
{
  protected Event $event;
  protected Collection $groups;
  protected Collection $registrations;
  protected bool $assignByAlc;
  protected int $minNonDrinkers;

  public function __construct(Event $event, bool $assignByAlc, int $minNonDrinkers = 3) {
    $this->event = $event;
    $this->groups = $event->groups()->get();
    $this->registrations = $event->registrations()->get();
    $this->assignByAlc = $assignByAlc;
    $this->minNonDrinkers = $minNonDrinkers;
  }

  /**
   * Returns all registrations for this event that have yet to be assigned a group
   *
   * @return Collection
   */
  abstract public function getUnassignedRegs();

  /**
   * Assigns non-drinkers registered for this event to groups
   *
   * @return void
   */
  abstract protected function assignNonDrinkers();

  /**
   * Assigns users registered for this event to groups in a way that satisfies requirements to that specific division algorithm
   *
   * @return void
   */
  abstract protected function assignUntilSatisfies();

  /**
   * Assigns users registered for this event that have yet to be assigned a group to groups
   *
   * @return void
   */
  abstract public function assignLeftover();

    /**
   * Assigns users registered for this event to groups
   *
   * @return void
   */
  abstract public function assign();
}
