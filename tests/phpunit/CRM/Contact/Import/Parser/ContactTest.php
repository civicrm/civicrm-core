<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This code is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @file
 * File for the CRM_Contact_Imports_Parser_ContactTest class.
 */

use Civi\Api4\Contact;
use Civi\Api4\UserJob;

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
   * Tear down after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_email', 'civicrm_user_job'], TRUE);
    parent::tearDown();
  }

  /**
   * Test that import parser will add contact with employee of relationship.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testImportParserWithEmployeeOfRelationship(): void {
    $this->organizationCreate([
      'organization_name' => 'Agileware',
      'legal_name'        => 'Agileware',
    ]);
    $contactImportValues = [
      'first_name' => 'Alok',
      'last_name' => 'Patel',
      'Employee of' => 'Agileware',
    ];

    $fields = array_keys($contactImportValues);
    $values = array_values($contactImportValues);
    $userJobID = $this->getUserJobID([
      'mapper' => [['first_name'], ['last_name'], ['5_a_b', 'organization_name']],
    ]);

    $parser = new CRM_Contact_Import_Parser_Contact($fields);
    $parser->setUserJobID($userJobID);
    $parser->_onDuplicate = CRM_Import_Parser::DUPLICATE_UPDATE;
    $parser->init();

    $this->assertEquals(CRM_Import_Parser::VALID, $parser->import(CRM_Import_Parser::DUPLICATE_UPDATE, $values), 'Return code from parser import was not as expected');
    $this->callAPISuccess('Contact', 'get', [
      'first_name' => 'Alok',
      'last_name' => 'Patel',
      'organization_name' => 'Agileware',
    ]);
  }

  /**
   * Test that import parser will not fail when same external_identifier found
   * of deleted contact.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportParserWithDeletedContactExternalIdentifier(): void {
    $contactId = $this->individualCreate([
      'external_identifier' => 'ext-1',
    ]);
    $this->callAPISuccess('Contact', 'delete', ['id' => $contactId]);
    [$originalValues, $result] = $this->setUpBaseContact([
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportParserWithUpdateWithoutExternalIdentifier(): void {
    [$originalValues, $result] = $this->setUpBaseContact();
    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];
    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test import parser will update based on a custom rule match.
   *
   * In this case the contact has no external identifier.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportParserWithUpdateWithCustomRule(): void {
    $this->createCustomGroupWithFieldsOfAllTypes();

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => $this->getCustomGroupTable(),
      'rule_weight' => 10,
      'rule_field' => $this->getCustomFieldColumnName('text'),
    ]);

    $extra = [
      $this->getCustomFieldName('select_string') => 'Yellow',
      $this->getCustomFieldName('text') => 'Duplicate',
    ];

    [$originalValues, $result] = $this->setUpBaseContact($extra);

    $contactValues = [
      'first_name' => 'Tim',
      'last_name' => 'Cook',
      'email' => 'tim.cook@apple.com',
      'nick_name' => 'Steve',
      $this->getCustomFieldName('select_string') => 'Red',
      $this->getCustomFieldName('text') => 'Duplicate',
    ];

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [], NULL, $ruleGroup['id']);
    $contactValues['id'] = $result['id'];
    $this->assertEquals('R', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => $this->getCustomFieldName('select_string')]));
    $this->callAPISuccessGetSingle('Contact', $contactValues);

    $foundDupes = CRM_Dedupe_Finder::dupes($ruleGroup['id']);
    $this->assertCount(0, $foundDupes);
  }

  /**
   * Test import parser will update based on a custom rule match.
   *
   * In this case the contact has no external identifier.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportParserWithUpdateWithCustomRuleNoExternalIDMatch(): void {
    $this->createCustomGroupWithFieldsOfAllTypes();

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => $this->getCustomGroupTable(),
      'rule_weight' => 10,
      'rule_field' => $this->getCustomFieldColumnName('text'),
    ]);

    $extra = [
      $this->getCustomFieldName('select_string') => 'Yellow',
      $this->getCustomFieldName('text') => 'Duplicate',
      'external_identifier' => 'ext-2',
    ];

    [$originalValues, $result] = $this->setUpBaseContact($extra);

    $contactValues = [
      'first_name' => 'Tim',
      'last_name' => 'Cook',
      'email' => 'tim.cook@apple.com',
      'nick_name' => 'Steve',
      'external_identifier' => 'ext-1',
      $this->getCustomFieldName('select_string') => 'Red',
      $this->getCustomFieldName('text') => 'Duplicate',
    ];

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [], NULL, $ruleGroup['id']);
    $contactValues['id'] = $result['id'];
    $this->assertEquals('R', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => $this->getCustomFieldName('select_string')]));
    $this->callAPISuccessGetSingle('Contact', $contactValues);

    $foundDupes = CRM_Dedupe_Finder::dupes($ruleGroup['id']);
    $this->assertCount(0, $foundDupes);
  }

  /**
   * Test import parser will update contacts with an external identifier.
   *
   * This is the basic test where the identifier matches the import parameters.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifier(): void {
    [$originalValues, $result] = $this->setUpBaseContact(['external_identifier' => 'windows']);

    $this->assertEquals($result['id'], CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', 'windows', 'id', 'external_identifier', TRUE));
    $this->assertEquals('windows', $result['external_identifier']);

    $originalValues['nick_name'] = 'Old Bill';
    $this->runImport($originalValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $originalValues['id'] = $result['id'];

    $this->assertEquals('Old Bill', $this->callAPISuccessGetValue('Contact', ['id' => $result['id'], 'return' => 'nick_name']));
    $this->callAPISuccessGetSingle('Contact', $originalValues);
  }

  /**
   * Test updating an existing contact with external_identifier match but subtype mismatch.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifierSubtypeMismatch(): void {
    $contactID = $this->individualCreate(['external_identifier' => 'billy', 'first_name' => 'William', 'contact_sub_type' => 'Parent']);
    $this->runImport([
      'external_identifier' => 'billy',
      'nick_name' => 'Old Bill',
      'contact_sub_type' => 'Staff',
    ], CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $contact = $this->callAPISuccessGetSingle('Contact', ['id' => $contactID]);
    $this->assertEquals('Old Bill', $contact['nick_name']);
    $this->assertEquals('William', $contact['first_name']);
    $this->assertEquals('billy', $contact['external_identifier']);
    $this->assertEquals(['Staff'], $contact['contact_sub_type']);
  }

  /**
   * Test updating an existing contact with external_identifier match but subtype mismatch.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifierTypeMismatch(): void {
    $contactID = $this->organizationCreate(['external_identifier' => 'billy']);
    $this->runImport([
      'external_identifier' => 'billy',
      'nick_name' => 'Old Bill',
    ], CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::NO_MATCH);
    $contact = $this->callAPISuccessGetSingle('Contact', ['id' => $contactID]);
    $this->assertEquals('', $contact['nick_name']);
    $this->assertEquals('billy', $contact['external_identifier']);
    $this->assertEquals('Organization', $contact['contact_type']);
  }

  /**
   * Test import parser will fallback to external identifier.
   *
   * In this case no primary match exists (e.g the details are not supplied) so it falls back on external identifier.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-17275
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifierButNoPrimaryMatch(): void {
    [$originalValues, $result] = $this->setUpBaseContact([
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
   * @see https://issues.civicrm.org/jira/browse/CRM-17275
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithContactID(): void {
    [$originalValues, $result] = $this->setUpBaseContact([
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
  public function testImportParserWithUpdateWithNoExternalIdentifier(): void {
    [$originalValues, $result] = $this->setUpBaseContact();
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
    [$contactValues, $result] = $this->setUpBaseContact(['external_identifier' => 'windows']);
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportBillingAddress(): void {
    [$contactValues] = $this->setUpBaseContact();
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
   * Test that the not-really-encouraged way of creating locations via contact.create doesn't mess up primaries.
   */
  public function testContactLocationBlockHandling() {
    $id = $this->individualCreate([
      'phone' => [
        1 => [
          'location_type_id' => 1,
          'phone' => '987654321',
        ],
        2 => [
          'location_type_id' => 2,
          'phone' => '456-7890',
        ],
      ],
      'im' => [
        1 => [
          'location_type_id' => 1,
          'name' => 'bob',
        ],
        2 => [
          'location_type_id' => 2,
          'name' => 'fred',
        ],
      ],
      'openid' => [
        1 => [
          'location_type_id' => 1,
          'openid' => 'bob',
        ],
        2 => [
          'location_type_id' => 2,
          'openid' => 'fred',
        ],
      ],
      'email' => [
        1 => [
          'location_type_id' => 1,
          'email' => 'bob@example.com',
        ],
        2 => [
          'location_type_id' => 2,
          'email' => 'fred@example.com',
        ],
      ],
    ]);
    $phones = $this->callAPISuccess('Phone', 'get', ['contact_id' => $id])['values'];
    $emails = $this->callAPISuccess('Email', 'get', ['contact_id' => $id])['values'];
    $openIDs = $this->callAPISuccess('OpenID', 'get', ['contact_id' => $id])['values'];
    $ims = $this->callAPISuccess('IM', 'get', ['contact_id' => $id])['values'];
    $this->assertCount(2, $phones);
    $this->assertCount(2, $emails);
    $this->assertCount(2, $ims);
    $this->assertCount(2, $openIDs);

    $this->assertLocationValidity();
    $this->callAPISuccess('Contact', 'create', [
      'id' => $id,
      // This is secret code for 'delete this phone'.
      'updateBlankLocInfo' => TRUE,
      'phone' => [
        1 => [
          'id' => key($phones),
        ],
      ],
      'email' => [
        1 => [
          'id' => key($emails),
        ],
      ],
      'im' => [
        1 => [
          'id' => key($ims),
        ],
      ],
      'openid' => [
        1 => [
          'id' => key($openIDs),
        ],
      ],
    ]);
    $this->assertLocationValidity();
    $this->callAPISuccessGetCount('Phone', ['contact_id' => $id], 1);
    $this->callAPISuccessGetCount('Email', ['contact_id' => $id], 1);
    $this->callAPISuccessGetCount('OpenID', ['contact_id' => $id], 1);
    $this->callAPISuccessGetCount('IM', ['contact_id' => $id], 1);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportPrimaryAddress() {
    [$contactValues] = $this->setUpBaseContact();
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
   * Test that address location type id is ignored for dedupe purposes on import.
   *
   * @throws \Exception
   */
  public function testIgnoreLocationTypeId() {
    // Create a rule that matches on last name and street address.
    $rgid = $this->createRuleGroup()['id'];
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $rgid,
      'rule_field' => 'last_name',
      'rule_table' => 'civicrm_contact',
      'rule_weight' => 4,
    ]);
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $rgid,
      'rule_field' => 'street_address',
      'rule_table' => 'civicrm_address',
      'rule_weight' => 4,
    ]);
    // Create a contact with an address of location_type_id 1.
    $contact1Params = [
      'contact_type' => 'Individual',
      'first_name' => 'Original',
      'last_name' => 'Smith',
    ];
    $contact1 = $this->callAPISuccess('Contact', 'create', $contact1Params);
    $this->callAPISuccess('Address', 'create', [
      'contact_id' => $contact1['id'],
      'location_type_id' => 1,
      'street_address' => 'Big Mansion',
    ]);

    $contactValues = [
      'first_name' => 'New',
      'last_name' => 'Smith',
      'street_address' => 'Big Mansion',
    ];

    // We want to import with a location_type_id of 4.
    $importLocationTypeId = '4';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_SKIP, CRM_Import_Parser::DUPLICATE, [0 => NULL, 1 => NULL, 2 => $importLocationTypeId], NULL, $rgid);
    $address = $this->callAPISuccessGetSingle('Address', ['street_address' => 'Big Mansion']);
    $this->assertEquals(1, $address['location_type_id']);
    $contact = $this->callAPISuccessGetSingle('Contact', $contact1Params);
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
    [$contactValues] = $this->setUpBaseContact();
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
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testPrefixLabel(): void {
    $this->callAPISuccess('OptionValue', 'create', ['option_group_id' => 'individual_prefix', 'name' => 'new_one', 'label' => 'special', 'value' => 70]);
    $mapping = [
      ['name' => 'first_name', 'column_number' => 0],
      ['name' => 'last_name', 'column_number' => 1],
      ['name' => 'email', 'column_number' => 2, 'location_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Email', 'location_type_id', 'Home')],
      ['name' => 'prefix_id', 'column_number' => 3],
      ['name' => 'suffix_id', 'column_number' => 4],
    ];
    $mapperInput = [['first_name'], ['last_name'], ['email', CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Email', 'location_type_id', 'Home')], ['prefix_id'], ['suffix_id']];

    $processor = new CRM_Import_ImportProcessor();
    $processor->setMappingFields($mapping);
    $userJobID = $this->getUserJobID(['mapper' => $mapperInput]);
    $processor->setUserJobID($userJobID);
    $importer = $processor->getImporterObject();

    $contactValues = [
      'Bill',
      'Gates',
      'bill.gates@microsoft.com',
      'special',
      'III',
    ];
    $importer->import(CRM_Import_Parser::DUPLICATE_NOCHECK, $contactValues);

    $contact = $this->callAPISuccessGetSingle('Contact', ['first_name' => 'Bill', 'prefix_id' => 'new_one', 'suffix_id' => 'III']);
    $this->assertEquals('special Bill Gates III', $contact['display_name']);
  }

  /**
   * Test that labels work for importing custom data.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCustomDataLabel(): void {
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
   * Test importing in the Preferred Language Field
   *
   * @throws \CRM_Core_Exception
   */
  public function testPreferredLanguageImport() {
    $contactValues = [
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      'email' => 'bill.gates@microsoft.com',
      'nick_name' => 'Billy-boy',
      'preferred_language' => 'English (Australia)',
    ];
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, [NULL, NULL, 'Primary', NULL, NULL]);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportDeceased() {
    [$contactValues] = $this->setUpBaseContact();
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
    [$contactValues] = $this->setUpBaseContact();
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
   * Test importing 2 phones of different types.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportTwoPhonesDifferentTypes(): void {
    $processor = new CRM_Import_ImportProcessor();
    $processor->setUserJobID($this->getUserJobID([
      'mapper' => [['first_name'], ['last_name'], ['email'], ['phone', 1, 2], ['phone', 1, 1]],
    ]));
    $processor->setMappingFields(
      [
        ['name' => 'first_name'],
        ['name' => 'last_name'],
        ['name' => 'email'],
        ['name' => 'phone', 'location_type_id' => 1, 'phone_type_id' => 2],
        ['name' => 'phone', 'location_type_id' => 1, 'phone_type_id' => 1],
      ]
    );
    $importer = $processor->getImporterObject();
    $fields = ['First Name', 'new last name', 'bob@example.com', '1234', '5678'];
    $importer->import(CRM_Import_Parser::DUPLICATE_UPDATE, $fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'new last name']);
    $phones = $this->callAPISuccess('Phone', 'get', ['contact_id' => $contact['id']])['values'];
    $this->assertCount(2, $phones);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportTwoAddressSecondPrimary() {
    [$contactValues] = $this->setUpBaseContact();
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
    [$contactValues] = $this->setUpBaseContact(['external_identifier' => 'android']);
    $contactValues['email'] = 'melinda.gates@microsoft.com';
    $contactValues['phone'] = '98765';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['city'] = 'Big City';
    $contactID = $this->callAPISuccessGetValue('Contact', ['external_identifier' => 'android', 'return' => 'id']);
    $originalAddress = $this->callAPISuccess('Address', 'create', ['location_type_id' => 2, 'street_address' => 'small house', 'contact_id' => $contactID]);
    $originalPhone = $this->callAPISuccess('phone', 'create', ['location_type_id' => 2, 'phone' => '1234', 'contact_id' => $contactID, 'phone_type_id' => 1]);
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, []);
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
  public function testCustomFieldValidation(): void {
    $errorMessage = [];
    $customGroup = $this->customGroupCreate([
      'extends' => 'Contact',
      'title' => 'ABC',
    ]);
    $customField = $this->customFieldOptionValueCreate($customGroup, 'fieldABC', ['html_type' => 'Select', 'serialize' => 1]);
    $params = [
      'custom_' . $customField['id'] => 'Label1|Label2',
    ];
    CRM_Contact_Import_Parser_Contact::isErrorInCustomData($params, $errorMessage);
    $this->assertEquals([], $errorMessage);
  }

  /**
   * Test the import validation.
   *
   * @dataProvider validateDataProvider
   *
   * @param string $csv
   * @param array $mapper
   * @param string $expectedError
   * @param array $submittedValues
   *
   * @throws \API_Exception
   */
  public function testValidation(string $csv, array $mapper, string $expectedError = '', $submittedValues = []): void {
    try {
      $this->validateCSV($csv, $mapper, $submittedValues);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertSame($expectedError, $e->getMessage());
      return;
    }
    if ($expectedError) {
      $this->fail('expected error :' . $expectedError);
    }
  }

  /**
   * Get combinations to test for validation.
   *
   * @return array[]
   */
  public function validateDataProvider(): array {
    return [
      'individual_required' => [
        'csv' => 'individual_invalid_missing_name.csv',
        'mapper' => [['last_name']],
        'expected_error' => 'Missing required fields: First Name OR Email Address',
      ],
      'individual_related_required_met' => [
        'csv' => 'individual_valid_with_related_email.csv',
        'mapper' => [['first_name'], ['last_name'], ['1_a_b', 'email']],
        'expected_error' => '',
      ],
      'individual_related_required_not_met' => [
        'csv' => 'individual_invalid_with_related_phone.csv',
        'mapper' => [['first_name'], ['last_name'], ['1_a_b', 'phone', 1, 2]],
        'expected_error' => '(Child of) Missing required fields: First Name and Last Name OR Email Address OR External Identifier',
      ],
      'individual_bad_email' => [
        'csv' => 'individual_invalid_email.csv',
        'mapper' => [['email', 1], ['first_name'], ['last_name']],
        'expected_error' => 'Invalid value for field(s) : email',
      ],
      'individual_related_bad_email' => [
        'csv' => 'individual_invalid_related_email.csv',
        'mapper' => [['1_a_b', 'email', 1], ['first_name'], ['last_name']],
        'expected_error' => 'Invalid value for field(s) : email',
      ],
      'individual_invalid_external_identifier_only' => [
        // External identifier is only enough in upgrade mode.
        'csv' => 'individual_invalid_external_identifier_only.csv',
        'mapper' => [['external_identifier'], ['gender_id']],
        'expected_error' => 'Missing required fields: First Name and Last Name OR Email Address',
      ],
      'individual_invalid_external_identifier_only_update_mode' => [
        // External identifier only enough in upgrade mode, so no error here.
        'csv' => 'individual_invalid_external_identifier_only.csv',
        'mapper' => [['external_identifier'], ['gender_id']],
        'expected_error' => '',
        'submitted_values' => ['onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE],
      ],
      'organization_email_no_organization_name' => [
        // Email is only enough in upgrade mode.
        'csv' => 'organization_email_no_organization_name.csv',
        'mapper' => [['email'], ['phone', 1, 1]],
        'expected_error' => 'Missing required fields: Organization Name',
        'submitted_values' => ['onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP, 'contactType' => CRM_Import_Parser::CONTACT_ORGANIZATION],
      ],
      'organization_email_no_organization_name_update_mode' => [
        // Email is enough in upgrade mode (at least to pass validate).
        'csv' => 'organization_email_no_organization_name.csv',
        'mapper' => [['email'], ['phone', 1, 1]],
        'expected_error' => '',
        'submitted_values' => ['onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE, 'contactType' => CRM_Import_Parser::CONTACT_ORGANIZATION],
      ],
    ];
  }

  /**
   * Test the import.
   *
   * @dataProvider importDataProvider
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testImport($csv, $mapper, $expectedError, $expectedOutcomes = []): void {
    try {
      $this->importCSV($csv, $mapper);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertSame($expectedError, $e->getMessage());
      return;
    }
    if ($expectedError) {
      $this->fail('expected error :' . $expectedError);
    }
    $dataSource = new CRM_Import_DataSource_CSV(UserJob::get(FALSE)->setSelect(['id'])->execute()->first()['id']);
    foreach ($expectedOutcomes as $outcome => $count) {
      $this->assertEquals($dataSource->getRowCount([$outcome]), $count);
    }
  }

  /**
   * Get combinations to test for validation.
   *
   * @return array[]
   */
  public function importDataProvider(): array {
    return [
      'individual_invalid_sub_type' => [
        'csv' => 'individual_invalid_contact_sub_type.csv',
        'mapper' => [['first_name'], ['last_name'], ['contact_sub_type']],
        'expected_error' => '',
        'expected_outcomes' => [CRM_Import_Parser::NO_MATCH => 1],
      ],
    ];
  }

  /**
   * Test the handling of validation when importing genders.
   *
   * If it's not gonna import it should fail at the validation stage...
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testImportGenders(): void {
    $mapper = [
      ['first_name'],
      ['last_name'],
      ['gender_id'],
      ['1_a_b', 'first_name'],
      ['1_a_b', 'last_name'],
      ['1_a_b', 'gender_id'],
      ['do_not_import'],
    ];
    $csv = 'individual_genders.csv';
    /* @var CRM_Import_DataSource_CSV $dataSource */
    /* @var \CRM_Contact_Import_Parser_Contact $parser */
    [$dataSource, $parser] = $this->getDataSourceAndParser($csv, $mapper, []);
    while ($values = $dataSource->getRow()) {
      try {
        $parser->validateValues(array_values($values));
        if ($values['expected'] !== 'Valid') {
          $this->fail($values['gender'] . ' should not have been valid');
        }
      }
      catch (CRM_Core_Exception $e) {
        if ($values['expected'] !== 'Invalid') {
          $this->fail($values['gender'] . ' should have been valid');
        }
      }
    }

    $this->importCSV($csv, $mapper);
    $contacts = Contact::get()
      ->addWhere('first_name', '=', 'Madame')
      ->addSelect('gender_id:name')->execute();
    foreach ($contacts as $contact) {
      $this->assertEquals('Female', $contact['gender_id:name']);
    }
    $this->assertCount(8, $contacts);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testImportAmbiguousStateCountry(): void {
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => 1228]);
    $countries = CRM_Core_PseudoConstant::country(FALSE, FALSE);
    $this->callAPISuccess('Setting', 'create', ['countryLimit' => [array_search('United States', $countries), array_search('Guyana', $countries), array_search('Netherlands', $countries)]]);
    $this->callAPISuccess('Setting', 'create', ['provinceLimit' => [array_search('United States', $countries), array_search('Guyana', $countries), array_search('Netherlands', $countries)]]);
    $mapper = [0 => NULL, 1 => NULL, 2 => 'Primary', 3 => NULL];
    [$contactValues] = $this->setUpBaseContact();
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
   * Test importing fields with various options.
   *
   * Ensure we can import multiple preferred_communication_methods, single
   * gender, and single preferred language using both labels and values.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportFieldsWithVariousOptions(): void {
    $processor = new CRM_Import_ImportProcessor();
    $processor->setUserJobID($this->getUserJobID([
      'mapper' => [['first_name'], ['last_name'], ['preferred_communication_method'], ['gender_id'], ['preferred_language']],
    ]));
    $processor->setMappingFields(
      [
        ['name' => 'first_name'],
        ['name' => 'last_name'],
        ['name' => 'preferred_communication_method'],
        ['name' => 'gender_id'],
        ['name' => 'preferred_language'],
      ]
    );
    $importer = $processor->getImporterObject();
    $fields = ['Ima', 'Texter', 'SMS,Phone', 'Female', 'Danish'];
    $importer->import(CRM_Import_Parser::DUPLICATE_NOCHECK, $fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'Texter']);

    $this->assertEquals([4, 1], $contact['preferred_communication_method'], "Import multiple preferred communication methods using labels.");
    $this->assertEquals(1, $contact['gender_id'], "Import gender with label.");
    $this->assertEquals('da_DK', $contact['preferred_language'], "Import preferred language with label.");

    $importer = $processor->getImporterObject();
    $fields = ['Ima', 'Texter', "4,1", "1", "da_DK"];
    $importer->import(CRM_Import_Parser::DUPLICATE_NOCHECK, $fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'Texter']);

    $this->assertEquals([4, 1], $contact['preferred_communication_method'], "Import multiple preferred communication methods using values.");
    $this->assertEquals(1, $contact['gender_id'], "Import gender with id.");
    $this->assertEquals('da_DK', $contact['preferred_language'], "Import preferred language with value.");
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
   * @param int|null $ruleGroupId
   *   To test against a specific dedupe rule group, pass its ID as this argument.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function runImport(array $originalValues, $onDuplicateAction, $expectedResult, $mapperLocType = [], $fields = NULL, int $ruleGroupId = NULL): void {
    if (!$fields) {
      $fields = array_keys($originalValues);
    }
    $values = array_values($originalValues);
    $mapper = [];
    foreach ($fields as $index => $field) {
      $mapper[] = [$field, $mapperLocType[$index] ?? NULL, $field === 'phone' ? 1 : NULL];
    }
    $userJobID = $this->getUserJobID(['mapper' => $mapper, 'onDuplicate' => $onDuplicateAction]);
    $parser = new CRM_Contact_Import_Parser_Contact($fields, $mapperLocType);
    $parser->setUserJobID($userJobID);
    $parser->_dedupeRuleGroupID = $ruleGroupId;
    $parser->init();
    $this->assertEquals($expectedResult, $parser->import($onDuplicateAction, $values), 'Return code from parser import was not as expected');
  }

  /**
   * @param string $csv
   * @param array $mapper
   * @param array $submittedValues
   *
   * @return array
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getDataSourceAndParser(string $csv, array $mapper, array $submittedValues): array {
    $userJobID = $this->getUserJobID(array_merge([
      'uploadFile' => ['name' => __DIR__ . '/../Form/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'mapper' => $mapper,
      'dataSource' => 'CRM_Import_DataSource_CSV',
    ], $submittedValues));

    $dataSource = new CRM_Import_DataSource_CSV($userJobID);
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($userJobID);
    $parser->init();
    return [$dataSource, $parser];
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
   * Test mapping fields within the Parser class.
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testMapFields(): void {
    $parser = new CRM_Contact_Import_Parser_Contact(
      // Array of field names
      ['first_name', 'phone', NULL, 'im', NULL],
      // Array of location types, ie columns 2 & 4 have types.
      [NULL, 1, NULL, 1, NULL],
      // Array of phone types
      [NULL, 1, NULL, NULL, NULL],
      // Array of im provider types
      [NULL, NULL, NULL, 1, NULL],
      // Array of filled in relationship values.
      [NULL, NULL, '5_a_b', NULL, '5_a_b'],
      // Array of the contact type to map to - note this can be determined from ^^
      [NULL, NULL, 'Organization', NULL, 'Organization'],
      // Related contact field names
      [NULL, NULL, 'url', NULL, 'phone'],
      // Related contact location types
      [NULL, NULL, NULL, NULL, 1],
      // Related contact phone types
      [NULL, NULL, NULL, NULL, 1],
      // Related contact im provider types
      [NULL, NULL, NULL, NULL, NULL],
      // Website types
      [NULL, NULL, NULL, NULL, NULL],
      // Related contact website types
      [NULL, NULL, 1, NULL, NULL]
    );
    $parser->setUserJobID($this->getUserJobID([
      'mapper' => [
        ['first_name'],
        ['phone', 1, 1],
        ['5_a_b', 'url', 1],
        ['im', 1, 1],
        ['5_a_b', 'phone', 1, 1],
      ],
    ]));
    $parser->init();
    $params = $parser->getMappedRow(
      ['Bob', '123', 'https://example.org', 'my-handle', '456']
    );
    $this->assertEquals([
      'first_name' => 'Bob',
      'phone' => [
        [
          'phone' => '123',
          'location_type_id' => 1,
          'phone_type_id' => 1,
        ],
      ],
      '5_a_b' => [
        'contact_type' => 'Organization',
        'url' =>
          [

            [
              'url' => 'https://example.org',
              'website_type_id' => 1,
            ],
          ],
        'phone' =>
          [
            [
              'phone' => '456',
              'location_type_id' => 1,
              'phone_type_id' => 1,
            ],
          ],
      ],
      'im' =>
        [

          [
            'im' => 'my-handle',
            'location_type_id' => 1,
            'provider_id' => 1,
          ],
        ],
      'contact_type' => 'Individual',
    ], $params);
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

  /**
   * @return mixed
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getUserJobID($submittedValues = []) {
    $userJobID = UserJob::create()->setValues([
      'metadata' => [
        'submitted_values' => array_merge([
          'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
          'contactSubType' => '',
          'doGeocodeAddress' => 0,
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'sqlQuery' => 'SELECT first_name FROM civicrm_contact',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
        ], $submittedValues),
      ],
      'status_id:name' => 'draft',
      'type_id:name' => 'contact_import',
    ])->execute()->first()['id'];
    if ($submittedValues['dataSource'] ?? NULL === 'CRM_Import_DataSource') {
      $dataSource = new CRM_Import_DataSource_CSV($userJobID);
    }
    else {
      $dataSource = new CRM_Import_DataSource_SQL($userJobID);
    }
    $dataSource->initialize();
    return $userJobID;
  }

  /**
   * Validate the csv file values.
   *
   * @param string $csv Name of csv file.
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   * @param array $submittedValues
   *   Any submitted values overrides.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  protected function validateCSV(string $csv, array $mapper, $submittedValues): void {
    [$dataSource, $parser] = $this->getDataSourceAndParser($csv, $mapper, $submittedValues);
    $parser->validateValues(array_values($dataSource->getRow()));
  }

  /**
   * Import the csv file values.
   *
   * This function uses a flow that mimics the UI flow.
   *
   * @param string $csv Name of csv file.
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   * @param array $submittedValues
   */
  protected function importCSV(string $csv, array $mapper, array $submittedValues = []): void {
    $submittedValues = array_merge([
      'uploadFile' => ['name' => __DIR__ . '/../Form/data/' . $csv],
      'skipColumnHeader' => TRUE,
      'fieldSeparator' => ',',
      'contactType' => CRM_Import_Parser::CONTACT_INDIVIDUAL,
      'mapper' => $mapper,
      'dataSource' => 'CRM_Import_DataSource_CSV',
      'file' => ['name' => $csv],
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
      'groups' => [],
    ], $submittedValues);
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource', $submittedValues);
    $form->buildForm();
    $form->postProcess();
    $userJobID = $form->getUserJobID();
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField', $submittedValues);
    $form->setUserJobID($userJobID);
    $form->buildForm();
    $form->postProcess();
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_Preview', $submittedValues);
    $form->setUserJobID($userJobID);
    $form->buildForm();
    $form->postProcess();
  }

}
