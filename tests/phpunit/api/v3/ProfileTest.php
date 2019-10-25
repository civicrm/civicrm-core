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
 *  Test APIv3 civicrm_profile_* functions
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_ProfileTest extends CiviUnitTestCase {

  protected $_profileID = 0;

  protected $_membershipTypeID;

  protected $_contactID;

  /**
   * Set up for test.
   */
  public function setUp() {
    parent::setUp();
    $config = CRM_Core_Config::singleton();
    $countryLimit = $config->countryLimit;
    $countryLimit[1] = 1013;
    $config->countryLimit = $countryLimit;

    $this->createLoggedInUser();
    $this->_membershipTypeID = $this->membershipTypeCreate();
  }

  /**
   * Cleanup after test.
   *
   * @throws \Exception
   */
  public function tearDown() {

    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_phone',
      'civicrm_address',
      'civicrm_membership',
      'civicrm_contribution',
      'civicrm_uf_match',
    ], TRUE);
    $this->callAPISuccess('membership_type', 'delete', ['id' => $this->_membershipTypeID]);
    CRM_Core_DAO::executeQuery(" DELETE FROM civicrm_uf_group WHERE id = $this->_profileID OR name = 'test_contact_activity_profile'");
    parent::tearDown();
  }

  /**
   * Check Without ProfileId.
   */
  public function testProfileGetWithoutProfileId() {
    $this->callAPIFailure('profile', 'get', ['contact_id' => 1],
      'Mandatory key(s) missing from params array: profile_id'
    );
  }

  /**
   * Check with no invalid profile Id.
   */
  public function testProfileGetInvalidProfileId() {
    $this->callAPIFailure('profile', 'get', [
      'contact_id' => 1,
      'profile_id' => 1000,
    ]);
  }

  /**
   * Check with success.
   */
  public function testProfileGet() {
    $profileFieldValues = $this->_createIndividualContact();
    $expected = reset($profileFieldValues);
    $contactId = key($profileFieldValues);
    $params = [
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ];
    $result = $this->callAPISuccess('profile', 'get', $params)['values'];
    foreach ($expected as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $result));
    }
  }

  /**
   * Test getting multiple profiles.
   */
  public function testProfileGetMultiple() {
    $profileFieldValues = $this->_createIndividualContact();
    $expected = reset($profileFieldValues);
    $contactId = key($profileFieldValues);
    $params = [
      'profile_id' => [$this->_profileID, 1, 'Billing'],
      'contact_id' => $contactId,
    ];

    $result = $this->callAPIAndDocument('profile', 'get', $params, __FUNCTION__, __FILE__)['values'];
    foreach ($expected as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $result[$this->_profileID]), " error message: " . "missing/mismatching value for {$profileField}");
    }
    $this->assertEquals('abc1', $result[1]['first_name'], " error message: " . "missing/mismatching value for first name");
    $this->assertFalse(array_key_exists('email-Primary', $result[1]), 'profile 1 does not include email');
    $this->assertEquals($result['Billing'], [
      'billing_first_name' => 'abc1',
      'billing_middle_name' => 'J.',
      'billing_last_name' => 'xyz1',
      'billing_street_address-5' => '5 Saint Helier St',
      'billing_city-5' => 'Gotham City',
      'billing_state_province_id-5' => '1021',
      'billing_country_id-5' => '1228',
      'billing_postal_code-5' => '90210',
      'billing-email-5' => 'abc1.xyz1@yahoo.com',
      'email-5' => 'abc1.xyz1@yahoo.com',
    ]);
  }

  /**
   * Test getting billing profile filled using is_billing.
   */
  public function testProfileGetBillingUseIsBillingLocation() {
    $individual = $this->_createIndividualContact();
    $contactId = key($individual);
    $this->callAPISuccess('address', 'create', [
      'is_billing' => 1,
      'street_address' => 'is billing st',
      'location_type_id' => 2,
      'contact_id' => $contactId,
    ]);

    $params = [
      'profile_id' => [$this->_profileID, 1, 'Billing'],
      'contact_id' => $contactId,
    ];

    $result = $this->callAPISuccess('profile', 'get', $params)['values'];
    $this->assertEquals('abc1', $result[1]['first_name']);
    $this->assertEquals([
      'billing_first_name' => 'abc1',
      'billing_middle_name' => 'J.',
      'billing_last_name' => 'xyz1',
      'billing_street_address-5' => 'is billing st',
      'billing_city-5' => '',
      'billing_state_province_id-5' => '',
      'billing_country_id-5' => '',
      'billing-email-5' => 'abc1.xyz1@yahoo.com',
      'email-5' => 'abc1.xyz1@yahoo.com',
      'billing_postal_code-5' => '',
    ], $result['Billing']);
  }

  /**
   * Test getting multiple profiles, including billing.
   */
  public function testProfileGetMultipleHasBillingLocation() {
    $individual = $this->_createIndividualContact();
    $contactId = key($individual);
    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactId,
      'street_address' => '25 Big Street',
      'city' => 'big city',
      'location_type_id' => 5,
    ]);
    $this->callAPISuccess('email', 'create', [
      'contact_id' => $contactId,
      'email' => 'big@once.com',
      'location_type_id' => 2,
      'is_billing' => 1,
    ]);

    $params = [
      'profile_id' => [$this->_profileID, 1, 'Billing'],
      'contact_id' => $contactId,
    ];

    $result = $this->callAPISuccess('profile', 'get', $params);
    $this->assertEquals('abc1', $result['values'][1]['first_name']);
    $this->assertEquals($result['values']['Billing'], [
      'billing_first_name' => 'abc1',
      'billing_middle_name' => 'J.',
      'billing_last_name' => 'xyz1',
      'billing_street_address-5' => '25 Big Street',
      'billing_city-5' => 'big city',
      'billing_state_province_id-5' => '',
      'billing_country_id-5' => '',
      'billing-email-5' => 'big@once.com',
      'email-5' => 'big@once.com',
      'billing_postal_code-5' => '',
    ]);
  }

  /**
   * Get Billing empty contact - this will return generic defaults.
   */
  public function testProfileGetBillingEmptyContact() {
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => 1228]);
    $params = [
      'profile_id' => ['Billing'],
    ];

    $result = $this->callAPISuccess('profile', 'get', $params)['values'];
    $this->assertEquals([
      'billing_first_name' => '',
      'billing_middle_name' => '',
      'billing_last_name' => '',
      'billing_street_address-5' => '',
      'billing_city-5' => '',
      'billing_state_province_id-5' => '',
      'billing_country_id-5' => '1228',
      'billing_email-5' => '',
      'email-5' => '',
      'billing_postal_code-5' => '',
    ], $result['Billing']);
  }

  /**
   * Check contact activity profile without activity id.
   */
  public function testContactActivityGetWithoutActivityId() {
    list($params) = $this->_createContactWithActivity();

    unset($params['activity_id']);
    $this->callAPIFailure('profile', 'get', $params, 'Mandatory key(s) missing from params array: activity_id');
  }

  /**
   * Check contact activity profile wrong activity id.
   */
  public function testContactActivityGetWrongActivityId() {
    list($params) = $this->_createContactWithActivity();
    $params['activity_id'] = 100001;
    $this->callAPIFailure('profile', 'get', $params, 'Invalid Activity Id (aid).');
  }

  /**
   * Check contact activity profile with wrong activity type.
   *
   * @throws \Exception
   */
  public function testContactActivityGetWrongActivityType() {
    $activity = $this->callAPISuccess('activity', 'create', [
      'source_contact_id' => $this->householdCreate(),
      'activity_type_id' => '2',
      'subject' => 'Test activity',
      'activity_date_time' => '20110316',
      'duration' => '120',
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => '1',
      'priority_id' => '1',
    ])['values'];

    $activityValues = array_pop($activity);

    list($params) = $this->_createContactWithActivity();

    $params['activity_id'] = $activityValues['id'];
    $this->callAPIFailure('profile', 'get', $params, 'This activity cannot be edited or viewed via this profile.');
  }

  /**
   * Check contact activity profile with success.
   */
  public function testContactActivityGetSuccess() {
    list($params, $expected) = $this->_createContactWithActivity();

    $result = $this->callAPISuccess('profile', 'get', $params);

    foreach ($expected as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $result['values']), " error message: " . "missing/mismatching value for {$profileField}"
      );
    }
  }

  /**
   * Check getfields works & gives us our fields
   */
  public function testGetFields() {
    $this->_createIndividualProfile();
    $this->_addCustomFieldToProfile($this->_profileID);
    $result = $this->callAPIAndDocument('profile', 'getfields', [
      'action' => 'submit',
      'profile_id' => $this->_profileID,
    ], __FUNCTION__, __FILE__,
      'demonstrates retrieving profile fields passing in an id');
    $this->assertArrayKeyExists('first_name', $result['values']);
    $this->assertEquals('2', $result['values']['first_name']['type']);
    $this->assertEquals('Email', $result['values']['email-primary']['title']);
    $this->assertEquals('civicrm_state_province', $result['values']['state_province-1']['pseudoconstant']['table']);
    $this->assertEquals('defaultValue', $result['values']['custom_1']['default_value']);
    $this->assertFalse(array_key_exists('participant_status', $result['values']));
  }

  /**
   * Check getfields works & gives us our fields - participant profile
   */
  public function testGetFieldsParticipantProfile() {
    $result = $this->callAPISuccess('profile', 'getfields', [
      'action' => 'submit',
      'profile_id' => 'participant_status',
      'get_options' => 'all',
    ]);
    $this->assertTrue(array_key_exists('participant_status_id', $result['values']));
    $this->assertEquals('Attended', $result['values']['participant_status_id']['options'][2]);
    $this->assertEquals(['participant_status'], $result['values']['participant_status_id']['api.aliases']);
  }

  /**
   * Check getfields works & gives us our fields - membership_batch_entry
   * (getting to the end with no e-notices is pretty good evidence it's working)
   */
  public function testGetFieldsMembershipBatchProfile() {
    $result = $this->callAPISuccess('profile', 'getfields', [
      'action' => 'submit',
      'profile_id' => 'membership_batch_entry',
      'get_options' => 'all',
    ]);
    $this->assertTrue(array_key_exists('total_amount', $result['values']));
    $this->assertTrue(array_key_exists('financial_type_id', $result['values']));
    $this->assertEquals([
      'contribution_type_id',
      'contribution_type',
      'financial_type',
    ], $result['values']['financial_type_id']['api.aliases']);
    $this->assertTrue(!array_key_exists('financial_type', $result['values']));
    $this->assertEquals(12, $result['values']['receive_date']['type']);
  }

  /**
   * Check getfields works & gives us our fields - do them all
   * (getting to the end with no e-notices is pretty good evidence it's working)
   */
  public function testGetFieldsAllProfiles() {
    $result = $this->callAPISuccess('uf_group', 'get', ['return' => 'id'])['values'];
    $profileIDs = array_keys($result);
    foreach ($profileIDs as $profileID) {
      $this->callAPISuccess('profile', 'getfields', [
        'action' => 'submit',
        'profile_id' => $profileID,
        'get_options' => 'all',
      ]);
    }
  }

  /**
   * Check Without ProfileId.
   */
  public function testProfileSubmitWithoutProfileId() {
    $params = [
      'contact_id' => 1,
    ];
    $this->callAPIFailure('profile', 'submit', $params,
      'Mandatory key(s) missing from params array: profile_id'
    );
  }

  /**
   * Check with no invalid profile Id.
   */
  public function testProfileSubmitInvalidProfileId() {
    $params = [
      'contact_id' => 1,
      'profile_id' => 1000,
    ];
    $this->callAPIFailure('profile', 'submit', $params);
  }

  /**
   * Check with missing required field in profile.
   */
  public function testProfileSubmitCheckProfileRequired() {
    $profileFieldValues = $this->_createIndividualContact();
    $contactId = key($profileFieldValues);
    $updateParams = [
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'phone-1-1' => '022 321 826',
      'country-1' => '1013',
      'state_province-1' => '1000',
    ];

    $params = array_merge([
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ],
      $updateParams
    );

    $this->callAPIFailure('profile', 'submit', $params,
      "Mandatory key(s) missing from params array: email-primary"
    );
  }

  /**
   * Check with success.
   */
  public function testProfileSubmit() {
    $profileFieldValues = $this->_createIndividualContact();
    $contactId = key($profileFieldValues);

    $updateParams = [
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email-primary' => 'abc2.xyz2@gmail.com',
      'phone-1-1' => '022 321 826',
      'country-1' => '1013',
      'state_province-1' => '1000',
    ];

    $params = array_merge([
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ], $updateParams);

    $this->callAPIAndDocument('profile', 'submit', $params, __FUNCTION__, __FILE__);

    $getParams = [
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ];
    $profileDetails = $this->callAPISuccess('profile', 'get', $getParams);

    foreach ($updateParams as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $profileDetails['values']), "missing/mismatching value for {$profileField}"
      );
    }
    unset($params['email-primary']);
    $params['email-Primary'] = 'my@mail.com';
    $this->callAPISuccess('profile', 'submit', $params);
    $profileDetails = $this->callAPISuccess('profile', 'get', $getParams);
    $this->assertEquals('my@mail.com', $profileDetails['values']['email-Primary']);
  }

  /**
   * Ensure caches are being cleared so we don't get into a debugging trap because of cached metadata
   * First we delete & create to increment the version & then check for caching problems.
   */
  public function testProfileSubmitCheckCaching() {
    $this->callAPISuccess('membership_type', 'delete', ['id' => $this->_membershipTypeID]);
    $this->_membershipTypeID = $this->membershipTypeCreate();

    $membershipTypes = $this->callAPISuccess('membership_type', 'get', []);
    $profileFields = $this->callAPISuccess('profile', 'getfields', [
      'get_options' => 'all',
      'action' => 'submit',
      'profile_id' => 'membership_batch_entry',
    ]);
    $getoptions = $this->callAPISuccess('membership', 'getoptions', [
      'field' => 'membership_type',
      'context' => 'validate',
    ]);
    $this->assertEquals(array_keys($membershipTypes['values']), array_keys($getoptions['values']));
    $this->assertEquals(array_keys($membershipTypes['values']), array_keys($profileFields['values']['membership_type_id']['options']));

  }

  /**
   * Test that the fields are returned in the right order despite the faffing around that goes on.
   */
  public function testMembershipGetFieldsOrder() {
    $result = $this->callAPISuccess('profile', 'getfields', [
      'action' => 'submit',
      'profile_id' => 'membership_batch_entry',
    ])['values'];
    $weight = 1;
    foreach ($result as $fieldName => $field) {
      if ($fieldName == 'profile_id') {
        continue;
      }
      $this->assertEquals($field['weight'], $weight);
      $weight++;
    }
  }

  /**
   * Check we can submit membership batch profiles (create mode)
   */
  public function testProfileSubmitMembershipBatch() {
    $this->_contactID = $this->individualCreate();
    $this->callAPISuccess('profile', 'submit', [
      'profile_id' => 'membership_batch_entry',
      'financial_type_id' => 1,
      'membership_type' => $this->_membershipTypeID,
      'join_date' => 'now',
      'total_amount' => 10,
      'contribution_status_id' => 1,
      'receive_date' => 'now',
      'contact_id' => $this->_contactID,
    ]);
  }

  /**
   * Set is deprecated but we need to ensure it still works.
   */
  public function testLegacySet() {
    $profileFieldValues = $this->_createIndividualContact();
    $contactId = key($profileFieldValues);

    $updateParams = [
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email-Primary' => 'abc2.xyz2@gmail.com',
      'phone-1-1' => '022 321 826',
      'country-1' => '1013',
      'state_province-1' => '1000',
    ];

    $params = array_merge([
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ], $updateParams);

    $result = $this->callAPISuccess('profile', 'set', $params);
    $this->assertArrayKeyExists('values', $result);
    $getParams = [
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
    ];
    $profileDetails = $this->callAPISuccess('profile', 'get', $getParams);

    foreach ($updateParams as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $profileDetails['values']), "In line " . __LINE__ . " error message: " . "missing/mismatching value for {$profileField}"
      );
    }
  }

  /**
   * Check contact activity profile without activity id.
   */
  public function testContactActivitySubmitWithoutActivityId() {
    list($params, $expected) = $this->_createContactWithActivity();

    $params = array_merge($params, $expected);
    unset($params['activity_id']);
    $this->callAPIFailure('profile', 'submit', $params, 'Mandatory key(s) missing from params array: activity_id');
  }

  /**
   * Check contact activity profile wrong activity id.
   */
  public function testContactActivitySubmitWrongActivityId() {
    list($params, $expected) = $this->_createContactWithActivity();
    $params = array_merge($params, $expected);
    $params['activity_id'] = 100001;
    $this->callAPIFailure('profile', 'submit', $params, 'Invalid Activity Id (aid).');
  }

  /**
   * Check contact activity profile with wrong activity type.
   *
   * @throws \Exception
   */
  public function testContactActivitySubmitWrongActivityType() {

    $sourceContactId = $this->householdCreate();

    $activityParams = [
      'source_contact_id' => $sourceContactId,
      'activity_type_id' => '2',
      'subject' => 'Test activity',
      'activity_date_time' => '20110316',
      'duration' => '120',
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => '1',
      'priority_id' => '1',
    ];

    $activity = $this->callAPISuccess('activity', 'create', $activityParams);

    $activityValues = array_pop($activity['values']);

    list($params, $expected) = $this->_createContactWithActivity();

    $params = array_merge($params, $expected);
    $params['activity_id'] = $activityValues['id'];
    $this->callAPIFailure('profile', 'submit', $params,
      'This activity cannot be edited or viewed via this profile.');
  }

  /**
   * Check contact activity profile with success.
   */
  public function testContactActivitySubmitSuccess() {
    list($params) = $this->_createContactWithActivity();

    $updateParams = [
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email-Primary' => 'abc2.xyz2@yahoo.com',
      'activity_subject' => 'Test Meeting',
      'activity_details' => 'a test activity details',
      'activity_duration' => '100',
      'activity_date_time' => '2010-03-08 00:00:00',
      'activity_status_id' => '2',
    ];
    $profileParams = array_merge($params, $updateParams);
    $this->callAPISuccess('profile', 'submit', $profileParams);
    $result = $this->callAPISuccess('profile', 'get', $params)['values'];

    foreach ($updateParams as $profileField => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($profileField, $result), " error message: " . "missing/mismatching value for {$profileField}"
      );
    }
  }

  /**
   * Check profile apply Without ProfileId.
   */
  public function testProfileApplyWithoutProfileId() {
    $params = [
      'contact_id' => 1,
    ];
    $this->callAPIFailure('profile', 'apply', $params,
      'Mandatory key(s) missing from params array: profile_id');
  }

  /**
   * Check profile apply with no invalid profile Id.
   */
  public function testProfileApplyInvalidProfileId() {
    $params = [
      'contact_id' => 1,
      'profile_id' => 1000,
    ];
    $this->callAPIFailure('profile', 'apply', $params);
  }

  /**
   * Check with success.
   */
  public function testProfileApply() {
    $profileFieldValues = $this->_createIndividualContact();
    current($profileFieldValues);
    $contactId = key($profileFieldValues);

    $params = [
      'profile_id' => $this->_profileID,
      'contact_id' => $contactId,
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email-Primary' => 'abc2.xyz2@gmail.com',
      'phone-1-1' => '022 321 826',
      'country-1' => '1013',
      'state_province-1' => '1000',
    ];

    $result = $this->callAPIAndDocument('profile', 'apply', $params, __FUNCTION__, __FILE__);

    // Expected field values
    $expected['contact'] = [
      'contact_id' => $contactId,
      'contact_type' => 'Individual',
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
    ];
    $expected['email'] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'email' => 'abc2.xyz2@gmail.com',
    ];

    $expected['phone'] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
      'phone' => '022 321 826',
    ];
    $expected['address'] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'country_id' => 1013,
      'state_province_id' => 1000,
    ];

    foreach ($expected['contact'] as $field => $value) {
      $this->assertEquals($value, CRM_Utils_Array::value($field, $result['values']), "missing/mismatching value for {$field}"
      );
    }

    foreach (['email', 'phone', 'address'] as $fieldType) {
      $typeValues = array_pop($result['values'][$fieldType]);
      foreach ($expected[$fieldType] as $field => $value) {
        $this->assertEquals($value, CRM_Utils_Array::value($field, $typeValues), "missing/mismatching value for {$field} ({$fieldType})"
        );
      }
    }
  }

  /**
   * Check success with tags.
   */
  public function testSubmitWithTags() {
    $profileFieldValues = $this->_createIndividualContact();
    $params = reset($profileFieldValues);
    $contactId = key($profileFieldValues);
    $params['profile_id'] = $this->_profileID;
    $params['contact_id'] = $contactId;

    $this->callAPISuccess('ufField', 'create', [
      'uf_group_id' => $this->_profileID,
      'field_name' => 'tag',
      'visibility' => 'Public Pages and Listings',
      'field_type' => 'Contact',
      'label' => 'Tags',
    ]);

    $tag_1 = $this->callAPISuccess('tag', 'create', ['name' => 'abc'])['id'];
    $tag_2 = $this->callAPISuccess('tag', 'create', ['name' => 'def'])['id'];

    $params['tag'] = "$tag_1,$tag_2";
    $this->callAPISuccess('profile', 'submit', $params);

    $tags = $this->callAPISuccess('entityTag', 'get', ['entity_id' => $contactId]);
    $this->assertEquals(2, $tags['count']);

    $params['tag'] = [$tag_1];
    $this->callAPISuccess('profile', 'submit', $params);

    $tags = $this->callAPISuccess('entityTag', 'get', ['entity_id' => $contactId]);
    $this->assertEquals(1, $tags['count']);

    $params['tag'] = '';
    $this->callAPISuccess('profile', 'submit', $params);

    $tags = $this->callAPISuccess('entityTag', 'get', ['entity_id' => $contactId]);
    $this->assertEquals(0, $tags['count']);

  }

  /**
   * Check success with a note.
   *
   * @throws \Exception
   */
  public function testSubmitWithNote() {
    $profileFieldValues = $this->_createIndividualContact();
    $params = reset($profileFieldValues);
    $contactId = key($profileFieldValues);
    $params['profile_id'] = $this->_profileID;
    $params['contact_id'] = $contactId;

    $this->callAPISuccess('ufField', 'create', [
      'uf_group_id' => $this->_profileID,
      'field_name' => 'note',
      'visibility' => 'Public Pages and Listings',
      'field_type' => 'Contact',
      'label' => 'Note',
    ]);

    $params['note'] = "Hello 123";
    $this->callAPISuccess('profile', 'submit', $params);

    $note = $this->callAPISuccessGetSingle('note', ['entity_id' => $contactId]);
    $this->assertEquals("Hello 123", $note['note']);
  }

  /**
   * Check handling a custom greeting.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitGreetingFields() {
    $profileFieldValues = $this->_createIndividualContact();
    $params = reset($profileFieldValues);
    $contactId = key($profileFieldValues);
    $params['profile_id'] = $this->_profileID;
    $params['contact_id'] = $contactId;

    $this->callAPISuccess('ufField', 'create', [
      'uf_group_id' => $this->_profileID,
      'field_name' => 'email_greeting',
      'visibility' => 'Public Pages and Listings',
      'field_type' => 'Contact',
      'label' => 'Email Greeting',
    ]);

    $emailGreetings = array_column(civicrm_api3('OptionValue', 'get', ['option_group_id' => "email_greeting"])['values'], NULL, 'name');

    $params['email_greeting'] = $emailGreetings['Customized']['value'];
    // Custom greeting should be required
    $this->callAPIFailure('profile', 'submit', $params);

    $params['email_greeting_custom'] = 'Hello fool!';
    $this->callAPISuccess('profile', 'submit', $params);

    // Api3 will not return custom greeting field so resorting to this
    $greeting = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'email_greeting_custom');

    $this->assertEquals("Hello fool!", $greeting);
  }

  /**
   * Helper function to create an Individual with address/email/phone info. Import UF Group and UF Fields
   *
   * @param array $params
   *
   * @return mixed
   */
  public function _createIndividualContact($params = []) {
    $contactParams = array_merge([
      'first_name' => 'abc1',
      'last_name' => 'xyz1',
      'email' => 'abc1.xyz1@yahoo.com',
      'api.address.create' => [
        'location_type_id' => 1,
        'is_primary' => 1,
        'street_address' => '5 Saint Helier St',
        'county' => 'Marin',
        'country' => 'UNITED STATES',
        'state_province' => 'Michigan',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
        'supplemental_address_3' => 'My Town',
        'postal_code' => '90210',
        'city' => 'Gotham City',
        'is_billing' => 0,
      ],
      'api.phone.create' => [
        'location_type_id' => '1',
        'phone' => '021 512 755',
        'phone_type_id' => '1',
        'is_primary' => '1',
      ],
    ], $params);

    $this->_contactID = $this->individualCreate($contactParams);
    $this->_createIndividualProfile();
    // expected result of above created profile with contact Id $contactId
    $profileData[$this->_contactID] = [
      'first_name' => 'abc1',
      'last_name' => 'xyz1',
      'email-primary' => 'abc1.xyz1@yahoo.com',
      'phone-1-1' => '021 512 755',
      'country-1' => '1228',
      'state_province-1' => '1021',
    ];

    return $profileData;
  }

  /**
   * @return array
   */
  public function _createContactWithActivity() {
    $ufGroupID = $this->callAPISuccess('UFGroup', 'create', [
      'group_type' => 'Individual,Contact,Activity',
      'title' => 'Test Contact-Activity Profile',
      'name' => 'test_contact_activity_profile',
    ])['id'];
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'first_name',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'First Name',
      'field_type' => 'Individual',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'last_name',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Last Name',
      'field_type' => 'Individual',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'email',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Email',
      'field_type' => 'Contact',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'activity_subject',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Activity Subject',
      'is_searchable' => TRUE,
      'field_type' => 'Activity',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'activity_details',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Activity Details',
      'is_searchable' => TRUE,
      'field_type' => 'Activity',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'activity_duration',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Activity Duration',
      'is_searchable' => TRUE,
      'field_type' => 'Activity',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'activity_date_time',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Activity Date',
      'is_searchable' => TRUE,
      'field_type' => 'Activity',
    ]);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $ufGroupID,
      'field_name' => 'activity_status_id',
      'is_required' => TRUE,
      'visibility' => 'Public Pages and Listings',
      'label' => 'Activity Status',
      'is_searchable' => TRUE,
      'field_type' => 'Activity',
    ]);

    // hack: xml data set did not accept  (CRM_Core_DAO::VALUE_SEPARATOR) - should be possible
    // to unhack now we use the api.
    CRM_Core_DAO::setFieldValue('CRM_Core_DAO_UFGroup', $ufGroupID, 'group_type', 'Individual,Contact,Activity' . CRM_Core_DAO::VALUE_SEPARATOR . 'ActivityType:1');

    $sourceContactId = $this->individualCreate();
    $contactParams = [
      'first_name' => 'abc1',
      'last_name' => 'xyz1',
      'contact_type' => 'Individual',
      'email' => 'abc1.xyz1@yahoo.com',
      'api.address.create' => [
        'location_type_id' => 1,
        'is_primary' => 1,
        'name' => 'Saint Helier St',
        'county' => 'Marin',
        'country' => 'UNITED STATES',
        'state_province' => 'Michigan',
        'supplemental_address_1' => 'Hallmark Ct',
        'supplemental_address_2' => 'Jersey Village',
        'supplemental_address_3' => 'My Town',
      ],
    ];

    $contact = $this->callAPISuccess('contact', 'create', $contactParams);

    $keys = array_keys($contact['values']);
    $contactId = array_pop($keys);

    $this->assertEquals(0, $contact['values'][$contactId]['api.address.create']['is_error'], " error message: " . CRM_Utils_Array::value('error_message', $contact['values'][$contactId]['api.address.create'])
    );

    $activityParams = [
      'source_contact_id' => $sourceContactId,
      'assignee_contact_id' => $contactId,
      'activity_type_id' => '1',
      'subject' => 'Make-it-Happen Meeting',
      'activity_date_time' => '2011-03-16 00:00:00',
      'duration' => '120',
      'location' => 'Pennsylvania',
      'details' => 'a test activity',
      'status_id' => '1',
      'priority_id' => '1',
    ];
    $activity = $this->callAPISuccess('activity', 'create', $activityParams);

    $activityValues = array_pop($activity['values']);

    // valid parameters for above profile
    $profileParams = [
      'profile_id' => $ufGroupID,
      'contact_id' => $contactId,
      'activity_id' => $activityValues['id'],
    ];

    // expected result of above created profile
    $expected = [
      'first_name' => 'abc1',
      'last_name' => 'xyz1',
      'email-Primary' => 'abc1.xyz1@yahoo.com',
      'activity_subject' => 'Make-it-Happen Meeting',
      'activity_details' => 'a test activity',
      'activity_duration' => '120',
      'activity_date_time' => '2011-03-16 00:00:00',
      'activity_status_id' => '1',
    ];

    return [$profileParams, $expected];
  }

  /**
   * Create a profile.
   */
  public function _createIndividualProfile() {
    $ufGroupParams = [
      'group_type' => 'Individual,Contact',
      // really we should remove this & test the ufField create sets it
      'name' => 'test_individual_contact_profile',
      'title' => 'Flat Coffee',
      'api.uf_field.create' => [
        [
          'field_name' => 'first_name',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Individual',
          'label' => 'First Name',
        ],
        [
          'field_name' => 'last_name',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Individual',
          'label' => 'Last Name',
        ],
        [
          'field_name' => 'email',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Contact',
          'label' => 'Email',
        ],
        [
          'field_name' => 'phone',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Contact',
          'location_type_id' => 1,
          'phone_type_id' => 1,
          'label' => 'Phone',
        ],
        [
          'field_name' => 'country',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Contact',
          'location_type_id' => 1,
          'label' => 'Country',
        ],
        [
          'field_name' => 'state_province',
          'is_required' => 1,
          'visibility' => 'Public Pages and Listings',
          'field_type' => 'Contact',
          'location_type_id' => 1,
          'label' => 'State Province',
        ],
        [
          'field_name' => 'postal_code',
          'is_required' => 0,
          'field_type' => 'Contact',
          'location_type_id' => 1,
          'label' => 'State Province',
        ],
      ],
    ];
    $profile = $this->callAPISuccess('uf_group', 'create', $ufGroupParams);
    $this->_profileID = $profile['id'];
  }

  /**
   * @param int $profileID
   */
  public function _addCustomFieldToProfile($profileID) {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, '');
    $this->uFFieldCreate([
      'uf_group_id' => $profileID,
      'field_name' => 'custom_' . $ids['custom_field_id'],
      'contact_type' => 'Contact',
    ]);
  }

}
