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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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

    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_address',
      'civicrm_loc_block',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_im',
    ]);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown() {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_openid',
      'civicrm_loc_block',
    ];
    $this->quickCleanup($tablesToTruncate);
  }

  public function testCreateWithMissingParams() {
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'street_address' => 'Saint Helier St',
    ];

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
    $params = [
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
      'openid' => [
        '1' => [
          'openid' => 'http://civicrm.org/',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
      'im' => [
        '1' => [
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ];

    $params['contact_id'] = $contactId;

    $locBlockId = CRM_Core_BAO_Location::create($params);

    //Now check DB for contact
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
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
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    $compareParams = ['email' => 'john.smith@example.org'];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    $compareParams = ['openid' => 'http://civicrm.org/'];
    $this->assertDBCompareValues('CRM_Core_DAO_OpenID', $searchParams, $compareParams);

    $compareParams = [
      'name' => 'jane.doe',
      'provider_id' => 1,
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    ];
    $compareParams = ['phone' => '303443689'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => 2,
    ];
    $compareParams = ['phone' => '9833910234'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

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
    $params = [
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
      'im' => [
        '1' => [
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ];

    $params['entity_id'] = $event['id'];
    $params['entity_table'] = 'civicrm_event';

    //create location block.
    //with various element of location block
    //like address, phone, email, im.
    $locBlockId = CRM_Core_BAO_Location::create($params, NULL, TRUE)['id'];

    //update event record with location block id
    $eventParams = [
      'id' => $event['id'],
      'loc_block_id' => $locBlockId,
    ];

    CRM_Event_BAO_Event::add($eventParams);

    //Now check DB for location block

    $this->assertDBCompareValue('CRM_Event_DAO_Event',
      $event['id'],
      'loc_block_id',
      'id',
      $locBlockId,
      'Checking database for the record.'
    );
    $locElementIds = [];
    $locParams = ['id' => $locBlockId];
    CRM_Core_DAO::commonRetrieve('CRM_Core_DAO_LocBlock',
      $locParams,
      $locElementIds
    );

    //Now check DB for location elements.
    $searchParams = [
      'id' => CRM_Utils_Array::value('address_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
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
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    $searchParams = [
      'id' => CRM_Utils_Array::value('email_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = ['email' => 'john.smith@example.org'];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    $searchParams = [
      'id' => CRM_Utils_Array::value('phone_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    ];
    $compareParams = ['phone' => '303443689'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = [
      'id' => CRM_Utils_Array::value('phone_2_id', $locElementIds),
      'location_type_id' => 1,
      'phone_type_id' => 2,
    ];
    $compareParams = ['phone' => '9833910234'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    $searchParams = [
      'id' => CRM_Utils_Array::value('im_id', $locElementIds),
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'name' => 'jane.doe',
      'provider_id' => 1,
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    // Cleanup.
    CRM_Core_BAO_Location::deleteLocBlock($locBlockId);
    $this->eventDelete($event['id']);
    $this->contactDelete($this->_contactId);
  }

  /**
   * GetValues() method
   * get the values of various location elements
   */
  public function testLocBlockgetValues() {
    $contactId = $this->individualCreate();

    //create various element of location block
    //like address, phone, email, openid, im.
    $params = [
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
      'openid' => [
        '1' => [
          'openid' => 'http://civicrm.org/',
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
      'im' => [
        '1' => [
          'name' => 'jane.doe',
          'provider_id' => 1,
          'location_type_id' => 1,
          'is_primary' => 1,
        ],
      ],
    ];

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
