<?php

class CRM_Dedupe_DAO_TestEntity extends CRM_Core_DAO {

  public static $_tableName = 'civicrm_dedupe_test_table';

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
          'where' => 'civicrm_dedupe_test_table.id',
          'table_name' => 'civicrm_dedupe_test_table',
          'entity' => 'TestEntity',
        ],
        'contact_id' => [
          'name' => 'contact_id',
          'type' => CRM_Utils_Type::T_INT,
          'where' => 'civicrm_dedupe_test_table.contact_id',
          'table_name' => 'civicrm_dedupe_test_table',
          'entity' => 'TestEntity',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
          'FKColumnName' => 'id',
        ],
        'dedupe_test_field' => [
          'name' => 'dedupe_test_field',
          'type' => CRM_Utils_Type::T_STRING,
          'maxlength' => 64,
          'size' => 8,
          'import' => TRUE,
          'where' => 'civicrm_dedupe_test_table.dedupe_test_field',
          'table_name' => 'civicrm_dedupe_test_table',
          'entity' => 'TestEntity',
        ],
      ];
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

}

/**
 * Class CRM_Dedupe_BAO_RuleGroupTest
 * @group headless
 */
class CRM_Dedupe_BAO_RuleGroupTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  /**
   * IDs of created contacts.
   *
   * @var array
   */
  protected array $contactIDs = [];

  /**
   * ID of the group holding the contacts.
   *
   * @var int
   */
  protected $groupID;

  /**
   * @var \Civi\API\Kernel
   */
  protected $apiKernel;

  /**
   * @var \Civi\API\Provider\AdhocProvider
   */
  protected $adhocProvider;

  /**
   * Clean up after the test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {

    foreach ($this->contactIDs as $contactId) {
      $this->contactDelete($contactId);
    }
    if ($this->groupID) {
      $this->callAPISuccess('group', 'delete', ['id' => $this->groupID]);
    }
    $this->quickCleanup(['civicrm_contact'], TRUE);
    CRM_Core_DAO::executeQuery("DELETE r FROM civicrm_dedupe_rule_group rg INNER JOIN civicrm_dedupe_rule r ON rg.id = r.dedupe_rule_group_id WHERE rg.is_reserved = 0 AND used = 'General'");
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_dedupe_rule_group WHERE is_reserved = 0 AND used = 'General'");

    parent::tearDown();
  }

  /**
   * Get the list of supportedFields to test against.
   *
   * This is a statically maintained (in this test list).
   *
   * @param string $contactType
   *
   * @return array
   */
  public function getSupportedFields(string $contactType): array {
    $sharedFields = [
      'civicrm_address' =>
        [
          'name' => 'Address Name',
          'city' => 'City',
          'country_id' => 'Country',
          'county_id' => 'County',
          'geo_code_1' => 'Latitude',
          'geo_code_2' => 'Longitude',
          'master_id' => 'Master Address ID',
          'postal_code' => 'Postal Code',
          'postal_code_suffix' => 'Postal Code Suffix',
          'state_province_id' => 'State',
          'street_address' => 'Street Address',
          'supplemental_address_1' => 'Supplemental Address 1',
          'supplemental_address_2' => 'Supplemental Address 2',
          'supplemental_address_3' => 'Supplemental Address 3',
        ],
      'civicrm_contact' =>
        [
          'addressee_custom' => 'Addressee Custom',
          'id' => 'Contact ID',
          'source' => 'Contact Source',
          'contact_sub_type' => 'Contact Subtype',
          'do_not_email' => 'Do Not Email',
          'do_not_mail' => 'Do Not Mail',
          'do_not_phone' => 'Do Not Phone',
          'do_not_sms' => 'Do Not Sms',
          'do_not_trade' => 'Do Not Trade',
          'email_greeting_custom' => 'Email Greeting Custom',
          'external_identifier' => 'External Identifier',
          'image_URL' => 'Image Url',
          'legal_identifier' => 'Legal Identifier',
          'nick_name' => 'Nickname',
          'is_opt_out' => 'No Bulk Emails (User Opt Out)',
          'postal_greeting_custom' => 'Postal Greeting Custom',
          'preferred_communication_method' => 'Preferred Communication Method',
          'preferred_language' => 'Preferred Language',
          'user_unique_id' => 'Unique ID (OpenID)',
          'sort_name' => 'Sort Name',
          'communication_style_id' => 'Communication Style',
        ],
      'civicrm_email' =>
        [
          'email' => 'Email',
          'signature_html' => 'Signature Html',
          'signature_text' => 'Signature Text',
        ],
      'civicrm_im' =>
        [
          'name' => 'IM Screen Name',
        ],
      'civicrm_note' =>
        [
          'note' => 'Note',
        ],
      'civicrm_openid' =>
        [
          'openid' => 'OpenID',
        ],
      'civicrm_phone' =>
        [
          'phone_numeric' => 'Phone',
          'phone_ext' => 'Phone Extension',
        ],
      'civicrm_website' =>
        [
          'url' => 'Website',
        ],
    ];
    $contactTypeFields = [
      'Organization' => [
        'legal_name' => 'Legal Name',
        'organization_name' => 'Organization Name',
        'sic_code' => 'Sic Code',
      ],
      'Individual' => [
        'birth_date' => 'Birth Date',
        'is_deceased' => 'Deceased',
        'deceased_date' => 'Deceased Date',
        'first_name' => 'First Name',
        'formal_title' => 'Formal Title',
        'gender_id' => 'Gender ID',
        'prefix_id' => 'Individual Prefix',
        'suffix_id' => 'Individual Suffix',
        'job_title' => 'Job Title',
        'last_name' => 'Last Name',
        'middle_name' => 'Middle Name',
      ],
      'Household' => [
        'household_name' => 'Household Name',
      ],
    ];
    $sharedFields['civicrm_contact'] += $contactTypeFields[$contactType];
    return $sharedFields;
  }

  /**
   * Test that sort_name is included in supported fields.
   *
   * This feels like kind of a brittle test but since I debated actually making it
   * importable in the schema & bottled out at least some degree of test support
   * to ensure the field remains 'hacked in' seems important.
   *
   * This will at least surface any changes that affect this function.
   *
   * In general we do have a bit of a problem with having overloaded the meaning of
   * importable & exportable fields.
   */
  public function testSupportedFields(): void {
    $fields = CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Organization');
    $this->assertEquals($this->getSupportedFields('Organization'), $fields);
  }

  /**
   * Test individual supported fields.
   */
  public function testSupportedFieldsIndividual(): void {
    $fields = CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Individual');
    $this->assertEquals($this->getSupportedFields('Individual'), $fields);
  }

  /**
   * Test individual supported fields.
   */
  public function testSupportedFieldsHousehold(): void {
    $fields = CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Household');
    $this->assertEquals($this->getSupportedFields('Household'), $fields);
  }

  /**
   * Test that custom_fields are included in supported fields.
   *
   */
  public function testSupportedCustomFields(): void {
    //Create custom group with fields of all types to test.
    $customGroup = $this->createCustomGroup(['extends' => 'Organization']);

    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $customField = $this->createTextCustomField(['custom_group_id' => $customGroupID]);

    $fields = $this->getSupportedFields('Organization');
    $fields[$this->getCustomGroupTable()][$customField['column_name']] = 'Custom Group' . ' : ' . $customField['label'];

    $this->assertEquals($fields, CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Organization'));
  }

  /**
   * Test that custom_fields for a sub_type are included in supported fields.
   *
   * dev/core#2300 Can not use Custom Fields defined on a contact_sub_type in
   * dedupe rule.
   *
   */
  public function testSupportedCustomFieldsSubtype(): void {

    //Create custom group with fields of all types to test.
    $contactType = $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggee', 'parent_id' => 'Organization']);
    $customGroup = $this->createCustomGroup(['extends' => 'Organization', 'extends_entity_column_value' => ['Big_Bank']]);

    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $cf = $this->createTextCustomField(['custom_group_id' => $customGroupID]);

    $fields = $this->getSupportedFields('Organization');
    $fields[$this->getCustomGroupTable()][$cf['column_name']] = 'Custom Group' . ' : ' . $cf['label'];

    $this->assertEquals($fields, CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Organization'));
  }

  /**
   * Test hook_dupeQuery match on custom entity field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testHookDupeQueryMatch(): void {
    $this->hookClass->setHook('civicrm_dupeQuery', [$this, 'hook_civicrm_dupeQuery']);
    $this->hookClass->setHook('civicrm_entityTypes', function (array &$entityTypes) {
      $entityTypes['TestEntity'] = [
        'name' => 'TestEntity',
        'class' => 'CRM_Dedupe_DAO_TestEntity',
        'table' => 'civicrm_dedupe_test_table',
      ];
    });
    \CRM_Core_DAO_AllCoreTables::flush();
    $this->apiKernel = \Civi::service('civi_api_kernel');
    $this->adhocProvider = new \Civi\API\Provider\AdhocProvider(3, 'TestEntity');
    $this->apiKernel->registerApiProvider($this->adhocProvider);

    //DedupeRule.php call this hook to get the type for the field.
    $this->adhocProvider->addAction('getfields', 'access CiviCRM', function ($apiRequest) {
      return [
        'values' => [
          'id' => [
            'name' => 'id',
            'type' => CRM_Utils_Type::T_INT,
          ],
          'contact_id' => [
            'name' => 'contact_id',
            'type' => CRM_Utils_Type::T_INT,
          ],
          'dedupe_test_field' => [
            'name' => 'dedupe_test_field',
            'type' => CRM_Utils_Type::T_STRING,
          ],
        ],
      ];
    });

    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_dedupe_test_table');
    // Setup our custom enity table.
    $sql = "CREATE TABLE `civicrm_dedupe_test_table` (
      `id` int(10) UNSIGNED NOT NULL COMMENT 'Unique ID',
      `contact_id` int(10) UNSIGNED DEFAULT NULL,
      `dedupe_test_field` varchar(64) DEFAULT NULL
    )";

    CRM_Core_DAO::executeQuery($sql);

    $sql = "ALTER TABLE `civicrm_dedupe_test_table` ADD INDEX `FK_civicrm_dedupe_test_table_contact_id` (`contact_id`);";

    CRM_Core_DAO::executeQuery($sql);

    $params = [
      'name' => 'Dupe Group',
      'title' => 'New Test Dupe Group',
      'domain_id' => 1,
      'is_active' => 1,
      'visibility' => 'Public Pages',
    ];

    $result = $this->callAPISuccess('group', 'create', $params);
    $this->groupID = $result['id'];

    $params = [
      [
        'first_name' => 'robin',
        'last_name' => 'hood',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'bob',
        'last_name' => 'dobbs',
        'contact_type' => 'Individual',
      ],
    ];

    $count = 1;
    foreach ($params as $param) {
      $contact = $this->callAPISuccess('contact', 'create', $param);
      $this->contactIDs[$count++] = $contact['id'];

      $grpParams = [
        'contact_id' => $contact['id'],
        'group_id' => $this->groupID,
      ];
      $this->callAPISuccess('group_contact', 'create', $grpParams);

      CRM_Core_DAO::executeQuery("INSERT INTO `civicrm_dedupe_test_table` (`id`, `contact_id`, `dedupe_test_field`) VALUES (" . $count . "," . $contact['id'] . ", 'duplicate');");
      $contact_id = $contact['id'];
    }

    // verify that all contacts have been created separately
    $this->assertEquals(count($this->contactIDs), 2, 'Check for number of contacts.');

    // Create our RuleGroup with one rule.
    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach (['dedupe_test_field'] as $field) {
      $rules[$field] = $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_weight' => 10,
        'rule_field' => $field,
        'rule_table' => 'civicrm_dedupe_test_table',
      ]);
    }

    // Test op supportedFields
    $fields = CRM_Dedupe_BAO_DedupeRuleGroup::supportedFields('Individual');
    $this->assertEquals([
      'dedupe_test_field' => 'Test Field',
    ], $fields['civicrm_dedupe_test_table']);

    // Test rule finds a match.
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);

    $this->assertCount(1, $foundDupes);

    // Test rule finds no match.
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_dedupe_test_table` SET dedupe_test_field = 'not a duplicate' WHERE contact_id = $contact_id;");

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);

    $this->assertCount(0, $foundDupes);

    CRM_Core_DAO::executeQuery('DROP TABLE civicrm_dedupe_test_table');
  }

  /**
   * Implements hook_civicrm_dupeQuery().
   *
   * Locks in expected params
   *
   */
  public function hook_civicrm_dupeQuery($baoObject, $op, &$objectData) {
    $this->assertContains($op, ['supportedFields', 'dedupeIndexes', 'query', 'table', 'threshold']);
    switch ($op) {
      case 'supportedFields':
        $this->assertIsArray($objectData);
        $this->assertNull($baoObject);
        $objectData['Individual']['civicrm_dedupe_test_table'] = [
          'dedupe_test_field' => 'Test Field',
        ];
        break;

      case 'dedupeIndexes':
        //Not tested.
        break;

      case 'query':
        $this->assertIsArray($objectData);
        $this->assertInstanceOf(CRM_Dedupe_BAO_DedupeRule::class, $baoObject);
        if ($baoObject->rule_table == 'civicrm_dedupe_test_table') {
          $objectData = $baoObject->entitySql('contact_id');
        }
        break;

      case 'table':
        $this->assertIsArray($objectData);
        $this->assertInstanceOf(CRM_Dedupe_BAO_DedupeRuleGroup::class, $baoObject);
        break;

      case 'threshold':
        $this->assertIsString($objectData);
        $this->assertInstanceOf(CRM_Dedupe_BAO_DedupeRuleGroup::class, $baoObject);
        break;

    }
  }

}
