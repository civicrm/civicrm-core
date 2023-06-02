<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Test;

use Civi\Api4\Event;
use Civi\Api4\ExampleData;
use Civi\Api4\PriceSetEntity;
use Civi\Api4\UFField;
use Civi\Api4\UFGroup;
use Civi\Api4\UFJoin;

/**
 * Helper for event tests.
 *
 * This provides functions to set up valid events
 * for unit tests.
 *
 * The primary functions in this class are
 * - `eventCreatePaid`
 * - `eventCreateUnpaid`
 *
 * Calling these function will create events with associated
 * profiles and price set data as appropriate.
 */
trait EventTestTrait {

  /**
   * Array of IDs created to support the test.
   *
   * e.g
   * $this->ids = ['Event' => ['descriptive_key' => $eventID], 'Group' => [$groupID]];
   *
   * @var array
   */
  protected $ids = [];

  /**
   * Records created which will be deleted during tearDown
   *
   * @var array
   */
  protected $testRecords = [];

  /**
   * Track tables we have modified during a test.
   *
   * Set up functions that add entities can register the relevant tables here for
   * the cleanup process.
   *
   * @var array
   */
  protected $tablesToCleanUp = [];

  /**
   * Create a paid event.
   *
   * @param array $eventParameters
   *   Values to
   *
   * @param array $priceSetParameters
   *
   * @param string $identifier
   *   Index for storing event ID in ids array.
   *
   * @return array
   */
  protected function eventCreatePaid(array $eventParameters = [], array $priceSetParameters = [], string $identifier = 'PaidEvent'): array {
    $eventParameters = array_merge($this->getEventExampleData(), $eventParameters);
    $event = $this->eventCreate($eventParameters, $identifier);
    if (array_keys($priceSetParameters) !== ['id']) {
      try {

        // @todo - eventPriceSetCreate is only available from core tests so this can't yet
        // be used in extensions.
        $this->setTestEntityID('PriceSet', $this->eventPriceSetCreate(8000.67, 0, 'Radio', [['name' => 'family_package', 'amount' => 1550.55], ['name' => 'corporate_table', 'amount' => 8000.67]]), $identifier);
        $this->setTestEntityID('PriceSetEntity', PriceSetEntity::create(FALSE)
          ->setValues([
            'entity_table' => 'civicrm_event',
            'entity_id' => $event['id'],
            'price_set_id' => $this->ids['PriceSet'][$identifier],
          ])
          ->execute()
          ->first()['id'], $identifier);
      }

      catch (\CRM_Core_Exception $e) {
        $this->fail('Failed to create PriceSetEntity: ' . $e->getMessage());
      }
    }
    return $event;
  }

  /**
   * Create a paid event.
   *
   * @param array $eventParameters
   *   Values to
   *
   * @param string $identifier
   *   Index for storing event ID in ids array.
   *
   * @return array
   */
  protected function eventCreateUnpaid(array $eventParameters = [], string $identifier = 'event'): array {
    $eventParameters = array_merge($this->getEventExampleData(), $eventParameters);
    $eventParameters['is_monetary'] = FALSE;
    return $this->eventCreate($eventParameters, $identifier);
  }

  /**
   * Set the test entity on the class for access.
   *
   * This follows the ids patter and also the api4TestTrait pattern.
   *
   * @param string $entity
   * @param array $values
   * @param string $identifier
   */
  protected function setTestEntity(string $entity, array $values, string $identifier): void {
    $this->ids[$entity][$identifier] = $values['id'];
    $this->testRecords[] = [$entity, [[$values['id'] => $values]]];
    $tableName = \CRM_Core_DAO_AllCoreTables::getTableForEntityName($entity);
    $this->tablesToCleanUp[$tableName] = $tableName;
  }

  /**
   * @param string $entity
   * @param int $id
   * @param string $identifier
   */
  protected function setTestEntityID(string $entity, int $id, string $identifier): void {
    $this->setTestEntity($entity, ['id' => $id], $identifier);
  }

  /**
   * Get the event id of the event created in set up.
   *
   * If only one has been created it will be selected. Otherwise
   * you should pass in the appropriate identifier.
   *
   * @param string $identifier
   *
   * @return int
   */
  protected function getEventID(string $identifier = 'event'): int {
    if (isset($this->ids['Event'][$identifier])) {
      return $this->ids['Event'][$identifier];
    }
    if (count($this->ids['Event']) === 1) {
      return reset($this->ids['Event']);
    }
    $this->fail('Could not identify event ID');
    // Unreachable but reduces IDE noise.
    return 0;
  }

  /**
   * Get a value from an event used in setup.
   *
   * @param string $value
   * @param string $identifier
   *
   * @return mixed|null
   */
  protected function getEventValue(string $value, string $identifier) {
    return $this->getEvent($identifier)[$value] ?? NULL;
  }

