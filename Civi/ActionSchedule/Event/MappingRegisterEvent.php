<?php
namespace Civi\ActionSchedule\Event;

use Civi\ActionSchedule\Mapping;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ActionScheduleEvent
 * @package Civi\ActionSchedule\Event
 *
 * Register any available mappings.
 */
class MappingRegisterEvent extends Event {

  /**
   * @var array
   *   Array(scalar $id => Mapping $mapping).
   */
  protected $mappings = array();

  /**
   * Register a new mapping.
   *
   * @param Mapping $mapping
   *   The new mapping.
   * @return $this
   */
  public function register(Mapping $mapping) {
    $this->mappings[$mapping->id] = $mapping;
    return $this;
  }

  /**
   * @return array
   *   Array(scalar $id => Mapping $mapping).
   */
  public function getMappings() {
    return $this->mappings;
  }

}
