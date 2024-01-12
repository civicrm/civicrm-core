<?php

/**
 * Class CRM_Contact_BAO_ContactTest
 * @group headless
 */
class CRM_Contact_BAO_ContactTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_note'], TRUE);
    parent::tearDown();
  }

  /**
   * Test case for add( ).
   *
   * test with empty params.
   */
  public function testAddWithEmptyParams(): void {
    $params = [];
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check Contact object.
    $this->assertNull($contact);
  }

  /**
   * Test case for add( ).
   *
   * Test with names (create and update modes)
   */
  public function testAddWithNames(): void {
    $firstName = 'Shane';
    $lastName = 'Whatson';
    $params = [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    ];

    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check $contact is object of contact DAO.
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $this->assertEquals($firstName, $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($lastName, $contact->last_name, 'Check for last name creation.');

    $contactId = $contact->id;

    // Update and change first name and last name, using add( ).
    $firstName = 'Jane';
    $params = [
      'first_name' => $firstName,
      'contact_type' => 'Individual',
      'contact_id' => $contactId,
    ];

    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check $contact is object of contact DAO.
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $this->assertEquals($firstName, $contact->first_name, 'Check for updated first name.');
  }

  /**
   * Test case for add.
   *
   * Test with all contact params
   * (create and update modes)
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddWithAll(): void {
    // Take the common contact params.
    $params = $this->contactParams();

    unset($params['location']);

    $contact = CRM_Contact_BAO_Contact::add($params);
    $contactId = $contact->id;

    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $createdContact = $this->callAPISuccessGetSingle('Contact', ['id' => $contact->id]);
    $this->assertEquals($params['first_name'], $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($params['last_name'], $contact->last_name, 'Check for last name creation.');
    $this->assertEquals($params['middle_name'], $contact->middle_name, 'Check for middle name creation.');
    $this->assertEquals($params['contact_type'], $contact->contact_type, 'Check for contact type creation.');
    $this->assertEquals('1', $contact->do_not_email, 'Check for do_not_email creation.');
    $this->assertEquals('1', $contact->do_not_phone, 'Check for do_not_phone creation.');
    $this->assertEquals('1', $contact->do_not_mail, 'Check for do_not_mail creation.');
    $this->assertEquals('1', $contact->do_not_trade, 'Check for do_not_trade creation.');
    $this->assertEquals('1', $contact->is_opt_out, 'Check for is_opt_out creation.');
    $this->assertEquals($params['external_identifier'], $contact->external_identifier, 'Check for external_identifier creation.');
    $this->assertEquals($params['last_name'] . ', ' . $params['first_name'] . ' Sr.', $contact->sort_name, 'Check for sort_name creation.');

    $this->assertEquals($params['contact_source'], $contact->source, 'Check for contact_source creation.');
    $this->assertEquals($params['prefix_id'], $contact->prefix_id, 'Check for prefix_id creation.');
    $this->assertEquals($params['suffix_id'], $contact->suffix_id, 'Check for suffix_id creation.');
    $this->assertEquals($params['job_title'], $contact->job_title, 'Check for job_title creation.');
    $this->assertEquals($params['gender_id'], $contact->gender_id, 'Check for gender_id creation.');
    $this->assertEquals('135', $contact->preferred_communication_method);
    $this->assertEquals(1, $createdContact['is_deceased'], 'Check is_deceased');
    $this->assertEquals('1961-06-06', $createdContact['birth_date'], 'Check birth_date');
    $this->assertEquals('1991-07-07', $createdContact['deceased_date'], 'Check deceased_date');

    $updateParams = [
      'contact_type' => 'Individual',
      'first_name' => 'Jane',
      'middle_name' => 'abc',
      'last_name' => 'Doe',
      'prefix_id' => 2,
      'suffix_id' => 3,
      'nick_name' => 'Nick Name Second',
      'job_title' => 'software Developer',
      'gender_id' => 1,
      'is_deceased' => 1,
      'website' => [
        1 => [
          'website_type_id' => 1,
          'url' => 'http://docs.civicrm.org',
        ],
      ],
      'contact_source' => 'test update contact',
      'external_identifier' => 111111111,
      'is_opt_out' => 0,
      'deceased_date' => '1981-03-03',
      'birth_date' => '1951-04-04',
      'privacy' => [
        'do_not_phone' => 0,
        'do_not_email' => 0,
        'do_not_mail' => 0,
        'do_not_trade' => 0,
      ],
      'preferred_communication_method' => [2, 4],
    ];

    $updateParams['contact_id'] = $contactId;
    // Annoyingly `create` alters params
    $preUpdateParams = $updateParams;
    CRM_Contact_BAO_Contact::create($updateParams);
    $return = array_merge(array_keys($updateParams), ['do_not_phone', 'do_not_email', 'do_not_trade', 'do_not_mail']);
    $contact = $this->callAPISuccessGetSingle('Contact', ['id' => $contactId, 'return' => $return]);
    foreach ($preUpdateParams as $key => $value) {
      if ($key === 'website') {
        continue;
      }
      if ($key === 'privacy') {
        foreach ($value as $privacyKey => $privacyValue) {
          $this->assertEquals($privacyValue, $contact[$privacyKey], $key);
        }
      }
      else {
        $this->assertEquals($value, $contact[$key], $key);
      }
    }
    $this->contactDelete($contactId);
  }

  /**
   * Test case for add( ) with All contact types.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddWithAllContactTypes(): void {
    $firstName = 'Bill';
    $lastName = 'Adams';
    $params = [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    ];

    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($firstName, $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($lastName, $contact->last_name, 'Check for last name creation.');

    $contactId = $contact->id;

    //update and change first name and last name, using create()
    $firstName = 'Joan';
    $params = [
      'first_name' => $firstName,
      'contact_type' => 'Individual',
      'contact_id' => $contactId,
    ];

    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($firstName, $contact->first_name, 'Check for updated first name.');
    $contactId = $contact->id;
    $this->contactDelete($contactId);

    $householdName = 'Adams house';
    $params = [
      'household_name' => $householdName,
      'contact_type' => 'Household',
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($householdName, $contact->sort_name, 'Check for created household.');
    $contactId = $contact->id;

    //update and change name of household, using create
    $householdName = 'Joans home';
    $params = [
      'household_name' => $householdName,
      'contact_type' => 'Household',
      'contact_id' => $contactId,
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($householdName, $contact->sort_name, 'Check for updated household.');
    $this->contactDelete($contactId);

    $organizationName = 'My Organization';
    $params = [
      'organization_name' => $organizationName,
      'contact_type' => 'Organization',
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($organizationName, $contact->sort_name, 'Check for created organization.');
    $contactId = $contact->id;

    //update and change name of organization, using create
    $organizationName = 'Your Changed Organization';
    $params = [
      'organization_name' => $organizationName,
      'contact_type' => 'Organization',
      'contact_id' => $contactId,
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($organizationName, $contact->sort_name, 'Check for updated organization.');
    $this->contactDelete($contactId);
  }

  /**
   * Test case for add( ) with duplicated sub contact types.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddWithDuplicatedSubContactType(): void {
    // Sub contact-type as array
    $sub_contact_type = ['Staff', 'Parent', 'Staff'];
    $params = [
      'first_name' => 'Duplicated sub contact-type as array',
      'contact_type' => 'Individual',
      'contact_sub_type' => $sub_contact_type,
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertSame(
      CRM_Core_DAO::serializeField((array_unique($sub_contact_type)), $contact->fields()['contact_sub_type']['serialize']),
      $contact->contact_sub_type,
      'Contact sub-type not deduplicated.'
    );

    // Sub contact-type as string
    $sub_contact_type = 'Staff';
    $params = [
      'first_name' => 'Sub contact-type as string',
      'contact_type' => 'Individual',
      'contact_sub_type' => $sub_contact_type,
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertSame(
      CRM_Core_DAO::serializeField($sub_contact_type, $contact->fields()['contact_sub_type']['serialize']),
      $contact->contact_sub_type,
      'Wrong contact sub-type saved.'
    );
  }

  /**
   * Test case for create.
   *
   * Test with missing params.
   */
  public function testCreateWithEmptyParams(): void {
    $params = [
      'first_name' => 'Bill',
      'last_name' => 'Adams',
    ];
    $contact = CRM_Contact_BAO_Contact::create($params);

    //Now check Contact object
    $this->assertNull($contact);
  }

  /**
   * Test case for create.
   *
   * Test with all params.
   * ( create and update modes ).
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateWithAll(): void {
    //take the common contact params
    $params = $this->contactParams();
    $params['note'] = 'test note';

    //create the contact with given params.
    $contact = CRM_Contact_BAO_Contact::create($params);

    //Now check $contact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $contactId = $contact->id;

    //Now check values of contact object with params.
    $this->assertEquals($params['first_name'], $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($params['last_name'], $contact->last_name, 'Check for last name creation.');
    $this->assertEquals($params['contact_type'], $contact->contact_type, 'Check for contact type creation.');

    //Now check DB for Address
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'street_address' => $params['address'][1]['street_address'],
      'supplemental_address_1' => $params['address'][1]['supplemental_address_1'],
      'supplemental_address_2' => $params['address'][1]['supplemental_address_2'],
      'supplemental_address_3' => $params['address'][1]['supplemental_address_3'],
      'city' => $params['address'][1]['city'],
      'postal_code' => $params['address'][1]['postal_code'],
      'country_id' => $params['address'][1]['country_id'],
      'state_province_id' => $params['address'][1]['state_province_id'],
      'geo_code_1' => $params['address'][1]['geo_code_1'],
      'geo_code_2' => $params['address'][1]['geo_code_2'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = ['email' => $params['email'][1]['email']];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for openid
    $compareParams = ['openid' => $params['openid'][1]['openid']];
    $this->assertDBCompareValues('CRM_Core_DAO_OpenID', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = [
      'name' => $params['im'][1]['name'],
      'provider_id' => $params['im'][1]['provider_id'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => $params['phone'][1]['phone_type_id'],
    ];
    $compareParams = ['phone' => $params['phone'][1]['phone']];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => $params['phone'][2]['phone_type_id'],
    ];
    $compareParams = ['phone' => $params['phone'][2]['phone']];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Note
    $searchParams = [
      'entity_id' => $contactId,
      'entity_table' => 'civicrm_contact',
    ];
    $compareParams = ['note' => $params['note']];
    $this->assertDBCompareValues('CRM_Core_DAO_Note', $searchParams, $compareParams);

    //update the contact.
    $updateParams = [
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
      'note' => 'new test note',
    ];
    $updateParams['address'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'A-wing:3037',
      'supplemental_address_2' => 'Andhery',
      'supplemental_address_3' => 'Anywhere',
      'city' => 'Mumbai',
      'postal_code' => '12345',
      'country_id' => 1228,
      'state_province_id' => 1004,
      'geo_code_1' => '31.694842',
      'geo_code_2' => '-106.29998',
    ];
    $updateParams['email'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'email' => 'john.doe@example.org',
    ];

    $updateParams['phone'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
      'phone' => '02115245336',
    ];
    $updateParams['phone'][2] = [
      'location_type_id' => 1,
      'phone_type_id' => 2,
      'phone' => '9766323895',
    ];

    $updateParams['contact_id'] = $contactId;
    //create the contact with given params.
    $contact = CRM_Contact_BAO_Contact::create($updateParams);

    //Now check $contact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $contactId = $contact->id;

    //Now check values of contact object with updated params.
    $this->assertEquals($updateParams['first_name'], $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($updateParams['last_name'], $contact->last_name, 'Check for last name creation.');
    $this->assertEquals($updateParams['contact_type'], $contact->contact_type, 'Check for contact type creation.');

    //Now check DB for updated Address
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'street_address' => 'Oberoi Garden',
      'supplemental_address_1' => 'A-wing:3037',
      'supplemental_address_2' => 'Andhery',
      'supplemental_address_3' => 'Anywhere',
      'city' => 'Mumbai',
      'postal_code' => '12345',
      'country_id' => 1228,
      'state_province_id' => 1004,
      'geo_code_1' => '31.694842',
      'geo_code_2' => '-106.29998',
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for updated Email
    $compareParams = ['email' => 'john.doe@example.org'];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for updated Phone
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    ];
    $compareParams = ['phone' => '02115245336'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for updated Mobile
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => 2,
    ];
    $compareParams = ['phone' => '9766323895'];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);
    // As we are not updating note.
    // Now check DB for New Note.
    $this->assertDBNotNull('CRM_Core_DAO_Note', $updateParams['note'], 'id', 'note',
      'Database check for New created note '
    );

    // Delete all notes related to contact.
    CRM_Core_BAO_Note::cleanContactNotes($contactId);

    // Cleanup DB by deleting the contact.
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact', 'civicrm_note']);
  }

  /**
   * Test case for resolveDefaults( ).
   *
   * @todo the resolveDefaults function is on it's way out - so is this test...
   *
   * Test all pseudoConstant, stateProvince, country.
   */
  public function testResolveDefaults(): void {
    $params = [];

    $params['address'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'country_id' => 1228,
      'state_province_id' => 1004,
    ];
    // @todo - we are testing this with $reverse = FALSE but it is never called that way!
    CRM_Contact_BAO_Contact::resolveDefaults($params);

    $this->assertEquals(1004, $params['address'][1]['state_province_id']);
  }

  /**
   * Test case for retrieve( ).
   *
   * Test with all values.
   */
  public function testRetrieve(): void {
    //take the common contact params
    $params = $this->contactParams();
    $params['note'] = 'test note';

    $contactID = $this->callAPISuccess('Contact', 'create', $params)['id'];

    $organizationID = $this->callAPISuccess('Contact', 'create', [
      'organization_name' => 'Test Organization ',
      'contact_type' => 'Organization',
    ])['id'];

    //create employee of relationship.
    CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contactID, $organizationID);

    //retrieve the contact values from database.
    $values = [];
    $searchParams = ['contact_id' => $contactID];
    $retrieveContact = CRM_Contact_BAO_Contact::retrieve($searchParams, $values);

    //Now check $retrieveContact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $retrieveContact, 'Check for retrieve object');

    //Now check the ids.
    $this->assertEquals($contactID, $retrieveContact->id, 'Check for contact id');

    //Now check values retrieve from database with params.
    $this->assertEquals($params['first_name'], $values['first_name'], 'Check for first name creation.');
    $this->assertEquals($params['last_name'], $values['last_name'], 'Check for last name creation.');
    $this->assertEquals($params['contact_type'], $values['contact_type'], 'Check for contact type creation.');

    //Now check values of address
    $this->assertAttributesEquals($params['address']['1'],
      $values['address']['1']
    );

    //Now check values of email
    $this->assertAttributesEquals($params['email']['1'],
      $values['email']['1']
    );

    //Now check values of phone
    $this->assertAttributesEquals($params['phone']['1'],
      $values['phone']['1']
    );

    //Now check values of mobile
    $this->assertAttributesEquals($params['phone']['2'],
      $values['phone']['2']
    );

    //Now check values of openid
    $this->assertAttributesEquals($params['openid']['1'],
      $values['openid']['1']
    );

    //Now check values of im
    $this->assertAttributesEquals($params['im']['1'],
      $values['im']['1']
    );

    //Now check values of Note Count.
    $this->assertEquals(1, $values['noteTotalCount'], 'Check for total note count');

    foreach ($values['note'] as $key => $val) {
      $retrieveNote = $val['note'];
      //check the note value
      $this->assertEquals($params['note'], $retrieveNote, 'Check for note');
    }

    //Now check values of Relationship Count.
    $this->assertEquals(1, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($organizationID, $val['contact_id_b'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete($val['contact_id_b']);
    }
  }

  /**
   * Test case for deleteContact( ).
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteContact(): void {
    $contactParams = $this->contactParams();

    $customGroup = $this->customGroupCreate();
    $customGroupTableName = $customGroup['values'][$customGroup['id']]['table_name'];
    $fields = [
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
      'sequential' => 1,
    ];
    $customField = $this->callAPISuccess('CustomField', 'create', $fields)['values'][0];
    $contactParams['custom'] = [
      $customField['id'] => [
        -1 => [
          'value' => 'Test custom value',
          'type' => 'String',
          'custom_field_id' => $customField['id'],
          'custom_group_id' => $customGroup['id'],
          'table_name' => $customGroupTableName,
          'column_name' => $customField['column_name'],
          'file_id' => NULL,
        ],
      ],
    ];

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($contactParams);
    $contactId = $contact->id;

    //delete contact permanently.
    $this->contactDelete($contactId);

    //Now check DB for location elements.
    //Now check DB for Address

    $this->assertDBNull('CRM_Core_DAO_Address', $contactId,
      'id', 'street_address', 'Database check, Address deleted successfully.'
    );

    //Now check DB for Email
    $this->assertDBNull('CRM_Core_DAO_Email', $contactId,
      'id', 'email', 'Database check, Email deleted successfully.'
    );
    //Now check DB for Phone
    $this->assertDBNull('CRM_Core_DAO_Phone', $contactId,
      'id', 'phone', 'Database check, Phone deleted successfully.'
    );
    //Now check DB for Mobile
    $this->assertDBNull('CRM_Core_DAO_Phone', $contactId,
      'id', 'phone', 'Database check, Mobile deleted successfully.'
    );
    //Now check DB for IM
    $this->assertDBNull('CRM_Core_DAO_IM', $contactId,
      'id', 'name', 'Database check, IM deleted successfully.'
    );
    //Now check DB for openId
    $this->assertDBNull('CRM_Core_DAO_OpenID', $contactId,
      'id', 'openid', 'Database check, openId deleted successfully.'
    );

    //Now check DB for contact.
    $this->assertDBNull('CRM_Contact_DAO_Contact', $contactId,
      'id', 'sort_name', 'Database check, contact deleted successfully.'
    );
    $this->assertEmpty(CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM ' . $customGroupTableName));
  }

  /**
   * Test case for createProfileContact.
   */
  public function testCreateProfileContact(): void {
    //Create 3 groups.
    foreach (['group1', 'group2', 'group3'] as $key => $title) {
      $this->ids['Group']["id{$key}"] = $this->callAPISuccess('Group', 'create', [
        'title' => $title,
        'visibility' => 'Public Pages',
      ])['id'];
    }

    $fields = CRM_Contact_BAO_Contact::exportableFields('Individual');

    //current employer field for individual
    $fields['organization_name'] = [
      'name' => 'organization_name',
      'where' => 'civicrm_organization.organization_name',
      'title' => 'Current Employer',
    ];
    //get the common params
    $contactParams = $this->contactParams();
    $unsetParams = ['location', 'privacy'];
    foreach ($unsetParams as $param) {
      unset($contactParams[$param]);
    }

    $profileParams = [
      'organization_name' => 'Yahoo',
      'gender_id' => '2',
      'prefix_id' => '3',
      'suffix_id' => '2',
      'city-Primary' => 'Newark',
      'contact_type' => 'Individual',
      'country-Primary' => '1228',
      'do_not_email' => '1',
      'do_not_mail' => '1',
      'do_not_phone' => '1',
      'do_not_trade' => '1',
      'do_not_sms' => '1',
      'email-Primary' => 'john.smith@example.org',
      'geo_code_1-Primary' => '18.219023',
      'geo_code_2-Primary' => '-105.00973',
      'im-Primary-provider_id' => '1',
      'im-Primary' => 'john.smith',
      'on_hold' => '1',
      'openid' => 'john.smith@example.org',
      'phone-Primary-1' => '303443689',
      'phone-Primary-2' => '9833910234',
      'postal_code-Primary' => '01903',
      'postal_code_suffix-Primary' => '12345',
      'state_province-Primary' => '1029',
      'street_address-Primary' => 'Saint Helier St',
      'supplemental_address_1-Primary' => 'Hallmark Ct',
      'supplemental_address_2-Primary' => 'Jersey Village',
      'supplemental_address_3-Primary' => 'My Town',
      'user_unique_id' => '123456789',
      'is_bulkmail' => '1',
      'tag' => [
        '3' => '1',
        '4' => '1',
        '1' => '1',
      ],
      'group' => [
        $this->ids['Group']["id0"] => '1',
      ],
    ];
    $createParams = array_merge($contactParams, $profileParams);

    //create the contact using create profile contact.
    $contactId = CRM_Contact_BAO_Contact::createProfileContact($createParams, $fields, NULL, NULL, NULL, NULL, TRUE);

    //Make sure contact is added to the group.
    $this->assertTrue(CRM_Contact_BAO_GroupContact::isContactInGroup($contactId, $this->ids['Group']['id0']));

    //get the parameters to compare.
    $params = $this->contactParams();

    //check the values in DB.
    foreach ($params as $key => $val) {
      if (!is_array($val)) {
        if ($key === 'contact_source') {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'source',
            'id', $val, "Check for {$key} creation."
          );
        }
        else {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, $key,
            'id', $val, "Check for {$key} creation."
          );
        }
      }
    }

    //check privacy options.
    foreach ($params['privacy'] as $key => $value) {
      $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, $key,
        'id', $params['privacy'][$key], 'Check for do_not_email creation.'
      );
    }

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'contact_type',
      'id', $profileParams['contact_type'], 'Check for contact type creation.'
    );
    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'user_unique_id',
      'id', $profileParams['user_unique_id'], 'Check for user_unique_id creation.'
    );

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'birth_date',
      'id', $params['birth_date'], 'Check for birth_date creation.'
    );

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'deceased_date',
      'id', $params['deceased_date'], 'Check for deceased_date creation.'
    );

    $dbPrefComm = array_values(array_filter(explode(CRM_Core_DAO::VALUE_SEPARATOR,
      CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'preferred_communication_method', 'id', TRUE)
    )));
    $this->assertEquals($dbPrefComm, $params['preferred_communication_method']);

    //Now check DB for Address
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'street_address' => $profileParams['street_address-Primary'],
      'supplemental_address_1' => $profileParams['supplemental_address_1-Primary'],
      'supplemental_address_2' => $profileParams['supplemental_address_2-Primary'],
      'supplemental_address_3' => $profileParams['supplemental_address_3-Primary'],
      'city' => $profileParams['city-Primary'],
      'postal_code' => $profileParams['postal_code-Primary'],
      'country_id' => $profileParams['country-Primary'],
      'state_province_id' => $profileParams['state_province-Primary'],
      'geo_code_1' => $profileParams['geo_code_1-Primary'],
      'geo_code_2' => $profileParams['geo_code_2-Primary'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = ['email' => $profileParams['email-Primary']];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = [
      'name' => $profileParams['im-Primary'],
      'provider_id' => $profileParams['im-Primary-provider_id'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = ['phone' => $profileParams['phone-Primary-1']];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => $params['phone'][2]['phone_type_id'],
    ];
    $compareParams = ['phone' => $profileParams['phone-Primary-2']];

    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //get the value of relationship
    $values = [];
    $searchParams = ['contact_id' => $contactId];
    $relationship = CRM_Contact_BAO_Relationship::getValues($searchParams, $values);
    //Now check values of Relationship Count.
    $this->assertEquals(0, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($profileParams['organization_name'], $val['name'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete($val['cid']);
    }

    //Now check values of tag ids.
    $tags = CRM_Core_BAO_EntityTag::getTag($contactId);
    foreach ($tags as $key => $val) {
      $tagIds[$key] = 1;
    }

    $this->assertAttributesEquals($profileParams['tag'], $tagIds);

    //update Contact mode
    $updateCParams = [
      'first_name' => 'john',
      'last_name' => 'doe',
      'contact_type' => 'Individual',
      'middle_name' => 'abc',
      'prefix_id' => 2,
      'suffix_id' => 3,
      'nick_name' => 'Nick Name Updated',
      'job_title' => 'software Developer',
      'gender_id' => 1,
      'is_deceased' => 1,
      'website' => [
        1 => [
          'website_type_id' => 1,
          'url' => 'http://civicrmUpdate.org',
        ],
      ],
      'contact_source' => 'test contact',
      'external_identifier' => 111222333,
      'is_opt_out' => 0,
      'legal_identifier' => '123123123123',
      'image_URL' => 'http://imageupdate.com',
      'deceased_date' => '1981-10-10',
      'birth_date' => '1951-11-11',
      'privacy' => [
        'do_not_phone' => 1,
        'do_not_email' => 1,
      ],
      'preferred_communication_method' => [2, 4],
    ];

    $updatePfParams = [
      'organization_name' => 'Google',
      'city-Primary' => 'Mumbai',
      'contact_type' => 'Individual',
      'country-Primary' => '1228',
      'do_not_email' => '1',
      'do_not_mail' => '1',
      'do_not_phone' => '1',
      'do_not_trade' => '1',
      'do_not_sms' => '1',
      'email-Primary' => 'john.doe@example.org',
      'geo_code_1-Primary' => '31.694842',
      'geo_code_2-Primary' => '-106.29998',
      'im-Primary-provider_id' => '1',
      'im-Primary' => 'john.doe',
      'on_hold' => '1',
      'openid' => 'john.doe@example.org',
      'phone-Primary-1' => '02115245336',
      'phone-Primary-2' => '9766323895',
      'postal_code-Primary' => '12345',
      'postal_code_suffix-Primary' => '123',
      'state_province-Primary' => '1004',
      'street_address-Primary' => 'Oberoi Garden',
      'supplemental_address_1-Primary' => 'A-wing:3037',
      'supplemental_address_2-Primary' => 'Andhery',
      'supplemental_address_3-Primary' => 'Anywhere',
      'user_unique_id' => '1122334455',
      'is_bulkmail' => '1',
      'tag' => [
        '2' => '1',
        '5' => '1',
      ],
      //Remove the contact from group1 and add to other 2 groups.
      'group' => [
        $this->ids['Group']["id0"] => '',
        $this->ids['Group']["id1"] => '1',
        $this->ids['Group']["id2"] => '1',
      ],
    ];

    $createParams = array_merge($updateCParams, $updatePfParams);

    //create the contact using create profile contact.
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($createParams, $fields, $contactId,
      NULL, NULL, NULL, TRUE
    );

    //Verify if contact is correctly removed from group1
    $groups = array_keys(CRM_Contact_BAO_GroupContact::getContactGroup($contactID, 'Removed'));
    $expectedGroups = [$this->ids['Group']['id0']];
    $this->checkArrayEquals($expectedGroups, $groups);

    //Verify if contact is correctly added to group1 and group2
    $groups = array_keys(CRM_Contact_BAO_GroupContact::getContactGroup($contactID, 'Added'));
    $expectedGroups = [$this->ids['Group']['id1'], $this->ids['Group']['id2']];
    $this->checkArrayEquals($expectedGroups, $groups);

    //check the contact ids
    $this->assertEquals($contactId, $contactID, 'check for Contact ids');

    //check the values in DB.
    foreach ($updateCParams as $key => $val) {
      if (!is_array($updateCParams[$key])) {
        if ($key === 'contact_source') {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'source',
            'id', $updateCParams[$key], "Check for {$key} creation."
          );
        }
        else {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, $key,
            'id', $updateCParams[$key], "Check for {$key} creation."
          );
        }
      }
    }

    //check privacy options.
    foreach ($updateCParams['privacy'] as $key => $value) {
      $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, $key,
        'id', $updateCParams['privacy'][$key], 'Check for do_not_email creation.'
      );
    }

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'contact_type',
      'id', $updatePfParams['contact_type'], 'Check for contact type creation.'
    );
    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'user_unique_id',
      'id', $updatePfParams['user_unique_id'], 'Check for user_unique_id creation.'
    );

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'birth_date', 'id',
      $updateCParams['birth_date'], 'Check for birth_date creation.'
    );

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'deceased_date', 'id',
      $updateCParams['deceased_date'], 'Check for deceased_date creation.'
    );
    $created = $this->callAPISuccessGetSingle('Contact', ['id' => $contactId]);
    $this->assertEquals($created['preferred_communication_method'], $updateCParams['preferred_communication_method']);

    //Now check DB for Address
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'street_address' => $updatePfParams['street_address-Primary'],
      'supplemental_address_1' => $updatePfParams['supplemental_address_1-Primary'],
      'supplemental_address_2' => $updatePfParams['supplemental_address_2-Primary'],
      'supplemental_address_3' => $updatePfParams['supplemental_address_3-Primary'],
      'city' => $updatePfParams['city-Primary'],
      'postal_code' => $updatePfParams['postal_code-Primary'],
      'country_id' => $updatePfParams['country-Primary'],
      'state_province_id' => $updatePfParams['state_province-Primary'],
      'geo_code_1' => $updatePfParams['geo_code_1-Primary'],
      'geo_code_2' => $updatePfParams['geo_code_2-Primary'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = ['email' => $updatePfParams['email-Primary']];
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = [
      'name' => $updatePfParams['im-Primary'],
      'provider_id' => $updatePfParams['im-Primary-provider_id'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = ['phone' => $updatePfParams['phone-Primary-1']];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => $params['phone'][2]['phone_type_id'],
    ];
    $compareParams = ['phone' => $updatePfParams['phone-Primary-2']];
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //get the value of relationship
    $values = [];
    $searchParams = ['contact_id' => $contactId];
    $relationship = CRM_Contact_BAO_Relationship::getValues($searchParams, $values);
    //Now check values of Relationship Count.
    $this->assertEquals(0, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($updatePfParams['organization_name'], $val['name'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete($val['cid']);
    }

    //Now check values of tag ids.
    $tags = CRM_Core_BAO_EntityTag::getTag($contactId);
    foreach ($tags as $key => $val) {
      $tagIds[$key] = 1;
    }
    $this->assertAttributesEquals($updatePfParams['tag'], $tagIds);
  }

  /**
   * Test case for getContactDetails( ).
   */
  public function testGetContactDetails(): void {
    //get the contact params
    $params = $this->contactParams();

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get the contact details
    $contactDetails = CRM_Contact_BAO_Contact::getContactDetails($contactId);
    $compareParams = [
      $params['first_name'] . ' ' . $params['last_name'],
      $params['email'][1]['email'],
      (bool ) $params['privacy']['do_not_email'],
    ];
    //Now check the contact details
    $this->assertAttributesEquals($compareParams, $contactDetails);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact']);
  }

  /**
   * Test case for importableFields( ) and exportableFields( ).
   */
  public function testFields(): void {
    $allImpFileds = CRM_Contact_BAO_Contact::importableFields('All');
    $allExpFileds = CRM_Contact_BAO_Contact::importableFields('All');
    //Now check all fields
    $this->assertAttributesEquals($allImpFileds, $allExpFileds);

    $individualImpFileds = CRM_Contact_BAO_Contact::importableFields('Individual');
    $individualExpFileds = CRM_Contact_BAO_Contact::importableFields('Individual');
    //Now check Individual fields
    $this->assertAttributesEquals($individualImpFileds, $individualExpFileds);

    $householdImpFileds = CRM_Contact_BAO_Contact::importableFields('Household');
    $householdExpFileds = CRM_Contact_BAO_Contact::importableFields('Household');
    //Now check Household fields
    $this->assertAttributesEquals($householdImpFileds, $householdExpFileds);

    $organizationImpFileds = CRM_Contact_BAO_Contact::importableFields('Organization');
    $organizationExpFileds = CRM_Contact_BAO_Contact::importableFields('Organization');
    //Now check Organization fields
    $this->assertAttributesEquals($organizationImpFileds, $organizationExpFileds);
  }

  /**
   * Test case for getPrimaryEmail.
   */
  public function testGetPrimaryEmail(): void {
    //get the contact params
    $params = $this->contactParams();
    $params['email'][2] = $params['email'][1];
    $params['email'][2]['email'] = 'primarymail@example.org';
    unset($params['email'][1]['is_primary']);

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;
    //get the primary email.
    $email = CRM_Contact_BAO_Contact::getPrimaryEmail($contactId);
    //Now check the primary email
    $this->assertEquals($email, $params['email'][2]['email'], 'Check Primary Email');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact']);
  }

  /**
   * Test case for matchContactOnEmail( ).
   */
  public function testMatchContactOnEmail(): void {
    //get the contact params
    $params = $this->contactParams();
    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get the matching contact.
    $match = CRM_Contact_BAO_Contact::matchContactOnEmail($params['email'][1]['email'],
      'Individual'
    );
    $this->assertEquals($contactId, $match->contact_id, 'Check For Matching Contact');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact']);
  }

  /**
   * Test case for getContactType( ).
   */
  public function testGetContactType(): void {
    //get the contact params
    $params = $this->contactParams();
    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get contact type.
    $contactType = CRM_Contact_BAO_Contact::getContactType($contactId);
    $this->assertEquals($contactType, $params['contact_type'], 'Check For Contact Type');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact']);
  }

  /**
   * Test case for displayName( ).
   */
  public function testDisplayName(): void {
    //get the contact params
    $params = $this->contactParams();

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get display name.
    $dbDisplayName = CRM_Contact_BAO_Contact::displayName($contactId);

    $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');

    //build display name
    $paramsDisplayName = $prefix[$params['prefix_id']] . ' ' . $params['first_name'] . ' ' . $params['last_name'] . ' ' . $suffix[$params['suffix_id']];

    $this->assertEquals($dbDisplayName, $paramsDisplayName, 'Check For Display Name');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(['civicrm_contact']);
  }

  /**
   * Test case for getDisplayAndImage( ).
   */
  public function testGetDisplayAndImage(): void {
    //get the contact params
    $params = $this->contactParams();

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get DisplayAndImage.
    [$displayName, $image] = CRM_Contact_BAO_Contact::getDisplayAndImage($contactId);

    $checkImage = CRM_Contact_BAO_Contact_Utils::getImage($params['contact_type'], FALSE, $contactId);

    $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');

    //build display name
    $paramsDisplayName = $prefix[$params['prefix_id']] . ' ' . $params['first_name'] . ' ' . $params['last_name'] . ' ' . $suffix[$params['suffix_id']];

    $this->assertEquals($displayName, $paramsDisplayName, 'Check For Display Name');
    $this->assertEquals($image, $checkImage, 'Check For Image');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
  }

  /**
   * Build common params.
   */
  private function contactParams() {

    $params = [
      'first_name' => 'john',
      'last_name' => 'smith',
      'contact_type' => 'Individual',
      'middle_name' => 'xyz',
      'prefix_id' => 3,
      'suffix_id' => 2,
      'nick_name' => 'Nick Name',
      'job_title' => 'software engg',
      'gender_id' => 2,
      'is_deceased' => 1,
      'website' => [
        1 => [
          'website_type_id' => 1,
          'url' => 'http://civicrm.org',
        ],
      ],
      'contact_source' => 'test contact',
      'external_identifier' => 123456789,
      'is_opt_out' => 1,
      'legal_identifier' => '123456789',
      'image_URL' => 'http://image.com',
      'deceased_date' => '1991-07-07',
      'birth_date' => '1961-06-06',
      'privacy' => [
        'do_not_phone' => 1,
        'do_not_email' => 1,
        'do_not_mail' => 1,
        'do_not_trade' => 1,
      ],
      'preferred_communication_method' => [1, 3, 5],
    ];

    $params['address'] = [];
    $params['address'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
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

    $params['email'] = [];
    $params['email'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'email' => 'john.smith@example.org',
    ];

    $params['phone'] = [];
    $params['phone'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
      'phone' => '303443689',
    ];
    $params['phone'][2] = [
      'location_type_id' => 1,
      'phone_type_id' => 2,
      'phone' => '9833910234',
    ];

    $params['openid'] = [];
    $params['openid'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'openid' => 'http://civicrm.org/',
    ];

    $params['im'] = [];
    $params['im'][1] = [
      'location_type_id' => 1,
      'is_primary' => 1,
      'name' => 'john.smith',
      'provider_id' => 1,
    ];

    return $params;
  }

  /**
   * Ensure that created_date and modified_date are set.
   */
  public function testTimestampContact(): void {
    $test = $this;
    $this->_testTimestamps([
      'UPDATE' => function ($contactId) use ($test) {
        $params = [
          'first_name' => 'Testing',
          'contact_type' => 'Individual',
          'contact_id' => $contactId,
        ];
        $contact = CRM_Contact_BAO_Contact::add($params);
        $test->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
      },
    ]);
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated when manipulating a phone record.
   */
  public function testTimestampsEmail(): void {
    $test = $this;
    $this->_testTimestamps([
      'INSERT' => function ($contactId) use ($test) {
        $params = [
          'email' => 'ex-1@example.com',
          'is_primary' => 1,
          'location_type_id' => 1,
          'contact_id' => $contactId,
        ];
        $this->callAPISuccess('Email', 'create', $params);
        $test->assertDBQuery('ex-1@example.com',
          'SELECT email FROM civicrm_email WHERE contact_id = %1 ORDER BY id DESC LIMIT 1',
          [1 => [$contactId, 'Integer']]
        );
      },
      'UPDATE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_email SET email = "ex-2@example.com" WHERE contact_id = %1',
          [1 => [$contactId, 'Integer']]
        );
      },
      'DELETE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'DELETE FROM civicrm_email WHERE contact_id = %1',
          [1 => [$contactId, 'Integer']]
        );
      },
    ]);
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated when manipulating an email.
   */
  public function testTimestampsPhone(): void {
    $test = $this;
    $this->_testTimestamps([
      'INSERT' => function ($contactId) use ($test) {
        $params = [
          'phone' => '202-555-1000',
          'is_primary' => 1,
          'location_type_id' => 1,
          'contact_id' => $contactId,
        ];
        CRM_Core_BAO_Phone::writeRecord($params);
        $test->assertDBQuery('202-555-1000',
          'SELECT phone FROM civicrm_phone WHERE contact_id = %1 ORDER BY id DESC LIMIT 1',
          [1 => [$contactId, 'Integer']]
        );
      },
      'UPDATE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_phone SET phone = "202-555-2000" WHERE contact_id = %1',
          [1 => [$contactId, 'Integer']]
        );
      },
      'DELETE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'DELETE FROM civicrm_phone WHERE contact_id = %1',
          [1 => [$contactId, 'Integer']]
        );
      },
    ]);
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated correctly.
   *
   * Looking at it when contact-related custom data is updated.
   */
  public function testTimestampsCustom(): void {
    $customGroup = $this->customGroupCreate();
    $customGroup = $customGroup['values'][$customGroup['id']];
    $fields = [
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'String',
      'html_type' => 'Text',
    ];
    $customField = $this->customFieldCreate($fields);
    $customField = $customField['values'][$customField['id']];
    $test = $this;
    $this->_testTimestamps([
      'INSERT' => function ($contactId) use ($test, $customGroup, $customField) {
        civicrm_api3('contact', 'create', [
          'contact_id' => $contactId,
          'custom_' . $customField['id'] => 'test-1',
        ]);
      },
      'UPDATE' => function ($contactId) use ($test, $customGroup, $customField) {
        CRM_Core_DAO::executeQuery(
          "UPDATE {$customGroup['table_name']} SET {$customField['column_name']} = 'test-2' WHERE entity_id = %1",
          [1 => [$contactId, 'Integer']]
        );
      },
      'DELETE' => function ($contactId) use ($test, $customGroup, $customField) {
        CRM_Core_DAO::executeQuery(
          "DELETE FROM {$customGroup['table_name']} WHERE entity_id = %1",
          [1 => [$contactId, 'Integer']]
        );
      },
    ]);
    $this->quickCleanup(['civicrm_contact'], TRUE);
  }

  /**
   * Helper for testing timestamp manipulation.
   *
   * Create a contact and perform a series of steps with it; after each
   * step, ensure that the contact's modified_date has increased.
   *
   * @param array $callbacks
   *   ($name => $callable).
   */
  public function _testTimestamps(array $callbacks): void {
    CRM_Core_DAO::triggerRebuild();
    $contactId = $this->individualCreate();

    $origTimestamps = CRM_Contact_BAO_Contact::getTimestamps($contactId);
    $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d /', $origTimestamps['created_date']);
    $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d /', $origTimestamps['modified_date']);
    $this->assertTrue($origTimestamps['created_date'] <= $origTimestamps['modified_date']);

    $prevTimestamps = $origTimestamps;
    foreach ($callbacks as $callbackName => $callback) {
      // advance clock by 1 second to ensure timestamps change
      sleep(1);

      $callback($contactId);
      $newTimestamps = CRM_Contact_BAO_Contact::getTimestamps($contactId);
      $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d /', $newTimestamps['created_date'], "Malformed created_date (after $callbackName)");
      $this->assertMatchesRegularExpression('/^\d\d\d\d-\d\d-\d\d /', $newTimestamps['modified_date'], "Malformed modified_date (after $callbackName)");
      $this->assertEquals($origTimestamps['created_date'], $newTimestamps['created_date'], "Changed created_date (after $callbackName)");
      $this->assertTrue($prevTimestamps['modified_date'] < $newTimestamps['modified_date'], "Misordered modified_date (after $callbackName)");

      $prevTimestamps = $newTimestamps;
    }

    $this->contactDelete($contactId);
  }

  /**
   * Test case for UpdateProfileLocationLeak (CRM-20598).
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateProfileLocationLeak(): void {
    // create a simple contact with address and phone that share the same location type
    $defaults = $this->contactParams();
    $params = [
      'first_name' => $defaults['first_name'],
      'last_name' => $defaults['last_name'],
      'contact_type' => 'Individual',
      'address' => [1 => $defaults['address'][1]],
      'phone' => [1 => $defaults['phone'][1]],
    ];
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    // now, update using a profile with phone, email, address... that share the same location type
    $updatePfParams = [
      'first_name' => $params['first_name'],
      'last_name' => $params['first_name'],
      'street_address-Primary' => $params['address'][1]['street_address'],
      'state_province-Primary' => $params['address'][1]['state_province_id'],
      'country-Primary' => $params['address'][1]['country_id'],
      'phone-Primary-1' => $params['phone'][1]['phone'],
      'phone_ext-Primary-1' => '345',
    ];

    //create the contact using create profile contact.
    $fields = CRM_Contact_BAO_Contact::exportableFields('Individual');

    $this->createLoggedInUser();
    // now, emulate the contact update using a profile
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($updatePfParams, $fields, $contactId,
      NULL, NULL, NULL, TRUE
    );

    //check the contact ids
    $this->assertEquals($contactId, $contactID, 'check for Contact ids');
    $phone = $this->callAPISuccess('Phone', 'getsingle', ['contact_id' => $contactID]);
    $this->assertEquals('345', $phone['phone_ext']);
    $this->assertEquals($params['phone'][1]['phone'], $phone['phone']);

    //check the values in DB.
    $searchParams = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    $compareParams = [
      'street_address' => $updatePfParams['street_address-Primary'],
    ];
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
  }

  /**
   * Test that contact details are still displayed if no email is present.
   *
   * @throws \Exception
   */
  public function testContactEmailDetailsWithNoPrimaryEmail(): void {
    $params = $this->contactParams();
    unset($params['email']);
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;
    $result = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactId);
    $this->assertEquals([$contact->display_name, NULL, NULL, NULL], $result);
  }

  /**
   * dev/core#1605 State/province not copied on shared address
   * 1. First, create contacts: A and B
   * 2. Create an address for contact A
   * 3. Use contact A's address for contact B's address
   * ALL the address fields on address A should be copied to address B
   *
   * @throws \CRM_Core_Exception
   */
  public function testSharedAddressCopiesAllAddressFields(): void {
    $contactIdA = $this->individualCreate([], 0);
    $contactIdB = $this->individualCreate([], 1);

    $addressParamsA = [
      'street_address' => '123 Fake St.',
      'location_type_id' => '1',
      'is_primary' => '1',
      'contact_id' => $contactIdA,
      'street_name' => 'Ambachtstraat',
      'street_number' => '23',
      'street_address' => 'Ambachtstraat 23',
      'postal_code' => '6971 BN',
      'country_id' => '1152',
      'city' => 'Brummen',
      'is_billing' => 1,
      'state_province_id' => '3934',
    ];
    $addAddressA = CRM_Core_BAO_Address::writeRecord($addressParamsA);

    $addressParamsB[1] = [
      'contact_id' => $contactIdB,
      'master_id' => $addAddressA->id,
      'use_shared_address' => 1,
    ];

    CRM_Contact_BAO_Contact_Utils::processSharedAddress($addressParamsB);
    $addAddressB = CRM_Core_BAO_Address::writeRecord($addressParamsB[1]);

    foreach ($addAddressA as $key => $value) {
      if (!in_array($key, ['id', 'contact_id', 'master_id', 'is_primary', 'is_billing', 'location_type_id', 'manual_geo_code'])) {
        $this->assertEquals($addAddressA->$key, $addAddressB->$key);
      }
    }
  }

  /**
   * Test that long unicode individual names are truncated properly when
   * creating sort/display name.
   *
   * @dataProvider longUnicodeIndividualNames
   *
   * @param array $input
   * @param array $expected
   *
   * @throws \CRM_Core_Exception
   */
  public function testLongUnicodeIndividualName(array $input, array $expected): void {
    // needs to be passed by reference
    $params = [
      'contact_type' => 'Individual',
      'first_name' => $input['first_name'],
      'last_name' => $input['last_name'],
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);

    $this->assertEquals($expected['sort_name'], $contact->sort_name);
    $this->assertEquals($expected['display_name'], $contact->display_name);

    $this->contactDelete($contact->id);
  }

  /**
   * Data provider for testLongUnicodeIndividualName
   * @return array
   */
  public function longUnicodeIndividualNames():array {
    return [
      'much less than 128' => [
        [
          'first_name' => 'асдадасда',
          'last_name' => 'лнплнплнп',
        ],
        [
          'sort_name' => 'лнплнплнп, асдадасда',
          'display_name' => 'асдадасда лнплнплнп',
        ],
      ],
      'less than 128 but still too big' => [
        [
          'first_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдаш',
          'last_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпш',
        ],
        [
          'sort_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпш, асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдаш',
          'display_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдаш лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпш',
        ],
      ],
      // note we have to account for the comma and space
      'equal 128 sort_name' => [
        [
          'first_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасд',
          'last_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп',
        ],
        [
          'sort_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп, асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасд',
          'display_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасд лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп',
        ],
      ],
      // note we have to account for the space
      'equal 128 display_name' => [
        [
          'first_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдa',
          'last_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп',
        ],
        [
          'sort_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп, асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасд',
          'display_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдa лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнп',
        ],
      ],
      'longer than 128' => [
        [
          'first_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдаш',
          'last_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпш',
        ],
        [
          'sort_name' => 'лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпш, асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдада',
          'display_name' => 'асдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдашасдадасдаш лнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнплнпшлнплнпл',
        ],
      ],
    ];
  }

  /**
   * Test that long unicode org names are truncated properly when creating
   * sort/display name.
   *
   * @dataProvider longUnicodeOrgNames
   *
   * @param string $input
   * @param string $expected
   *
   * @throws \CRM_Core_Exception
   */
  public function testLongUnicodeOrgName(string $input, string $expected): void {
    // needs to be passed by reference
    $params = [
      'contact_type' => 'Organization',
      'organization_name' => $input,
    ];
    $contact = CRM_Contact_BAO_Contact::add($params);

    $this->assertEquals($expected, $contact->sort_name);
    $this->assertEquals($expected, $contact->display_name);
  }

  /**
   * Data provider for testLongUnicodeOrgName
   * @return array
   */
  public function longUnicodeOrgNames():array {
    return [
      'much less than 128' => [
        'асдадасда шшшшшшшшшш',
        'асдадасда шшшшшшшшшш',
      ],
      'less than 128 but still too big' => [
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшасд',
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшасд',
      ],
      'equal 128' => [
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшасд',
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшасд',
      ],
      'longer than 128' => [
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшасдасд',
        'асдадасда шшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшшш...',
      ],
    ];
  }

  /**
   * Show age of contact on Deceased date
   */
  public function testAgeOfDeceasedContact(): void {
    $birthDate = '1961-06-06';
    $deceasedDate = '1991-07-07';
    $age = CRM_Utils_Date::calculateAge($birthDate, $deceasedDate);
    $this->assertEquals('30', $age['years']);
  }

  /**
   * Show age of Contact with current date
   */
  public function testAgeOfNormalContact(): void {
    $birthDate = '1961-06-06';
    $age = CRM_Utils_Date::calculateAge($birthDate);
    $this->assertGreaterThanOrEqual('59', $age['years']);
  }

  /**
   * Test invalidateChecksum hook.
   *
   * @throws \CRM_Core_Exception
   */
  public function testInvalidateChecksumHook(): void {
    $contact_id = $this->individualCreate();
    $checksum = CRM_Contact_BAO_Contact_Utils::generateChecksum($contact_id);
    // without the hook it's valid
    $this->assertTrue(CRM_Contact_BAO_Contact_Utils::validChecksum($contact_id, $checksum));
    $this->hookClass->setHook('civicrm_invalidateChecksum', [$this, 'hookForInvalidateChecksum']);
    // with the hook it should be invalid, because our hook implementation says so
    $this->assertFalse(CRM_Contact_BAO_Contact_Utils::validChecksum($contact_id, $checksum));
  }

  /**
   * Hook for invalidateChecksum.
   *
   * @param int $contactID
   * @param string $inputCheck
   * @param bool $invalid
   */
  public function hookForInvalidateChecksum(int $contactID, string $inputCheck, bool &$invalid): void {
    // invalidate all checksums
    $invalid = TRUE;
  }

}
