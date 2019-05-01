<?php

/**
 * Class CRM_Contact_BAO_ContactTest
 * @group headless
 */
class CRM_Contact_BAO_ContactTest extends CiviUnitTestCase {

  /**
   * Set up function.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Test case for add( ).
   *
   * test with empty params.
   */
  public function testAddWithEmptyParams() {
    $params = array();
    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check Contact object.
    $this->assertNull($contact);
  }

  /**
   * Test case for add( ).
   *
   * Test with names (create and update modes)
   */
  public function testAddWithNames() {
    $firstName = 'Shane';
    $lastName = 'Whatson';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check $contact is object of contact DAO.
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $this->assertEquals($firstName, $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($lastName, $contact->last_name, 'Check for last name creation.');

    $contactId = $contact->id;

    // Update and change first name and last name, using add( ).
    $firstName = 'Jane';
    $params = array(
      'first_name' => $firstName,
      'contact_type' => 'Individual',
      'contact_id' => $contactId,
    );

    $contact = CRM_Contact_BAO_Contact::add($params);

    // Now check $contact is object of contact DAO.
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $this->assertEquals($firstName, $contact->first_name, 'Check for updated first name.');

    $contactId = $contact->id;
    $this->contactDelete($contactId);
  }

  /**
   * Test case for add.
   *
   * Test with all contact params
   * (create and update modes)
   */
  public function testAddWithAll() {
    // Take the common contact params.
    $params = $this->contactParams();

    unset($params['location']);
    $prefComm = $params['preferred_communication_method'];
    $contact = CRM_Contact_BAO_Contact::add($params);
    $contactId = $contact->id;

    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');

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
    $this->assertEquals($params['last_name'] . ', ' . $params['first_name'], $contact->sort_name, 'Check for sort_name creation.');
    $this->assertEquals($params['preferred_mail_format'], $contact->preferred_mail_format,
      'Check for preferred_mail_format creation.'
    );
    $this->assertEquals($params['contact_source'], $contact->source, 'Check for contact_source creation.');
    $this->assertEquals($params['prefix_id'], $contact->prefix_id, 'Check for prefix_id creation.');
    $this->assertEquals($params['suffix_id'], $contact->suffix_id, 'Check for suffix_id creation.');
    $this->assertEquals($params['job_title'], $contact->job_title, 'Check for job_title creation.');
    $this->assertEquals($params['gender_id'], $contact->gender_id, 'Check for gender_id creation.');
    $this->assertEquals('1', $contact->is_deceased, 'Check for is_deceased creation.');
    $this->assertEquals(CRM_Utils_Date::processDate($params['birth_date']),
      $contact->birth_date, 'Check for birth_date creation.'
    );
    $this->assertEquals(CRM_Utils_Date::processDate($params['deceased_date']),
      $contact->deceased_date, 'Check for deceased_date creation.'
    );
    $dbPrefComm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      $contact->preferred_communication_method
    );
    $checkPrefComm = array();
    foreach ($dbPrefComm as $key => $value) {
      if ($value) {
        $checkPrefComm[$value] = 1;
      }
    }
    $this->assertAttributesEquals($checkPrefComm, $prefComm);

    $updateParams = array(
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
      'website' => array(
        1 => array(
          'website_type_id' => 1,
          'url' => 'http://docs.civicrm.org',
        ),
      ),
      'contact_source' => 'test update contact',
      'external_identifier' => 111111111,
      'preferred_mail_format' => 'Both',
      'is_opt_out' => 0,
      'deceased_date' => '1981-03-03',
      'birth_date' => '1951-04-04',
      'privacy' => array(
        'do_not_phone' => 0,
        'do_not_email' => 0,
        'do_not_mail' => 0,
        'do_not_trade' => 0,
      ),
      'preferred_communication_method' => array(
        '1' => 0,
        '2' => 1,
        '3' => 0,
        '4' => 1,
        '5' => 0,
      ),
    );

    $prefComm = $updateParams['preferred_communication_method'];
    $updateParams['contact_id'] = $contactId;
    $contact = CRM_Contact_BAO_Contact::create($updateParams);
    $contactId = $contact->id;

    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');

    $this->assertEquals($updateParams['first_name'], $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($updateParams['last_name'], $contact->last_name, 'Check for last name creation.');
    $this->assertEquals($updateParams['middle_name'], $contact->middle_name, 'Check for middle name creation.');
    $this->assertEquals($updateParams['contact_type'], $contact->contact_type, 'Check for contact type creation.');
    $this->assertEquals('0', $contact->do_not_email, 'Check for do_not_email creation.');
    $this->assertEquals('0', $contact->do_not_phone, 'Check for do_not_phone creation.');
    $this->assertEquals('0', $contact->do_not_mail, 'Check for do_not_mail creation.');
    $this->assertEquals('0', $contact->do_not_trade, 'Check for do_not_trade creation.');
    $this->assertEquals('0', $contact->is_opt_out, 'Check for is_opt_out creation.');
    $this->assertEquals($updateParams['external_identifier'], $contact->external_identifier,
      'Check for external_identifier creation.'
    );
    $this->assertEquals($updateParams['last_name'] . ', ' . $updateParams['first_name'],
      $contact->sort_name, 'Check for sort_name creation.'
    );
    $this->assertEquals($updateParams['preferred_mail_format'], $contact->preferred_mail_format,
      'Check for preferred_mail_format creation.'
    );
    $this->assertEquals($updateParams['contact_source'], $contact->source, 'Check for contact_source creation.');
    $this->assertEquals($updateParams['prefix_id'], $contact->prefix_id, 'Check for prefix_id creation.');
    $this->assertEquals($updateParams['suffix_id'], $contact->suffix_id, 'Check for suffix_id creation.');
    $this->assertEquals($updateParams['job_title'], $contact->job_title, 'Check for job_title creation.');
    $this->assertEquals($updateParams['gender_id'], $contact->gender_id, 'Check for gender_id creation.');
    $this->assertEquals('1', $contact->is_deceased, 'Check for is_deceased creation.');
    $this->assertEquals(CRM_Utils_Date::processDate($updateParams['birth_date']),
      date('YmdHis', strtotime($contact->birth_date)), 'Check for birth_date creation.'
    );
    $this->assertEquals(CRM_Utils_Date::processDate($updateParams['deceased_date']),
      date('YmdHis', strtotime($contact->deceased_date)), 'Check for deceased_date creation.'
    );
    $dbPrefComm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      $contact->preferred_communication_method
    );
    $checkPrefComm = array();
    foreach ($dbPrefComm as $key => $value) {
      if ($value) {
        $checkPrefComm[$value] = 1;
      }
    }
    $this->assertAttributesEquals($checkPrefComm, $prefComm);

    $this->contactDelete($contactId);
  }

