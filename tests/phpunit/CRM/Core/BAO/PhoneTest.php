<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_BAO_PhoneTest
 * @group headless
 */
class CRM_Core_BAO_PhoneTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Add() method (create and update modes)
   */
  public function testAdd() {
    $contactId = Contact::createIndividual();

    $params = array();
    $params = array(
      'phone' => '(415) 222-1011 x 221',
      'is_primary' => 1,
      'location_type_id' => 1,
      'phone_type' => 'Mobile',
      'contact_id' => $contactId,
    );

    CRM_Core_BAO_Phone::add($params);

    $phoneId = $this->assertDBNotNull('CRM_Core_DAO_Phone', $contactId, 'id', 'contact_id',
      'Database check for created phone record.'
    );

    $this->assertDBCompareValue('CRM_Core_DAO_Phone', $phoneId, 'phone', 'id', '(415) 222-1011 x 221',
      "Check if phone field has expected value in new record ( civicrm_phone.id={$phoneId} )."
    );

    // Now call add() to modify the existing phone number

    $params = array();
    $params = array(
      'id' => $phoneId,
      'contact_id' => $contactId,
      'phone' => '(415) 222-5432',
    );

    CRM_Core_BAO_Phone::add($params);

    $this->assertDBCompareValue('CRM_Core_DAO_Phone', $phoneId, 'phone', 'id', '(415) 222-5432',
      "Check if phone field has expected value in updated record ( civicrm_phone.id={$phoneId} )."
    );

    Contact::delete($contactId);
  }

  /**
   * AllPhones() method - get all Phones for our contact, with primary Phone first
   */
  public function testAllPhones() {
    $contactParams = array(
      'first_name' => 'Alan',
      'last_name' => 'Smith',
      'phone-1' => '(415) 222-1011 x 221',
      'phone-2' => '(415) 222-5432',
    );

    $contactId = Contact::createIndividual($contactParams);

    $Phones = CRM_Core_BAO_Phone::allPhones($contactId);

    $this->assertEquals(count($Phones), 2, 'Checking number of returned Phones.');

    $firstPhoneValue = array_slice($Phones, 0, 1);

    // Since we're not passing in a location type to createIndividual above, CRM_Contact_BAO_Contact::createProfileContact uses default location
    // type for first phone and sets that to primary.
    $this->assertEquals('(415) 222-1011 x 221', $firstPhoneValue[0]['phone'], "Confirm primary Phone value ( {$firstPhoneValue[0]['phone']} ).");
    $this->assertEquals(1, $firstPhoneValue[0]['is_primary'], 'Confirm first Phone is primary.');

    Contact::delete($contactId);
  }

  /**
   * AllEntityPhones() method - get all Phones for a location block, with primary Phone first
   * @todo FIXME: Fixing this test requires add helper functions in CiviTest to create location block and phone and link them to an event. Punting to 3.1 cycle. DGG
   */
  public function SKIPPED_testAllEntityPhones() {
  }

}
