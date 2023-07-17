<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Event;
use App\Models\Group;
use App\Models\GroupTutor;
use App\Models\Page;
use App\Models\Registration;
use App\Models\Slot;
use App\Models\Station;
use App\Models\StationTutor;
use App\Models\Stop;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\String_;

class DatabaseTestController extends Controller
{
    /**
     * Tables that should be affected by clearAllTables.
     */
    private array $tableNames = [
        'courses',
        'events',
        'group_tutor',
        'groups',
        'pages',
        'registrations',
        'slots',
        'station_tutor',
        'stations',
        'stops',
        'users',
    ];

    /**
     * Removes all data from all tables found in tableNames
     *
     * @return void
     */
    public function clearAllTables()
    {
        foreach ($this->tableNames as $tableName) {
            DB::table($tableName)->delete();
        }
    }

  /**
   * Removes all data from specified table
   *
   * @return void
   */
  public function clearTable(string $tableName)
  {
      DB::table($tableName)->delete();
  }

  /**
   * Create a random collection of models for given table name and persist them to the database.
   *
   * @return Collection<int, Model>|Model|string
   */
  public function randomFillTable(String_ $tableName, int $amount)
  {
      switch ($tableName) {
          case 'courses':
              return $this->randomFillCourses($amount);
          case 'events':
              return $this->randomFillEvents($amount);
          case 'pages':
              return $this->randomFillPages($amount);
          case 'users':
              return $this->randomFillUsers($amount);
          case 'slots':
              return $this->randomFillSlots($amount);
          case 'stations':
              return $this->randomFillStations($amount);
          case 'groups':
              return $this->randomFillGroups($amount);
          case 'group_tutor':
              return $this->randomFillGroupTutors($amount);
          case 'stops':
              return $this->randomFillStops($amount);
          case 'station_tutor':
              return $this->randomFillStationTutors($amount);
          case 'registrations':
              return $this->randomFillRegistrations($amount);
          default:
              return 'Specified table could not be found!';
      }
  }

  /**
   * Create a random collection of courses and persist them to the database.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillCourses(int $amount)
  {
      return Course::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of events and persist them to the database.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillEvents(int $amount)
  {
      return Event::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of pages and persist them to the database.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillPages(int $amount)
  {
      return Page::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of users and persist them to the database. Requires the courses table to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillUsers(int $amount)
  {
      return User::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of slots and persist them to the database. Requires the events table to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillSlots(int $amount)
  {
      return Slot::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of stations and persist them to the database. Requires the events table to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillStations(int $amount)
  {
      return Station::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of groups and persist them to the database. Requires the events and courses tables to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillGroups(int $amount)
  {
      return Group::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of group_tutors and persist them to the database. Requires the users and groups tables to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillGroupTutors(int $amount)
  {
      return GroupTutor::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of stops and persist them to the database. Requires the groups and stations tables to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillStops(int $amount)
  {
      return Stop::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of station_tutors and persist them to the database. Requires the users and stations tables to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillStationTutors(int $amount)
  {
      return StationTutor::factory()->count($amount)->create();
  }

  /**
   * Create a random collection of registrations and persist them to the database. Requires the events, users, slots and groups tables to be pre-populated.
   *
   * @return Collection<int, Model>|Model
   */
  public function randomFillRegistrations(int $amount)
  {
      return Registration::factory()->count($amount)->create();
  }
}
