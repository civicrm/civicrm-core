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
 */

/**
 * Class CRM_Core_BAO_LocationTest
 * @group headless
 */
class CRM_Core_BAO_LocationTest extends CiviUnitTestCase {

  public function setUp(): void {
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
  public function tearDown(): void {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_openid',
      'civicrm_loc_block',
    ];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  public function testCreateWithMissingParams(): void {
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
  public function testCreateWithoutLocBlock(): void {
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
          'openid' => 'https://civicrm.org/',
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

    CRM_Core_BAO_Location::create($params);

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

    $compareParams = ['openid' => 'https://civicrm.org/'];
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
   * GetValues() method
   * get the values of various location elements
   *
   * @throws \CRM_Core_Exception
   */
  public function testLocBlockgetValues(): void {
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
          'openid' => 'https://civicrm.org/',
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

    $this->assertAttributesEquals($params['address'][1], $values['address'][1]);
    $this->assertAttributesEquals($params['email'][1], $values['email'][1]);
    $this->assertAttributesEquals($params['phone'][1], $values['phone'][1]);
    $this->assertAttributesEquals($params['phone'][2], $values['phone'][2]);
    $this->assertAttributesEquals($params['openid'][1], $values['openid'][1]);
    $this->assertAttributesEquals($params['im'][1], $values['im'][1]);
  }

}