  /**
   * This retrieves the values used to create the event.
   *
   * Note this does not actually retrieve the event from the database
   * although it arguably might be more useful.
   *
   * @param string $identifier
   *
   * @return array
   */
  protected function getEvent(string $identifier): array {
    foreach ($this->testRecords as $record) {
      if ($record[0] === 'Event') {
        $values = $record[1][0] ?? [];
        if ($this->getEventID($identifier) === array_key_first($values)) {
          return (reset($values));
        }
      }
    }
    return [];
  }

  /**
   * Create an Event.
   *
   * Note this is not expected to be called directly - call
   * - eventCreatePaid
   * - eventCreateUnpaid
   *
   * @param array $params
   *   Name-value pair for an event.
   * @param string $identifier
   *
   * @return array
   */
  protected function eventCreate(array $params = [], string $identifier = 'event'): array {
    try {
      $event = Event::create(FALSE)->setValues($params)->execute()->first();
      $this->setTestEntity('Event', $event, $identifier);
      $this->addProfilesToEvent($identifier);
      return $event;
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('Event creation failed with error ' . $e->getMessage());
    }
    // Unreachable but reduces IDE noise.
    return [];
  }

  /**
   * Get example data with which to create the event.
   *
   * @param string $name
   *
   * @return array
   */
  protected function getEventExampleData(string $name = 'PaidEvent'): array {
    try {
      $data = ExampleData::get(FALSE)
        ->addSelect('data')
        ->addWhere('name', '=', 'entity/Event/' . $name)
        ->execute()->first()['data'];
      unset($data['id']);
      return $data;
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('Event example data retrieval failed with error ' . $e->getMessage());
    }
    // Unreachable but reduces IDE noise.
    return [];
  }

  /**
   * Add profiles to the event.
   *
   * This function is designed to reflect the
   * normal use case where events do have profiles.
   *
   * Note if any classes do not want profiles, or want something different,
   * the thinking is they should override this. Once that arises we can review
   * making it protected rather than private & checking we are happy with the
   * signature.
   *
   * @param string $identifier
   *
   * @throws \CRM_Core_Exception
   */
  private function addProfilesToEvent(string $identifier = 'event'): void {
    $profiles = [
      ['name' => '_pre', 'title' => 'Event Pre Profile', 'weight' => 1, 'fields' => ['email']],
      ['name' => '_post', 'title' => 'Event Post Profile', 'weight' => 2, 'fields' => ['first_name', 'last_name']],
      ['name' => '_post_post', 'title' => 'Event Post Post Profile', 'weight' => 3, 'fields' => ['job_title']],
    ];
    foreach ($profiles as $profile) {
      $this->createEventProfile($profile, $identifier);
      if ($this->getEventValue('is_multiple_registrations', $identifier)) {
        $this->createEventProfile($profile, $identifier, TRUE);
      }
    }
  }

  /**
   * Create a profile attached to an event.
   *
   * @param array $profile
   * @param string $identifier
   * @param bool $isAdditional
   *
   * @throws \CRM_Core_Exception
   */
  private function createEventProfile(array $profile, string $identifier, bool $isAdditional = FALSE): void {
    $profileName = $identifier . ($isAdditional ? $profile['name'] . '_additional' : $profile['name']);
    $profileIdentifier = $profileName . '_' . $identifier;
    $additionalSuffix = $isAdditional ? ' (Additional) ' : '';
    try {
      $this->setTestEntity('UFGroup', UFGroup::create(FALSE)->setValues([
        'group_type' => 'Individual,Contact',
        'name' => $profileName,
        'title' => $profile['title'] . $additionalSuffix,
        'frontend_title' => 'Public ' . $profile['title'] . $additionalSuffix,
      ])->execute()->first(),
        $profileIdentifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('UF group creation failed for ' . $profileName . ' with error ' . $e->getMessage());
    }
    foreach ($profile['fields'] as $field) {
      $this->setTestEntity('UFField', UFField::create(FALSE)
        ->setValues([
          'uf_group_id:name' => $profileName,
          'field_name' => $field,
          'label' => $field,
        ])
        ->execute()
        ->first(), $field . '_' . $profileIdentifier);
    }
    try {
      $this->setTestEntity('UFJoin', UFJoin::create(FALSE)->setValues([
        'module' => $additionalSuffix ? 'CiviEvent_Additional' : 'CiviEvent',
        'uf_group_id:name' => $profileName,
        'entity_id' => $this->getEventID($identifier),
      ])->execute()->first(), $profileIdentifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('UF join creation failed for UF Group ' . $profileName . ' with error ' . $e->getMessage());
    }
  }

}
