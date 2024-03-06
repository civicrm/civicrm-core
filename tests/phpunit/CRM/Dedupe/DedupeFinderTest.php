<?php

/**
 * Class CRM_Dedupe_DedupeFinderTest
 * @group headless
 */
class CRM_Dedupe_DedupeFinderTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;
  /**
   * IDs of created contacts.
   *
   * @var array
   */
  protected $contactIDs = [];

  /**
   * ID of the group holding the contacts.
   *
   * @var int
   */
  protected $groupID;

  /**
   * Clean up after the test.
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
   * Test the unsupervised dedupe rule against a group.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUnsupervisedDupes(): void {
    // make dupe checks based on following contact sets:
    // FIRST - LAST - EMAIL
    // ---------------------------------
    // robin  - hood - robin@example.com
    // robin  - hood - hood@example.com
    // robin  - dale - robin@example.com
    // little - dale - dale@example.com
    // will   - dale - dale@example.com
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    $this->setupForGroupDedupe();

    $ruleGroup = $this->callAPISuccessGetSingle('RuleGroup', ['is_reserved' => 1, 'contact_type' => 'Individual', 'used' => 'Unsupervised']);

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(3, $foundDupes, 'Check Individual-Fuzzy dupe rule for dupesInGroup().');
  }

  /**
   * Test duplicate contact retrieval with 2 email fields.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUnsupervisedWithTwoEmailFields(): void {
    $this->setupForGroupDedupe();
    $emails = [
      ['hood@example.com', ''],
      ['', 'hood@example.com'],
    ];
    for ($i = 0; $i < 2; $i++) {
      $fields = [
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email-1' => $emails[$i][0],
        'email-2' => $emails[$i][1],
      ];
      $dedupeResults = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual');

      $this->assertCount(1, $dedupeResults);
    }
  }

  /**
   * Test that a rule set to is_reserved = 0 works.
   *
   * There is a different search used dependent on this variable.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRule(): void {
    $this->setupForGroupDedupe();

    $ruleGroup = $this->createRuleGroup();
    foreach (['birth_date', 'first_name', 'last_name'] as $field) {
      $rules[$field] = $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 4,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(4, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

  }

  /**
   * Test our rule group with a custom group.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRuleCustomFields(): void {

    $this->setupForGroupDedupe();

    //Create custom group with fields of all types to test.
    $this->createCustomGroup();

    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $ids = &$this->ids['CustomField'];
    $ids['string'] = (int) $this->createTextCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['date'] = (int) $this->createDateCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['int'] = (int) $this->createIntCustomField(['custom_group_id' => $customGroupID])['id'];

    $params = [];
    foreach ($this->ids['CustomField'] as $key => $field_id) {
      switch ($key) {
        case 'string':
          $params["custom_{$field_id}"] = 'text';
          break;

        case 'date':
          $params["custom_{$field_id}"] = '20220511';
          break;

        case 'int':
          $params["custom_{$field_id}"] = 5;
          break;
      }
    }

    $count = 0;
    foreach ($this->contactIDs as $contact_id) {
      // Update the text custom fields for duplicate contact
      foreach ($this->ids['CustomField'] as $ignored) {
        $this->callAPISuccess('Contact', 'create', array_merge([
          'id' => $contact_id,
        ], $params));
      }
      $count++;
      if ($count === 2) {
        break;
      }
    }

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 4 * count($this->ids['CustomField']),
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach ($this->ids['CustomField'] as $key => $field_id) {
      $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => $this->getCustomGroupTable(),
        'rule_weight' => 4,
        'rule_field' => $this->getCustomFieldColumnName($key),
      ]);
    }

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(1, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

    $fields = [
      'first_name' => 'robin',
      'last_name' => 'hood',
      'email' => 'hood@example.com',
      'street_address' => 'Ambachtstraat 23',
    ];

    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, $ruleGroup['id'], ['event_id' => 1]);
    $this->assertCount(0, $ids);

    $fields = array_merge($fields, $params);
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, $ruleGroup['id'], ['event_id' => 1]);
    $this->assertCount(2, $ids);
  }

  /**
   * Test our rule group with a custom group for a SubType.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRuleCustomFieldsSubtypes(): void {

    $this->setupForGroupDedupe();

    // Create custom group with fields of all types to test.
    $this->callAPISuccess('ContactType', 'create', ['name' => 'Big Bank', 'label' => 'biggie', 'parent_id' => 'Individual']);
    $this->createCustomGroup(['extends' => 'Individual', 'extends_entity_column_value' => ['Big_Bank']]);

    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $ids = &$this->ids['CustomField'];
    $ids['string'] = (int) $this->createTextCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['date'] = (int) $this->createDateCustomField(['custom_group_id' => $customGroupID])['id'];
    $ids['int'] = (int) $this->createIntCustomField(['custom_group_id' => $customGroupID])['id'];

    $params = [];
    foreach ($this->ids['CustomField'] as $key => $field_id) {
      switch ($key) {
        case 'string':
          $params["custom_{$field_id}"] = 'text';
          break;

        case 'date':
          $params["custom_{$field_id}"] = '20220511';
          break;

        case 'int':
          $params["custom_{$field_id}"] = 5;
          break;
      }
    }

    foreach ($this->contactIDs as $contact_id) {
      $this->callAPISuccess('Contact', 'create', array_merge([
        'id' => $contact_id,
        'contact_sub_type' => 'Big_Bank',
      ]));
    }

    $count = 0;
    foreach ($this->contactIDs as $contact_id) {
      // Update the text custom fields for duplicate contact
      foreach ($this->ids['CustomField'] as $ignored) {
        $this->callAPISuccess('Contact', 'create', array_merge([
          'id' => $contact_id,
        ], $params));
      }
      $count++;
      if ($count === 2) {
        break;
      }
    }

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 4 * count($this->ids['CustomField']),
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach ($this->ids['CustomField'] as $key => $field_id) {
      $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => $this->getCustomGroupTable(),
        'rule_weight' => 4,
        'rule_field' => $this->getCustomFieldColumnName($key),
      ]);
    }

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(1, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

    $fields = [
      'first_name' => 'robin',
      'last_name' => 'hood',
      'email' => 'hood@example.com',
      'street_address' => 'Ambachtstraat 23',
    ];

    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, $ruleGroup['id'], ['event_id' => 1]);
    $this->assertCount(0, $ids);

    $fields = array_merge($fields, $params);
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, $ruleGroup['id'], ['event_id' => 1]);
    $this->assertCount(2, $ids);
  }

  /**
   * Test that we do not get a fatal error when our rule group is a custom date field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRuleCustomDateField(): void {

    $ruleGroup = $this->createRuleGroup();
    $this->createCustomGroupWithFieldOfType([], 'date');
    $this->callAPISuccess('Rule', 'create', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => $this->getCustomGroupTable(),
      'rule_weight' => 4,
      'rule_field' => $this->getCustomFieldColumnName('date'),
    ]);

    CRM_Dedupe_Finder::dupes($ruleGroup['id']);
  }

  /**
   * Test a custom rule with a non-default field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRuleWithAddress(): void {
    $this->setupForGroupDedupe();

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach (['postal_code'] as $field) {
      $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_address',
        'rule_weight' => 10,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(1, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

  }

  /**
   * Test rule from Richard
   *
   * @throws \CRM_Core_Exception
   */
  public function testRuleThreeContactFieldsEqualWeightWithThresholdTheTotalSumOfAllWeight(): void {
    $this->setupForGroupDedupe();

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', [
      'contact_type' => 'Individual',
      'threshold' => 30,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach (['first_name', 'last_name', 'birth_date'] as $field) {
      $rules[$field] = $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 10,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(1, $foundDupes);
  }

  /**
   * Test a custom rule with a non-default field.
   *
   * @throws \CRM_Core_Exception
   */
  public function testInclusiveRule(): void {
    $this->setupForGroupDedupe();

    $ruleGroup = $this->createRuleGroup();
    foreach (['first_name', 'last_name'] as $field) {
      $rules[$field] = $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 4,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertCount(4, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);
  }

  /**
   * Test the supervised dedupe rule against a group.
   *
   * @throws \Exception
   */
  public function testSupervisedDupes(): void {
    $this->setupForGroupDedupe();
    $ruleGroup = $this->callAPISuccessGetSingle('RuleGroup', ['is_reserved' => 1, 'contact_type' => 'Individual', 'used' => 'Supervised']);
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    // -------------------------------------------------------------------------
    // default dedupe rule: threshold = 20 => (First + Last + Email) Matches ( 1 pair )
    // --------------------------------------------------------------------------
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    // so 1 pair for - first + last + mail
    $this->assertCount(1, $foundDupes, 'Check Individual-Fuzzy dupe rule for dupesInGroup().');
  }

  /**
   * Test dupesByParams function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDupesByParams(): void {
    // make dupe checks based on based on following contact sets:
    // FIRST - LAST - EMAIL
    // ---------------------------------
    // robin  - hood - robin@example.com
    // robin  - hood - hood@example.com
    // robin  - dale - robin@example.com
    // little - dale - dale@example.com
    // will   - dale - dale@example.com
    // will   - dale - will@example.com
    // will   - dale - will@example.com

    // contact data set
    // FIXME: move create params to separate function
    $params = [
      [
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'hood@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'robin',
        'last_name' => 'dale',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'little',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ],
    ];

    $this->hookClass->setHook('civicrm_findDuplicates', [$this, 'hook_civicrm_findDuplicates']);

    $count = 1;

    foreach ($params as $param) {
      $contact = $this->callAPISuccess('contact', 'create', $param);
      $params = [
        'contact_id' => $contact['id'],
        'street_address' => 'Ambachtstraat 23',
        'location_type_id' => 1,
      ];
      $this->callAPISuccess('address', 'create', $params);
      $contactIds[$count++] = $contact['id'];
    }

    // Verify that all contacts have been created separately.
    $this->assertCount(7, $contactIds, 'Check for number of contacts.');

    $fields = [
      'first_name' => 'robin',
      'last_name' => 'hood',
      'email' => 'hood@example.com',
      'street_address' => 'Ambachtstraat 23',
    ];
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, NULL, ['event_id' => 1]);

    // Check with default Individual-General rule
    $this->assertCount(2, $ids, 'Check Individual-General rule for dupesByParams().');

    // delete all created contacts
    foreach ($contactIds as $contactId) {
      $this->contactDelete($contactId);
    }
  }

  /**
   * Implements hook_civicrm_findDuplicates().
   *
   * Locks in expected params
   *
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  public function hook_civicrm_findDuplicates($dedupeParams, &$dedupeResults, $contextParams) {
    $expectedDedupeParams = [
      'check_permission' => TRUE,
      'contact_type' => 'Individual',
      'rule' => 'General',
      'rule_group_id' => NULL,
      'excluded_contact_ids' => [],
    ];
    foreach ($expectedDedupeParams as $key => $value) {
      $this->assertEquals($value, $dedupeParams[$key]);
    }
    $expectedDedupeResults = [
      'ids' => [],
      'handled' => FALSE,
    ];
    foreach ($expectedDedupeResults as $key => $value) {
      $this->assertEquals($value, $dedupeResults[$key]);
    }

    $expectedContext = ['event_id' => 1];
    foreach ($expectedContext as $key => $value) {
      $this->assertEquals($value, $contextParams[$key]);
    }

    return $dedupeResults;
  }

  /**
   * Set up a group of dedupe-able contacts.
   */
  protected function setupForGroupDedupe(): void {
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
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
        'birth_date' => '2016-01-01',
        'api.Address.create' => ['street_address' => '123 Happy world', 'location_type_id' => 'Billing', 'postal_code' => '99999'],
      ],
      [
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'hood@example.com',
        'contact_type' => 'Individual',
        'birth_date' => '2016-01-01',
        'api.Address.create' => ['street_address' => '123 Happy World', 'location_type_id' => 'Billing', 'postal_code' => '99999'],
      ],
      [
        'first_name' => 'robin',
        'last_name' => 'dale',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'little',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ],
      [
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
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
    }

    // Verify that all contacts have been created separately.
    $this->assertCount(7, $this->contactIDs, 'Check for number of contacts.');
  }

}
