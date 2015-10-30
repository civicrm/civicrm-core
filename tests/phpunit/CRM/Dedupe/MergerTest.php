<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

/**
 * Class CRM_Dedupe_DedupeMergerTest
 */
class CRM_Dedupe_MergerTest extends CiviUnitTestCase {

  protected $_groupId;
  protected $_contactIds = array();

  public function createDupeContacts() {
    // create a group to hold contacts, so that dupe checks don't consider any other contacts in the DB
    $params = array(
      'name'       => 'Test Dupe Merger Group',
      'title'      => 'Test Dupe Merger Group',
      'domain_id'  => 1,
      'is_active'  => 1,
      'visibility' => 'Public Pages',
      'version'    => 3,
    );
    // TODO: This is not an API test!!
    $result = civicrm_api('group', 'create', $params);
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
      $param['version'] = 3;
      $contact = civicrm_api('contact', 'create', $param);
      $this->_contactIds[$count++] = $contact['id'];

      $grpParams = array(
        'contact_id' => $contact['id'],
        'group_id'   => $this->_groupId,
        'version'    => 3,
      );
      $res = civicrm_api('group_contact', 'create', $grpParams);
    }
  }

  public function deleteDupeContacts() {
    // delete all created contacts
    foreach ($this->_contactIds as $contactId) {
      Contact::delete($contactId);
    }

    // delete dupe group
    $params = array('id' => $this->_groupId, 'version' => 3);
    civicrm_api('group', 'delete', $params);
  }

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
    $_SERVER['REQUEST_METHOD'] = 'GET'; //avoid invalid key error
    $object = new CRM_Contact_Page_DedupeFind();
    $object->set('gid', $this->_groupId);
    $object->set('rgid', $dao->id);
    $object->set('action', CRM_Core_Action::UPDATE);
    @$object->run();

    // Retrieve pairs from prev next cache table
    $select = array('pn.is_selected' => 'is_selected');
    $cacheKeyString = "merge Individual_{$dao->id}_{$this->_groupId}";
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);

    $this->assertEquals(count($foundDupes), count($pnDupePairs), 'Check number of dupe pairs in prev next cache.');

    // mark first two pairs as selected
    CRM_Core_DAO::singleValueQuery("UPDATE civicrm_prevnext_cache SET is_selected = 1 WHERE id IN ({$pnDupePairs[0]['prevnext_id']}, {$pnDupePairs[1]['prevnext_id']})");

    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals($pnDupePairs[0]['is_selected'], 1, 'Check if first record in dupe pairs is marked as selected.');
    $this->assertEquals($pnDupePairs[0]['is_selected'], 1, 'Check if second record in dupe pairs is marked as selected.');

    // batch merge selected dupes
    $result = CRM_Dedupe_Merger::batchMerge($dao->id, $this->_groupId, 'safe', TRUE, 5, 1);
    $this->assertEquals(count($result['merged']), 2, 'Check number of merged pairs.');

    // retrieve pairs from prev next cache table
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals(count($pnDupePairs), 1, 'Check number of remaining dupe pairs in prev next cache.');

    $this->deleteDupeContacts();
  }

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
    $_SERVER['REQUEST_METHOD'] = 'GET'; //avoid invalid key error
    $object = new CRM_Contact_Page_DedupeFind();
    $object->set('gid', $this->_groupId);
    $object->set('rgid', $dao->id);
    $object->set('action', CRM_Core_Action::UPDATE);
    @$object->run();

    // Retrieve pairs from prev next cache table
    $select = array('pn.is_selected' => 'is_selected');
    $cacheKeyString = "merge Individual_{$dao->id}_{$this->_groupId}";
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);

    $this->assertEquals(count($foundDupes), count($pnDupePairs), 'Check number of dupe pairs in prev next cache.');

    // batch merge all dupes
    $result = CRM_Dedupe_Merger::batchMerge($dao->id, $this->_groupId, 'safe', TRUE, 5, 2);
    $this->assertEquals(count($result['merged']), 3, 'Check number of merged pairs.');

    // retrieve pairs from prev next cache table
    $pnDupePairs = CRM_Core_BAO_PrevNextCache::retrieve($cacheKeyString, NULL, NULL, 0, 0, $select);
    $this->assertEquals(count($pnDupePairs), 0, 'Check number of remaining dupe pairs in prev next cache.');

    $this->deleteDupeContacts();
  }

}
