<?php
namespace Civi\ActionSchedule\Event;

use Civi\ActionSchedule\MappingInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class ActionScheduleEvent
 * @package Civi\ActionSchedule\Event
 *
 * Register any available mappings.
 *
 * Event name: 'civi.actionSchedule.getMappings'
 */
class MappingRegisterEvent extends Event {

  /**
   * @var array
   *   Array(scalar $id => Mapping $mapping).
   */
  protected $mappings = [];

  /**
   * Register a new mapping.
   *
   * @param \Civi\ActionSchedule\MappingInterface $mapping
   *   The new mapping.
   * @return MappingRegisterEvent
   */
  public function register(MappingInterface $mapping) {
    $this->mappings[$mapping->getId()] = $mapping;
    return $this;
  }

  /**
   * @return array
   *   Array(scalar $id => MappingInterface $mapping).
   */
  public function getMappings() {
    ksort($this->mappings);
    return $this->mappings;
  }

}
