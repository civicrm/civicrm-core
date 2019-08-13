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
 * @file
 * File for the CRM_Contact_Imports_Parser_ContactTest class.
 */

/**
 *  Test contact import parser.
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Import_Parser_ContactTest extends CiviUnitTestCase {
  use CRMTraits_Custom_CustomDataTrait;

  /**
   * Main entity for the class.
   *
   * @var string
   */
  protected $entity = 'Contact';

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tear down after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_email'], TRUE);
    parent::tearDown();
  }

  /**
   * Test that import parser will add contact with employee of relationship.
   *
   * @throws \Exception
   */
  public function testImportParserWtihEmployeeOfRelationship() {
    $this->organizationCreate([
      "organization_name" => "Agileware",
      "legal_name"        => "Agileware",
    ]);
    $contactImportValues = [
      "first_name"  => "Alok",
      "last_name"   => "Patel",
      "Employee of" => "Agileware",
    ];

    $fields = array_keys($contactImportValues);
    $values = array_values($contactImportValues);
    $parser = new CRM_Contact_Import_Parser_Contact($fields, []);
    $parser->_contactType = 'Individual';
    $parser->init();
    $this->mapRelationshipFields($fields, $parser->getAllFields());

    $parser = new CRM_Contact_Import_Parser_Contact($fields, [], [], [], [
      NULL,
      NULL,
      $fields[2],
    ], [
      NULL,
      NULL,
      "Organization",
    ], [
      NULL,
      NULL,
      "organization_name",
    ], [], [], [], [], []);

    $parser->_contactType = 'Individual';
    $parser->_onDuplicate = CRM_Import_Parser::DUPLICATE_UPDATE;
    $parser->init();

    $this->assertEquals(CRM_Import_Parser::VALID, $parser->import(CRM_Import_Parser::DUPLICATE_UPDATE, $values), 'Return code from parser import was not as expected');
    $this->callAPISuccess("Contact", "get", [
      "first_name"        => "Alok",
      "last_name"         => "Patel",
      "organization_name" => "Agileware",
    ]);
  }

  /**
   * Test that import parser will not fail when same external_identifier found of deleted contact.
   *
   * @throws \Exception
   */
  public function testImportParserWtihDeletedContactExternalIdentifier() {
    $contactId = $this->individualCreate([
      "external_identifier" => "ext-1",
    ]);
    CRM_Contact_BAO_Contact::deleteContact($contactId);
    list($originalValues, $result) = $this->setUpBaseContact([
      'external_identifier' => 'ext-1',
    ]);
    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('ext-1', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'external_identifier']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test import parser will update based on a rule match.
   *
   * In this case the contact has no external identifier.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithoutExternalIdentifier() {
    list($originalValues, $result) = $this->setUpBaseContact();
    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test import parser will update contacts with an external identifier.
   *
   * This is the basic test where the identifier matches the import parameters.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifier() {
    list($originalValues, $result) = $this->setUpBaseContact(['external_identifier' => 'windows']);

    $this->assertEquals($result['id'], CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', 'windows', 'id', 'external_identifier', TRUE));
    $this->assertEquals('windows', $result['external_identifier']);

    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];

    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test import parser will fallback to external identifier.
   *
   * In this case no primary match exists (e.g the details are not supplied) so it falls back on external identifier.
   *
   * CRM-17275
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifierButNoPrimaryMatch() {
    list($originalValues, $result) = $this->setUpBaseContact([
      'external_identifier' => 'windows',
      'email' => NULL,
    ]);

    $this->assertEquals('windows', $result['external_identifier']);

    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];

    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test import parser will fallback to external identifier.
   *
   * In this case no primary match exists (e.g the details are not supplied) so it falls back on external identifier.
   *
   * CRM-17275
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithContactID() {
    list($originalValues, $result) = $this->setUpBaseContact([
      'external_identifier' => '',
      'email' => NULL,
    ]);
    $updateValues = ['id' => $result['id'], 'email' => 'bill@example.com'];
    // This is some deep weirdness - this sets a flag for updatingBlankLocinfo - allowing input to be blanked
    // (which IS a good thing but it's pretty weird & all to do with legacy profile stuff).
    CRM_Core_Session::singleton()->set('authSrc', CRM_Core_Permission::AUTH_SRC_CHECKSUM);
    $this->runImport($updateValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [NULL, 1]);
    $originalValues['id'] = $result['id'];
    $this->callAPISuccessGetSingle('Email', ['contact_id' => $originalValues['id'], 'is_primary' => 1]);
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test that the import parser adds the external identifier where none is set.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithNoExternalIdentifier() {
    list($originalValues, $result) = $this->setUpBaseContact();
    $originalValues['nick_name'] = 'Old Bill';
    $originalValues['external_identifier'] = 'windows';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test that the import parser changes the external identifier when there is a dedupe match.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithChangedExternalIdentifier() {
    list($contactValues, $result) = $this->setUpBaseContact(['external_identifier' => 'windows']);
    $contact_id = $result['id'];
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $contactValues['id'] = $contact_id;
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $contact_id, 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $contactValues);
  }

  /**
   * Test that the import parser adds the address to the right location.
   *
   * @throws \Exception
   */
  public function testImportBillingAddress() {
    list($contactValues) = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = '911';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => 2, 6 => 2]);
    $address = $this->callAPISuccessGetSingle('Address', ['street_address' => 'Big Mansion']);
    $this->assertEquals(2, $address['location_type_id']);

    $phone = $this->callAPISuccessGetSingle('Phone', ['phone' => '911']);
    $this->assertEquals(2, $phone['location_type_id']);

    $contact = $this->callAPISuccessGetSingle('Contact', $contactValues);
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportPrimaryAddress() {
    list($contactValues) = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => 'Primary', 3 => NULL, 4 => NULL, 5 => 'Primary', 6 => 'Primary']);
    $address = $this->callAPISuccessGetSingle('Address', ['street_address' => 'Big Mansion']);
    $this->assertEquals(1, $address['location_type_id']);
    $this->assertEquals(1, $address['is_primary']);

    $phone = $this->callAPISuccessGetSingle('Phone', ['phone' => '12334']);
    $this->assertEquals(1, $phone['location_type_id']);

    $this->callAPISuccessGetSingle('Email', ['email' => 'bill.gates@microsoft.com']);

    $contact = $this->callAPISuccessGetSingle('Contact', $contactValues);
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);
  }

  /**
   * Test that address custom fields can be imported
   * FIXME: Api4
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddressWithCustomData() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate('Address', 'AddressTest.php');
    list($contactValues) = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['custom_' . $ids['custom_field_id']] = 'Update';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => 'Primary', 6 => 'Primary']);
    $address = $this->callAPISuccessGetSingle('Address', ['street_address' => 'Big Mansion', 'return' => 'custom_' . $ids['custom_field_id']]);
    $this->assertEquals('Update', $address['custom_' . $ids['custom_field_id']]);
  }

  /**
   * Test gender works when you specify the label.
   *
   * There is an expectation that you can import by label here.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGenderLabel() {
    $contactValues = [
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
      'gender_id' => 'Female',
    ];
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [NULL, NULL, 'Primary', NULL, NULL]);
    $this->callAPISuccessGetSingle('Contact', $contactValues);
  }

  /**
   * Test prefix & suffix work when you specify the label.
   *
   * There is an expectation that you can import by label here.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testPrefixLabel() {
    $this->callAPISuccess('OptionValue', 'create', ['option_group_id' => 'individual_prefix', 'name' => 'new_one', 'label' => 'special', 'value' => 70]);
    $mapping = [
      ['name' => 'first_name', 'column_number' => 1],
      ['name' => 'last_name', 'column_number' => 2],
      ['name' => 'email', 'column_number' => 3, 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Email', 'location_type_id', 'Home')],
      ['name' => 'prefix_id', 'column_number' => 5],
      ['name' => 'suffix_id', 'column_number' => 4],
    ];
    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingFields($mapping);
    $processor->setContactType('Individual');
    $importer = $processor->getImporterObject();

    $contactValues = [
      'Bill',
      'Gates',
      'bill.gates@microsoft.com',
      'III',
      'special',
    ];
    $importer->import(CRM_Import_Parser::DUPLICATE_NOCHECK, $contactValues);

    $contact = $this->callAPISuccessGetSingle('Contact', ['first_name' => 'Bill', 'prefix_id' => 'new_one', 'suffix_id' => 'III']);
  }

  /**
   * Test that labels work for importing custom data.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomDataLabel() {
    $this->createCustomGroupWithFieldOfType([], 'select');
    $contactValues = [
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
      $this->getCustomFieldName('select') => 'Yellow',
    ];
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [NULL, NULL, 'Primary', NULL, NULL]);
    $contact = $this->callAPISuccessGetSingle('Contact', array_merge($contactValues, ['return' => $this->getCustomFieldName('select')]));
    $this->assertEquals('Y', $contact[$this->getCustomFieldName('select')]);
  }

  /**
   * Test that names work for importing custom data.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomDataName() {
    $this->createCustomGroupWithFieldOfType([], 'select');
    $contactValues = [
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
      $this->getCustomFieldName('select') => 'Y',
    ];
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [NULL, NULL, 'Primary', NULL, NULL]);
    $contact = $this->callAPISuccessGetSingle('Contact', array_merge($contactValues, ['return' => $this->getCustomFieldName('select')]));
    $this->assertEquals('Y', $contact[$this->getCustomFieldName('select')]);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportDeceased() {
    list($contactValues) = $this->setUpBaseContact();
    CRM_Core_Session::singleton()->set("dateTypes", 1);
    $contactValues['birth_date'] = '1910-12-17';
    $contactValues['deceased_date'] = '2010-12-17';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $contact = $this->callAPISuccessGetSingle('Contact', $contactValues);
    $this->assertEquals('1910-12-17', $contact['birth_date']);
    $this->assertEquals('2010-12-17', $contact['deceased_date']);
    $this->assertEquals(1, $contact['is_deceased']);
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportTwoAddressFirstPrimary() {
    list($contactValues) = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;
    $fields = array_keys($contactValues);
    $contactValues['street_address_2'] = 'Teeny Mansion';
    $contactValues['phone_2'] = 4444;
    $fields[] = 'street_address';
    $fields[] = 'phone';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => 'Primary', 6 => 'Primary', 7 => 3, 8 => 3], $fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['external_identifier' => 'android']);
    $address = $this->callAPISuccess('Address', 'get', ['contact_id' => $contact['id'], 'sequential' => 1]);

    $this->assertEquals(3, $address['values'][0]['location_type_id']);
    $this->assertEquals(0, $address['values'][0]['is_primary']);
    $this->assertEquals('Teeny Mansion', $address['values'][0]['street_address']);

    $this->assertEquals(1, $address['values'][1]['location_type_id']);
    $this->assertEquals(1, $address['values'][1]['is_primary']);
    $this->assertEquals('Big Mansion', $address['values'][1]['street_address']);

    $phone = $this->callAPISuccess('Phone', 'get', ['contact_id' => $contact['id'], 'sequential' => 1]);
    $this->assertEquals(1, $phone['values'][0]['location_type_id']);
    $this->assertEquals(1, $phone['values'][0]['is_primary']);
    $this->assertEquals(12334, $phone['values'][0]['phone']);
    $this->assertEquals(3, $phone['values'][1]['location_type_id']);
    $this->assertEquals(0, $phone['values'][1]['is_primary']);
    $this->assertEquals(4444, $phone['values'][1]['phone']);

    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportTwoAddressSecondPrimary() {
    list($contactValues) = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;
    $fields = array_keys($contactValues);
    $contactValues['street_address_2'] = 'Teeny Mansion';
    $contactValues['phone_2'] = 4444;
    $fields[] = 'street_address';
    $fields[] = 'phone';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => NULL, 3 => NULL, 4 => NULL, 5 => 3, 6 => 3, 7 => 'Primary', 8 => 'Primary'], $fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['external_identifier' => 'android']);
    $address = $this->callAPISuccess('Address', 'get', ['contact_id' => $contact['id'], 'sequential' => 1])['values'];

    $this->assertEquals(1, $address[1]['location_type_id']);
    $this->assertEquals(1, $address[1]['is_primary']);
    $this->assertEquals('Teeny Mansion', $address[1]['street_address']);

    $this->assertEquals(3, $address[0]['location_type_id']);
    $this->assertEquals(0, $address[0]['is_primary']);
    $this->assertEquals('Big Mansion', $address[0]['street_address']);

    $phone = $this->callAPISuccess('Phone', 'get', ['contact_id' => $contact['id'], 'sequential' => 1, 'options' => ['sort' => 'is_primary DESC']])['values'];
    $this->assertEquals(3, $phone[1]['location_type_id']);
    $this->assertEquals(0, $phone[1]['is_primary']);
    $this->assertEquals(12334, $phone[1]['phone']);
    $this->assertEquals(1, $phone[0]['location_type_id']);
    $this->assertEquals(1, $phone[0]['is_primary']);
    $this->assertEquals(4444, $phone[0]['phone']);

    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);
  }

  /**
   * Test that the import parser updates the address on the existing primary location.
   *
   * @throws \Exception
   */
  public function testImportPrimaryAddressUpdate() {
    list($contactValues) = $this->setUpBaseContact(['external_identifier' => 'android']);
    $contactValues['email'] = 'melinda.gates@microsoft.com';
    $contactValues['phone'] = '98765';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['city'] = 'Big City';
    $contactID = $this->callAPISuccessGetValue('Contact', ['external_identifier' => 'android', 'return' => 'id']);
    $originalAddress = $this->callAPISuccess('Address', 'create', ['location_type_id' => 2, 'street_address' => 'small house', 'contact_id' => $contactID]);
    $originalPhone = $this->callAPISuccess('phone', 'create', ['location_type_id' => 2, 'phone' => '1234', 'contact_id' => $contactID]);
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [0 => NULL, 1 => NULL, 2 => 'Primary', 3 => NULL, 4 => NULL, 5 => 'Primary', 6 => 'Primary', 7 => 'Primary']);
    $phone = $this->callAPISuccessGetSingle('Phone', ['phone' => '98765']);
    $this->assertEquals(2, $phone['location_type_id']);
    $this->assertEquals($originalPhone['id'], $phone['id']);
    $email = $this->callAPISuccess('Email', 'getsingle', ['contact_id' => $contactID]);
    $address = $this->callAPISuccessGetSingle('Address', ['street_address' => 'Big Mansion']);
    $this->assertEquals(2, $address['location_type_id']);
    $this->assertEquals($originalAddress['id'], $address['id']);
    $this->assertEquals('Big City', $address['city']);
    $this->callAPISuccessGetSingle('Contact', $contactValues);
  }

  /**
   * Test the determination of whether a custom field is valid.
   */
  public function testCustomFieldValidation() {
    $errorMessage = [];
    $customGroup = $this->customGroupCreate([
      'extends' => 'Contact',
      'title' => 'ABC',
    ]);
    $customField = $this->customFieldOptionValueCreate($customGroup, 'fieldABC', ['html_type' => 'Multi-Select']);
    $params = [
      'custom_' . $customField['id'] => 'Label1|Label2',
    ];
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($params, $errorMessage);
    $this->assertEquals([], $errorMessage);
  }

  /**
   * Test that setting duplicate action to fill doesn't blow away data
   * that exists, but does fill in where it's empty.
   *
   * @throw \Exception
   */
  public function testImportFill() {
    // Create a custom field group for testing.
    $this->createCustomGroup([
      'title' => 'importFillGroup',
      'extends' => 'Individual',
      'is_active' => TRUE,
    ]);
    $customGroupID = $this->ids['CustomGroup']['importFillGroup'];

    // Add two custom fields.
    $api_params = [
      'custom_group_id' => $customGroupID,
      'label' => 'importFillField1',
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_values' => [
        'foo' => 'Foo',
        'bar' => 'Bar',
      ],
    ];
    $result = $this->callAPISuccess('custom_field', 'create', $api_params);
    $customField1 = $result['id'];

    $api_params = [
      'custom_group_id' => $customGroupID,
      'label' => 'importFillField2',
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_values' => [
        'baz' => 'Baz',
        'boo' => 'Boo',
      ],
    ];
    $result = $this->callAPISuccess('custom_field', 'create', $api_params);
    $customField2 = $result['id'];

    // Now set up values.
    $original_gender = 'Male';
    $original_custom1 = 'foo';
    $original_email = 'test-import-fill@example.org';

    $import_gender = 'Female';
    $import_custom1 = 'bar';
    $import_job_title = 'Chief data importer';
    $import_custom2 = 'baz';

    // Create contact with both one known core field and one custom
    // field filled in.
    $api_params = [
      'contact_type' => 'Individual',
      'email' => $original_email,
      'gender' => $original_gender,
      'custom_' . $customField1 => $original_custom1,
    ];
    $result = $this->callAPISuccess('contact', 'create', $api_params);
    $contact_id = $result['id'];

    // Run an import.
    $import = [
      'email' => $original_email,
      'gender_id' => $import_gender,
      'custom_' . $customField1 => $import_custom1,
      'job_title' => $import_job_title,
      'custom_' . $customField2 => $import_custom2,
    ];

    $this->runImport($import, CRM_Import_Parser::DUPLICATE_FILL, CRM_Import_Parser::VALID);

    $expected = [
      'gender' => $original_gender,
      'custom_' . $customField1 => $original_custom1,
      'job_title' => $import_job_title,
      'custom_' . $customField2 => $import_custom2,
    ];

    $params = [
      'id' => $contact_id,
      'return' => [
        'gender',
        'custom_' . $customField1,
        'job_title',
        'custom_' . $customField2,
      ],
    ];
    $result = civicrm_api3('Contact', 'get', $params);
    $values = array_pop($result['values']);
    foreach ($expected as $field => $expected_value) {
      if (!isset($values[$field])) {
        $given_value = NULL;
      }
      else {
        $given_value = $values[$field];
      }
      // We expect:
      //   gender: Male
      //   job_title: Chief Data Importer
      //   importFillField1: foo
      //   importFillField2: baz
      $this->assertEquals($expected_value, $given_value, "$field properly handled during Fill import");
    }
  }

  /**
   * CRM-19888 default country should be used if ambigous.
   */
  public function testImportAmbiguousStateCountry() {
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => 1228]);
    $countries = CRM_Core_PseudoConstant::country(FALSE, FALSE);
    $this->callAPISuccess('Setting', 'create', ['countryLimit' => [array_search('United States', $countries), array_search('Guyana', $countries), array_search('Netherlands', $countries)]]);
    $this->callAPISuccess('Setting', 'create', ['provinceLimit' => [array_search('United States', $countries), array_search('Guyana', $countries), array_search('Netherlands', $countries)]]);
    $mapper = [0 => NULL, 1 => NULL, 2 => 'Primary', 3 => NULL];
    list($contactValues) = $this->setUpBaseContact();
    $fields = array_keys($contactValues);
    $addressValues = [
      'street_address' => 'PO Box 2716',
      'city' => 'Midway',
      'state_province' => 'UT',
      'postal_code' => 84049,
      'country' => 'United States',
    ];
    $locationTypes = $this->callAPISuccess('Address', 'getoptions', ['field' => 'location_type_id']);
    $locationTypes = $locationTypes['values'];
    foreach ($addressValues as $field => $value) {
      $contactValues['home_' . $field] = $value;
      $mapper[] = array_search('Home', $locationTypes);
      $contactValues['work_' . $field] = $value;
      $mapper[] = array_search('Work', $locationTypes);
      $fields[] = $field;
      $fields[] = $field;
    }
    $contactValues['work_country'] = '';

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $mapper, $fields);
    $addresses = $this->callAPISuccess('Address', 'get', ['contact_id' => ['>' => 2], 'sequential' => 1]);
    $this->assertEquals(2, $addresses['count']);
    $this->assertEquals(array_search('United States', $countries), $addresses['values'][0]['country_id']);
    $this->assertEquals(array_search('United States', $countries), $addresses['values'][1]['country_id']);
  }

  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int $expectedResult
   * @param array|null $mapperLocType
   *   Array of location types that map to the input arrays.
   * @param array|null $fields
   *   Array of field names. Will be calculated from $originalValues if not passed in, but
   *   that method does not cope with duplicates.
   */
  protected function runImport($originalValues, $onDuplicateAction, $expectedResult, $mapperLocType = [], $fields = NULL) {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    $values = array_values($originalValues);
    $parser = new CRM_Contact_Import_Parser_Contact($fields, $mapperLocType);
    $parser->_contactType = 'Individual';
    $parser->_onDuplicate = $onDuplicateAction;
    $parser->init();
    $this->assertEquals($expectedResult, $parser->import($onDuplicateAction, $values), 'Return code from parser import was not as expected');
  }

  /**
   * @param array $fields Array of fields to be imported
   * @param array $allfields Array of all fields which can be part of import
   */
  private function mapRelationshipFields(&$fields, $allfields) {
    foreach ($allfields as $key => $fieldtocheck) {
      $elementIndex = array_search($fieldtocheck->_title, $fields);
      if ($elementIndex !== FALSE) {
        $fields[$elementIndex] = $key;
      }
    }
  }

  /**
   * Set up the underlying contact.
   *
   * @param array $params
   *   Optional extra parameters to set.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function setUpBaseContact($params = []) {
    $originalValues = array_merge([
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
    ], $params);
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $result = $this->callAPISuccessGetSingle('Contact', $originalValues);
    return [$originalValues, $result];
  }

}
