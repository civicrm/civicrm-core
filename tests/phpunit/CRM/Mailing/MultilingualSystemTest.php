<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @group headless
 * @group civimail
 * @group locale
 * @see \Civi\FlexMailer\FlexMailerSystemTest
 */
class CRM_Mailing_MultilingualSystemTest extends CiviUnitTestCase {

  private $_groupID;

  /**
   * @var CiviMailUtils
   */
  private $_mut;

  public function setUp(): void {
    parent::setUp();
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;

    $this->_groupID = $this->groupCreate();
    $this->createContactsInGroup(2, $this->_groupID);

    $this->_mut = new CiviMailUtils($this, TRUE);
    $this->callAPISuccess('mail_settings', 'get',
      ['api.mail_settings.create' => ['domain' => 'chaos.org']]);
  }

  public function tearDown(): void {
    $this->quickCleanup([
      'civicrm_mailing',
      'civicrm_mailing_job',
      'civicrm_mailing_spool',
      'civicrm_mailing_group',
      'civicrm_mailing_recipients',
      'civicrm_mailing_event_queue',
      'civicrm_mailing_event_bounce',
      'civicrm_mailing_event_delivered',
      'civicrm_group',
      'civicrm_group_contact',
      'civicrm_contact',
      'civicrm_activity_contact',
      'civicrm_activity',
    ]);

    global $dbLocale;
    if ($dbLocale) {
      $this->disableMultilingual();
    }

    $this->_mut->stop();
    CRM_Utils_Hook::singleton()->reset();
    // DGW
    CRM_Mailing_BAO_MailingJob::$mailsProcessed = 0;

    parent::tearDown();
  }

  /**
   * Data provider for testGitLabIssue1108
   *
   * First we run it without multiLingual mode, then with.
   *
   * This is because we test table names, which may have been translated in a
   * multiLingual context.
   *
   */
  public function multiLingual() {
    return [[0], [1]];
  }

