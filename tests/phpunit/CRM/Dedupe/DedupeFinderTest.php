<?php
declare(strict_types = 1);
use Civi\Api4\DedupeRuleGroup;
use Civi\Api4\DedupeRule;

/**
 * Class CRM_Dedupe_DedupeFinderTest
 * @group headless
 */
class CRM_Dedupe_DedupeFinderTest extends CiviUnitTestCase {

  use CRMTraits_Custom_CustomDataTrait;

  public function setUp(): void {
    parent::setUp();
    $this->callAPISuccess('Extension', 'disable', ['keys' => 'legacydedupefinder']);
  }

  /**
   * Clean up after the test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_group_contact', 'civicrm_group'], TRUE);
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

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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
   * Test the ability of the Dedupe Query Optimizer to join queries appropriately.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function testFinderQueryOptimizer(): void {
    $this->createRuleGroup(['threshold' => 16]);
    // Note that in this format the number at the end is the weight.
    $queries = [
      ['civicrm_email', 'email', 16],
      ['civicrm_contact', 'first_name', 7],
      ['civicrm_phone', 'phone', 5],
      ['civicrm_contact', 'nick_name', 5],
      ['civicrm_address', 'street_address', 4],
      ['civicrm_address', 'city', 3],
    ];
    $this->createRules($queries);
    $optimizer = new CRM_Dedupe_FinderQueryOptimizer($this->ids['DedupeRuleGroup']['individual_general'], [16], []);
    $combinations = $optimizer->getValidCombinations();
    // There are 5 possible combinations that add up to 16.
    // 1 combo with civicrm_email.x.16 (because we don't need to do any more)
    // 3 with civicrm_contact.x.7 and 1 with all the fields excluding those 2.
    $this->assertCount(5, $combinations);
    // There are no opportunities to combine fields here
    // as each field can be combined in multiple ways.
    $this->assertCount(0, $optimizer->getCombinableQueries());

    $queries = [
      ['civicrm_contact', 'first_name', 8],
      ['civicrm_contact', 'last_name', 7],
      ['civicrm_contact', 'nick_name', 5],
      ['civicrm_address', 'street_address', 5],
    ];
    $this->createRules($queries, 20);
    $optimizer = new CRM_Dedupe_FinderQueryOptimizer($this->ids['DedupeRuleGroup']['individual_general'], [], []);
    // we can get there with first+last+nick name or first+last + street_address
    $this->assertCount(2, $optimizer->getValidCombinations());
    // We can combine the first & last name queries because they are
    // always both required.
    $this->assertCount(1, $optimizer->getCombinableQueries());

    $queries = [
      ['civicrm_contact', 'first_name', 8],
      ['civicrm_contact', 'last_name', 7],
      ['civicrm_contact', 'nick_name', 3],
      ['civicrm_address', 'street_address', 2],
      ['civicrm_address', 'city', 2],
    ];
    $this->createRules($queries, 20);
    $optimizer = new CRM_Dedupe_FinderQueryOptimizer($this->ids['DedupeRuleGroup']['individual_general'], [], []);

    // we can get there with first+last+nick name+street  or first+last+nick + city
    $this->assertCount(2, $optimizer->getValidCombinations());
    // We can combine the first & last name + nick name queries because they are
    // always both required. Even though we can also combine first+last
    // this should not be returned as it is a subset.
    $this->assertCount(1, $optimizer->getCombinableQueries());
  }

  /**
   * Test the ability of the Dedupe Query Optimizer to join queries appropriately.
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function testFinderQueryOptimizerLookup(): void {
    $this->createRuleGroup(['threshold' => 16]);
    // Note that in this format the number at the end is the weight.
    $queries = [
      ['civicrm_email', 'email', 16],
      ['civicrm_contact', 'first_name', 7],
      ['civicrm_phone', 'phone', 5],
      ['civicrm_contact', 'nick_name', 5],
      ['civicrm_address', 'street_address', 4],
      ['civicrm_address', 'city', 3],
    ];
    $this->createRules($queries);
    $this->individualCreate(['first_name' => 'Robert', 'nick_name' => 'Bob', 'address_primary.street_address' => 'sesame street', 'version' => 4]);
    $result = \CRM_Contact_BAO_Contact::findDuplicates([
      'civicrm_contact' => ['first_name' => 'Robert', 'nick_name' => 'Bob', 'last_name' => 'Smith'],
      'civicrm_address' => ['city' => 'Bobville', 'street_address' => 'sesame street'],
      'rule_group_id' => $this->ids['DedupeRuleGroup']['individual_general'],
      'contact_type' => 'Individual',
    ]);
    $this->assertEquals([$this->ids['Contact']['individual_0']], $result);
    $queries = \Civi::$statics['CRM_Dedupe_FinderQueryOptimizer']['queries'];
    // Check that the city query was eliminated - it has data but it's weight of 3 cannot influence the match outcome.
    // The other queries are combined.
    $this->assertCount(1, $queries);
    $query = reset($queries);
    $this->assertEquals(16, $query['weight']);
    $this->assertEquals(1, $query['found_rows']);
    $this->assertLike("SELECT civicrm_address .contact_id id1,  16 weight  FROM civicrm_contact t1
          INNER JOIN civicrm_address
          ON t1.id = civicrm_address.contact_id
          AND civicrm_address.street_address = 'sesame street' WHERE t1.contact_type = 'Individual' AND t1.first_name = 'Robert' AND t1.contact_type = 'Individual' AND t1.nick_name = 'Bob'", $query['query']);
  }

  /**
   * @throws \Civi\API\Exception\UnauthorizedException
   * @throws \CRM_Core_Exception
   */
  protected function createRules($queries, $threshold = NULL) {
    DedupeRule::delete(FALSE)
      ->addWhere('dedupe_rule_group_id', '=', $this->ids['DedupeRuleGroup']['individual_general'])
      ->execute();
    if ($threshold) {
      DedupeRuleGroup::update()
        ->addWhere('id', '=', $this->ids['DedupeRuleGroup']['individual_general'])
        ->addValue('threshold', $threshold)
        ->execute();
    }
    foreach ($queries as $query) {
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id' => $this->ids['DedupeRuleGroup']['individual_general'],
        'rule_table' => $query[0],
        'rule_field' => $query[1],
        'rule_weight' => $query[2],
      ], implode('.', $query));
    }
  }

  /**
   * Test that the sql works when the query can be optimised to include 2 tables.
   *
   * We are looking for no-sql-error here.
   *
   * @return void
   */
  public function testCrossTableOptimized(): void {
    $this->createRuleGroup();
    $fields = [
      'email' => ['weight' => 8, 'rule_table' => 'civicrm_email'],
      'first_name' => ['weight' => 3],
      'last_name' => ['weight' => 1],
      'street_address' => ['weight' => 5, 'rule_table' => 'civicrm_address'],
    ];
    foreach ($fields as $field => $rule) {
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id.name' => 'TestRule',
        'rule_table' => $rule['rule_table'] ?? 'civicrm_contact',
        'rule_weight' => $rule['weight'],
        'rule_field' => $field,
      ]);
    }
    $this->individualCreate(['first_name' => 'Bob', 'last_name' => 'Smith', 'street_address' => '123 Main St']);
    $this->individualCreate(['first_name' => 'Bob', 'last_name' => 'Smith', 'street_address' => '123 Main St']);
    $this->individualCreate(['first_name' => 'Bob', 'email' => 'bob@example.org']);
    $this->individualCreate(['first_name' => 'Bob', 'email' => 'bob@example.org']);
    $result = $this->callAPISuccess('Job', 'process_batch_merge', ['rule_group_id' => $this->ids['DedupeRuleGroup']['individual_general']])['values'];
    $this->assertCount(2, $result['merged']);
    $queries = \Civi::$statics['CRM_Dedupe_FinderQueryOptimizer']['queries'];
    $this->assertEquals(['civicrm_email.email.8', 'civicrm_address.street_address.5', 'civicrm_contact.first_name.3'], array_keys($queries));
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
    $this->callAPISuccess('Extension', 'install', ['keys' => 'legacydedupefinder']);

    $ruleGroup = $this->createRuleGroup();
    foreach (['birth_date', 'first_name', 'last_name'] as $field) {
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 4,
        'rule_field' => $field,
      ], $field);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
    $this->assertCount(4, $foundDupes);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

    // Make sure it is the same with the extension disabled.
    $this->callAPISuccess('Extension', 'disable', ['keys' => 'legacydedupefinder']);
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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

    $this->ids['CustomField']['string'] = (int) $this->createTextCustomField(['custom_group_id' => $customGroupID])['id'];
    $this->ids['CustomField']['date'] = (int) $this->createDateCustomField(['custom_group_id' => $customGroupID])['id'];
    $this->ids['CustomField']['int'] = (int) $this->createIntCustomField(['custom_group_id' => $customGroupID])['id'];

    $params = $this->getCustomFieldParams();

    $this->getMatchesByCustomFields($params);
  }

  /**
   * Test our rule group with a custom group for a SubType.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomRuleCustomFieldsSubtypes(): void {

    $this->setupForGroupDedupe();

    // Create custom group with fields of all types to test.
    $this->createTestEntity('ContactType', ['name' => 'Big_Bank', 'label' => 'biggie', 'parent_id:name' => 'Individual']);
    foreach ($this->ids['Contact'] as $contact_id) {
      $this->callAPISuccess('Contact', 'create', array_merge([
        'id' => $contact_id,
        'contact_sub_type' => 'Big_Bank',
      ]));
    }

    $this->createCustomGroup(['extends' => 'Individual', 'extends_entity_column_value' => ['Big_Bank']]);

    $customGroupID = $this->ids['CustomGroup']['Custom Group'];
    $this->ids['CustomField']['string'] = (int) $this->createTextCustomField(['custom_group_id' => $customGroupID])['id'];
    $this->ids['CustomField']['date'] = (int) $this->createDateCustomField(['custom_group_id' => $customGroupID])['id'];
    $this->ids['CustomField']['int'] = (int) $this->createIntCustomField(['custom_group_id' => $customGroupID])['id'];

    $params = $this->getCustomFieldParams();

    $this->getMatchesByCustomFields($params);
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

    $ruleGroup = $this->createTestEntity('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'threshold' => 10,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    $this->createTestEntity('DedupeRule', [
      'dedupe_rule_group_id' => $ruleGroup['id'],
      'rule_table' => 'civicrm_address',
      'rule_weight' => 10,
      'rule_field' => 'postal_code',
    ]);
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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

    $ruleGroup = $this->createTestEntity('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'threshold' => 30,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach (['first_name', 'last_name', 'birth_date'] as $field) {
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id.name' => 'TestRule',
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 10,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 4,
        'rule_field' => $field,
      ], $field);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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
      'phone' => '123-456',
    ];
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, NULL, ['event_id' => 1]);
    // Also check the deprecated method.
    $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
    $legacyIds = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'General');
    $this->assertEquals($ids, $legacyIds);
    // Check with default Individual-General rule
    $this->assertCount(2, $ids, 'Check Individual-General rule for dupesByParams().');
  }

  /**
   * Implements hook_civicrm_findDuplicates().
   *
   * Locks in expected params
   *
   * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
   */
  public function hook_civicrm_findDuplicates($dedupeParams, &$dedupeResults, $contextParams) {
    $ruleGroupID = DedupeRuleGroup::get(FALSE)
      ->addWhere('name', '=', 'IndividualGeneral')
      ->execute()->first()['id'];
    $expectedDedupeParams = [
      'check_permission' => TRUE,
      'contact_type' => 'Individual',
      'rule' => 'General',
      'rule_group_id' => $ruleGroupID,
      'excluded_contact_ids' => [],
    ];
    if (!empty($dedupeParams['civicrm_phone'])) {
      $this->assertEquals(123456, $dedupeParams['civicrm_phone']['phone_numeric']);
    }
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

    if (empty($contextParams['is_legacy_usage'])) {
      $expectedContext = ['event_id' => 1];
      foreach ($expectedContext as $key => $value) {
        $this->assertEquals($value, $contextParams[$key]);
      }
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

    $this->createTestEntity('Group', $params);

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
      $this->ids['Contact'][$count++] = $contact['id'];

      $grpParams = [
        'contact_id' => $contact['id'],
        'group_id' => $this->ids['Group']['default'],
      ];
      $this->callAPISuccess('group_contact', 'create', $grpParams);
    }

    // Verify that all contacts have been created separately.
    $this->assertCount(7, $this->ids['Contact'], 'Check for number of contacts.');
  }

  /**
   * @return array
   */
  public function getCustomFieldParams(): array {
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
    return $params;
  }

  /**
   * @param array $params
   *
   * @return void
   * @throws \CRM_Core_Exception
   */
  public function getMatchesByCustomFields(array $params): void {
    $count = 0;
    foreach ($this->ids['Contact'] as $contact_id) {
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

    $ruleGroup = $this->createTestEntity('DedupeRuleGroup', [
      'contact_type' => 'Individual',
      'threshold' => 4 * count($this->ids['CustomField']),
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ]);

    foreach ($this->ids['CustomField'] as $key => $field_id) {
      $this->createTestEntity('DedupeRule', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => $this->getCustomGroupTable(),
        'rule_weight' => 4,
        'rule_field' => $this->getCustomFieldColumnName($key),
      ]);
    }

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->ids['Group']['default']);
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

}
