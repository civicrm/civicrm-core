<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 * Class CRM_Core_BAO_LocationTest
 * @group headless
 */
class CRM_Core_BAO_LocationTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->quickCleanup(array(
      'civicrm_contact',
      'civicrm_address',
      'civicrm_loc_block',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_im',
    ));
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_openid',
      'civicrm_loc_block',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  public function testCreateWithMissingParams() {
    $contactId = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId,
      'street_address' => 'Saint Helier St',
    );

    CRM_Core_BAO_Location::create($params);

    //Now check DB for Address
    $this->assertDBNull('CRM_Core_DAO_Address', 'Saint Helier St', 'id', 'street_address',
      'Database check, Address created successfully.'
    );

    $this->contactDelete($contactId);
  }

  /**
   * Create() method
   * create various elements of location block
   * without civicrm_loc_block entry
   */
  public function testCreateWithoutLocBlock() {
    $contactId = $this->individualCreate();

    //create various element of location block
    //like address, phone, email, openid, im.
    $params = array(
      'address' => array(
        '1' => array(
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
        ),
      ),
      'email' => array(
        '1' => array(
          'email' => 'john.smith@example.org',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
      ),
      'phone' => array(
        '1' => array(
          'phone_type_id' => 1,
          'phone' => '303443689',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
        '2' => array(
          'phone_type_id' => 2,
          'phone' => '9833910234',
          'location_type_id' => 1,
        ),
      ),
      'openid' => array(
        '1' => array(
          'openid' => 'http://civicrm.org/',
          'location_type_id' => 1,
          'is_primary' => 1,
        ),
      ),
      'im' => array(
        '1' => array(
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ),
      ),
    );

    $params['contact_id'] = $contactId;

    $location = CRM_Core_BAO_Location::create($params);

    $locBlockId = CRM_Utils_Array::value('id', $location);

    //Now check DB for contact
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
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
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    $compareParams = array('email' => 'john.smith@example.org');
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    $compareParams = array('openid' => 'http://civicrm.org/');
    $this->assertDBCompareValues('CRM_Core_DAO_OpenID', $searchParams, $compareParams);

    $compareParams = array(
      'name' => 'jane.doe',
      'provider_id' => 1,
    );
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    );
    $compareParams = array('phone' => '303443689');
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => 2,
    );
    $compareParams = array('phone' => '9833910234');
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //delete the location block
    CRM_Core_BAO_Location::deleteLocBlock($locBlockId);
    $this->contactDelete($contactId);
  }

  /**
   * Create() method
   * create various elements of location block
   * with civicrm_loc_block
   */
  public function testCreateWithLocBlock() {
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $params = array(
      'address' => array(
        '1' => array(
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
        ),
      ),
      'email' => array(
        '1' => array(
          'email' => 'john.smith@example.org',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
      ),
      'phone' => array(
        '1' => array(
          'phone_type_id' => 1,
          'phone' => '303443689',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
        '2' => array(
          'phone_type_id' => 2,
          'phone' => '9833910234',
          'location_type_id' => 1,
        ),
      ),
      'im' => array(
        '1' => array(
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ),
      ),
    );

    $params['entity_id'] = $event['id'];
    $params['entity_table'] = 'civicrm_event';

    //create location block.
    //with various element of location block
    //like address, phone, email, im.
    $location = CRM_Core_BAO_Location::create($params, NULL, TRUE);
    $locBlockId = CRM_Utils_Array::value('id', $location);

    //update event record with location block id
    $eventParams = array(
      'id' => $event['id'],
      'loc_block_id' => $locBlockId,
    );

    CRM_Event_BAO_Event::add($eventParams);

    //Now check DB for location block

    $this->assertDBCompareValue('CRM_Event_DAO_Event',
      $event['id'],
      'loc_block_id',
      'id',
      $locBlockId,
      'Checking database for the record.'
    );
    $locElementIds = array();
    $locParams = array('id' => $locBlockId);
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock',
      $locParams,
      $locElementIds
    );

    //Now check DB for location elements.
    $searchParams = array(
      'id' => CRM_Utils_Array::value('address_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
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
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    $searchParams = array(
      'id' => CRM_Utils_Array::value('email_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array('email' => 'john.smith@example.org');
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    $searchParams = array(
      'id' => CRM_Utils_Array::value('phone_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    );
    $compareParams = array('phone' => '303443689');
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = array(
      'id' => CRM_Utils_Array::value('phone_2_id', $locElementIds),
      'location_type_id' => 1,
      'phone_type_id' => 2,
    );
    $compareParams = array('phone' => '9833910234');
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = array(
      'id' => CRM_Utils_Array::value('im_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
      'name' => 'jane.doe',
      'provider_id' => 1,
    );
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    // Cleanup.
    CRM_Core_BAO_Location::deleteLocBlock($locBlockId);
    $this->eventDelete($event['id']);
    $this->contactDelete($this->_contactId);
  }

  /**
   * DeleteLocBlock() method
   * delete the location block
   * created with various elements.
   */
  public function testDeleteLocBlock() {
    $this->_contactId = $this->individualCreate();
    //create test event record.
    $event = $this->eventCreate();
    $params['location'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'address' => array(
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
      ),
      'email' => array(
        '1' => array('email' => 'john.smith@example.org'),
      ),
      'phone' => array(
        '1' => array(
          'phone_type_id' => 1,
          'phone' => '303443689',
        ),
        '2' => array(
          'phone_type_id' => 2,
          'phone' => '9833910234',
        ),
      ),
      'im' => array(
        '1' => array(
          'name' => 'jane.doe',
          'provider_id' => 1,
        ),
      ),
    );
    $params['entity_id'] = $event['id'];
    $params['entity_table'] = 'civicrm_event';

    //create location block.
    //with various elements
    //like address, phone, email, im.
    $location = CRM_Core_BAO_Location::create($params, NULL, TRUE);
    $locBlockId = CRM_Utils_Array::value('id', $location);
    //update event record with location block id
    $eventParams = array(
      'id' => $event['id'],
      'loc_block_id' => $locBlockId,
    );
    CRM_Event_BAO_Event::add($eventParams);

    //delete the location block
    CRM_Core_BAO_Location::deleteLocBlock($locBlockId);

    //Now check DB for location elements.
    //Now check DB for Address
    $this->assertDBNull('CRM_Core_DAO_Address', 'Saint Helier St', 'id', 'street_address',
      'Database check, Address deleted successfully.'
    );
    //Now check DB for Email
    $this->assertDBNull('CRM_Core_DAO_Email', 'john.smith@example.org', 'id', 'email',
      'Database check, Email deleted successfully.'
    );
    //Now check DB for Phone
    $this->assertDBNull('CRM_Core_DAO_Phone', '303443689', 'id', 'phone',
      'Database check, Phone deleted successfully.'
    );
    //Now check DB for Mobile
    $this->assertDBNull('CRM_Core_DAO_Phone', '9833910234', 'id', 'phone',
      'Database check, Mobile deleted successfully.'
    );
    //Now check DB for IM
    $this->assertDBNull('CRM_Core_DAO_IM', 'jane.doe', 'id', 'name',
      'Database check, IM deleted successfully.'
    );

    //cleanup DB by deleting the record.
    $this->eventDelete($event['id']);
    $this->contactDelete($this->_contactId);

    //Now check DB for Event
    $this->assertDBNull('CRM_Event_DAO_Event', $event['id'], 'id', 'id',
      'Database check, Event deleted successfully.'
    );
  }

  /**
   * GetValues() method
   * get the values of various location elements
   */
  public function testLocBlockgetValues() {
    $contactId = $this->individualCreate();

    //create various element of location block
    //like address, phone, email, openid, im.
    $params = array(
      'address' => array(
        '1' => array(
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
        ),
      ),
      'email' => array(
        '1' => array(
          'email' => 'john.smith@example.org',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
      ),
      'phone' => array(
        '1' => array(
          'phone_type_id' => 1,
          'phone' => '303443689',
          'is_primary' => 1,
          'location_type_id' => 1,
        ),
        '2' => array(
          'phone_type_id' => 2,
          'phone' => '9833910234',
          'location_type_id' => 1,
        ),
      ),
      'openid' => array(
        '1' => array(
          'openid' => 'http://civicrm.org/',
          'location_type_id' => 1,
          'is_primary' => 1,
        ),
      ),
      'im' => array(
        '1' => array(
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ),
      ),
    );

    $params['contact_id'] = $contactId;

    //create location elements.
    CRM_Core_BAO_Location::create($params);

    //get the values from DB
    $values = CRM_Core_BAO_Location::getValues($params);

    //Now check values of address
    $this->assertAttributesEquals(CRM_Utils_Array::value('1', $params['address']),
      CRM_Utils_Array::value('1', $values['address'])
    );

    //Now check values of email
    $this->assertAttributesEquals(CRM_Utils_Array::value('1', $params['email']),
      CRM_Utils_Array::value('1', $values['email'])
    );

    //Now check values of phone
    $this->assertAttributesEquals(CRM_Utils_Array::value('1', $params['phone']),
      CRM_Utils_Array::value('1', $values['phone'])
    );

    //Now check values of mobile
    $this->assertAttributesEquals(CRM_Utils_Array::value('2', $params['phone']),
      CRM_Utils_Array::value('2', $values['phone'])
    );

    //Now check values of openid
    $this->assertAttributesEquals(CRM_Utils_Array::value('1', $params['openid']),
      CRM_Utils_Array::value('1', $values['openid'])
    );

    //Now check values of im
    $this->assertAttributesEquals(CRM_Utils_Array::value('1', $params['im']),
      CRM_Utils_Array::value('1', $values['im'])
    );
    $this->contactDelete($contactId);
  }

}
