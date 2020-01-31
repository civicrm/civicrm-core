<?php

/**
 * Class CRM_Dedupe_DedupeMergerTest
 *
 * @group headless
 */
class CRM_Dedupe_MergerTest extends CiviUnitTestCase {

  protected $_groupId;

  protected $_contactIds = [];

  /**
   * Contacts created for the test.
   *
   * Overlaps contactIds....
   *
   * @var array
   */
  protected $contacts = [];

  /**
   * Tear down.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_group_contact',
      'civicrm_group',
      'civicrm_prevnext_cache',
    ]);
    parent::tearDown();
  }

  public function createDupeContacts() {
    // create a group to hold contacts, so that dupe checks don't consider any other contacts in the DB
    $params = [
      'name' => 'Test Dupe Merger Group',
      'title' => 'Test Dupe Merger Group',
      'domain_id' => 1,
      'is_active' => 1,
      'visibility' => 'Public Pages',
    ];

    $result = $this->callAPISuccess('group', 'create', $params);
    $this->_groupId = $result['id'];

    // contact data set

    // make dupe checks based on based on following contact sets:
    // FIRST - LAST - EMAIL
    // ---------------------------------
    // robin  - hood - robin@example.com
    // robin  - hood - robin@example.com
    // robin  - hood - hood@example.com
    // robin  - dale - robin@example.com
    // little - dale - dale@example.com
    // little - dale - dale@example.com
    // will   - dale - dale@example.com
    // will   - dale - will@example.com
    // will   - dale - will@example.com
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
      $param['version'] = 3;
      $contact = civicrm_api('contact', 'create', $param);
      $this->_contactIds[$count++] = $contact['id'];

      $grpParams = [
        'contact_id' => $contact['id'],
        'group_id' => $this->_groupId,
        'version' => 3,
      ];
      $this->callAPISuccess('group_contact', 'create', $grpParams);
    }
  }

  /**
   * Delete all created contacts.
   */
  public function deleteDupeContacts() {
    foreach ($this->_contactIds as $contactId) {
      $this->contactDelete($contactId);
    }
    $this->groupDelete($this->_groupId);
  }

  /**
   * Test the batch merge.
   */
  public function testBatchMergeSelectedDuplicates() {
    $this->createDupeContacts();

    // verify that all contacts have been created separately
    $this->assertEquals(count($this->_contactIds), 9, 'Check for number of contacts.');

    $dao = new CRM_Dedupe_DAO_RuleGroup();
    $dao->contact_type = 'Individual';
    $dao->name = 'IndividualSupervised';
    $dao->is_default = 1;
    $dao->find(TRUE);

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $this->_groupId);

    // -------------------------------------------------------------------------
    // Name and Email (reserved) Matches ( 3 pairs )
    // --------------------------------------------------------------------------
    // robin  - hood - robin@example.com
    // robin  - hood - robin@example.com
    // little - dale - dale@example.com
    // little - dale - dale@example.com
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    // so 3 pairs for - first + last + mail
    $this->assertEquals(count($foundDupes), 3, 'Check Individual-Supervised dupe rule for dupesInGroup().');

    // Run dedupe finder as the browser would
    //avoid invalid key error
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $object = new CRM_Contact_Page_DedupeFind();
    $object->set('gid', $this->_groupId);
    $object->set('rgid', $dao->id);
    $object->set('action', CRM_Core_Action::UPDATE);
    $object->setEmbedded(TRUE);
    @$object->run();

    // Retrieve pairs from prev next cache table
    $select = ['pn.is_selected' => 'is_selected'];
    $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($dao->id, $this->_groupId, [], TRUE, 0);
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals(count($foundDupes), count($pnDupePairs), 'Check number of dupe pairs in prev next cache.');

    // mark first two pairs as selected
    CRM_Core_DAO::singleValueQuery("UPDATE civicrm_prevnext_cache SET is_selected = 1 WHERE id IN ({$pnDupePairs[0]['prevnext_id']}, {$pnDupePairs[1]['prevnext_id']})");

    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals($pnDupePairs[0]['is_selected'], 1, 'Check if first record in dupe pairs is marked as selected.');
    $this->assertEquals($pnDupePairs[0]['is_selected'], 1, 'Check if second record in dupe pairs is marked as selected.');

    // batch merge selected dupes
    $result = CRM_Dedupe_Merger::batchMerge($dao->id, $this->_groupId, 'safe', 5, 1);
    $this->assertEquals(count($result['merged']), 2, 'Check number of merged pairs.');

    $stats = $this->callAPISuccess('Dedupe', 'getstatistics', [
      'group_id' => $this->_groupId,
      'rule_group_id' => $dao->id,
      'check_permissions' => TRUE,
    ])['values'];
    $this->assertEquals(['merged' => 2, 'skipped' => 0], $stats);

    // retrieve pairs from prev next cache table
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals(count($pnDupePairs), 1, 'Check number of remaining dupe pairs in prev next cache.');