  /**
   * Test case for add( ) with All contact types.
   */
  public function testAddWithAllContactTypes() {
    $firstName = 'Bill';
    $lastName = 'Adams';
    $params = array(
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($firstName, $contact->first_name, 'Check for first name creation.');
    $this->assertEquals($lastName, $contact->last_name, 'Check for last name creation.');

    $contactId = $contact->id;

    //update and change first name and last name, using create()
    $firstName = 'Joan';
    $params = array(
      'first_name' => $firstName,
      'contact_type' => 'Individual',
      'contact_id' => $contactId,
    );

    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($firstName, $contact->first_name, 'Check for updated first name.');
    $contactId = $contact->id;
    $this->contactDelete($contactId);

    $householdName = 'Adams house';
    $params = array(
      'household_name' => $householdName,
      'contact_type' => 'Household',
    );
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($householdName, $contact->sort_name, 'Check for created household.');
    $contactId = $contact->id;

    //update and change name of household, using create
    $householdName = 'Joans home';
    $params = array(
      'household_name' => $householdName,
      'contact_type' => 'Household',
      'contact_id' => $contactId,
    );
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($householdName, $contact->sort_name, 'Check for updated household.');
    $this->contactDelete($contactId);

    $organizationName = 'My Organization';
    $params = array(
      'organization_name' => $organizationName,
      'contact_type' => 'Organization',
    );
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($organizationName, $contact->sort_name, 'Check for created organization.');
    $contactId = $contact->id;

    //update and change name of organization, using create
    $organizationName = 'Your Changed Organization';
    $params = array(
      'organization_name' => $organizationName,
      'contact_type' => 'Organization',
      'contact_id' => $contactId,
    );
    $contact = CRM_Contact_BAO_Contact::add($params);
    $this->assertEquals($organizationName, $contact->sort_name, 'Check for updated organization.');
    $this->contactDelete($contactId);
  }

  /**
   * Test case for create.
   *
   * Test with missing params.
   */
  public function testCreateWithEmptyParams() {
    $params = array(
      'first_name' => 'Bill',
      'last_name' => 'Adams',
    );
    $contact = CRM_Contact_BAO_Contact::create($params);

    //Now check Contact object
    $this->assertNull($contact);
  }

  /**
   * Test case for create.
   *
   * Test with all params.
   * ( create and update modes ).
   */
  public function testCreateWithAll() {
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
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
      'street_address' => CRM_Utils_Array::value('street_address', $params['address'][1]),
      'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1',
        $params['address'][1]
      ),
      'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2',
        $params['address'][1]
      ),
      'supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3',
        $params['address'][1]
      ),
      'city' => CRM_Utils_Array::value('city', $params['address'][1]),
      'postal_code' => CRM_Utils_Array::value('postal_code', $params['address'][1]),
      'country_id' => CRM_Utils_Array::value('country_id', $params['address'][1]),
      'state_province_id' => CRM_Utils_Array::value('state_province_id',
        $params['address'][1]
      ),
      'geo_code_1' => CRM_Utils_Array::value('geo_code_1', $params['address'][1]),
      'geo_code_2' => CRM_Utils_Array::value('geo_code_2', $params['address'][1]),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = array('email' => CRM_Utils_Array::value('email', $params['email'][1]));
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for openid
    $compareParams = array('openid' => CRM_Utils_Array::value('openid', $params['openid'][1]));
    $this->assertDBCompareValues('CRM_Core_DAO_OpenID', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = array(
      'name' => CRM_Utils_Array::value('name', $params['im'][1]),
      'provider_id' => CRM_Utils_Array::value('provider_id', $params['im'][1]),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => CRM_Utils_Array::value('phone_type_id', $params['phone'][1]),
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone', $params['phone'][1]));
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => CRM_Utils_Array::value('phone_type_id', $params['phone'][2]),
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone', $params['phone'][2]));
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Note
    $searchParams = array(
      'entity_id' => $contactId,
      'entity_table' => 'civicrm_contact',
    );
    $compareParams = array('note' => $params['note']);
    $this->assertDBCompareValues('CRM_Core_DAO_Note', $searchParams, $compareParams);

    //update the contact.
    $updateParams = array(
      'first_name' => 'John',
      'last_name' => 'Doe',
      'contact_type' => 'Individual',
      'note' => 'new test note',
    );
    $updateParams['address'][1] = array(
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
    );
    $updateParams['email'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'email' => 'john.doe@example.org',
    );

    $updateParams['phone'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
      'phone' => '02115245336',
    );
    $updateParams['phone'][2] = array(
      'location_type_id' => 1,
      'phone_type_id' => 2,
      'phone' => '9766323895',
    );

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
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
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
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for updated Email
    $compareParams = array('email' => 'john.doe@example.org');
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for updated Phone
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
    );
    $compareParams = array('phone' => '02115245336');
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for updated Mobile
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => 2,
    );
    $compareParams = array('phone' => '9766323895');
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
    $this->quickCleanup(array('civicrm_contact', 'civicrm_note'));
  }

  /**
   * Test case for resolveDefaults( ).
   *
   * Test all pseudoConstant, stateProvince, country.
   */
  public function testResolveDefaults() {
    $params = array(
      'prefix_id' => 3,
      'suffix_id' => 2,
      'gender_id' => 2,
      'birth_date' => '1983-12-13',
    );

    $params['address'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'country_id' => 1228,
      'state_province_id' => 1004,
    );
    // @todo - we are testing this with $reverse = FALSE but it is never called that way!
    CRM_Contact_BAO_Contact::resolveDefaults($params);

    //check the resolve values.
    $genders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id');
    $this->assertEquals($genders[$params['gender_id']], $params['gender'], 'Check for gender.');
    $prefix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'prefix_id');
    $this->assertEquals($prefix[$params['prefix_id']], $params['prefix'], 'Check for prefix.');
    $suffix = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'suffix_id');
    $this->assertEquals($suffix[$params['suffix_id']], $params['suffix'], 'Check for suffix.');
    $this->assertEquals(1004, $params['address'][1]['state_province_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::country($params['address'][1]['country_id']),
      $params['address'][1]['country'],
      'Check for country.'
    );
  }

  /**
   * Test case for retrieve( ).
   *
   * Test with all values.
   */
  public function testRetrieve() {
    //take the common contact params
    $params = $this->contactParams();
    $params['note'] = 'test note';

    //create the contact with given params.
    $contact = CRM_Contact_BAO_Contact::create($params);
    //Now check $contact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
    $contactId = $contact->id;
    //create the organization contact with the given params.
    $orgParams = array(
      'organization_name' => 'Test Organization ' . substr(sha1(rand()), 0, 4),
      'contact_type' => 'Organization',
    );
    $orgContact = CRM_Contact_BAO_Contact::add($orgParams);
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $orgContact, 'Check for created object');

    //create employee of relationship.
    CRM_Contact_BAO_Contact_Utils::createCurrentEmployerRelationship($contactId, $orgContact->id);

    //retrieve the contact values from database.
    $values = array();
    $searchParams = array('contact_id' => $contactId);
    $retrieveContact = CRM_Contact_BAO_Contact::retrieve($searchParams, $values);

    //Now check $retrieveContact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $retrieveContact, 'Check for retrieve object');

    //Now check the ids.
    $this->assertEquals($contactId, $retrieveContact->id, 'Check for contact id');

    //Now check values retrieve from database with params.
    $this->assertEquals($params['first_name'], $values['first_name'], 'Check for first name creation.');
    $this->assertEquals($params['last_name'], $values['last_name'], 'Check for last name creation.');
    $this->assertEquals($params['contact_type'], $values['contact_type'], 'Check for contact type creation.');

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

    //Now check values of Note Count.
    $this->assertEquals(1, $values['noteTotalCount'], 'Check for total note count');

    foreach ($values['note'] as $key => $val) {
      $retrieveNote = CRM_Utils_Array::value('note', $val);
      //check the note value
      $this->assertEquals($params['note'], $retrieveNote, 'Check for note');
    }

    //Now check values of Relationship Count.
    $this->assertEquals(1, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($orgContact->id, $val['contact_id_b'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete(CRM_Utils_Array::value('contact_id_b', $val));
    }

    //delete all notes related to contact
    CRM_Core_BAO_Note::cleanContactNotes($contactId);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for deleteContact( ).
   */
  public function testDeleteContact() {
    $contactParams = $this->contactParams();

    $customGroup = $this->customGroupCreate();
    $fields = array(
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'custom_group_id' => $customGroup['id'],
    );
    $customField = CRM_Core_BAO_CustomField::create($fields);
    $contactParams['custom'] = array(
      $customField->id => array(
        -1 => array(
          'value' => 'Test custom value',
          'type' => 'String',
          'custom_field_id' => $customField->id,
          'custom_group_id' => $customGroup['id'],
          'table_name' => $customGroup['values'][$customGroup['id']]['table_name'],
          'column_name' => $customField->column_name,
          'file_id' => NULL,
        ),
      ),
    );

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($contactParams);
    $contactId = $contact->id;

    //delete contact permanently.
    CRM_Contact_BAO_Contact::deleteContact($contactId, FALSE, TRUE);

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

    // Check that the custom field value is no longer present
    $params = array(
      'entityID' => $contactId,
      'custom_' . $customField->id => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($params);
    $this->assertEquals(CRM_Utils_Array::value("custom_" . $customField->id, $values), '',
      'Verify that the data value is empty for contact ' . $contactId
    );
    $this->assertEquals($values['is_error'], 1, 'Verify that is_error = 0 (success).');

    //Now check DB for contact.
    $this->assertDBNull('CRM_Contact_DAO_Contact', $contactId,
      'id', 'sort_name', 'Database check, contact deleted successfully.'
    );
    $this->quickCleanup(array('civicrm_contact', 'civicrm_note'));
    $this->customGroupDelete($customGroup['id']);
  }

  /**
   * Test case for createProfileContact.
   */
  public function testCreateProfileContact() {
    $fields = CRM_Contact_BAO_Contact::exportableFields('Individual');

    //current employer field for individual
    $fields['organization_name'] = array(
      'name' => 'organization_name',
      'where' => 'civicrm_organization.organization_name',
      'title' => 'Current Employer',
    );
    //get the common params
    $contactParams = $this->contactParams();
    $unsetParams = array('location', 'privacy');
    foreach ($unsetParams as $param) {
      unset($contactParams[$param]);
    }

    $profileParams = array(
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
      'world_region' => 'India',
      'tag' => array(
        '3' => '1',
        '4' => '1',
        '1' => '1',
      ),
    );
    $createParams = array_merge($contactParams, $profileParams);

    //create the contact using create profile contact.
    $contactId = CRM_Contact_BAO_Contact::createProfileContact($createParams, $fields, NULL, NULL, NULL, NULL, TRUE);

    //get the parameters to compare.
    $params = $this->contactParams();

    //check the values in DB.
    foreach ($params as $key => $val) {
      if (!is_array($params[$key])) {
        if ($key == 'contact_source') {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, 'source',
            'id', $params[$key], "Check for {$key} creation."
          );
        }
        else {
          $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $contactId, $key,
            'id', $params[$key], "Check for {$key} creation."
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

    $dbPrefComm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'preferred_communication_method', 'id', TRUE)
    );
    $checkPrefComm = array();
    foreach ($dbPrefComm as $key => $value) {
      if ($value) {
        $checkPrefComm[$value] = 1;
      }
    }
    $this->assertAttributesEquals($checkPrefComm, $params['preferred_communication_method']);

    //Now check DB for Address
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
      'street_address' => CRM_Utils_Array::value('street_address-Primary', $profileParams),
      'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1-Primary', $profileParams),
      'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2-Primary', $profileParams),
      'supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3-Primary', $profileParams),
      'city' => CRM_Utils_Array::value('city-Primary', $profileParams),
      'postal_code' => CRM_Utils_Array::value('postal_code-Primary', $profileParams),
      'country_id' => CRM_Utils_Array::value('country-Primary', $profileParams),
      'state_province_id' => CRM_Utils_Array::value('state_province-Primary', $profileParams),
      'geo_code_1' => CRM_Utils_Array::value('geo_code_1-Primary', $profileParams),
      'geo_code_2' => CRM_Utils_Array::value('geo_code_2-Primary', $profileParams),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = array('email' => CRM_Utils_Array::value('email-Primary', $profileParams));
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = array(
      'name' => CRM_Utils_Array::value('im-Primary', $profileParams),
      'provider_id' => CRM_Utils_Array::value('im-Primary-provider_id', $profileParams),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone-Primary-1', $profileParams));
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => CRM_Utils_Array::value('phone_type_id', $params['phone'][2]),
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone-Primary-2', $profileParams));

    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //get the value of relationship
    $values = array();
    $searchParams = array('contact_id' => $contactId);
    $relationship = CRM_Contact_BAO_Relationship::getValues($searchParams, $values);
    //Now check values of Relationship Count.
    $this->assertEquals(0, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($profileParams['organization_name'], $val['name'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete(CRM_Utils_Array::value('cid', $val));
    }

    //Now check values of tag ids.
    $tags = CRM_Core_BAO_EntityTag::getTag($contactId);
    foreach ($tags as $key => $val) {
      $tagIds[$key] = 1;
    }

    $this->assertAttributesEquals($profileParams['tag'], $tagIds);

    //update Contact mode
    $updateCParams = array(
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
      'website' => array(
        1 => array(
          'website_type_id' => 1,
          'url' => 'http://civicrmUpdate.org',
        ),
      ),
      'contact_source' => 'test contact',
      'external_identifier' => 111222333,
      'preferred_mail_format' => 'Both',
      'is_opt_out' => 0,
      'legal_identifier' => '123123123123',
      'image_URL' => 'http://imageupdate.com',
      'deceased_date' => '1981-10-10',
      'birth_date' => '1951-11-11',
      'privacy' => array(
        'do_not_phone' => 1,
        'do_not_email' => 1,
      ),
      'preferred_communication_method' => array(
        '1' => 0,
        '2' => 1,
        '3' => 0,
        '4' => 1,
        '5' => 0,
      ),
    );

    $updatePfParams = array(
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
      'world_region' => 'India',
      'tag' => array(
        '2' => '1',
        '5' => '1',
      ),
    );

    $createParams = array_merge($updateCParams, $updatePfParams);

    //create the contact using create profile contact.
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($createParams, $fields, $contactId,
      NULL, NULL, NULL, TRUE
    );

    //check the contact ids
    $this->assertEquals($contactId, $contactID, 'check for Contact ids');

    //check the values in DB.
    foreach ($updateCParams as $key => $val) {
      if (!is_array($updateCParams[$key])) {
        if ($key == 'contact_source') {
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

    $dbPrefComm = explode(CRM_Core_DAO::VALUE_SEPARATOR,
      CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactId, 'preferred_communication_method', 'id', TRUE)
    );
    $checkPrefComm = array();
    foreach ($dbPrefComm as $key => $value) {
      if ($value) {
        $checkPrefComm[$value] = 1;
      }
    }
    $this->assertAttributesEquals($checkPrefComm, $updateCParams['preferred_communication_method']);

    //Now check DB for Address
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
      'street_address' => CRM_Utils_Array::value('street_address-Primary', $updatePfParams),
      'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1-Primary', $updatePfParams),
      'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2-Primary', $updatePfParams),
      'supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3-Primary', $updatePfParams),
      'city' => CRM_Utils_Array::value('city-Primary', $updatePfParams),
      'postal_code' => CRM_Utils_Array::value('postal_code-Primary', $updatePfParams),
      'country_id' => CRM_Utils_Array::value('country-Primary', $updatePfParams),
      'state_province_id' => CRM_Utils_Array::value('state_province-Primary', $updatePfParams),
      'geo_code_1' => CRM_Utils_Array::value('geo_code_1-Primary', $updatePfParams),
      'geo_code_2' => CRM_Utils_Array::value('geo_code_2-Primary', $updatePfParams),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //Now check DB for Email
    $compareParams = array('email' => CRM_Utils_Array::value('email-Primary', $updatePfParams));
    $this->assertDBCompareValues('CRM_Core_DAO_Email', $searchParams, $compareParams);

    //Now check DB for IM
    $compareParams = array(
      'name' => CRM_Utils_Array::value('im-Primary', $updatePfParams),
      'provider_id' => CRM_Utils_Array::value('im-Primary-provider_id', $updatePfParams),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_IM', $searchParams, $compareParams);

    //Now check DB for Phone
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone-Primary-1', $updatePfParams));
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //Now check DB for Mobile
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'phone_type_id' => CRM_Utils_Array::value('phone_type_id', $params['phone'][2]),
    );
    $compareParams = array('phone' => CRM_Utils_Array::value('phone-Primary-2', $updatePfParams));
    $this->assertDBCompareValues('CRM_Core_DAO_Phone', $searchParams, $compareParams);

    //get the value of relationship
    $values = array();
    $searchParams = array('contact_id' => $contactId);
    $relationship = CRM_Contact_BAO_Relationship::getValues($searchParams, $values);
    //Now check values of Relationship Count.
    $this->assertEquals(0, $values['relationship']['totalCount'], 'Check for total relationship count');
    foreach ($values['relationship']['data'] as $key => $val) {
      //Now check values of Relationship organization.
      $this->assertEquals($updatePfParams['organization_name'], $val['name'], 'Check for organization');
      //Now check values of Relationship type.
      $this->assertEquals('Employee of', $val['relation'], 'Check for relationship type');
      //delete the organization.
      $this->contactDelete(CRM_Utils_Array::value('cid', $val));
    }

    //Now check values of tag ids.
    $tags = CRM_Core_BAO_EntityTag::getTag($contactId);
    foreach ($tags as $key => $val) {
      $tagIds[$key] = 1;
    }
    $this->assertAttributesEquals($updatePfParams['tag'], $tagIds);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
  }

  /**
   * Test case for getContactDetails( ).
   */
  public function testGetContactDetails() {
    //get the contact params
    $params = $this->contactParams();

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get the contact details
    $contactDetails = CRM_Contact_BAO_Contact::getContactDetails($contactId);
    $compareParams = array(
      $params['first_name'] . ' ' . $params['last_name'],
      CRM_Utils_Array::value('email', $params['email'][1]),
      (bool ) $params['privacy']['do_not_email'],
    );
    //Now check the contact details
    $this->assertAttributesEquals($compareParams, $contactDetails);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for importableFields( ) and exportableFields( ).
   */
  public function testFields() {
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
  public function testGetPrimaryEmail() {
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
    $this->assertEquals($email, CRM_Utils_Array::value('email', $params['email'][2]), 'Check Primary Email');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for getPrimaryOpenId( ).
   */
  public function testGetPrimaryOpenId() {
    //get the contact params
    $params = $this->contactParams();
    $params['openid'][2] = $params['openid'][1];
    $params['openid'][2]['location_type_id'] = 2;
    $params['openid'][2]['openid'] = 'http://primaryopenid.org/';
    unset($params['openid'][1]['is_primary']);

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;
    //get the primary openid
    $openID = CRM_Contact_BAO_Contact::getPrimaryOpenId($contactId);

    //Now check the primary openid
    $this->assertEquals($openID, strtolower($params['openid'][2]['openid']), 'Check Primary OpenID');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
  }

  /**
   * Test case for matchContactOnEmail( ).
   */
  public function testMatchContactOnEmail() {
    //get the contact params
    $params = $this->contactParams();
    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get the matching contact.
    $match = CRM_Contact_BAO_Contact::matchContactOnEmail(CRM_Utils_Array::value('email', $params['email'][1]),
      'Individual'
    );
    $this->assertEquals($contactId, $match->contact_id, 'Check For Matching Contact');

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for getContactType( ).
   */
  public function testGetContactType() {
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
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for displayName( ).
   */
  public function testDisplayName() {
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
    $this->quickCleanup(array('civicrm_contact'));
  }

  /**
   * Test case for getDisplayAndImage( ).
   */
  public function testGetDisplayAndImage() {
    //get the contact params
    $params = $this->contactParams();

    //create contact
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    //get DisplayAndImage.
    list($displayName, $image) = CRM_Contact_BAO_Contact::getDisplayAndImage($contactId);

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

    $params = array(
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
      'website' => array(
        1 => array(
          'website_type_id' => 1,
          'url' => 'http://civicrm.org',
        ),
      ),
      'contact_source' => 'test contact',
      'external_identifier' => 123456789,
      'preferred_mail_format' => 'Both',
      'is_opt_out' => 1,
      'legal_identifier' => '123456789',
      'image_URL' => 'http://image.com',
      'deceased_date' => '1991-07-07',
      'birth_date' => '1961-06-06',
      'privacy' => array(
        'do_not_phone' => 1,
        'do_not_email' => 1,
        'do_not_mail' => 1,
        'do_not_trade' => 1,
      ),
      'preferred_communication_method' => array(
        '1' => 1,
        '2' => 0,
        '3' => 1,
        '4' => 0,
        '5' => 1,
      ),
    );

    $params['address'] = array();
    $params['address'][1] = array(
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
    );

    $params['email'] = array();
    $params['email'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'email' => 'john.smith@example.org',
    );

    $params['phone'] = array();
    $params['phone'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'phone_type_id' => 1,
      'phone' => '303443689',
    );
    $params['phone'][2] = array(
      'location_type_id' => 1,
      'phone_type_id' => 2,
      'phone' => '9833910234',
    );

    $params['openid'] = array();
    $params['openid'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'openid' => 'http://civicrm.org/',
    );

    $params['im'] = array();
    $params['im'][1] = array(
      'location_type_id' => 1,
      'is_primary' => 1,
      'name' => 'john.smith',
      'provider_id' => 1,
    );

    return $params;
  }

  /**
   * Ensure that created_date and modified_date are set.
   */
  public function testTimestampContact() {
    $test = $this;
    $this->_testTimestamps(array(
      'UPDATE' => function ($contactId) use ($test) {
        $params = array(
          'first_name' => 'Testing',
          'contact_type' => 'Individual',
          'contact_id' => $contactId,
        );
        $contact = CRM_Contact_BAO_Contact::add($params);
        $test->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');
      },
    ));
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated when manipulating a phone record.
   */
  public function testTimestampsEmail() {
    $test = $this;
    $this->_testTimestamps(array(
      'INSERT' => function ($contactId) use ($test) {
        $params = array(
          'email' => 'ex-1@example.com',
          'is_primary' => 1,
          'location_type_id' => 1,
          'contact_id' => $contactId,
        );
        CRM_Core_BAO_Email::add($params);
        $test->assertDBQuery('ex-1@example.com',
          'SELECT email FROM civicrm_email WHERE contact_id = %1 ORDER BY id DESC LIMIT 1',
          array(1 => array($contactId, 'Integer'))
        );
      },
      'UPDATE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_email SET email = "ex-2@example.com" WHERE contact_id = %1',
          array(1 => array($contactId, 'Integer'))
        );
      },
      'DELETE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'DELETE FROM civicrm_email WHERE contact_id = %1',
          array(1 => array($contactId, 'Integer'))
        );
      },
    ));
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated when manipulating an email.
   */
  public function testTimestampsPhone() {
    $test = $this;
    $this->_testTimestamps(array(
      'INSERT' => function ($contactId) use ($test) {
        $params = array(
          'phone' => '202-555-1000',
          'is_primary' => 1,
          'location_type_id' => 1,
          'contact_id' => $contactId,
        );
        CRM_Core_BAO_Phone::add($params);
        $test->assertDBQuery('202-555-1000',
          'SELECT phone FROM civicrm_phone WHERE contact_id = %1 ORDER BY id DESC LIMIT 1',
          array(1 => array($contactId, 'Integer'))
        );
      },
      'UPDATE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_phone SET phone = "202-555-2000" WHERE contact_id = %1',
          array(1 => array($contactId, 'Integer'))
        );
      },
      'DELETE' => function ($contactId) use ($test) {
        CRM_Core_DAO::executeQuery(
          'DELETE FROM civicrm_phone WHERE contact_id = %1',
          array(1 => array($contactId, 'Integer'))
        );
      },
    ));
  }

  /**
   * Ensure that civicrm_contact.modified_date is updated correctly.
   *
   * Looking at it when contact-related custom data is updated.
   */
  public function testTimestampsCustom() {
    $customGroup = $this->customGroupCreate();
    $customGroup = $customGroup['values'][$customGroup['id']];
    $fields = array(
      'custom_group_id' => $customGroup['id'],
      'data_type' => 'String',
      'html_type' => 'Text',
    );
    $customField = $this->customFieldCreate($fields);
    $customField = $customField['values'][$customField['id']];
    $test = $this;
    $this->_testTimestamps(array(
      'INSERT' => function ($contactId) use ($test, $customGroup, $customField) {
        civicrm_api3('contact', 'create', array(
          'contact_id' => $contactId,
          'custom_' . $customField['id'] => 'test-1',
        ));
      },
      'UPDATE' => function ($contactId) use ($test, $customGroup, $customField) {
        CRM_Core_DAO::executeQuery(
          "UPDATE {$customGroup['table_name']} SET {$customField['column_name']} = 'test-2' WHERE entity_id = %1",
          array(1 => array($contactId, 'Integer'))
        );
      },
      'DELETE' => function ($contactId) use ($test, $customGroup, $customField) {
        CRM_Core_DAO::executeQuery(
          "DELETE FROM {$customGroup['table_name']} WHERE entity_id = %1",
          array(1 => array($contactId, 'Integer'))
        );
      },
    ));
    $this->quickCleanup(array('civicrm_contact'), TRUE);
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
  public function _testTimestamps($callbacks) {
    CRM_Core_DAO::triggerRebuild();
    $contactId = $this->individualCreate();

    $origTimestamps = CRM_Contact_BAO_Contact::getTimestamps($contactId);
    $this->assertRegexp('/^\d\d\d\d-\d\d-\d\d /', $origTimestamps['created_date']);
    $this->assertRegexp('/^\d\d\d\d-\d\d-\d\d /', $origTimestamps['modified_date']);
    $this->assertTrue($origTimestamps['created_date'] <= $origTimestamps['modified_date']);

    $prevTimestamps = $origTimestamps;
    foreach ($callbacks as $callbackName => $callback) {
      // advance clock by 1 second to ensure timestamps change
      sleep(1);

      $callback($contactId);
      $newTimestamps = CRM_Contact_BAO_Contact::getTimestamps($contactId);
      $this->assertRegexp('/^\d\d\d\d-\d\d-\d\d /', $newTimestamps['created_date'], "Malformed created_date (after $callbackName)");
      $this->assertRegexp('/^\d\d\d\d-\d\d-\d\d /', $newTimestamps['modified_date'], "Malformed modified_date (after $callbackName)");
      $this->assertEquals($origTimestamps['created_date'], $newTimestamps['created_date'], "Changed created_date (after $callbackName)");
      $this->assertTrue($prevTimestamps['modified_date'] < $newTimestamps['modified_date'], "Misordered modified_date (after $callbackName)");

      $prevTimestamps = $newTimestamps;
    }

    $this->contactDelete($contactId);
  }

  /**
   * Test case for UpdateProfileLocationLeak (CRM-20598).
   */
  public function testUpdateProfileLocationLeak() {
    // create a simple contact with address and phone that share the same location type
    $defaults = $this->contactParams();
    $params = array(
      'first_name' => $defaults['first_name'],
      'last_name' => $defaults['last_name'],
      'contact_type' => 'Individual',
      'address' => array(1 => $defaults['address'][1]),
      'phone' => array(1 => $defaults['phone'][1]),
    );
    $contact = CRM_Contact_BAO_Contact::create($params);
    $contactId = $contact->id;

    // now, update using a profile with phone, email, address... that share the same location type
    $updatePfParams = array(
      'first_name' => $params['first_name'],
      'last_name' => $params['first_name'],
      'street_address-Primary' => $params['address'][1]['street_address'],
      'state_province-Primary' => $params['address'][1]['state_province_id'],
      'country-Primary' => $params['address'][1]['country_id'],
      'phone-Primary-1' => $params['phone'][1]['phone'],
      'phone_ext-Primary-1' => '345',
    );

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
    $searchParams = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'is_primary' => 1,
    );
    $compareParams = array(
      'street_address' => CRM_Utils_Array::value('street_address-Primary', $updatePfParams),
    );
    $this->assertDBCompareValues('CRM_Core_DAO_Address', $searchParams, $compareParams);

    //cleanup DB by deleting the contact
    $this->contactDelete($contactId);
  }

}
