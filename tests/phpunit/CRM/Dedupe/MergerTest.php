<?php

/**
 * Class CRM_Dedupe_DedupeMergerTest
 * @group headless
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
    return array(
      'civicrm_entity_tag' => array(
        0 => 'entity_id',
      ),
    );
  }

  /**
   * Get the list of tables that refer to the CID.
   *
   * This is a statically maintained (in this test list).
   *
   * There is also a check against an automated list but having both seems to add extra stability to me. They do
   * not change often.
   */
  public function getStaticCIDRefs() {
    return array(
      'civicrm_acl_cache' => array(
        0 => 'contact_id',
      ),
      'civicrm_acl_contact_cache' => array(
        0 => 'user_id',
        1 => 'contact_id',
      ),
      'civicrm_action_log' => array(
        0 => 'contact_id',
      ),
      'civicrm_activity_contact' => array(
        0 => 'contact_id',
      ),
      'civicrm_address' => array(
        0 => 'contact_id',
      ),
      'civicrm_batch' => array(
        0 => 'created_id',
        1 => 'modified_id',
      ),
      'civicrm_campaign' => array(
        0 => 'created_id',
        1 => 'last_modified_id',
      ),
      'civicrm_case_contact' => array(
        0 => 'contact_id',
      ),
      'civicrm_contact' => array(
        0 => 'primary_contact_id',
        1 => 'employer_id',
      ),
      'civicrm_contribution' => array(
        0 => 'contact_id',
      ),
      'civicrm_contribution_page' => array(
        0 => 'created_id',
      ),
      'civicrm_contribution_recur' => array(
        0 => 'contact_id',
      ),
      'civicrm_contribution_soft' => array(
        0 => 'contact_id',
      ),
      'civicrm_custom_group' => array(
        0 => 'created_id',
      ),
      'civicrm_dashboard_contact' => array(
        0 => 'contact_id',
      ),
      'civicrm_dedupe_exception' => array(
        0 => 'contact_id1',
        1 => 'contact_id2',
      ),
      'civicrm_domain' => array(
        0 => 'contact_id',
      ),
      'civicrm_email' => array(
        0 => 'contact_id',
      ),
      'civicrm_event' => array(
        0 => 'created_id',
      ),
      'civicrm_event_carts' => array(
        0 => 'user_id',
      ),
      'civicrm_financial_account' => array(
        0 => 'contact_id',
      ),
      'civicrm_financial_item' => array(
        0 => 'contact_id',
      ),
      'civicrm_grant' => array(
        0 => 'contact_id',
      ),
      'civicrm_group' => array(
        0 => 'created_id',
        1 => 'modified_id',
      ),
      'civicrm_group_contact' => array(
        0 => 'contact_id',
      ),
      'civicrm_group_contact_cache' => array(
        0 => 'contact_id',
      ),
      'civicrm_group_organization' => array(
        0 => 'organization_id',
      ),
      'civicrm_im' => array(
        0 => 'contact_id',
      ),
      'civicrm_log' => array(
        0 => 'modified_id',
      ),
      'civicrm_mailing' => array(
        0 => 'created_id',
        1 => 'scheduled_id',
        2 => 'approver_id',
      ),
      'civicrm_mailing_abtest' => array(
        0 => 'created_id',
      ),
      'civicrm_mailing_event_queue' => array(
        0 => 'contact_id',
      ),
      'civicrm_mailing_event_subscribe' => array(
        0 => 'contact_id',
      ),
      'civicrm_mailing_recipients' => array(
        0 => 'contact_id',
      ),
      'civicrm_membership' => array(
        0 => 'contact_id',
      ),
      'civicrm_membership_log' => array(
        0 => 'modified_id',
      ),
      'civicrm_membership_type' => array(
        0 => 'member_of_contact_id',
      ),
      'civicrm_note' => array(
        0 => 'contact_id',
      ),
      'civicrm_openid' => array(
        0 => 'contact_id',
      ),
      'civicrm_participant' => array(
        0 => 'contact_id',
        1 => 'transferred_to_contact_id', //CRM-16761
      ),
      'civicrm_payment_token' => array(
        0 => 'contact_id',
        1 => 'created_id',
      ),
      'civicrm_pcp' => array(
        0 => 'contact_id',
      ),
      'civicrm_phone' => array(
        0 => 'contact_id',
      ),
      'civicrm_pledge' => array(
        0 => 'contact_id',
      ),
      'civicrm_print_label' => array(
        0 => 'created_id',
      ),
      'civicrm_relationship' => array(
        0 => 'contact_id_a',
        1 => 'contact_id_b',
      ),
      'civicrm_report_instance' => array(
        0 => 'created_id',
        1 => 'owner_id',
      ),
      'civicrm_setting' => array(
        0 => 'contact_id',
        1 => 'created_id',
      ),
      'civicrm_subscription_history' => array(
        0 => 'contact_id',
      ),
      'civicrm_survey' => array(
        0 => 'created_id',
        1 => 'last_modified_id',
      ),
      'civicrm_tag' => array(
        0 => 'created_id',
      ),
      'civicrm_uf_group' => array(
        0 => 'created_id',
      ),
      'civicrm_uf_match' => array(
        0 => 'contact_id',
      ),
      'civicrm_value_testgetcidref_1' => array(
        0 => 'entity_id',
      ),
      'civicrm_website' => array(
        0 => 'contact_id',
      ),
    );
  }

  /**
   * Get a list of CIDs that is calculated off the schema.
   *
   * Note this is an expensive and table locking query. Should be safe in tests though.
   */
  public function getCalculatedCIDRefs() {
    $cidRefs = array();
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
    $cidRefs['civicrm_acl_contact_cache'][0] = 'user_id';
    $cidRefs['civicrm_acl_contact_cache'][1] = 'contact_id';
    $cidRefs['civicrm_mailing'][0] = 'created_id';
    $cidRefs['civicrm_mailing'][1] = 'scheduled_id';
    $cidRefs['civicrm_mailing'][2] = 'approver_id';
    return $cidRefs;
  }

}