  /**
   * - unsubscribe used dodgy SQL that only checked half of the polymorphic
   *   relationship in mailing_group, meaning it could match 'mailing 123'
   *   against _group_ 123.
   *
   * - also, an INNER JOIN on the group table hid the mailing-based
   *   mailing_group records.
   *
   * - in turn this inner join meant the query returned nothing, which then
   *   caused the code that is supposed to find the contact within those groups
   *   to basically find all the groups that the contact in or were smart groups.
   *
   * - in certain situations (which I have not been able to replicate in this
   *   test) it caused the unsubscribe to fail to find *any* groups to unsubscribe
   *   people from, thereby breaking the unsubscribe.
   *
   * @dataProvider multiLingual
   *
   */
  public function testGitLabIssue1108($isMultiLingual): void {

    // We need to make sure the mailing IDs are higher than the groupIDs.
    // We do this by adding mailings until the mailing.id value is at least 10
    // higher than the highest group.id
    // Note that creating a row in a transaction then rolling back the
    // transaction still increments the AUTO_INCREMENT counter for the table.
    // (If this behaviour ever changes we throw an exception.)
    if ($isMultiLingual) {
      $cleanup = $this->useMultilingual(['en_US' => 'fr_FR']);
    }
    $max_group_id = CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_group");
    $max_mailing_id = 0;
    while ($max_mailing_id < $max_group_id + 10) {
      CRM_Core_Transaction::create()->run(function($tx) use (&$max_mailing_id) {
        CRM_Core_DAO::executeQuery("INSERT INTO civicrm_mailing (name) VALUES ('dummy');");
        $_ = (int) CRM_Core_DAO::singleValueQuery("SELECT MAX(id) FROM civicrm_mailing");
        if ($_ === $max_mailing_id) {
          throw new RuntimeException("Expected that creating a new row would increment ID, but it did not. This could be a change in MySQL's implementation of rollback");
        }
        $max_mailing_id = $_;
        $tx->rollback();
      });
    }

    // Because our parent class marks the _groupID as private, we can't use that :-(
    $group_1 = $this->groupCreate([
      'name' => 'Test Group 1108.1',
      'title' => 'Test Group 1108.1',
    ]);
    $this->createContactsInGroup(2, $group_1);

    // Also _mut is private to the parent, so we have to make our own:
    $mut = new CiviMailUtils($this, TRUE);

    // Create initial mailing to the group.
    $mailingParams = [
      'name'           => 'Issue 1108: mailing 1',
      'subject'        => 'Issue 1108: mailing 1',
      'created_id'     => 1,
      'groups'         => ['include' => [$group_1]],
      'scheduled_date' => 'now',
      'body_text'      => 'Please just {action.unsubscribe}',
    ];

    // The following code is exactly the same as runMailingSuccess() except that we store the ID of the mailing.
    $mailing_1 = $this->callAPISuccess('mailing', 'create', $mailingParams);
    $mut->assertRecipients([]);
    $this->callAPISuccess('job', 'process_mailing', ['runInNonProductionEnvironment' => TRUE]);

    $allMessages = $mut->getAllMessages('ezc');
    // There are exactly two contacts produced by setUp().
    $this->assertEquals(2, count($allMessages));

    // We need a new group
    $group_2 = $this->groupCreate([
      'name'  => 'Test Group 1108.2',
      'title' => 'Test Group 1108.2',
    ]);

    // Now create the 2nd mailing to the recipients of the first,
    // excluding our new albeit empty group.
    $mailingParams = [
      'name'           => 'Issue 1108: mailing 2',
      'subject'        => 'Issue 1108: mailing 2',
      'created_id'     => 1,
      'mailings'       => ['include' => [$mailing_1['id']]],
      'groups'         => ['exclude' => [$group_2]],
      'scheduled_date' => 'now',
      'body_text'      => 'Please just {action.unsubscribeUrl}',
    ];
    $this->callAPISuccess('mailing', 'create', $mailingParams);
    $_ = $this->callAPISuccess('job', 'process_mailing', ['runInNonProductionEnvironment' => TRUE]);

    $allMessages = $mut->getAllMessages('ezc');
    // We should have 2+2 messages sent by the mail system now.
    $this->assertEquals(4, count($allMessages));

    // So far so good.
    // Now extract the unsubscribe details.
    $message = end($allMessages);
    $this->assertTrue($message->body instanceof ezcMailText);
    $this->assertEquals('plain', $message->body->subType);
    $this->assertEquals(1, preg_match(
      '@mailing/unsubscribe.*jid=(\d+)&qid=(\d+)&h=([0-9a-z]+)@',
      $message->body->text,
      $matches
    ));

    // Create a group that has nothing to do with this mailing.
    $group_3 = $this->groupCreate([
      'name' => 'Test Group 1108.3',
      'title' => 'Test Group 1108.3',
    ]);
    // Add contacts from group 1 to group 3.
    $gcQuery = new CRM_Contact_BAO_GroupContact();
    $gcQuery->group_id = $group_1;
    $gcQuery->status = 'Added';
    $gcQuery->find();
    while ($gcQuery->fetch()) {
      $this->callAPISuccess('group_contact', 'create',
        ['group_id' => $group_3, 'contact_id' => $gcQuery->contact_id, 'status' => 'Added']);
    }

    // Part of the issue is caused by the fact that (at time of writing) the
    // SQL joined the mailing_group table on just the entity_id, assuming it to
    // be a group, but actually it could be a mailing.
    // The difficulty in testing this is that because all our IDs are very low
    // and contiguous the SQL looking for a match for 'mailing 1' does match a
    // group ID of '1', which is created in this class's parent's setUp().
    // Strictly speaking we don't know that it has ID 1, but as we can't access _groupID
    // we'll have to assume that.
    //
    // So by deleting that group the SQL then matches nothing which is what we
    // need for this case.
    $_ = new CRM_Contact_BAO_Group();
    $_->id = 1;
    $_->delete();

    $hooks = \CRM_Utils_Hook::singleton();
    $found = [];
    $hooks->setHook('civicrm_unsubscribeGroups',
      function ($op, $mailingId, $contactId, &$groups, &$baseGroups) use (&$found) {
        $found['groups'] = $groups;
        $found['baseGroups'] = $baseGroups;
      });

    // Now test unsubscribe groups.
    $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(
      $matches[1],
      $matches[2],
      $matches[3],
      TRUE
    );

    // We expect that our group_1 was found.
    $this->assertEquals(['groups' => [$group_1], 'baseGroups' => []], $found);

    // We *should* get an array with just our $group_1 since this is the only group
    // that we have included.
    // $group_2 was only used to exclude people.
    // $group_3 has nothing to do with this mailing and should not be there.
    $this->assertNotEmpty($groups, "We should have received an array.");
    $this->assertEquals([$group_1], array_keys($groups),
      "We should have received an array with our group 1 in it.");

    if ($isMultiLingual) {
      global $dbLocale;
      $dbLocale = '_fr_FR';
      // Now test unsubscribe groups.
      $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(
        $matches[1],
        $matches[2],
        $matches[3],
        TRUE
      );

      // We expect that our group_1 was found.
      $this->assertEquals(['groups' => [$group_1], 'baseGroups' => []], $found);

      // We *should* get an array with just our $group_1 since this is the only group
      // that we have included.
      // $group_2 was only used to exclude people.
      // $group_3 has nothing to do with this mailing and should not be there.
      $this->assertNotEmpty($groups, "We should have received an array.");
      $this->assertEquals([$group_1], array_keys($groups),
        "We should have received an array with our group 1 in it.");
      global $dbLocale;
      $dbLocale = '_en_US';
    }
  }

  /**
   * (FIXME) De-duplicate BaseMailingSystemTest::createContactsInGroup and MultilingualSystemTest::createContactsInGroup
   * This should probably be in a trait.
   *
   * Create contacts in group.
   *
   * @param int $count
   * @param int $groupID
   * @param string $domain
   */
  protected function createContactsInGroup(
    $count,
    $groupID,
    $domain = 'nul.example.com'
  ) {
    for ($i = 1; $i <= $count; $i++) {
      $contactID = $this->individualCreate([
        'first_name' => "Foo{$i}",
        'email' => 'mail' . $i . '@' . $domain,
      ]);
      $this->callAPISuccess('group_contact', 'create', [
        'contact_id' => $contactID,
        'group_id' => $groupID,
        'status' => 'Added',
      ]);
    }
  }

}
