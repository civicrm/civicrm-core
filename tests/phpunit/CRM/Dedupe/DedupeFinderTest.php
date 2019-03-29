<?php

/**
 * Class CRM_Dedupe_DedupeFinderTest
 * @group headless
 */
class CRM_Dedupe_DedupeFinderTest extends CiviUnitTestCase {

  /**
   * IDs of created contacts.
   *
   * @var array
   */
  protected $contactIDs = array();

  /**
   * ID of the group holding the contacts.
   *
   * @var int
   */
  protected $groupID;

  /**
   * Clean up after the test.
   */
  public function tearDown() {

    foreach ($this->contactIDs as $contactId) {
      $this->contactDelete($contactId);
    }
    if ($this->groupID) {
      $this->callAPISuccess('group', 'delete', array('id' => $this->groupID));
    }
    parent::tearDown();
  }

  /**
   * Test the unsupervised dedupe rule against a group.
   *
   * @throws \Exception
   */
  public function testUnsupervisedDupes() {
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
    $this->setupForGroupDedupe();

    $ruleGroup = $this->callAPISuccessGetSingle('RuleGroup', array('is_reserved' => 1, 'contact_type' => 'Individual', 'used' => 'Unsupervised'));

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertEquals(count($foundDupes), 3, 'Check Individual-Fuzzy dupe rule for dupesInGroup().');
  }

  /**
   * Test that a rule set to is_reserved = 0 works.
   *
   * There is a different search used dependent on this variable.
   */
  public function testCustomRule() {
    $this->setupForGroupDedupe();

    $ruleGroup = $this->callAPISuccess('RuleGroup', 'create', array(
      'contact_type' => 'Individual',
      'threshold' => 8,
      'used' => 'General',
      'name' => 'TestRule',
      'title' => 'TestRule',
      'is_reserved' => 0,
    ));
    $rules = [];
    foreach (array('birth_date', 'first_name', 'last_name') as $field) {
      $rules[$field] = $this->callAPISuccess('Rule', 'create', [
        'dedupe_rule_group_id' => $ruleGroup['id'],
        'rule_table' => 'civicrm_contact',
        'rule_weight' => 4,
        'rule_field' => $field,
      ]);
    }
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    $this->assertEquals(count($foundDupes), 4);
    CRM_Dedupe_Finder::dupes($ruleGroup['id']);

  }

  /**
   * Test the supervised dedupe rule against a group.
   *
   * @throws \Exception
   */
  public function testSupervisedDupes() {
    $this->setupForGroupDedupe();
    $ruleGroup = $this->callAPISuccessGetSingle('RuleGroup', array('is_reserved' => 1, 'contact_type' => 'Individual', 'used' => 'Supervised'));
    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($ruleGroup['id'], $this->groupID);
    // -------------------------------------------------------------------------
    // default dedupe rule: threshold = 20 => (First + Last + Email) Matches ( 1 pair )
    // --------------------------------------------------------------------------
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    // so 1 pair for - first + last + mail
    $this->assertEquals(count($foundDupes), 1, 'Check Individual-Fuzzy dupe rule for dupesInGroup().');
  }

  /**
   * Test dupesByParams function.
   */
  public function testDupesByParams() {
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
    $params = array(
      array(
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'hood@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'robin',
        'last_name' => 'dale',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'little',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ),
    );

    $this->hookClass->setHook('civicrm_findDuplicates', array($this, 'hook_civicrm_findDuplicates'));

    $count = 1;

    foreach ($params as $param) {
      $contact = $this->callAPISuccess('contact', 'create', $param);
      $params = array(
        'contact_id' => $contact['id'],
        'street_address' => 'Ambachtstraat 23',
        'location_type_id' => 1,
      );
      $this->callAPISuccess('address', 'create', $params);
      $contactIds[$count++] = $contact['id'];
    }

    // verify that all contacts have been created separately
    $this->assertEquals(count($contactIds), 7, 'Check for number of contacts.');

    $fields = array(
      'first_name' => 'robin',
      'last_name' => 'hood',
      'email' => 'hood@example.com',
      'street_address' => 'Ambachtstraat 23',
    );
    CRM_Core_TemporaryErrorScope::useException();
    $ids = CRM_Contact_BAO_Contact::getDuplicateContacts($fields, 'Individual', 'General', [], TRUE, NULL, ['event_id' => 1]);

    // Check with default Individual-General rule
    $this->assertEquals(count($ids), 2, 'Check Individual-General rule for dupesByParams().');

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
   * Set up a group of dedupable contacts.
   */
  protected function setupForGroupDedupe() {
    $params = array(
      'name' => 'Dupe Group',
      'title' => 'New Test Dupe Group',
      'domain_id' => 1,
      'is_active' => 1,
      'visibility' => 'Public Pages',
    );

    $result = $this->callAPISuccess('group', 'create', $params);
    $this->groupID = $result['id'];

    $params = array(
      array(
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
        'birth_date' => '2016-01-01',
      ),
      array(
        'first_name' => 'robin',
        'last_name' => 'hood',
        'email' => 'hood@example.com',
        'contact_type' => 'Individual',
        'birth_date' => '2016-01-01',
      ),
      array(
        'first_name' => 'robin',
        'last_name' => 'dale',
        'email' => 'robin@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'little',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'dale@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ),
      array(
        'first_name' => 'will',
        'last_name' => 'dale',
        'email' => 'will@example.com',
        'contact_type' => 'Individual',
      ),
    );

    $count = 1;
    foreach ($params as $param) {
      $contact = $this->callAPISuccess('contact', 'create', $param);
      $this->contactIDs[$count++] = $contact['id'];

      $grpParams = array(
        'contact_id' => $contact['id'],
        'group_id' => $this->groupID,
      );
      $this->callAPISuccess('group_contact', 'create', $grpParams);
    }

    // verify that all contacts have been created separately
    $this->assertEquals(count($this->contactIDs), 7, 'Check for number of contacts.');
  }

}