    $this->deleteDupeContacts();
  }

  /**
   * Test the batch merge.
   */
  public function testBatchMergeAllDuplicates() {
    $this->createDupeContacts();

    // verify that all contacts have been created separately
    $this->assertEquals(count($this->_contactIds), 9, 'Check for number of contacts.');

    $dao = new CRM_Dedupe_DAO_RuleGroup();
    $dao->contact_type = 'Individual';
    $dao->name = 'IndividualSupervised';
    $dao->is_default = 1;
    $dao->find(TRUE);

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $this->_groupId);

    // -------------------------------------------------------------------------
    // Name and Email (reserved) Matches ( 3 pairs )
    // --------------------------------------------------------------------------
    // robin  - hood - robin@example.com
    // robin  - hood - robin@example.com
    // little - dale - dale@example.com
    // little - dale - dale@example.com
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    // so 3 pairs for - first + last + mail
    $this->assertEquals(count($foundDupes), 3, 'Check Individual-Supervised dupe rule for dupesInGroup().');

    // Run dedupe finder as the browser would
    //avoid invalid key error
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $object = new CRM_Contact_Page_DedupeFind();
    $object->set('gid', $this->_groupId);
    $object->set('rgid', $dao->id);
    $object->set('action', CRM_Core_Action::UPDATE);
    $object->setEmbedded(TRUE);
    @$object->run();

    // Retrieve pairs from prev next cache table
    $select = ['pn.is_selected' => 'is_selected'];
    $cacheKeyString = CRM_Dedupe_Merger::getMergeCacheKeyString($dao->id, $this->_groupId, [], TRUE, 0);
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);

    $this->assertEquals(count($foundDupes), count($pnDupePairs), 'Check number of dupe pairs in prev next cache.');

    // batch merge all dupes
    $result = CRM_Dedupe_Merger::batchMerge($dao->id, $this->_groupId, 'safe', 5, 2);
    $this->assertEquals(count($result['merged']), 3, 'Check number of merged pairs.');

    $stats = $this->callAPISuccess('Dedupe', 'getstatistics', [
      'rule_group_id' => $dao->id,
      'group_id' => $this->_groupId,
    ]);
    // retrieve pairs from prev next cache table
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals(count($pnDupePairs), 0, 'Check number of remaining dupe pairs in prev next cache.');

    $this->deleteDupeContacts();
  }

  /**
   * The goal of this function is to test that all required tables are returned.
   */
  public function testGetCidRefs() {
    $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'Contacts');
    $this->assertEquals(array_merge($this->getStaticCIDRefs(), $this->getHackedInCIDRef()), CRM_Dedupe_Merger::cidRefs());
    $this->assertEquals(array_merge($this->getCalculatedCIDRefs(), $this->getHackedInCIDRef()), CRM_Dedupe_Merger::cidRefs());
  }

  /**
   * Get the list of not-really-cid-refs that are currently hacked in.
   *
   * This is hacked into getCIDs function.
   *
   * @return array
   */
  public function getHackedInCIDRef() {
    return [
      'civicrm_entity_tag' => [
        0 => 'entity_id',
      ],
    ];
  }

  /**
   * Test function that gets duplicate pairs.
   *
   * It turns out there are 2 code paths retrieving this data so my initial
   * focus is on ensuring they match.
   */
  public function testGetMatches() {
    $this->setupMatchData();

    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
    ])['values'];
    $this->assertEquals([
      0 => [
        'srcID' => $this->contacts[1]['id'],
        'srcName' => 'Mr. Mickey Mouse II',
        'dstID' => $this->contacts[0]['id'],
        'dstName' => 'Mr. Mickey Mouse II',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
      1 => [
        'srcID' => $this->contacts[3]['id'],
        'srcName' => 'Mr. Minnie Mouse II',
        'dstID' => $this->contacts[2]['id'],
        'dstName' => 'Mr. Minnie Mouse II',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
    ], $pairs);
  }

  /**
   * Test results are returned when criteria are passed in.
   */
  public function testGetMatchesCriteriaMatched() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
      'criteria' => ['contact' => ['id' => ['>' => 1]]],
    ])['values'];
    $this->assertCount(2, $pairs);
  }

  /**
   * Test results are returned when criteria are passed in & limit is  respected.
   */
  public function testGetMatchesCriteriaMatchedWithLimit() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
      'criteria' => ['contact' => ['id' => ['>' => 1]]],
      'options' => ['limit' => 1],
    ])['values'];
    $this->assertCount(1, $pairs);
  }

  /**
   * Test results are returned when criteria are passed in & limit is  respected.
   */
  public function testGetMatchesCriteriaMatchedWithSearchLimit() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
      'criteria' => ['contact' => ['id' => ['>' => 1]]],
      'search_limit' => 1,
    ])['values'];
    $this->assertCount(1, $pairs);
  }

  /**
   * Test getting matches where there are  no criteria.
   */
  public function testGetMatchesNoCriteria() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
    ])['values'];
    $this->assertCount(2, $pairs);
  }

  /**
   * Test getting matches with a limit in play.
   */
  public function testGetMatchesNoCriteriaButLimit() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
      'options' => ['limit' => 1],
    ])['values'];
    $this->assertCount(1, $pairs);
  }

  /**
   * Test that if criteria are passed and there are no matching contacts no matches are returned.
   */
  public function testGetMatchesCriteriaNotMatched() {
    $this->setupMatchData();
    $pairs = $this->callAPISuccess('Dedupe', 'getduplicates', [
      'rule_group_id' => 1,
      'criteria' => ['contact' => ['id' => ['>' => 100000]]],
    ])['values'];
    $this->assertCount(0, $pairs);
  }

  /**
   * Test function that gets organization pairs.
   *
   * Note the rule will match on organization_name OR email - hence lots of
   * matches.
   *
   * @throws \Exception
   */
  public function testGetOrganizationMatches() {
    $this->setupMatchData();
    $ruleGroups = $this->callAPISuccessGetSingle('RuleGroup', [
      'contact_type' => 'Organization',
      'used' => 'Supervised',
    ]);

    $pairs = CRM_Dedupe_Merger::getDuplicatePairs(
      $ruleGroups['id'],
      NULL,
      TRUE,
      25,
      FALSE
    );

    $expectedPairs = [
      0 => [
        'srcID' => $this->contacts[5]['id'],
        'srcName' => 'Walt Disney Ltd',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
      1 => [
        'srcID' => $this->contacts[7]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[6]['id'],
        'dstName' => 'Walt Disney',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
      2 => [
        'srcID' => $this->contacts[6]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
      3 => [
        'srcID' => $this->contacts[6]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[5]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
    ];
    usort($pairs, [__CLASS__, 'compareDupes']);
    usort($expectedPairs, [__CLASS__, 'compareDupes']);
    $this->assertEquals($expectedPairs, $pairs);
  }

  /**
   * Function to sort $duplicate records in a stable way.
   *
   * @param array $a
   * @param array $b
   *
   * @return int
   */
  public static function compareDupes($a, $b) {
    foreach (['srcName', 'dstName', 'srcID', 'dstID'] as $field) {
      if ($a[$field] != $b[$field]) {
        return ($a[$field] < $b[$field]) ? 1 : -1;
      }
    }
    return 0;
  }

  /**
   *  Test function that gets organization duplicate pairs.
   *
   * @throws \Exception
   */
  public function testGetOrganizationMatchesInGroup() {
    $this->setupMatchData();
    $ruleGroups = $this->callAPISuccessGetSingle('RuleGroup', [
      'contact_type' => 'Organization',
      'used' => 'Supervised',
    ]);

    $groupID = $this->groupCreate(['title' => 'she-mice']);

    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $this->contacts[4]['id'],
    ]);

    $pairs = CRM_Dedupe_Merger::getDuplicatePairs(
      $ruleGroups['id'],
      $groupID,
      TRUE,
      25,
      FALSE
    );

    $this->assertEquals([
      0 => [
        'srcID' => $this->contacts[5]['id'],
        'srcName' => 'Walt Disney Ltd',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
      1 => [
        'srcID' => $this->contacts[6]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
    ], $pairs);

    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $this->contacts[5]['id'],
    ]);
    CRM_Core_DAO::executeQuery("DELETE FROM civicrm_prevnext_cache");
    $pairs = CRM_Dedupe_Merger::getDuplicatePairs(
      $ruleGroups['id'],
      $groupID,
      TRUE,
      25,
      FALSE
    );

    $this->assertEquals([
      0 => [
        'srcID' => $this->contacts[5]['id'],
        'srcName' => 'Walt Disney Ltd',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
      1 => [
        'srcID' => $this->contacts[6]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[4]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
      2 => [
        'srcID' => $this->contacts[6]['id'],
        'srcName' => 'Walt Disney',
        'dstID' => $this->contacts[5]['id'],
        'dstName' => 'Walt Disney Ltd',
        'weight' => 10,
        'canMerge' => TRUE,
      ],
    ], $pairs);
  }

  /**
   * Test function that gets duplicate pairs.
   *
   * It turns out there are 2 code paths retrieving this data so my initial
   * focus is on ensuring they match.
   */
  public function testGetMatchesInGroup() {
    $this->setupMatchData();

    $groupID = $this->groupCreate(['title' => 'she-mice']);

    $this->callAPISuccess('GroupContact', 'create', [
      'group_id' => $groupID,
      'contact_id' => $this->contacts[3]['id'],
    ]);

    $pairs = CRM_Dedupe_Merger::getDuplicatePairs(
      1,
      $groupID,
      TRUE,
      25,
      FALSE
    );

    $this->assertEquals([
      0 => [
        'srcID' => $this->contacts[3]['id'],
        'srcName' => 'Mr. Minnie Mouse II',
        'dstID' => $this->contacts[2]['id'],
        'dstName' => 'Mr. Minnie Mouse II',
        'weight' => 20,
        'canMerge' => TRUE,
      ],
    ], $pairs);
  }

  /**
   * Test the special info handling is unchanged after cleanup.
   *
   * Note the handling is silly - we are testing to lock in over short term
   * changes not to imply any contract on the function.
   */
  public function testGetRowsElementsAndInfoSpecialInfo() {
    $contact1 = $this->individualCreate([
      'preferred_communication_method' => [],
      'communication_style_id' => 'Familiar',
      'prefix_id' => 'Mrs.',
      'suffix_id' => 'III',
    ]);
    $contact2 = $this->individualCreate([
      'preferred_communication_method' => [
        'SMS',
        'Fax',
      ],
      'communication_style_id' => 'Formal',
      'gender_id' => 'Female',
    ]);
    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($contact1, $contact2);
    $rows = $rowsElementsAndInfo['rows'];
    $this->assertEquals([
      'main' => 'Mrs.',
      'other' => 'Mr.',
      'title' => 'Individual Prefix',
    ], $rows['move_prefix_id']);
    $this->assertEquals([
      'main' => 'III',
      'other' => 'II',
      'title' => 'Individual Suffix',
    ], $rows['move_suffix_id']);
    $this->assertEquals([
      'main' => '',
      'other' => 'Female',
      'title' => 'Gender',
    ], $rows['move_gender_id']);
    $this->assertEquals([
      'main' => 'Familiar',
      'other' => 'Formal',
      'title' => 'Communication Style',
    ], $rows['move_communication_style_id']);
    $this->assertEquals(1, $rowsElementsAndInfo['migration_info']['move_communication_style_id']);
    $this->assertEquals([
      'main' => '',
      'other' => 'SMS, Fax',
      'title' => 'Preferred Communication Method',
    ], $rows['move_preferred_communication_method']);
    $this->assertEquals('45', $rowsElementsAndInfo['migration_info']['move_preferred_communication_method']);
  }

  /**
   * Test migration of Membership.
   */
  public function testMergeMembership() {
    // Contacts setup
    $this->setupMatchData();
    $originalContactID = $this->contacts[0]['id'];
    $duplicateContactID = $this->contacts[1]['id'];

    //Add Membership for the duplicate contact.
    $memTypeId = $this->membershipTypeCreate();
    $this->callAPISuccess('Membership', 'create', [
      'membership_type_id' => $memTypeId,
      'contact_id' => $duplicateContactID,
    ]);
    //Assert if 'add new' checkbox is enabled on the merge form.
    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($originalContactID, $duplicateContactID);
    foreach ($rowsElementsAndInfo['elements'] as $element) {
      if (!empty($element[3]) && $element[3] == 'add new') {
        $checkedAttr = ['checked' => 'checked'];
        $this->checkArrayEquals($element[4], $checkedAttr);
      }
    }

    //Merge and move the mem to the main contact.
    $this->mergeContacts($originalContactID, $duplicateContactID, [
      'move_rel_table_memberships' => 1,
      'operation' => ['move_rel_table_memberships' => ['add' => 1]],
    ]);

    //Check if membership is correctly transferred to original contact.
    $originalContactMembership = $this->callAPISuccess('Membership', 'get', [
      'membership_type_id' => $memTypeId,
      'contact_id' => $originalContactID,
    ]);
    $this->assertEquals(1, $originalContactMembership['count']);
  }

  /**
   * CRM-19653 : Test that custom field data should/shouldn't be overriden on
   *   selecting/not selecting option to migrate data respectively
   */
  public function testCustomDataOverwrite() {
    // Create Custom Field
    $createGroup = $this->setupCustomGroupForIndividual();
    $createField = $this->setupCustomField('Graduation', $createGroup);
    $customFieldName = "custom_" . $createField['id'];

    // Contacts setup
    $this->setupMatchData();

    $originalContactID = $this->contacts[0]['id'];
    // used as duplicate contact in 1st use-case
    $duplicateContactID1 = $this->contacts[1]['id'];
    // used as duplicate contact in 2nd use-case
    $duplicateContactID2 = $this->contacts[2]['id'];

    // update the text custom field for original contact with value 'abc'
    $this->callAPISuccess('Contact', 'create', [
      'id' => $originalContactID,
      "{$customFieldName}" => 'abc',
    ]);
    $this->assertCustomFieldValue($originalContactID, 'abc', $customFieldName);

    // update the text custom field for duplicate contact 1 with value 'def'
    $this->callAPISuccess('Contact', 'create', [
      'id' => $duplicateContactID1,
      "{$customFieldName}" => 'def',
    ]);
    $this->assertCustomFieldValue($duplicateContactID1, 'def', $customFieldName);

    // update the text custom field for duplicate contact 2 with value 'ghi'
    $this->callAPISuccess('Contact', 'create', [
      'id' => $duplicateContactID2,
      "{$customFieldName}" => 'ghi',
    ]);
    $this->assertCustomFieldValue($duplicateContactID2, 'ghi', $customFieldName);

    /*** USE-CASE 1: DO NOT OVERWRITE CUSTOM FIELD VALUE **/
    $this->mergeContacts($originalContactID, $duplicateContactID1, [
      "move_{$customFieldName}" => NULL,
    ]);
    $this->assertCustomFieldValue($originalContactID, 'abc', $customFieldName);

    /*** USE-CASE 2: OVERWRITE CUSTOM FIELD VALUE **/
    $this->mergeContacts($originalContactID, $duplicateContactID2, [
      "move_{$customFieldName}" => 'ghi',
    ]);
    $this->assertCustomFieldValue($originalContactID, 'ghi', $customFieldName);

    // cleanup created custom set
    $this->callAPISuccess('CustomField', 'delete', ['id' => $createField['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $createGroup['id']]);
  }

  /**
   * Creatd Date merge cases
   * @return array
   */
  public function createdDateMergeCases() {
    $cases = [];
    // Normal pattern merge into the lower id
    $cases[] = [0, 1];
    // Check if we flipped the contacts that it still does right thing
    $cases[] = [1, 0];
    return $cases;
  }

  /**
   * dev/core#996 Ensure that the oldest created date is retained even if duplicates have been flipped
   * @dataProvider createdDateMergeCases
   */
  public function testCreatedDatePostMerge($keepContactKey, $duplicateContactKey) {
    $this->setupMatchData();
    $lowerContactCreatedDate = $this->callAPISuccess('Contact', 'getsingle', [
      'id' => $this->contacts[0]['id'],
      'return' => ['created_date'],
    ])['created_date'];
    // Assume contats have been flipped in the UL so merging into the higher id
    $this->mergeContacts($this->contacts[$keepContactKey]['id'], $this->contacts[$duplicateContactKey]['id'], []);
    $this->assertEquals($lowerContactCreatedDate, $this->callAPISuccess('Contact', 'getsingle', ['id' => $this->contacts[$keepContactKey]['id'], 'return' => ['created_date']])['created_date']);
  }

  /**
   * Verifies that when a contact with a custom field value is merged into a
   * contact without a record int its corresponding custom group table, and none
   * of the custom fields of that custom table are selected, the value is not
   * merged in.
   */
  public function testMigrationOfUnselectedCustomDataOnEmptyCustomRecord() {
    // Create Custom Fields
    $createGroup = $this->setupCustomGroupForIndividual();
    $customField1 = $this->setupCustomField('TestField', $createGroup);

    // Create multi-value custom field
    $multiGroup = $this->CustomGroupMultipleCreateByParams();
    $multiField = $this->customFieldCreate([
      'custom_group_id' => $multiGroup['id'],
      'label' => 'field_1' . $multiGroup['id'],
      'in_selector' => 1,
    ]);

    // Contacts setup
    $this->setupMatchData();
    $originalContactID = $this->contacts[0]['id'];
    $duplicateContactID = $this->contacts[1]['id'];

    // Update the text custom fields for duplicate contact
    $this->callAPISuccess('Contact', 'create', [
      'id' => $duplicateContactID,
      "custom_{$customField1['id']}" => 'abc',
      "custom_{$multiField['id']}" => 'def',
    ]);
    $this->assertCustomFieldValue($duplicateContactID, 'abc', "custom_{$customField1['id']}");
    $this->assertCustomFieldValue($duplicateContactID, 'def', "custom_{$multiField['id']}");

    // Merge, and ensure that no value was migrated
    $this->mergeContacts($originalContactID, $duplicateContactID, [
      "move_custom_{$customField1['id']}" => NULL,
      "move_rel_table_custom_{$multiGroup['id']}" => NULL,
    ]);
    $this->assertCustomFieldValue($originalContactID, '', "custom_{$customField1['id']}");
    $this->assertCustomFieldValue($originalContactID, '', "custom_{$multiField['id']}");

    // cleanup created custom set
    $this->callAPISuccess('CustomField', 'delete', ['id' => $customField1['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $createGroup['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $multiField['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $multiGroup['id']]);
  }

  /**
   * Tests that if only part of the custom fields of a custom group are selected
   * for a merge, only those values are merged, while all other fields of the
   * custom group retain their original value, specifically for a contact with
   * no records on the custom group table.
   */
  public function testMigrationOfSomeCustomDataOnEmptyCustomRecord() {
    // Create Custom Fields
    $createGroup = $this->setupCustomGroupForIndividual();
    $customField1 = $this->setupCustomField('Test1', $createGroup);
    $customField2 = $this->setupCustomField('Test2', $createGroup);

    // Create multi-value custom field
    $multiGroup = $this->CustomGroupMultipleCreateByParams();
    $multiField = $this->customFieldCreate([
      'custom_group_id' => $multiGroup['id'],
      'label' => 'field_1' . $multiGroup['id'],
      'in_selector' => 1,
    ]);

    // Contacts setup
    $this->setupMatchData();
    $originalContactID = $this->contacts[0]['id'];
    $duplicateContactID = $this->contacts[1]['id'];

    // Update the text custom fields for duplicate contact
    $this->callAPISuccess('Contact', 'create', [
      'id' => $duplicateContactID,
      "custom_{$customField1['id']}" => 'abc',
      "custom_{$customField2['id']}" => 'def',
      "custom_{$multiField['id']}" => 'ghi',
    ]);
    $this->assertCustomFieldValue($duplicateContactID, 'abc', "custom_{$customField1['id']}");
    $this->assertCustomFieldValue($duplicateContactID, 'def', "custom_{$customField2['id']}");
    $this->assertCustomFieldValue($duplicateContactID, 'ghi', "custom_{$multiField['id']}");

    // Perform merge
    $this->mergeContacts($originalContactID, $duplicateContactID, [
      "move_custom_{$customField1['id']}" => NULL,
      "move_custom_{$customField2['id']}" => 'def',
      "move_rel_table_custom_{$multiGroup['id']}" => '1',
    ]);
    $this->assertCustomFieldValue($originalContactID, '', "custom_{$customField1['id']}");
    $this->assertCustomFieldValue($originalContactID, 'def', "custom_{$customField2['id']}");
    $this->assertCustomFieldValue($originalContactID, 'ghi', "custom_{$multiField['id']}");

    // cleanup created custom set
    $this->callAPISuccess('CustomField', 'delete', ['id' => $customField1['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $customField2['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $createGroup['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $multiField['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $multiGroup['id']]);
  }

  /**
   * Test that ContactReference fields are updated to point to the main contact
   * after a merge is performed and the duplicate contact is deleted.
   */
  public function testMigrationOfContactReferenceCustomField() {
    // Create Custom Fields
    $contactGroup = $this->setupCustomGroupForIndividual();
    $activityGroup = $this->customGroupCreate([
      'name'    => 'test_group_activity',
      'extends' => 'Activity',
    ]);
    $refFieldContact = $this->customFieldCreate([
      'custom_group_id' => $contactGroup['id'],
      'label'           => 'field_1' . $contactGroup['id'],
      'data_type'       => 'ContactReference',
      'default_value'   => NULL,
    ]);
    $refFieldActivity = $this->customFieldCreate([
      'custom_group_id' => $activityGroup['id'],
      'label'           => 'field_1' . $activityGroup['id'],
      'data_type'       => 'ContactReference',
      'default_value'   => NULL,
    ]);

    // Contacts setup
    $this->setupMatchData();
    $originalContactID = $this->contacts[0]['id'];
    $duplicateContactID = $this->contacts[1]['id'];

    // create a contact that won't be merged but has a ContactReference field
    // pointing to the duplicate (to be deleted) contact
    $unrelatedContact = $this->individualCreate([
      'first_name'               => 'Unrelated',
      'first_name'               => 'Contact',
      'email'                    => 'unrelated@example.com',
      "custom_{$refFieldContact['id']}" => $duplicateContactID,
    ]);
    // also create an activity with a ContactReference custom field
    $activity = $this->activityCreate([
      'target_contact_id'                => $unrelatedContact,
      "custom_{$refFieldActivity['id']}" => $duplicateContactID,
    ]);

    // verify that the fields were set
    $this->assertCustomFieldValue($unrelatedContact, $duplicateContactID, "custom_{$refFieldContact['id']}");
    $this->assertEntityCustomFieldValue('Activity', $activity['id'], $duplicateContactID, "custom_{$refFieldActivity['id']}_id");

    // Perform merge
    $this->mergeContacts($originalContactID, $duplicateContactID, []);

    // verify that the ContactReference fields were updated to point to the surviving contact post-merge
    $this->assertCustomFieldValue($unrelatedContact, $originalContactID, "custom_{$refFieldContact['id']}");
    $this->assertEntityCustomFieldValue('Activity', $activity['id'], $originalContactID, "custom_{$refFieldActivity['id']}_id");

    // cleanup created custom set
    $this->callAPISuccess('CustomField', 'delete', ['id' => $refFieldContact['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $contactGroup['id']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $refFieldActivity['id']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $activityGroup['id']]);
  }

  /**
   * Calls merge method on given contacts, with values given in $params array.
   *
   * @param $originalContactID
   *   ID of target contact
   * @param $duplicateContactID
   *   ID of contact to be merged
   * @param $params
   *   Array of fields to be merged from source into target contact, of the form
   *   ['move_<fieldName>' => <fieldValue>]
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  private function mergeContacts($originalContactID, $duplicateContactID, $params) {
    $rowsElementsAndInfo = CRM_Dedupe_Merger::getRowsElementsAndInfo($originalContactID, $duplicateContactID);

    $migrationData = [
      'main_details' => $rowsElementsAndInfo['main_details'],
      'other_details' => $rowsElementsAndInfo['other_details'],
    ];

    // Migrate data of duplicate contact
    CRM_Dedupe_Merger::moveAllBelongings($originalContactID, $duplicateContactID, array_merge($migrationData, $params));
  }

  /**
   * Checks if the expected value for the given field corresponds to what is
   * stored in the database for the given contact ID.
   *
   * @param $contactID
   * @param $expectedValue
   * @param $customFieldName
   */
  private function assertCustomFieldValue($contactID, $expectedValue, $customFieldName) {
    $this->assertEntityCustomFieldValue('Contact', $contactID, $expectedValue, $customFieldName);
  }

  /**
   * Check if the custom field of the given field and entity id matches the
   * expected value
   *
   * @param $entity
   * @param $id
   * @param $expectedValue
   * @param $customFieldName
   */
  private function assertEntityCustomFieldValue($entity, $id, $expectedValue, $customFieldName) {
    $data = $this->callAPISuccess($entity, 'getsingle', [
      'id'     => $id,
      'return' => [$customFieldName],
    ]);

    $this->assertEquals($expectedValue, $data[$customFieldName], "Custom field value was supposed to be '{$expectedValue}', '{$data[$customFieldName]}' found.");
  }

  /**
   * Creates a custom group to run tests on contacts that are individuals.
   *
   * @return array
   *   Data for the created custom group record
   */
  private function setupCustomGroupForIndividual() {
    $customGroup = $this->callAPISuccess('custom_group', 'get', [
      'name' => 'test_group',
    ]);

    if ($customGroup['count'] > 0) {
      $this->callAPISuccess('CustomGroup', 'delete', ['id' => $customGroup['id']]);
    }

    $customGroup = $this->callAPISuccess('custom_group', 'create', [
      'title' => 'Test_Group',
      'name' => 'test_group',
      'extends' => ['Individual'],
      'style' => 'Inline',
      'is_multiple' => FALSE,
      'is_active' => 1,
    ]);

    return $customGroup;
  }

  /**
   * Creates a custom field on the provided custom group with the given field
   * label.
   *
   * @param $fieldLabel
   * @param $createGroup
   *
   * @return array
   *   Data for the created custom field record
   */
  private function setupCustomField($fieldLabel, $createGroup) {
    return $this->callAPISuccess('custom_field', 'create', [
      'label' => $fieldLabel,
      'data_type' => 'Alphanumeric',
      'html_type' => 'Text',
      'custom_group_id' => $createGroup['id'],
    ]);
  }

  /**
   * Set up some contacts for our matching.
   */
  public function setupMatchData() {
    $fixtures = [
      [
        'first_name' => 'Mickey',
        'last_name' => 'Mouse',
        'email' => 'mickey@mouse.com',
      ],
      [
        'first_name' => 'Mickey',
        'last_name' => 'Mouse',
        'email' => 'mickey@mouse.com',
      ],
      [
        'first_name' => 'Minnie',
        'last_name' => 'Mouse',
        'email' => 'mickey@mouse.com',
      ],
      [
        'first_name' => 'Minnie',
        'last_name' => 'Mouse',
        'email' => 'mickey@mouse.com',
      ],
    ];
    foreach ($fixtures as $fixture) {
      $contactID = $this->individualCreate($fixture);
      $this->contacts[] = array_merge($fixture, ['id' => $contactID]);
      sleep(2);
    }
    $organizationFixtures = [
      [
        'organization_name' => 'Walt Disney Ltd',
        'email' => 'walt@disney.com',
      ],
      [
        'organization_name' => 'Walt Disney Ltd',
        'email' => 'walt@disney.com',
      ],
      [
        'organization_name' => 'Walt Disney',
        'email' => 'walt@disney.com',
      ],
      [
        'organization_name' => 'Walt Disney',
        'email' => 'walter@disney.com',
      ],
    ];
    foreach ($organizationFixtures as $fixture) {
      $contactID = $this->organizationCreate($fixture);
      $this->contacts[] = array_merge($fixture, ['id' => $contactID]);
    }
  }

  /**
   * Get the list of tables that refer to the CID.
   *
   * This is a statically maintained (in this test list).
   *
   * There is also a check against an automated list but having both seems to
   * add extra stability to me. They do not change often.
   */
  public function getStaticCIDRefs() {
    return [
      'civicrm_acl_cache' => [
        0 => 'contact_id',
      ],
      'civicrm_acl_contact_cache' => [
        0 => 'contact_id',
      ],
      'civicrm_action_log' => [
        0 => 'contact_id',
      ],
      'civicrm_activity_contact' => [
        0 => 'contact_id',
      ],
      'civicrm_address' => [
        0 => 'contact_id',
      ],
      'civicrm_batch' => [
        0 => 'created_id',
        1 => 'modified_id',
      ],
      'civicrm_campaign' => [
        0 => 'created_id',
        1 => 'last_modified_id',
      ],
      'civicrm_case_contact' => [
        0 => 'contact_id',
      ],
      'civicrm_contact' => [
        0 => 'primary_contact_id',
        1 => 'employer_id',
      ],
      'civicrm_contribution' => [
        0 => 'contact_id',
      ],
      'civicrm_contribution_page' => [
        0 => 'created_id',
      ],
      'civicrm_contribution_recur' => [
        0 => 'contact_id',
      ],
      'civicrm_contribution_soft' => [
        0 => 'contact_id',
      ],
      'civicrm_custom_group' => [
        0 => 'created_id',
      ],
      'civicrm_dashboard_contact' => [
        0 => 'contact_id',
      ],
      'civicrm_dedupe_exception' => [
        0 => 'contact_id1',
        1 => 'contact_id2',
      ],
      'civicrm_domain' => [
        0 => 'contact_id',
      ],
      'civicrm_email' => [
        0 => 'contact_id',
      ],
      'civicrm_event' => [
        0 => 'created_id',
      ],
      'civicrm_event_carts' => [
        0 => 'user_id',
      ],
      'civicrm_financial_account' => [
        0 => 'contact_id',
      ],
      'civicrm_financial_item' => [
        0 => 'contact_id',
      ],
      'civicrm_grant' => [
        0 => 'contact_id',
      ],
      'civicrm_group' => [
        0 => 'created_id',
        1 => 'modified_id',
      ],
      'civicrm_group_contact' => [
        0 => 'contact_id',
      ],
      'civicrm_group_contact_cache' => [
        0 => 'contact_id',
      ],
      'civicrm_group_organization' => [
        0 => 'organization_id',
      ],
      'civicrm_im' => [
        0 => 'contact_id',
      ],
      'civicrm_log' => [
        0 => 'modified_id',
      ],
      'civicrm_mailing' => [
        0 => 'created_id',
        1 => 'scheduled_id',
        2 => 'approver_id',
      ],
      'civicrm_file' => [
        'created_id',
      ],
      'civicrm_mailing_abtest' => [
        0 => 'created_id',
      ],
      'civicrm_mailing_event_queue' => [
        0 => 'contact_id',
      ],
      'civicrm_mailing_event_subscribe' => [
        0 => 'contact_id',
      ],
      'civicrm_mailing_recipients' => [
        0 => 'contact_id',
      ],
      'civicrm_membership' => [
        0 => 'contact_id',
      ],
      'civicrm_membership_log' => [
        0 => 'modified_id',
      ],
      'civicrm_membership_type' => [
        0 => 'member_of_contact_id',
      ],
      'civicrm_note' => [
        0 => 'contact_id',
      ],
      'civicrm_openid' => [
        0 => 'contact_id',
      ],
      'civicrm_participant' => [
        0 => 'contact_id',
        //CRM-16761
        1 => 'transferred_to_contact_id',
      ],
      'civicrm_payment_token' => [
        0 => 'contact_id',
        1 => 'created_id',
      ],
      'civicrm_pcp' => [
        0 => 'contact_id',
      ],
      'civicrm_phone' => [
        0 => 'contact_id',
      ],
      'civicrm_pledge' => [
        0 => 'contact_id',
      ],
      'civicrm_print_label' => [
        0 => 'created_id',
      ],
      'civicrm_relationship' => [
        0 => 'contact_id_a',
        1 => 'contact_id_b',
      ],
      'civicrm_report_instance' => [
        0 => 'created_id',
        1 => 'owner_id',
      ],
      'civicrm_setting' => [
        0 => 'contact_id',
        1 => 'created_id',
      ],
      'civicrm_subscription_history' => [
        0 => 'contact_id',
      ],
      'civicrm_survey' => [
        0 => 'created_id',
        1 => 'last_modified_id',
      ],
      'civicrm_tag' => [
        0 => 'created_id',
      ],
      'civicrm_uf_group' => [
        0 => 'created_id',
      ],
      'civicrm_uf_match' => [
        0 => 'contact_id',
      ],
      'civicrm_value_testgetcidref_1' => [
        0 => 'entity_id',
      ],
      'civicrm_website' => [
        0 => 'contact_id',
      ],
    ];
  }

  /**
   * Get a list of CIDs that is calculated off the schema.
   *
   * Note this is an expensive and table locking query. Should be safe in tests
   * though.
   */
  public function getCalculatedCIDRefs() {
    $cidRefs = [];
    $sql = "
SELECT
    table_name,
    column_name
FROM information_schema.key_column_usage
WHERE
    referenced_table_schema = database() AND
    referenced_table_name = 'civicrm_contact' AND
    referenced_column_name = 'id';
      ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $cidRefs[$dao->table_name][] = $dao->column_name;
    }
    // Do specific re-ordering changes to make this the same as the ref validated one.
    // The above query orders by FK alphabetically.
    // There might be cleverer ways to do this but it shouldn't change much.
    $cidRefs['civicrm_contact'][0] = 'primary_contact_id';
    $cidRefs['civicrm_contact'][1] = 'employer_id';
    $cidRefs['civicrm_acl_contact_cache'][0] = 'contact_id';
    $cidRefs['civicrm_mailing'][0] = 'created_id';
    $cidRefs['civicrm_mailing'][1] = 'scheduled_id';
    $cidRefs['civicrm_mailing'][2] = 'approver_id';
    return $cidRefs;
  }

}
