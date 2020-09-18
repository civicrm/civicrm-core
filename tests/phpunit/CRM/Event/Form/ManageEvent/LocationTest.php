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
    $form = $this->getFormObject('CRM_Event_Form_ManageEvent_Location', $this->getFormValues());
    $form->set('id', $eventID);
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    $this->assertCorrectEmails($eventID);

    // Now do it again to see if it gets messed with.
    $form = $this->getFormObject('CRM_Event_Form_ManageEvent_Location', array_merge($this->getFormValues(), ['loc_event_id' => $this->ids['LocBlock'][0]]));
    $form->set('id', $eventID);
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    $this->assertCorrectEmails($eventID);
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
      ->setSelect(['loc_block.*', 'loc_block_id'])
      ->execute()->first();
    $this->ids['LocBlock'][0] = $locationBlock['loc_block_id'];
    $this->assertEquals($firstEmail['id'], $locationBlock['loc_block.email_id']);
    $secondEmail = $emails->last();
    $this->assertEquals($secondEmail['id'], $locationBlock['loc_block.email_2_id']);
    return $emails;
  }

}
