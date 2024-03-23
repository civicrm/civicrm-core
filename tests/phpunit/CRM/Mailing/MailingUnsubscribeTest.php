<?php
/**
 * Test that email unsubscribing works as expected.
 *
 * @package CiviCRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


/**
 * Class CRM_Mailing_MailingUnsubscribeTest
 * @group headless
 */
class CRM_Mailing_MailingUnsubscribeTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  /**
   * Test CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing()
   *
   */
  public function testMailingUnsubscribe() : void {

    // Create contact, groupIds and group contacts
    $indivId = $this->individualCreate();
    $groupIds['group1'] = $this->groupCreate(['name' => 'group1', 'title' => 'Group with indiv']);
    $groupIds['group2'] = $this->groupCreate(['name' => 'group2', 'title' => 'Group without indiv']);
    $belongsTo = ['group1'];
    $sentTo = ['group1', 'group2'];

    // Create 4 sets of parent + 3 child groups
    // The contact is sometimes in child*a, never in child*b, always in child*c
    // child*c is never directly sent to. child*a is sometimes sent to.
    foreach ([1, 2, 3, 4] as $parent) {
      $parentkey = 'parent' . $parent;
      $groupIds[$parentkey] = $this->groupCreate(['name' => $parentkey, 'title' => "Parent Group $parent"]);
      foreach (['a', 'b', 'c'] as $child) {
        $childkey = 'child' . $parent . $child;
        $groupIds[$childkey] = $this->groupCreate([
          'name' => $childkey,
          'title' => "Child Group {$parent}{$child}",
          'parents' => [$groupIds[$parentkey]],
        ]);
      }
      $belongsTo[] = 'child' . $parent . 'c';
    }

    // 1: Parent in mailing, Child not in mailing, contact in child group
    $sentTo[] = 'parent1'; $belongsTo[] = 'child1a';

    // 2: Parent not in mailing, Child in mailing, contact in child group
    $sentTo[] = 'child2a'; $belongsTo[] = 'child2a';

    // 3: Parent in mailing, child not in mailing, contact in parent
    $sentTo[] = 'parent3'; $belongsTo[] = 'parent3';

    // 4: Parent in mailing, child in mailing, contact in parent, contact in child
    $sentTo[] = 'parent4'; $sentTo[] = 'child4a'; $belongsTo[] = 'parent4'; $belongsTo[] = 'child4a';

    // Add contact to groups
    foreach ($belongsTo as $group) {
      $this->callAPISuccess('GroupContact', 'create', ['contact_id' => $indivId, 'group_id' => $groupIds[$group]]);
    }

    // Populate $sentToIds from $sentTo and $groupIds
    foreach ($sentTo as $group) {
      $sentToIds[$group] = $groupIds[$group];
    }

    // end of setup... lets start testing:

    // Test that contact starts in the expected groups
    $myGroups = Civi\Api4\GroupContact::get(FALSE)
      ->addWhere('contact_id', '=', $indivId)
      ->execute()
      ->indexBy('group_id');
    foreach ($groupIds as $group => $gid) {
      if (in_array($group, $belongsTo)) {
        $this->assertEquals('Added', $myGroups[$gid]['status'], "Contact should be Added to group $group");
      }
      else {
        $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
      }
    }

    // Need to set this to prevent error when calling Job process_mailing
    $this->callAPISuccess('mail_settings', 'get', ['api.mail_settings.create' => ['domain' => 'chaos.org']]);

    // Create the mailing
    $mailingId = $this->callAPISuccess('Mailing', 'create', [
      'name' => 'mailing name',
      'created_id' => 1,
      'groups' => ['include' => $sentToIds],
      'scheduled_date' => 'now',
      'subject' => 'My really interesting subject',
      'body_text' => 'Hello world',
    ])['id'];

    // .. and send it
    $ret = $this->callAPISuccess('job', 'process_mailing', ['runInNonProductionEnvironment' => TRUE]);

    // Check the mailing has the right groupIds
    $mailinggroupIds = \Civi\Api4\MailingGroup::get(FALSE)
      ->addWhere('mailing_id', '=', $mailingId)
      ->execute()
      ->column('entity_id');
    $this->assertArrayValuesEqual($sentToIds, $mailinggroupIds);

    // Find the queue_id and hash for our mailing
    $queue_info = \Civi\Api4\MailingEventQueue::get(TRUE)
      ->addSelect('id', 'hash')
      ->addWhere('mailing_id', '=', $mailingId)
      ->addWhere('contact_id', '=', $indivId)
      ->execute()
      ->single();

    // Do the unsubscribe
    CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(NULL, $queue_info['id'], $queue_info['hash']);

    // Test that contact is in the expected groups
    $myGroups = Civi\Api4\GroupContact::get(FALSE)
      ->addWhere('contact_id', '=', $indivId)
      ->execute()
      ->indexBy('group_id');

    foreach ($groupIds as $group => $gid) {
      switch ($group) {

        // Group in mailing, contact in group
        case 'group1':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        // Group in mailing, contact not in group
        case 'group2':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        // 1: Parent in mailing, Child not in mailing, contact in child group
        case 'parent1':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child1a':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        case 'child1b':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child1c':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        // 2: Parent not in mailing, Child in mailing, contact in child group
        case 'parent2':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child2a':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        case 'child2b':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child2c':
          $this->assertEquals('Added', $myGroups[$gid]['status'], "Contact should be Added in group $group");
          break;

        // 3: Parent in mailing, child not in mailing, contact in parent
        case 'parent3':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        case 'child3a':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child3b':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child3c':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        // 4: Parent in mailing, child in mailing, contact in parent, contact in child
        case 'parent4':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        case 'child4a':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        case 'child4b':
          $this->assertTrue(!isset($myGroups[$gid]), "Contact should not be in $group");
          break;

        case 'child4c':
          $this->assertEquals('Removed', $myGroups[$gid]['status'], "Contact should be Removed in group $group");
          break;

        default:
          throw new Exception("Group $group not configured in tests");
        break;
      }
    }
  }

}
