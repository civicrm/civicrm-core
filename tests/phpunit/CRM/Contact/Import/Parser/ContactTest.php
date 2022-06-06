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

use Civi\Api4\Address;
use Civi\Api4\Contact;
use Civi\Api4\ContactType;
use Civi\Api4\Email;
use Civi\Api4\IM;
use Civi\Api4\LocationType;
use Civi\Api4\OpenID;
use Civi\Api4\Phone;
use Civi\Api4\RelationshipType;
use Civi\Api4\UserJob;
use Civi\Api4\Website;

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
   * Array of existing relationships.
   *
   * @var array
   */
  private $relationships = [];

  /**
   * User Job ID.
   *
   * @var int
   */
  private $userJobID;

  /**
   * Tear down after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_address', 'civicrm_phone', 'civicrm_openid', 'civicrm_email', 'civicrm_user_job', 'civicrm_relationship', 'civicrm_im', 'civicrm_website', 'civicrm_queue', 'civicrm_queue_item'], TRUE);
    RelationshipType::delete()->addWhere('name_a_b', '=', 'Dad to')->execute();
    ContactType::delete()->addWhere('name', '=', 'baby')->execute();
    parent::tearDown();
  }

  /**
   * Test that import parser will add contact with employee of relationship.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ]);

    $parser = new CRM_Contact_Import_Parser_Contact($fields);
    $parser->setUserJobID($userJobID);
    $parser->init();

    $this->assertEquals(CRM_Import_Parser::VALID, $parser->import($values), 'Return code from parser import was not as expected');
    $this->callAPISuccessGetSingle('Contact', [
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
   * The subtype is updated, as there is no conflicting contact data.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithExternalIdentifierSubtypeChange(): void {
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
   * The subtype is not updated, as there is conflicting contact data.
   *
   * @throws \Exception
   */
  public function testImportParserUpdateWithExternalIdentifierSubtypeChangeFail(): void {
    $contactID = $this->individualCreate(['external_identifier' => 'billy', 'first_name' => 'William', 'contact_sub_type' => 'Parent']);
    $this->addChild($contactID);

    $this->runImport([
      'external_identifier' => 'billy',
      'nick_name' => 'Old Bill',
      'contact_sub_type' => 'Staff',
    ], CRM_Import_Parser::DUPLICATE_UPDATE, FALSE);
    $contact = $this->callAPISuccessGetSingle('Contact', ['id' => $contactID]);
    $this->assertEquals('', $contact['nick_name']);
    $this->assertEquals(['Parent'], $contact['contact_sub_type']);
  }

  /**
   * Test updating an existing contact with external_identifier match but subtype mismatch.
   *
   * @throws \Exception
   */
  public function testImportParserWithUpdateWithTypeMismatch(): void {
    $contactID = $this->organizationCreate(['external_identifier' => 'billy']);
    $this->runImport([
      'external_identifier' => 'billy',
      'nick_name' => 'Old Bill',
    ], CRM_Import_Parser::DUPLICATE_UPDATE, FALSE);
    $contact = $this->callAPISuccessGetSingle('Contact', ['id' => $contactID]);
    $this->assertEquals('', $contact['nick_name']);
    $this->assertEquals('billy', $contact['external_identifier']);
    $this->assertEquals('Organization', $contact['contact_type']);

    $this->runImport([
      'id' => $contactID,
      'nick_name' => 'Old Bill',
    ], CRM_Import_Parser::DUPLICATE_UPDATE, FALSE);
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
    $this->runImport($updateValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
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
  public function testImportParserWithUpdateWithChangedExternalIdentifier(): void {
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
    $mapper = $this->getFieldMappingFromInput($contactValues, 2);
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $mapper);
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
  public function testContactLocationBlockHandling(): void {
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
  public function testImportPrimaryAddress(): void {
    [$contactValues] = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;
    $mapper = $this->getFieldMappingFromInput($contactValues);
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $mapper);
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
  public function testIgnoreLocationTypeId(): void {
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
    $fieldMapping = $this->getFieldMappingFromInput($contactValues, 4);
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_SKIP, CRM_Import_Parser::DUPLICATE, $fieldMapping, NULL, $rgid);
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
  public function testAddressWithCustomData(): void {
    $ids = $this->entityCustomGroupWithSingleFieldCreate('Address', 'AddressTest.php');
    [$contactValues] = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['custom_' . $ids['custom_field_id']] = 'Update';
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
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
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
    $this->callAPISuccessGetSingle('Contact', $contactValues);
  }

  /**
   * Test greeting imports.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testGreetings(): void {
    $contactValues = [
      'first_name' => 'Bill',
      'last_name' => 'Gates',
      // id = 2
      'email_greeting' => 'Dear {contact.prefix_id:label} {contact.first_name} {contact.last_name}',
      // id = 3
      'postal_greeting' => 'Dear {contact.prefix_id:label} {contact.last_name}',
      // id = 1
      'addressee' => '{contact.prefix_id:label}{ }{contact.first_name}{ }{contact.middle_name}{ }{contact.last_name}{ }{contact.suffix_id:label}',
      5 => 1,
    ];
    $userJobID = $this->getUserJobID([
      'mapper' => [['first_name'], ['last_name'], ['email_greeting'], ['postal_greeting'], ['addressee']],
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ]);
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($userJobID);
    $values = array_values($contactValues);
    $parser->import($values);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', 'Gates')->addSelect('email_greeting_id', 'postal_greeting_id', 'addressee_id')->execute()->first();
    $this->assertEquals(2, $contact['email_greeting_id']);
    $this->assertEquals(3, $contact['postal_greeting_id']);
    $this->assertEquals(1, $contact['addressee_id']);

    Contact::delete()->addWhere('id', '=', $contact['id'])->setUseTrash(TRUE)->execute();

    // Now try again with numbers.
    $values[2] = 2;
    $values[3] = 3;
    $values[4] = 1;
    $parser->import($values);
    $contact = Contact::get(FALSE)->addWhere('last_name', '=', 'Gates')->addSelect('email_greeting_id', 'postal_greeting_id', 'addressee_id')->execute()->first();
    $this->assertEquals(2, $contact['email_greeting_id']);
    $this->assertEquals(3, $contact['postal_greeting_id']);
    $this->assertEquals(1, $contact['addressee_id']);

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
    $userJobID = $this->getUserJobID(['mapper' => $mapperInput, 'onDuplicate' => CRM_Import_Parser::DUPLICATE_NOCHECK]);
    $processor->setUserJobID($userJobID);
    $importer = $processor->getImporterObject();

    $contactValues = [
      'Bill',
      'Gates',
      'bill.gates@microsoft.com',
      'special',
      'III',
    ];
    $importer->import($contactValues);

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
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
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
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
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
    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID);
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
  public function testImportTwoAddressFirstPrimary(): void {
    [$contactValues] = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';

    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;

    $fieldMapping = $this->getFieldMappingFromInput($contactValues);
    $contactValues['street_address_2'] = 'Teeny Mansion';
    $fieldMapping[] = ['name' => 'street_address', 'location_type_id' => 3];
    $contactValues['phone_2'] = 4444;
    $fieldMapping[] = ['name' => 'phone', 'location_type_id' => 3, 'phone_type_id' => 1];

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $fieldMapping);
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
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
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
    $importer->import($fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'new last name']);
    $phones = $this->callAPISuccess('Phone', 'get', ['contact_id' => $contact['id']])['values'];
    $this->assertCount(2, $phones);
  }

  /**
   * Test that the import parser adds the address to the primary location.
   *
   * @throws \Exception
   */
  public function testImportTwoAddressSecondPrimary(): void {
    [$contactValues] = $this->setUpBaseContact();
    $contactValues['nick_name'] = 'Old Bill';
    $contactValues['external_identifier'] = 'android';
    $contactValues['street_address'] = 'Big Mansion';
    $contactValues['phone'] = 12334;

    $fieldMapping = $this->getFieldMappingFromInput($contactValues, 3);

    $contactValues['street_address_2'] = 'Teeny Mansion';
    $fieldMapping[] = ['name' => 'street_address', 'location_type_id' => 'Primary'];
    $contactValues['phone_2'] = 4444;
    $fieldMapping[] = ['name' => 'phone', 'location_type_id' => 'Primary', 'phone_type_id' => 1];

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $fieldMapping);
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
  public function testImportPrimaryAddressUpdate(): void {
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
    $errorMessage = '';
    $customGroup = $this->customGroupCreate([
      'extends' => 'Contact',
      'title' => 'ABC',
    ]);
    $customField = $this->customFieldOptionValueCreate($customGroup, 'fieldABC', ['html_type' => 'Select', 'serialize' => 1]);
    $params = [
      'custom_' . $customField['id'] => 'Label1|Label2',
    ];
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->isErrorInCustomData($params, $errorMessage);
    $this->assertEquals(NULL, $errorMessage);
  }

  /**
   * Test the import validation.
   *
   * @dataProvider validateDataProvider
   *
   * @param string $csv
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   *   {@see \CRM_Contact_Import_Parser_Contact::getMappingFieldFromMapperInput}
   * @param string $expectedError
   * @param array $submittedValues
   *
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
        'expected_error' => 'Invalid value for field(s) : Email',
      ],
      'individual_related_bad_email' => [
        'csv' => 'individual_invalid_related_email.csv',
        'mapper' => [['1_a_b', 'email', 1], ['first_name'], ['last_name']],
        'expected_error' => 'Invalid value for field(s) : (Child of) Email',
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
  public function testImport($csv, $mapper, $expectedError, $expectedOutcomes = [], $submittedValues = []): void {
    $this->importCSV($csv, $mapper, $submittedValues);
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
        'expected_outcomes' => [CRM_Import_Parser::ERROR => 1],
      ],
      //Record duplicates multiple contacts
      'organization_multiple_duplicates_invalid' => [
        'csv' => 'organization_multiple_duplicates_invalid.csv',
        'mapper' => [['organization_name'], ['email']],
        'expected_error' => '',
        'expected_outcomes' => [
          CRM_Import_Parser::VALID => 2,
          CRM_Import_Parser::ERROR => 1,
        ],
        'submitted_values' => [
          'contactType' => CRM_Import_Parser::CONTACT_ORGANIZATION,
        ],
      ],
      //Matching this contact based on the de-dupe rule would cause an external ID conflict
      'individual_invalid_external_identifier_email_mismatch' => [
        'csv' => 'individual_invalid_external_identifier_email_mismatch.csv',
        'mapper' => [['first_name'], ['last_name'], ['email'], ['external_identifier']],
        'expected_error' => '',
        'expected_outcomes' => [
          CRM_Import_Parser::VALID => 2,
          CRM_Import_Parser::ERROR => 1,
        ],
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
    $this->validateMultiRowCsv($csv, $mapper, 'gender');

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
   * Test importing state country & county.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testImportCountryStateCounty(): void {
    $childKey = $this->getRelationships()['Child of']['id'] . '_a_b';
    $addressCustomGroupID = $this->createCustomGroup(['extends' => 'Address', 'name' => 'Address']);
    $contactCustomGroupID = $this->createCustomGroup(['extends' => 'Contact', 'name' => 'Contact']);
    $addressCustomFieldID = $this->createCountryCustomField(['custom_group_id' => $addressCustomGroupID])['id'];
    $contactCustomFieldID = $this->createMultiCountryCustomField(['custom_group_id' => $contactCustomGroupID])['id'];
    $contactStateCustomFieldID = $this->createStateCustomField(['custom_group_id' => $contactCustomGroupID])['id'];
    $customField = 'custom_' . $contactCustomFieldID;
    $addressCustomField = 'custom_' . $addressCustomFieldID;
    $contactStateCustomField = 'custom_' . $contactStateCustomFieldID;

    $mapper = [
      ['first_name'],
      ['last_name'],
      ['email'],
      ['county'],
      ['country'],
      ['state_province'],
      [$contactStateCustomField],
      [$customField],
      [$addressCustomField],
      // [$addressCustomField, 'state_province'],
      ['do_not_import'],
      [$childKey, 'first_name'],
      [$childKey, 'last_name'],
      [$childKey, 'email'],
      [$childKey, 'state_province'],
      [$childKey, 'country'],
      [$childKey, 'county'],
      // [$childKey, $addressCustomField, 'country'],
      ['do_not_import'],
      // [$childKey, $addressCustomField, 'state_province'],
      ['do_not_import'],
      // [$childKey, $customField, 'country'],
      ['do_not_import'],
      // [$childKey, $customField, 'state_province'],
      ['do_not_import'],
      // mapField Form expects all fields to be mapped.
      ['do_not_import'],
      ['do_not_import'],
    ];
    $csv = 'individual_country_state_county_with_related.csv';
    $this->validateMultiRowCsv($csv, $mapper, 'error_value');

    $this->importCSV($csv, $mapper);
    $contacts = $this->getImportedContacts();
    foreach ($contacts as $contact) {
      $this->assertEquals(1013, $contact['address'][0]['country_id']);
      $this->assertEquals(1640, $contact['address'][0]['state_province_id']);
    }
    $this->assertCount(2, $contacts);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    $dataSource->setOffset(4);
    $dataSource->setLimit(1);
    $row = $dataSource->getRow();
    $this->assertEquals(1, $row['_related_contact_matched']);
  }

  /**
   * Test date validation.
   *
   * @dataProvider dateDataProvider
   *
   * @param string $csv
   * @param int $dateType
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testValidateDateData($csv, $dateType): void {
    $addressCustomGroupID = $this->createCustomGroup(['extends' => 'Address', 'name' => 'Address']);
    $contactCustomGroupID = $this->createCustomGroup(['extends' => 'Contact', 'name' => 'Contact']);
    $addressCustomFieldID = $this->createDateCustomField(['custom_group_id' => $addressCustomGroupID])['id'];
    $contactCustomFieldID = $this->createDateCustomField(['custom_group_id' => $contactCustomGroupID])['id'];
    $mapper = [
      ['first_name'],
      ['last_name'],
      ['birth_date'],
      ['deceased_date'],
      ['custom_' . $contactCustomFieldID],
      ['custom_' . $addressCustomFieldID, 1],
      ['street_address', 1],
      ['do_not_import'],
    ];
    // Date types should be picked up from submitted values but still some clean up to do.
    CRM_Core_Session::singleton()->set('dateTypes', $dateType);
    $this->validateMultiRowCsv($csv, $mapper, 'custom_date_one', ['dateFormats' => $dateType]);
    $fields = [
      'contact_id.birth_date',
      'contact_id.deceased_date',
      'contact_id.is_deceased',
      'contact_id.custom_' . $contactCustomFieldID,
      $addressCustomFieldID,
    ];
    $contacts = Address::get()->addWhere('contact_id.first_name', '=', 'Joe')->setSelect($fields)->execute();
    foreach ($contacts as $contact) {
      foreach ($fields as $field) {
        if ($field === 'contact_is_deceased') {
          $this->assertTrue($contact[$field]);
        }
        else {
          $this->assertEquals('2008-09-01', $contact[$field]);
        }
      }
    }
  }

  /**
   * @throws \API_Exception
   */
  public function testImportContactSubTypes(): void {
    ContactType::create()->setValues([
      'name' => 'baby',
      'label' => 'Infant',
      'parent_id:name' => 'Individual',
    ])->execute();
    $mapper = [
      ['first_name'],
      ['last_name'],
      ['5_a_b', 'organization_name'],
      ['contact_sub_type'],
      ['5_a_b', 'contact_sub_type'],
      // mapField Form expects all fields to be mapped.
      ['do_not_import'],
      ['do_not_import'],
      ['do_not_import'],
    ];
    $csv = 'individual_contact_sub_types.csv';
    $field = 'contact_sub_type';

    $this->validateMultiRowCsv($csv, $mapper, $field);
    $this->importCSV($csv, $mapper);
    $contacts = Contact::get()
      ->addWhere('last_name', '=', 'Green')
      ->addSelect('contact_sub_type:name')->execute();
    foreach ($contacts as $contact) {
      $this->assertEquals(['baby'], $contact['contact_sub_type:name']);
    }
    $this->assertCount(3, $contacts);
  }

  /**
   * Data provider for date tests.
   *
   * @return array[]
   */
  public function dateDataProvider(): array {
    return [
      'type_1' => ['csv' => 'individual_dates_type1.csv', 'dateType' => CRM_Core_Form_Date::DATE_yyyy_mm_dd],
      'type_2' => ['csv' => 'individual_dates_type2.csv', 'dateType' => CRM_Core_Form_Date::DATE_mm_dd_yy],
      'type_4' => ['csv' => 'individual_dates_type4.csv', 'dateType' => CRM_Core_Form_Date::DATE_mm_dd_yyyy],
      'type_8' => ['csv' => 'individual_dates_type8.csv', 'dateType' => CRM_Core_Form_Date::DATE_Month_dd_yyyy],
      'type_16' => ['csv' => 'individual_dates_type16.csv', 'dateType' => CRM_Core_Form_Date::DATE_dd_mon_yy],
      'type_32' => ['csv' => 'individual_dates_type32.csv', 'dateType' => CRM_Core_Form_Date::DATE_dd_mm_yyyy],
    ];
  }

  /**
   * Test location importing, including for related contacts.
   *
   * @throws \API_Exception
   */
  public function testImportLocations(): void {
    $csv = 'individual_locations_with_related.csv';
    $relationships = $this->getRelationships();

    $childKey = $relationships['Child of']['id'] . '_a_b';
    $siblingKey = $relationships['Sibling of']['id'] . '_a_b';
    $employeeKey = $relationships['Employee of']['id'] . '_a_b';
    $locations = LocationType::get()->execute()->indexBy('name');
    $phoneTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Phone');
    $mobileTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Phone', 'phone_type_id', 'Mobile');
    $skypeTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_IM', 'provider_id', 'Skype');
    $mainWebsiteTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Website', 'website_type_id', 'Main');
    $linkedInTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Website', 'website_type_id', 'LinkedIn');
    $homeID = $locations['Home']['id'];
    $workID = $locations['Work']['id'];
    $mapper = [
      ['first_name'],
      ['last_name'],
      ['birth_date'],
      ['street_address', $homeID],
      ['city', $homeID],
      ['postal_code', $homeID],
      ['country', $homeID],
      ['state_province', $homeID],
      // No location type ID means 'Primary'
      ['email'],
      ['signature_text'],
      ['im', NULL, $skypeTypeID],
      ['url', $mainWebsiteTypeID],
      ['phone', $homeID, $phoneTypeID],
      ['phone_ext', $homeID, $phoneTypeID],
      [$childKey, 'first_name'],
      [$childKey, 'last_name'],
      [$childKey, 'street_address'],
      [$childKey, 'city'],
      [$childKey, 'country'],
      [$childKey, 'state_province'],
      [$childKey, 'email', $homeID],
      [$childKey, 'signature_text', $homeID],
      [$childKey, 'im', $homeID, $skypeTypeID],
      [$childKey, 'url', $linkedInTypeID],
      // Same location type, different phone typ in these phones
      [$childKey, 'phone', $homeID, $phoneTypeID],
      [$childKey, 'phone_ext', $homeID, $phoneTypeID],
      [$childKey, 'phone', $homeID, $mobileTypeID],
      [$childKey, 'phone_ext', $homeID, $mobileTypeID],
      [$siblingKey, 'street_address', $homeID],
      [$siblingKey, 'city', $homeID],
      [$siblingKey, 'country', $homeID],
      [$siblingKey, 'state_province', $homeID],
      [$siblingKey, 'email', $homeID],
      [$siblingKey, 'signature_text', $homeID],
      [$siblingKey, 'im', $homeID, $skypeTypeID],
      // The 2 is website_type_id (yes, small hard-coding cheat)
      [$siblingKey, 'url', $linkedInTypeID],
      [$siblingKey, 'phone', $workID, $phoneTypeID],
      [$siblingKey, 'phone_ext', $workID, $phoneTypeID],
      [$employeeKey, 'organization_name'],
      [$employeeKey, 'url', $mainWebsiteTypeID],
      [$employeeKey, 'email', $homeID],
      [$employeeKey, 'do_not_import'],
      [$employeeKey, 'street_address', $homeID],
      [$employeeKey, 'supplemental_address_1', $homeID],
      [$employeeKey, 'do_not_import'],
      // Second website, different type.
      [$employeeKey, 'url', $linkedInTypeID],
      ['openid'],
    ];
    $this->validateCSV($csv, $mapper);

    $this->importCSV($csv, $mapper);
    $contacts = $this->getImportedContacts();
    $this->assertCount(4, $contacts);
    $this->assertCount(1, $contacts['Susie Jones']['phone']);
    $this->assertEquals('123', $contacts['Susie Jones']['phone'][0]['phone_ext']);
    $this->assertCount(2, $contacts['Mum Jones']['phone']);
    $this->assertCount(1, $contacts['sis@example.com']['phone']);
    $this->assertCount(0, $contacts['Soccer Superstars']['phone']);
    $this->assertCount(1, $contacts['Susie Jones']['website']);
    $this->assertCount(1, $contacts['Mum Jones']['website']);
    $this->assertCount(0, $contacts['sis@example.com']['website']);
    $this->assertCount(2, $contacts['Soccer Superstars']['website']);
    $this->assertCount(1, $contacts['Susie Jones']['email']);
    $this->assertEquals('Regards', $contacts['Susie Jones']['email'][0]['signature_text']);
    $this->assertCount(1, $contacts['Mum Jones']['email']);
    $this->assertCount(1, $contacts['sis@example.com']['email']);
    $this->assertCount(1, $contacts['Soccer Superstars']['email']);
    $this->assertCount(1, $contacts['Susie Jones']['im']);
    $this->assertCount(1, $contacts['Mum Jones']['im']);
    $this->assertCount(0, $contacts['sis@example.com']['im']);
    $this->assertCount(0, $contacts['Soccer Superstars']['im']);
    $this->assertCount(1, $contacts['Susie Jones']['address']);
    $this->assertCount(1, $contacts['Mum Jones']['address']);
    $this->assertCount(1, $contacts['sis@example.com']['address']);
    $this->assertCount(1, $contacts['Soccer Superstars']['address']);
    $this->assertCount(1, $contacts['Susie Jones']['openid']);
  }

  /**
   * Test that setting duplicate action to fill doesn't blow away data
   * that exists, but does fill in where it's empty.
   *
   * @throw \Exception
   */
  public function testImportFill(): void {
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
   * CRM-19888 default country should be used if ambiguous.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportAmbiguousStateCountry(): void {
    $this->callAPISuccess('Setting', 'create', ['defaultContactCountry' => 1228]);
    $countries = CRM_Core_PseudoConstant::country(FALSE, FALSE);
    $this->callAPISuccess('Setting', 'create', ['countryLimit' => [array_search('United States', $countries, TRUE), array_search('Guyana', $countries, TRUE), array_search('Netherlands', $countries, TRUE)]]);
    $this->callAPISuccess('Setting', 'create', ['provinceLimit' => [array_search('United States', $countries, TRUE), array_search('Guyana', $countries, TRUE), array_search('Netherlands', $countries, TRUE)]]);
    [$contactValues] = $this->setUpBaseContact();

    // Set up the field mapping  - this looks like an array per mapping as saved in
    // civicrm_mapping_field - eg ['name' => 'street_address', 'location_type_id' => 1],
    $fieldMapping = [];
    foreach (array_keys($contactValues) as $fieldName) {
      $fieldMapping[] = ['name' => $fieldName];
    }

    $addressValues = [
      'street_address' => 'PO Box 2716',
      'city' => 'Midway',
      'state_province' => 'UT',
      'postal_code' => 84049,
      'country' => 'United States',
    ];

    $homeLocationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', 'Home');
    $workLocationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'location_type_id', 'Work');
    foreach ($addressValues as $field => $value) {
      $contactValues['home_' . $field] = $value;
      $contactValues['work_' . $field] = $value;
      $fieldMapping[] = ['name' => $field, 'location_type_id' => $homeLocationTypeID];
      $fieldMapping[] = ['name' => $field, 'location_type_id' => $workLocationTypeID];
    }
    // The value is set to nothing to show it will be calculated.
    $contactValues['work_country'] = '';

    $this->runImport($contactValues, CRM_Import_Parser::DUPLICATE_UPDATE, CRM_Import_Parser::VALID, $fieldMapping);
    $addresses = $this->callAPISuccess('Address', 'get', ['contact_id' => ['>' => 2], 'sequential' => 1]);
    $this->assertEquals(2, $addresses['count']);
    $this->assertEquals(array_search('United States', $countries, TRUE), $addresses['values'][0]['country_id']);
    $this->assertEquals(array_search('United States', $countries, TRUE), $addresses['values'][1]['country_id']);
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
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_NOCHECK,
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
    $importer->import($fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'Texter']);

    $this->assertEquals([4, 1], $contact['preferred_communication_method'], 'Import multiple preferred communication methods using labels.');
    $this->assertEquals(1, $contact['gender_id'], 'Import gender with label.');
    $this->assertEquals('da_DK', $contact['preferred_language'], 'Import preferred language with label.');
    $this->callAPISuccess('Contact', 'delete', ['id' => $contact['id']]);

    $importer = $processor->getImporterObject();
    $fields = ['Ima', 'Texter', '4,1', '1', 'da_DK'];
    $importer->import($fields);
    $contact = $this->callAPISuccessGetSingle('Contact', ['last_name' => 'Texter']);

    $this->assertEquals([4, 1], $contact['preferred_communication_method'], 'Import multiple preferred communication methods using values.');
    $this->assertEquals(1, $contact['gender_id'], 'Import gender with id.');
    $this->assertEquals('da_DK', $contact['preferred_language'], 'Import preferred language with value.');
  }

  /**
   * Run the import parser.
   *
   * @param array $originalValues
   *
   * @param int $onDuplicateAction
   * @param int $expectedResult
   * @param array|null $fieldMapping
   *   Array of field mappings in the format used in civicrm_mapping_field.
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
  protected function runImport(array $originalValues, $onDuplicateAction, $expectedResult, $fieldMapping = [], $fields = NULL, int $ruleGroupId = NULL): void {
    $values = array_values($originalValues);
    // Stand in for row number.
    $values[] = 1;

    if ($fieldMapping) {
      $fields = [];
      foreach ($fieldMapping as $mappedField) {
        $fields[] = $mappedField['name'];
      }
      $mapper = $this->getMapperFromFieldMappingFormat($fieldMapping);
    }
    else {
      if (!$fields) {
        $fields = array_keys($originalValues);
      }
      $mapper = [];
      foreach ($fields as $field) {
        $mapper[] = [
          $field,
          in_array($field, ['phone', 'email'], TRUE) ? 'Primary' : NULL,
          $field === 'phone' ? 1 : NULL,
        ];
      }
    }
    $this->userJobID = $this->getUserJobID(['mapper' => $mapper, 'onDuplicate' => $onDuplicateAction, 'dedupe_rule_id' => $ruleGroupId]);
    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($this->userJobID);
    $parser->_dedupeRuleGroupID = $ruleGroupId;
    $parser->init();

    $result = $parser->import($values);
    $dataSource = new CRM_Import_DataSource_CSV($this->userJobID);
    if ($result === FALSE && $expectedResult !== FALSE) {
      // Import is moving away from returning a status - this is a better way to check
      $this->assertGreaterThan(0, $dataSource->getRowCount([$expectedResult]));
      return;
    }
    $this->assertEquals($expectedResult, $result, 'Return code from parser import was not as expected');
  }

  /**
   * @param string $csv
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   *   {@see \CRM_Contact_Import_Parser_Contact::getMappingFieldFromMapperInput}
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
   * @param int $contactID
   *
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function addChild(int $contactID): void {
    $relatedContactID = $this->individualCreate();
    $relationshipTypeID = RelationshipType::create()->setValues([
      'name_a_b' => 'Dad to',
      'name_b_a' => 'Sleep destroyer of',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'contact_sub_type_a' => 'Parent',
    ])->execute()->first()['id'];
    \Civi\Api4\Relationship::create()->setValues([
      'relationship_type_id' => $relationshipTypeID,
      'contact_id_a' => $contactID,
      'contact_id_b' => $relatedContactID,
    ])->execute();
  }

  /**
   * @return array
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getRelationships(): array {
    if (empty($this->relationships)) {
      $this->relationships = (array) RelationshipType::get()
        ->addSelect('name_a_b', 'id')
        ->execute()
        ->indexBy('name_a_b');
    }
    return $this->relationships;
  }

  /**
   * Get the mapper array from the field mapping array format.
   *
   * The fieldMapping format is the same as the civicrm_mapping_field
   * table and is readable  - eg ['name' => 'street_address', 'location_type_id' => 1].
   *
   * The mapper format is converted to the array that would be submitted by the form
   * and is keyed by row number with the meaning of the fields depending on
   * the selection.
   *
   * @param array $fieldMapping
   *
   * @return array
   */
  protected function getMapperFromFieldMappingFormat($fieldMapping): array {
    $mapper = [];
    foreach ($fieldMapping as $mapping) {
      $mappedRow = [];
      if (!empty($mapping['relationship_type_id'])) {
        $mappedRow[] = $mapping['relationship_type_id'] . $mapping['relationship_direction'];
      }
      $mappedRow[] = $mapping['name'];
      if (!empty($mapping['location_type_id'])) {
        $mappedRow[] = $mapping['location_type_id'];
      }
      elseif (in_array($mapping['name'], ['email', 'phone'], TRUE)) {
        // Lets make it easy on test writers by assuming primary if not specified.
        $mappedRow[] = 'Primary';
      }
      if (!empty($mapping['im_provider_id'])) {
        $mappedRow[] = $mapping['im_provider_id'];
      }
      if (!empty($mapping['phone_type_id'])) {
        $mappedRow[] = $mapping['phone_type_id'];
      }
      if (!empty($mapping['website_type_id'])) {
        $mappedRow[] = $mapping['website_type_id'];
      }
      $mapper[] = $mappedRow;
    }
    return $mapper;
  }

  /**
   * Get a suitable mapper for the array with location defaults.
   *
   * This function is designed for when 'good assumptions' are required rather
   * than careful mapping.
   *
   * @param array $contactValues
   * @param string|int $defaultLocationType
   *
   * @return array
   */
  protected function getFieldMappingFromInput(array $contactValues, $defaultLocationType = 'Primary'): array {
    $mapper = [];
    foreach (array_keys($contactValues) as $fieldName) {
      $mapping = ['name' => $fieldName];
      $addressFields = $this->callAPISuccess('Address', 'getfields', [])['values'];
      unset($addressFields['contact_id'], $addressFields['id'], $addressFields['location_type_id']);
      $locationFields = array_merge(['email', 'phone', 'im', 'openid'], array_keys($addressFields));
      if (in_array($fieldName, $locationFields, TRUE)) {
        $mapping['location_type_id'] = $defaultLocationType;
      }
      if ($fieldName === 'phone') {
        $mapping['phone_type_id'] = 1;
      }
      $mapper[] = $mapping;
    }
    return $mapper;
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
        '1_1' => [
          'phone' => '123',
          'location_type_id' => 1,
          'phone_type_id' => 1,
        ],
      ],
      'relationship' => [
        '5_a_b' => [
          'contact_type' => 'Organization',
          'contact_sub_type' => NULL,
          'website' => [
            'https://example.org' => [
              'url' => 'https://example.org',
              'website_type_id' => 1,
            ],
          ],
          'phone' => [
            '1_1' => [
              'phone' => '456',
              'location_type_id' => 1,
              'phone_type_id' => 1,
            ],
          ],
        ],
      ],
      'im' => [
        '1_1' => [
          'name' => 'my-handle',
          'location_type_id' => 1,
          'provider_id' => 1,
        ],
      ],
      'contact_type' => 'Individual',
    ], $params);
  }

  /**
   * Test that import parser will not match the imported primary to
   * an existing contact via the related contacts fields.
   *
   * Currently fails because CRM_Dedupe_Finder::formatParams($input, $contactType);
   * called in getDuplicateContacts flattens the contact array adding the
   * related contacts values to the primary contact.
   *
   * https://github.com/civicrm/civicrm-core/blob/ca13ec46eae2042604e4e106c6cb3dc0439db3e2/CRM/Dedupe/Finder.php#L238
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testImportParserDoesNotMatchPrimaryToRelated(): void {
    $this->individualCreate([
      'first_name' => 'Bob',
      'last_name' => 'Dobbs',
      'email' => 'tim.cook@apple.com',
    ]);

    $mapper = [
      ['first_name'],
      ['last_name'],
      ['1_a_b', 'email'],
    ];
    $values = ['Alok', 'Patel', 'tim.cook@apple.com', 1];

    $userJobID = $this->getUserJobID([
      'mapper' => $mapper,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
    ]);

    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($userJobID);
    $parser->init();
    $parser->import($values);
    $this->callAPISuccessGetSingle('Contact', [
      'first_name' => 'Bob',
      'last_name' => 'Dobbs',
      'email' => 'tim.cook@apple.com',
    ]);
    $contact = $this->callAPISuccessGetSingle('Contact', ['first_name' => 'Alok', 'last_name' => 'Patel']);
    $this->assertEmpty($contact['email']);
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
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
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
   * Test geocode validation.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testImportGeocodes(): void {
    $mapper = [
      ['first_name'],
      ['last_name'],
      ['geo_code_1', 1],
      ['geo_code_2', 1],
    ];
    $csv = 'individual_geocode.csv';
    $this->validateMultiRowCsv($csv, $mapper, 'GeoCode2');
  }

  /**
   * Validate the csv file values.
   *
   * @param string $csv Name of csv file.
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   *   {@see \CRM_Contact_Import_Parser_Contact::getMappingFieldFromMapperInput}
   * @param array $submittedValues
   *   Any submitted values overrides.
   *
   * @throws \API_Exception
   */
  protected function validateCSV(string $csv, array $mapper, array $submittedValues = []): void {
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
   *   {@see \CRM_Contact_Import_Parser_Contact::getMappingFieldFromMapperInput}
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
      'dateFormats' => CRM_Core_Form_Date::DATE_yyyy_mm_dd,
      'onDuplicate' => CRM_Import_Parser::DUPLICATE_UPDATE,
      'groups' => [],
    ], $submittedValues);
    $form = $this->getFormObject('CRM_Contact_Import_Form_DataSource', $submittedValues);
    $form->buildForm();
    $form->postProcess();
    $this->userJobID = $form->getUserJobID();
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_MapField', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();
    $form->postProcess();
    /* @var CRM_Contact_Import_Form_MapField $form */
    $form = $this->getFormObject('CRM_Contact_Import_Form_Preview', $submittedValues);
    $form->setUserJobID($this->userJobID);
    $form->buildForm();

    try {
      $form->postProcess();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $queue = Civi::queue('user_job_' . $userJobID);
      $runner = new CRM_Queue_Runner([
        'queue' => $queue,
        'errorMode' => CRM_Queue_Runner::ERROR_ABORT,
      ]);
      $runner->runAll();
    }
  }

  /**
   * Validate a csv with multiple rows in it.
   *
   * @param string $csv
   * @param array $mapper Mapping as entered on MapField form.
   *   e.g [['first_name']['email', 1]].
   * @param string $field
   *   Name of the field whose data should be output in the error message.
   * @param array $submittedValues
   *   Values submitted in the form process.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function validateMultiRowCsv(string $csv, array $mapper, string $field, $submittedValues = []): void {
    /* @var CRM_Import_DataSource_CSV $dataSource */
    /* @var \CRM_Contact_Import_Parser_Contact $parser */
    [$dataSource, $parser] = $this->getDataSourceAndParser($csv, $mapper, $submittedValues);
    while ($values = $dataSource->getRow()) {
      try {
        $parser->validateValues(array_values($values));
        if ($values['expected'] !== 'Valid') {
          $this->fail($values[$field] . ' should not have been valid');
        }
      }
      catch (CRM_Core_Exception $e) {
        if ($values['expected'] !== 'Invalid') {
          $this->fail($values[$field] . ' should have been valid');
        }
      }
    }
    UserJob::delete()->addWhere('id', '=', $parser->getUserJobID())->execute();
  }

  /**
   * Get the contacts we imported (Susie Jones & family).
   *
   * @return array
   * @throws \API_Exception
   */
  public function getImportedContacts(): array {
    return (array) Contact::get()
      ->addWhere('display_name', 'IN', [
        'Susie Jones',
        'Mum Jones',
        'sis@example.com',
        'Soccer Superstars',
      ])
      ->addChain('phone', Phone::get()->addWhere('contact_id', '=', '$id'))
      ->addChain('address', Address::get()->addWhere('contact_id', '=', '$id'))
      ->addChain('website', Website::get()->addWhere('contact_id', '=', '$id'))
      ->addChain('im', IM::get()->addWhere('contact_id', '=', '$id'))
      ->addChain('email', Email::get()->addWhere('contact_id', '=', '$id'))
      ->addChain('openid', OpenID::get()->addWhere('contact_id', '=', '$id'))
      ->execute()->indexBy('display_name');
  }

  /**
   * Test that import parser will not throw error if Related Contact is not found via passed in External ID.
   *
   * If the organization is present it will create it - otherwise fail without error.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testImportParserWithExternalIdForRelationship(bool $isOrganizationProvided): void {
    $contactImportValues = [
      'first_name' => 'Alok',
      'last_name' => 'Patel',
      'Employee of' => 'related external identifier',
      'organization_name' => $isOrganizationProvided ? 'Big shop' : '',
    ];

    $mapper = [
      ['first_name'],
      ['last_name'],
      ['5_a_b', 'external_identifier'],
      ['5_a_b', 'organization_name'],
    ];

    $values = array_values($contactImportValues);
    $userJobID = $this->getUserJobID([
      'mapper' => $mapper,
    ]);

    $parser = new CRM_Contact_Import_Parser_Contact();
    $parser->setUserJobID($userJobID);
    $parser->init();

    $parser->import($values);
    $this->callAPISuccessGetCount('Contact', ['organization_name' => 'Big shop'], $isOrganizationProvided ? 2 : 0);
  }

}
