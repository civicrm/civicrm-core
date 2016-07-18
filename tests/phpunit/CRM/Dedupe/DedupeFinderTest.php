<?php

/**
 * Class CRM_Dedupe_DedupeFinderTest
 * @group headless
 */
class CRM_Dedupe_DedupeFinderTest extends CiviUnitTestCase {
  public function testFuzzyDupes() {
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

    // create a group to hold contacts, so that dupe checks don't consider any other contacts in the DB
    $params = array(
      'name' => 'Dupe Group',
      'title' => 'New Test Dupe Group',
      'domain_id' => 1,
      'is_active' => 1,
      'visibility' => 'Public Pages',
    );

    $result = $this->callAPISuccess('group', 'create', $params);
    $groupId = $result['id'];

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

    $count = 1;
    foreach ($params as $param) {
      $contact = $this->callAPISuccess('contact', 'create', $param);
      $contactIds[$count++] = $contact['id'];

      $grpParams = array(
        'contact_id' => $contact['id'],
        'group_id' => $groupId,
      );
      $this->callAPISuccess('group_contact', 'create', $grpParams);
    }

    // verify that all contacts have been created separately
    $this->assertEquals(count($contactIds), 7, 'Check for number of contacts.');

    $dao = new CRM_Dedupe_DAO_RuleGroup();
    $dao->contact_type = 'Individual';
    $dao->level = 'Fuzzy';
    $dao->is_default = 1;
    $dao->find(TRUE);

    $foundDupes = CRM_Dedupe_Finder::dupesInGroup($dao->id, $groupId);

    // -------------------------------------------------------------------------
    // default dedupe rule: threshold = 20 => (First + Last + Email) Matches ( 1 pair )
    // --------------------------------------------------------------------------
    // will   - dale - will@example.com
    // will   - dale - will@example.com
    // so 1 pair for - first + last + mail
    $this->assertEquals(count($foundDupes), 1, 'Check Individual-Fuzzy dupe rule for dupesInGroup().');

    foreach ($contactIds as $contactId) {
      $this->contactDelete($contactId);
    }
    // delete dupe group
    $params = array('id' => $groupId, 'version' => 3);
    civicrm_api('group', 'delete', $params);
  }

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

    $dao = new CRM_Dedupe_DAO_RuleGroup();
    $dao->contact_type = 'Individual';
    $dao->used = 'General';
    $dao->is_default = 1;
    $dao->find(TRUE);

    $fields = array(
      'first_name' => 'robin',
      'last_name' => 'hood',
      'email' => 'hood@example.com',
      'street_address' => 'Ambachtstraat 23',
    );
    CRM_Core_TemporaryErrorScope::useException();
    $dedupeParams = CRM_Dedupe_Finder::formatParams($fields, 'Individual');
    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual', 'General');

    // Check with default Individual-General rule
    $this->assertEquals(count($ids), 2, 'Check Individual-General rule for dupesByParams().');

    // delete all created contacts
    foreach ($contactIds as $contactId) {
      $this->contactDelete($contactId);
    }
  }

}
