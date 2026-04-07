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
use Civi\Api4\PriceFieldValue;
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
  use EntityTrait;

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
    try {
      if (empty($priceSetParameters['id'])) {
        $this->eventCreatePriceSet($priceSetParameters, $identifier);
        $priceSetParameters['id'] = $this->ids['PriceSet'][$identifier];
      }
      $this->createTestEntity('PriceSetEntity', [
        'entity_table' => 'civicrm_event',
        'entity_id' => $event['id'],
        'price_set_id' => $priceSetParameters['id'],
      ], $identifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('Failed to create PriceSetEntity: ' . $e->getMessage());
    }
    return $event;
  }

  /**
   * Add a discount price set to the given event.
   *
   * @param string $eventIdentifier
   * @param array $discountParameters
   * @param array $priceSetParameters
   * @param string $identifier
   * @param float $fraction
   *
   * @noinspection PhpUnhandledExceptionInspection
   * @noinspection PhpDocMissingThrowsInspection
   */
  public function addDiscountPriceSet(string $eventIdentifier = 'PaidEvent', array $discountParameters = [], array $priceSetParameters = [], string $identifier = 'discount', $fraction = .5): void {
    $this->eventCreatePriceSet($priceSetParameters, $identifier);
    $discountParameters = array_merge([
      'start_date' => '1 week ago',
      'end_date' => 'tomorrow',
      'entity_id' => $this->getEventID($eventIdentifier),
      'entity_table' => 'civicrm_event',
      'price_set_id' => $this->ids['PriceSet'][$identifier],
    ], $discountParameters);
    $this->createTestEntity('Discount', $discountParameters, $identifier);
    $priceOptions = PriceFieldValue::get(FALSE)
      ->addWhere('price_field_id.price_set_id', '=', $this->ids['PriceSet'][$identifier])
      ->execute();
    foreach ($priceOptions as $price) {
      PriceFieldValue::update(FALSE)->addWhere('id', '=', $price['id'])
        ->setValues(['amount' => round($price['amount'] * $fraction)])->execute();
    }
  }

  /**
   * Create an unpaid event.
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
   * Update an event.
   *
   * @param array $eventParameters
   *   Values to
   *
   * @param string $identifier
   *   Index for storing event ID in ids array.
   *
   */
  protected function updateEvent(array $eventParameters = [], string $identifier = 'event'): void {
    Event::update(FALSE)
      ->addWhere('id', '=', $this->getEventID($identifier))
      ->setValues($eventParameters)
      ->execute();
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
  public function eventCreate(array $params = [], string $identifier = 'event'): array {
    try {
      if ($params['is_template'] ?? NULL && empty($params['template_title'])) {
        $params['template_title'] = 'template event';
      }
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
    ];
    foreach ($profiles as $profile) {
      $this->createEventProfile($profile, $identifier);
      if ($this->getEventValue('is_multiple_registrations', $identifier)) {
        $this->createEventProfile($profile, $identifier, TRUE);
      }
    }
    $sharedProfile = ['name' => '_post_post', 'title' => 'Event Post Post Profile', 'weight' => 3, 'fields' => ['job_title']];
    $this->createEventProfile($sharedProfile, $identifier);
    if ($this->getEventValue('is_multiple_registrations', $identifier)) {
      // For this one use the same profile but 2 UFJoins - to provide variation.
      // e.g. we hit a bug where behaviour was different if the profiles for
      // additional were the same uf group or different ones.
      $profileName = $identifier . '_post_post';
      $this->setTestEntity('UFJoin', UFJoin::create(FALSE)->setValues([
        'module' => 'CiviEvent_Additional',
        'entity_table' => 'civicrm_event',
        'uf_group_id:name' => $profileName,
        'weight' => $profile['weight'],
        'entity_id' => $this->getEventID($identifier),
      ])->execute()->first(), $profileName . '_' . $identifier);
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
        'entity_table' => 'civicrm_event',
        'uf_group_id:name' => $profileName,
        'weight' => $profile['weight'],
        'entity_id' => $this->getEventID($identifier),
      ])->execute()->first(), $profileIdentifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('UF join creation failed for UF Group ' . $profileName . ' with error ' . $e->getMessage());
    }
  }

  /**
   * Create a price set for an event.
   *
   * @param array $priceSetParameters
   * @param string $identifier
   */
  private function eventCreatePriceSet(array $priceSetParameters, string $identifier): void {
    $priceSetParameters = array_merge([
      'min_amount' => 0,
      'title' => 'Fundraising dinner',
      'name' => $identifier,
      'extends:name' => 'CiviEvent',
      'financial_type_id:name' => 'Event Fee',
    ], $priceSetParameters);

    $this->createTestEntity('PriceSet', $priceSetParameters, $identifier);
    $this->createTestEntity('PriceField', [
      'label' => 'Fundraising Dinner',
      'name' => 'fundraising_dinner',
      'html_type' => 'Radio',
      'is_display_amounts' => 1,
      'options_per_line' => 1,
      'price_set_id' => $this->ids['PriceSet'][$identifier],
      'is_enter_qty' => 1,
      'financial_type_id:name' => 'Event Fee',
    ], $identifier);

    foreach ($this->getPriceFieldOptions() as $optionIdentifier => $priceFieldOption) {
      $this->createTestEntity('PriceFieldValue',
        array_merge([
          'price_field_id' => $this->ids['PriceField'][$identifier],
          'financial_type_id:name' => 'Event Fee',
        ], $priceFieldOption), $identifier . '_' . $optionIdentifier);
    }
  }

  /**
   * Get the options for the price set.
   *
   * @param string $identifier Optional string if we want to specify different
   *   options. This is not currently used but is consistent with our other
   *   functions and would allow over-riding.
   *
   * @return array[]
   */
  protected function getPriceFieldOptions(string $identifier = 'PaidEvent'): array {
    if ($identifier !== 'PaidEvent') {
      $this->fail('Only paid event currently supported');
    }
    return [
      'free' => ['name' => 'free', 'label' => 'Complementary', 'amount' => 0],
      'student_early' => ['name' => 'student_early', 'label' => 'Student early bird', 'amount' => 50],
      'student' => ['name' => 'student', 'label' => 'Student Rate', 'amount' => 100],
      'student_plus' => ['name' => 'student_plus', 'label' => 'Student Deluxe', 'amount' => 200],
      'standard' => ['name' => 'standard', 'label' => 'Standard Rate', 'amount' => 300],
      'family_package' => ['name' => 'family_package', 'label' => 'Family Deal', 'amount' => 1550.55],
      'corporate_table' => ['name' => 'corporate_table', 'label' => 'Corporate Table', 'amount' => 8000.67],
    ];
  }

}
