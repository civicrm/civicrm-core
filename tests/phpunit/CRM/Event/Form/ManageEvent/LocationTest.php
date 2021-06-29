<?php

use Civi\Api4\Event;
use Civi\Api4\Email;

/**
 *  Test CRM_Event_Form_Registration functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Event_Form_ManageEvent_LocationTest extends CiviUnitTestCase {

  /**
   * Test the right emails exist after submitting the location form twice.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmit() {
    $eventID = (int) $this->eventCreate()['id'];
    $this->submitForm([], $eventID);
    $this->assertCorrectEmails($eventID);

    // Now do it again to see if it gets messed with.
    $this->submitForm(['loc_event_id' => $this->ids['LocBlock'][0]], $eventID);
    $this->assertCorrectEmails($eventID);
  }

  /**
   * Create() method
   * create various elements of location block
   * with civicrm_loc_block
   */
  public function testCreateWithLocBlock() {
    $eventID = (int) $this->eventCreate()['id'];
    $this->submitForm([
      'address' => [
        '1' => [
          'street_address' => 'Saint Helier St',
          'supplemental_address_1' => 'Hallmark Ct',
          'supplemental_address_2' => 'Jersey Village',
          'supplemental_address_3' => 'My Town',
          'city' => 'Newark',
          'postal_code' => '01903',
          'country_id' => 1228,
          'state_province_id' => 1029,
          'geo_code_1' => '18.219023',
          'geo_code_2' => '-105.00973',
          'is_primary' => 1,
          'location_type_id' => 1,
        ],
      ],
      'email' => [
        '1' => [
          'email' => 'john.smith@example.org',
          'is_primary' => 1,
          'location_type_id' => 1,
        ],
      ],
      'phone' => [
        '1' => [
          'phone_type_id' => 1,
          'phone' => '303443689',
          'is_primary' => 1,
          'location_type_id' => 1,
        ],
        '2' => [
          'phone_type_id' => 2,
          'phone' => '9833910234',
          'location_type_id' => 1,
        ],
      ],
    ], $eventID);

    //Now check DB for location block

    $locationBlock = Event::get()
      ->addWhere('id', '=', $eventID)
      ->setSelect(['loc_block_id.*', 'loc_block_id'])
      ->execute()->first();

    $address = $this->callAPISuccessGetSingle('Address', ['id' => $locationBlock['loc_block_id.address_id']]);

    $this->assertEquals([
      'id' => $address['id'],
      'location_type_id' => '1',
      'is_primary' => '1',
      'is_billing' => '0',
      'street_address' => 'Saint Helier St',
      'supplemental_address_1' => 'Hallmark Ct',
      'supplemental_address_2' => 'Jersey Village',
      'supplemental_address_3' => 'My Town',
      'city' => 'Newark',
      'postal_code' => '01903',
      'country_id' => 1228,
      'state_province_id' => 1029,
      'geo_code_1' => '18.219023',
      'geo_code_2' => '-105.00973',
      'manual_geo_code' => '0',
    ], $address);

    $this->callAPISuccessGetSingle('Email', ['id' => $locationBlock['loc_block_id.email_id'], 'email' => 'john.smith@example.org']);
    $this->callAPISuccessGetSingle('Phone', ['id' => $locationBlock['loc_block_id.phone_id'], 'phone' => '303443689']);
    $this->callAPISuccessGetSingle('Phone', ['id' => $locationBlock['loc_block_id.phone_2_id'], 'phone' => '9833910234']);

    // Cleanup.
    CRM_Core_BAO_Location::deleteLocBlock($locationBlock['loc_block_id']);
    $this->eventDelete($eventID);
  }

  /**
   * Test updating a location block.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testUpdateLocationBlock() {
    $eventID = (int) $this->eventCreate()['id'];
    $this->submitForm([
      'address' => [
        '1' => [
          'street_address' => 'Old address',
          'supplemental_address_1' => 'Hallmark Ct',
          'supplemental_address_2' => 'Jersey Village',
          'supplemental_address_3' => 'My Town',
          'city' => 'Newark',
          'postal_code' => '01903',
          'country_id' => 1228,
          'state_province_id' => 1029,
          'geo_code_1' => '18.219023',
          'geo_code_2' => '-105.00973',
          'is_primary' => 1,
          'location_type_id' => 1,
        ],
      ],
    ], $eventID);

    $this->submitForm([
      'location_option' => 1,
      'loc_event_id' => Event::get()->addWhere('id', '=', $eventID)->addSelect('loc_block_id')->execute()->first()['loc_block_id'],
      'address' => [
        '1' => [
          'street_address' => 'New address',
          'supplemental_address_1' => 'Hallmark Ct',
          'supplemental_address_2' => 'Jersey Village',
          'supplemental_address_3' => 'My Town',
          'city' => 'Newark',
          'postal_code' => '01903',
          'country_id' => 1228,
          'state_province_id' => 1029,
          'geo_code_1' => '18.219023',
          'geo_code_2' => '-105.00973',
        ],
      ],
      'email' => [
        '1' => [
          'email' => '',
        ],
        '2' => [
          'email' => '',
        ],
      ],
      'phone' => [
        '1' => [
          'phone_type_id' => 1,
          'phone' => '',
          'phone_ext' => '',
        ],
        '2' => [
          'phone_type_id' => 1,
          'phone' => '',
          'phone_ext' => '',
        ],
      ],
    ], $eventID);
    // Cleanup.
    $this->eventDelete($eventID);
  }

  /**
   * Get the values to submit for the form.
   *
   * @return array
   */
  protected function getFormValues() {
    return [
      'address' =>
        [
          1 =>
            [
              'master_id' => '',
              'street_address' => '581O Lincoln Dr SW',
              'supplemental_address_1' => '',
              'supplemental_address_2' => '',
              'supplemental_address_3' => '',
              'city' => 'Santa Fe',
              'postal_code' => '87594',
              'country_id' => '1228',
              'state_province_id' => '1030',
              'county_id' => '',
              'geo_code_1' => '35.5212',
              'geo_code_2' => '-105.982',
            ],
        ],
      'email' =>
        [
          1 =>
            [
              'email' => 'celebration@example.org',
            ],
          2 =>
            [
              'email' => 'bigger_party@example.org',
            ],
        ],
      'phone' =>
        [
          1 =>
            [
              'phone_type_id' => '1',
              'phone' => '303 323-1000',
              'phone_ext' => '',
            ],
          2 =>
            [
              'phone_type_id' => '1',
              'phone' => '44',
              'phone_ext' => '',
            ],
        ],
      'location_option' => '2',
      'loc_event_id' => '3',
      'is_show_location' => '1',
      'is_template' => '0',
    ];
  }

  /**
   * @param int $eventID
   *
   * @return \Civi\Api4\Generic\Result
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function assertCorrectEmails($eventID) {
    $emails = Email::get()
      ->addWhere('email', 'IN', ['bigger_party@example.org', 'celebration@example.org'])
      ->addOrderBy('email', 'DESC')
      ->execute();

    $this->assertCount(2, $emails);
    $firstEmail = $emails->first();
    $locationBlock = Event::get()
      ->addWhere('id', '=', $eventID)
      ->setSelect(['loc_block_id.*', 'loc_block_id'])
      ->execute()->first();
    $this->ids['LocBlock'][0] = $locationBlock['loc_block_id'];
    $this->assertEquals($firstEmail['id'], $locationBlock['loc_block_id.email_id']);
    $secondEmail = $emails->last();
    $this->assertEquals($secondEmail['id'], $locationBlock['loc_block_id.email_2_id']);
    return $emails;
  }

  /**
   * @param array $formValues
   * @param int $eventID
   *
   * @throws \CRM_Core_Exception
   */
  protected function submitForm(array $formValues, int $eventID): void {
    $form = $this->getFormObject('CRM_Event_Form_ManageEvent_Location', array_merge($this->getFormValues(), $formValues));
    $form->set('id', $eventID);
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
  }

}
