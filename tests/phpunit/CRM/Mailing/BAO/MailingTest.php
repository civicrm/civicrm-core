<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Mailing_BAO_MailingTest
 */
class CRM_Mailing_BAO_MailingTest extends CiviUnitTestCase {

  protected $allowedContactId = 0;

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    global $dbLocale;
    if ($dbLocale) {
      CRM_Core_I18n_Schema::makeSinglelingual('en_US');
    }
    parent::tearDown();
  }

  /**
   * Helper function to assert whether the calculated recipients of a mailing
   * match the expected list
   *
   * @param $mailingID
   * @param $expectedRecipients array
   *   Array of contact ID that should be in the recipient list.
   */
  private function assertRecipientsCorrect($mailingID, $expectedRecipients) {

    // Reset keys to ensure match
    $expectedRecipients = array_values($expectedRecipients);

    // Load the recipients as a list of contact IDs
    CRM_Mailing_BAO_Mailing::getRecipients($mailingID);
    $recipients = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $mailingID));
    $contactIDs = array();
    foreach ($recipients['values'] as $recipient) {
      $contactIDs[] = $recipient['contact_id'];
    }

    // Check the lists match
    $this->assertTreeEquals($expectedRecipients, $contactIDs);
  }

  /**
   * Helper function to create a mailing include/exclude group.
   *
   * @param $mailingID
   * @param $groupID
   * @param string $type
   * @return array|int
   */
  private function createMailingGroup($mailingID, $groupID, $type = 'Include') {
    return $this->callAPISuccess('MailingGroup', 'create', array(
      'mailing_id' => $mailingID,
      'group_type' => $type,
      'entity_table' => CRM_Contact_BAO_Group::getTableName(),
      'entity_id' => $groupID,
    ));
  }

  /**
   * Test to ensure that using ACL permitted contacts are correctly fetched for bulk mailing
   */
  public function testgetRecipientsUsingACL() {
    $this->prepareForACLs();
    $this->createLoggedInUser();
    // create hook to build ACL where clause which choses $this->allowedContactId as the only contact to be considered as mail recipient
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereAllowedOnlyOne'));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'view my contact');

    // Create dummy group and assign 2 contacts
    $name = 'Test static group ' . substr(sha1(rand()), 0, 7);
    $groupID = $this->groupCreate([
      'name' => $name,
      'title' => $name,
      'is_active' => 1,
    ]);
    // Create 2 contacts where one of them identified as $this->allowedContactId will be used in ACL where clause
    $contactID1 = $this->individualCreate(array(), 0);
    $this->allowedContactId = $this->individualCreate(array(), 1);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $contactID1,
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $this->allowedContactId,
    ));

    // Create dummy mailing
    $mailingID = $this->callAPISuccess('Mailing', 'create', array())['id'];
    $this->createMailingGroup($mailingID, $groupID);

    // Check that the desired contact (identified as Contact ID - $this->allowedContactId) is the only
    //  contact chosen as mail recipient
    $expectedContactIDs = [$this->allowedContactId];
    $this->assertRecipientsCorrect($mailingID, $expectedContactIDs);

    $this->cleanUpAfterACLs();
    $this->callAPISuccess('Group', 'Delete', ['id' => $groupID]);
    $this->contactDelete($contactID1);
    $this->contactDelete($this->allowedContactId);
  }

  /**
   * Test verify that a disabled mailing group doesn't prvent access to the mailing generated with the group.
   */
  public function testGetMailingDisabledGroup() {
    $this->prepareForACLs();
    $this->createLoggedInUser();
    // create hook to build ACL where clause which choses $this->allowedContactId as the only contact to be considered as mail recipient
    $this->hookClass->setHook('civicrm_aclWhereClause', array($this, 'aclWhereAllowedOnlyOne'));
    $this->hookClass->setHook('civicrm_aclGroup', array($this, 'hook_civicrm_aclGroup'));
    CRM_Core_Config::singleton()->userPermissionClass->permissions = array('access CiviCRM', 'edit groups');
    // Create dummy group and assign 2 contacts
    $name = 'Test static group ' . substr(sha1(rand()), 0, 7);
    $groupID = $this->groupCreate([
      'name' => $name,
      'title' => $name,
      'is_active' => 1,
    ]);
    $contactID = $this->individualCreate(array(), 0);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $contactID,
    ));

    // Create dummy mailing
    $mailingID = $this->callAPISuccess('Mailing', 'create', array())['id'];
    $this->createMailingGroup($mailingID, $groupID);
    // Now disable the group.
    $this->callAPISuccess('group', 'create', [
      'id' => $groupID,
      'is_active' => 0,
    ]);
    $groups = CRM_Mailing_BAO_Mailing::mailingACLIDs();
    $this->assertTrue(in_array($groupID, $groups));
    $this->cleanUpAfterACLs();
    $this->contactDelete($contactID);
  }

  /**
   * Build ACL where clause
   *
   * @implements CRM_Utils_Hook::aclWhereClause
   *
   * @param string $type
   * @param array $tables
   * @param array $whereTables
   * @param int $contactID
   * @param string $where
   */
  public function aclWhereAllowedOnlyOne($type, &$tables, &$whereTables, &$contactID, &$where) {
    $where = " contact_a.id = " . $this->allowedContactId;
  }

  /**
   * Implements ACLGroup hook.
   *
   * @implements CRM_Utils_Hook::aclGroup
   *
   * aclGroup function returns a list of permitted groups
   * @param string $type
   * @param int $contactID
   * @param string $tableName
   * @param array $allGroups
   * @param array $currentGroups
   */
  public function hook_civicrm_aclGroup($type, $contactID, $tableName, &$allGroups, &$currentGroups) {
    //don't use api - you will get a loop
    $sql = " SELECT * FROM civicrm_group";
    $groups = array();
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $groups[] = $dao->id;
    }
    if (!empty($allGroups)) {
      //all groups is empty if we really mean all groups but if a filter like 'is_disabled' is already applied
      // it is populated, ajax calls from Manage Groups will leave empty but calls from New Mailing pass in a filtered list
      $currentGroups = array_intersect($groups, array_flip($allGroups));
    }
    else {
      $currentGroups = $groups;
    }
  }


  /**
   * @todo Missing tests:
   * - Ensure opt out emails are not mailed
   * - Ensure 'stop' emails are not mailed
   * - Ensure the deceased are not mailed
   * - Tests for getLocationFilterAndOrderBy (selecting correct 'type')
   * - ...
   */

  /**
   * Test to ensure that static and smart mailing groups can be added to an
   * email mailing as 'include' or 'exclude' groups - and the members are
   * included or excluded appropriately.
   *
   * contact 0 : static 0 (inc) + smart 5 (exc)
   * contact 1 : static 0 (inc)
   * contact 2 : static 1 (inc)
   * contact 3 : static 1 (inc)
   * contact 4 : static 2 (exc) + smart 3 (inc)
   * contact 5 : smart 3 (inc)
   * contact 6 : smart 4 (inc)
   * contact 7 : smart 4 (inc)
   * contact 8 : smart 5 (base)
   *
   * here 'contact 1 : static 0 (inc)' identified as static group $groupIDs[0]
   *  that has 'contact 1' identified as $contactIDs[0] and Included in the mailing recipient list
   */
  public function testgetRecipientsEmailGroupIncludeExclude() {
    // Set up groups; 3 standard, 4 smart
    $groupIDs = array();
    for ($i = 0; $i < 7; $i++) {
      $params = array(
        'name' => 'Test static group ' . $i,
        'title' => 'Test static group ' . $i,
        'is_active' => 1,
      );
      if ($i < 3) {
        $groupIDs[$i] = $this->groupCreate($params);
      }
      else {
        $groupIDs[$i] = $this->smartGroupCreate(array(
          'formValues' => ['last_name' => (($i == 6) ? 'smart5' : 'smart' . $i)],
        ), $params);
      }
    }

    // Create contacts
    $contactIDs = array(
      $this->individualCreate(array('last_name' => 'smart5'), 0),
      $this->individualCreate(array(), 1),
      $this->individualCreate(array(), 2),
      $this->individualCreate(array(), 3),
      $this->individualCreate(array('last_name' => 'smart3'), 4),
      $this->individualCreate(array('last_name' => 'smart3'), 5),
      $this->individualCreate(array('last_name' => 'smart4'), 6),
      $this->individualCreate(array('last_name' => 'smart4'), 7),
      $this->individualCreate(array('last_name' => 'smart5'), 8),
    );

    // Add contacts to static groups
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupIDs[0],
      'contact_id' => $contactIDs[0],
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupIDs[0],
      'contact_id' => $contactIDs[1],
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupIDs[1],
      'contact_id' => $contactIDs[2],
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupIDs[1],
      'contact_id' => $contactIDs[3],
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupIDs[2],
      'contact_id' => $contactIDs[4],
    ));

    // Force rebuild the smart groups
    for ($i = 3; $i < 7; $i++) {
      $group = new CRM_Contact_DAO_Group();
      $group->id = $groupIDs[$i];
      $group->find(TRUE);
      CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
    }

    // Check that we can include static groups in the mailing.
    // Expected: Contacts [0-3] should be included.
    $mailing = $this->callAPISuccess('Mailing', 'create', array());
    $this->createMailingGroup($mailing['id'], $groupIDs[0]);
    $this->createMailingGroup($mailing['id'], $groupIDs[1]);
    $this->createMailingGroup($mailing['id'], $groupIDs[6], 'Base');
    $expected = $contactIDs;
    unset($expected[4], $expected[5], $expected[6], $expected[7], $expected[8]);
    $this->assertRecipientsCorrect($mailing['id'], $expected);

    // Check that we can include smart groups in the mailing too.
    // Expected: All contacts should be included.
    // Also (dev/mail/6): Enable multilingual mode to check that restructing group doesn't affect recipient rebuilding
    $this->enableMultilingual();
    $this->createMailingGroup($mailing['id'], $groupIDs[3]);
    $this->createMailingGroup($mailing['id'], $groupIDs[4]);
    $this->createMailingGroup($mailing['id'], $groupIDs[5]);
    // Check that all the contacts whould be present is recipient list as static group [0], [1] and [2] and
    //  smart groups [3], [4] and [5] is included in the recipient listing.
    //  NOTE: that contact[8] is present in both included smart group[5] and base smart group [6] so it will be
    // present in recipient list as contact(s) from Base smart groups are not excluded the list as per (dev/mail/13)
    $this->assertRecipientsCorrect($mailing['id'], $contactIDs);

    // Check we can exclude static groups from the mailing.
    // Expected: All contacts except [4]
    $this->createMailingGroup($mailing['id'], $groupIDs[2], 'Exclude');
    $expected = $contactIDs;
    unset($expected[4]);
    // NOTE: as per (dev/mail/13) if a contact A is present in smartGroup [5] which is Included in the mailing AND
    //  also present in another smartGroup [6] which is considered as Base group, then contact A should not be excluded from
    //  the recipient list due to later
    $this->assertRecipientsCorrect($mailing['id'], $expected);

    // Check we can exclude smart groups from the mailing too.
    // Expected: All contacts except [0], [4] and [8]
    $this->createMailingGroup($mailing['id'], $groupIDs[5], 'Exclude');
    $expected = $contactIDs;
    // As contact [0] and [8] belongs to excluded smart group[5] and base smart group[6] respectively,
    //  both these contacts should not be present in the mailing list
    unset($expected[0], $expected[4], $expected[8]);
    $this->assertRecipientsCorrect($mailing['id'], $expected);

    // Tear down: delete mailing, groups, contacts
    $this->deleteMailing($mailing['id']);
    foreach ($groupIDs as $groupID) {
      $this->groupDelete($groupID);
    }
    foreach ($contactIDs as $contactID) {
      $this->contactDelete($contactID);
    }
  }

  /**
   * Test CRM_Mailing_BAO_Mailing::getRecipients() on sms mode
   */
  public function testgetRecipientsSMS() {
    // Tests for SMS bulk mailing recipients
    // +CRM-21320 Ensure primary mobile number is selected over non-primary

    // Setup
    $smartGroupParams = array(
      'formValues' => array('contact_type' => array('IN' => array('Individual'))),
    );
    $group = $this->smartGroupCreate($smartGroupParams);
    $sms_provider = $this->callAPISuccess('SmsProvider', 'create', array(
      'sequential' => 1,
      'name' => 1,
      'title' => "Test",
      'username' => "Test",
      'password' => "Test",
      'api_type' => 1,
      'is_active' => 1,
    ));

    // Create Contact 1 and add in group
    $contactID1 = $this->individualCreate(array(), 0);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $group,
      'contact_id' => $contactID1,
    ));

    // Create contact 2 and add in group
    $contactID2 = $this->individualCreate(array(), 1);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $group,
      'contact_id' => $contactID2,
    ));

    $contactIDPhoneRecords = array(
      $contactID1 => array(
        'primary_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID1,
          'phone' => "01 01",
          'location_type_id' => "Home",
          'phone_type_id' => "Mobile",
          'is_primary' => 1,
        ))),
        'other_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID1,
          'phone' => "01 02",
          'location_type_id' => "Work",
          'phone_type_id' => "Mobile",
          'is_primary' => 0,
        ))),
      ),
      // Create the non-primary with a lower ID than the primary, to test CRM-21320
      $contactID2 => array(
        'other_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID2,
          'phone' => "02 01",
          'location_type_id' => "Home",
          'phone_type_id' => "Mobile",
          'is_primary' => 0,
        ))),
        'primary_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID2,
          'phone' => "02 02",
          'location_type_id' => "Work",
          'phone_type_id' => "Mobile",
          'is_primary' => 1,
        ))),
      ),
    );

    // Prepare expected results
    $checkPhoneIDs = array(
      $contactID1 => $contactIDPhoneRecords[$contactID1]['primary_phone_id'],
      $contactID2 => $contactIDPhoneRecords[$contactID2]['primary_phone_id'],
    );

    // Create mailing
    $mailing = $this->callAPISuccess('Mailing', 'create', array('sms_provider_id' => $sms_provider['id']));
    $mailingInclude = $this->createMailingGroup($mailing['id'], $group);

    // Get recipients
    CRM_Mailing_BAO_Mailing::getRecipients($mailing['id']);
    $recipients = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $mailing['id']));

    // Check the count is correct
    $this->assertEquals(2, $recipients['count'], 'Check recipient count');

    // Check we got the 'primary' mobile for both contacts
    foreach ($recipients['values'] as $value) {
      $this->assertEquals($value['phone_id'], $checkPhoneIDs[$value['contact_id']], 'Check correct phone number for contact ' . $value['contact_id']);
    }

    // Tidy up
    $this->deleteMailing($mailing['id']);
    $this->callAPISuccess('SmsProvider', 'Delete', array('id' => $sms_provider['id']));
    $this->groupDelete($group);
    $this->contactDelete($contactID1);
    $this->contactDelete($contactID2);
  }

}
