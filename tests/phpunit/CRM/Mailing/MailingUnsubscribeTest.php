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

  public $indivId;
  public $groupIds;

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    // Need to set this to prevent error when calling Job process_mailing
    $this->callAPISuccess('mail_settings', 'get', ['api.mail_settings.create' => ['domain' => 'chaos.org']]);
    $this->indivId = $this->individualCreate(['last_name' => 'Testy']);
  }

  /*
   * Tests
   */

  /**
   * Sent to 2 groups, contact in 1 group
   */
  public function testUnsubscribeSimple() : void {
    $this->setUpGroups('group1', 'group2');
    $this->addIndivToGroups('group1');
    $this->sendAndUnsubscribe('group1', 'group2');
    $this->checkGroupContactStatus(['group1' => 'Removed', 'group2' => NULL]);
  }

  /**
   * Sent to Parent of Child groups, contact in some Child groups
   * Should remove from all child groups if a member
   */
  public function testUnsubscribeParent() : void {
    $this->setUpGroups('parent', 'child1', 'child2', 'child3');
    $this->addParent('parent', ['child1', 'child2', 'child3']);
    $this->addIndivToGroups('child1', 'child2');
    $this->sendAndUnsubscribe('parent');
    $this->checkGroupContactStatus(['parent' => NULL, 'child1' => 'Removed', 'child2' => 'Removed', 'child3' => NULL]);
  }

  /**
   * Sent to a child group, contact in some Child groups
   * Should remove from child group but not siblings
   */
  public function testUnsubscribeChild() : void {
    $this->setUpGroups('parent', 'child1', 'child2', 'child3');
    $this->addParent('parent', ['child1', 'child2', 'child3']);
    $this->addIndivToGroups('child1', 'child2');
    $this->sendAndUnsubscribe('child1');
    $this->checkGroupContactStatus(['parent' => NULL, 'child1' => 'Removed', 'child2' => 'Added', 'child3' => NULL]);
  }

  /**
   * Sent to Parent, contact directly in parent & child
   * Should remove from parent and child groups if a member
   */
  public function testUnsubscribeParentDirect() : void {
    $this->setUpGroups('parent', 'child1', 'child2', 'child3');
    $this->addParent('parent', ['child1', 'child2']);
    $this->addIndivToGroups('parent', 'child1', 'child2');
    $this->sendAndUnsubscribe('parent');
    $this->checkGroupContactStatus(['parent' => 'Removed', 'child1' => 'Removed', 'child2' => 'Removed', 'child3' => NULL]);
  }

  /**
   * Sent to Parent & Child, contact in child group
   * Should remove from child if a member
   */
  public function testUnsubscribeParentChild() : void {
    $this->setUpGroups('parent', 'child1', 'child2');
    $this->addParent('parent', ['child1', 'child2']);
    $this->addIndivToGroups('child1');
    $this->sendAndUnsubscribe('parent', 'child1');
    $this->checkGroupContactStatus(['parent' => NULL, 'child1' => 'Removed', 'child2' => NULL]);
  }

  /**
   * Sent to Grandparent, contact in child group
   * Should remove from sibling groups and groups of other parent
   */
  public function testUnsubscribeGrandparent() : void {
    $this->setUpGroups('grandparent', 'parent1', 'parent2', 'child1a', 'child1b', 'child2a', 'child2b');
    $this->addParent('grandparent', ['parent1', 'parent2']);
    $this->addParent('parent1', ['child1a', 'child1b']);
    $this->addParent('parent2', ['child2a', 'child2b']);
    $this->addIndivToGroups('child1a', 'child2a');
    $this->sendAndUnsubscribe('grandparent');
    $this->checkGroupContactStatus([
      'grandparent' => NULL,
      'parent1' => NULL,
      'child1a' => 'Removed',
      'child1b' => NULL,
      'parent2' => NULL,
      'child2a' => 'Removed',
      'child2b' => NULL,
    ]);
  }

  /**
   * Grandparent structue, Sent to parent, contact in child group
   * Should remove from sibling groups but not groups of other parent
   */
  public function testUnsubscribeGrandparent2() : void {
    $this->setUpGroups('grandparent', 'parent1', 'parent2', 'child1a', 'child1b', 'child2a', 'child2b');
    $this->addParent('grandparent', ['parent1', 'parent2']);
    $this->addParent('parent1', ['child1a', 'child1b']);
    $this->addParent('parent2', ['child2a', 'child2b']);
    $this->addIndivToGroups('child1a', 'child2a');
    $this->sendAndUnsubscribe('parent1');
    $this->checkGroupContactStatus([
      'grandparent' => NULL,
      'parent1' => NULL,
      'child1a' => 'Removed',
      'child1b' => NULL,
      'parent2' => NULL,
      'child2a' => 'Added',
      'child2b' => NULL,
    ]);
  }

  /**
   * Sent to a smart group, contact in smart group
   * Should remove from smart group
   */
  public function testUnsubscribeFromSmartGroup() : void {
    $this->setUpSmartGroup('smart');
    $this->sendAndUnsubscribe('smart');
    $this->checkNotInSmartGroup('smart');
  }

  /**
   * Sent to a parent of smart group, contact in smart group.
   * Should remove from smart group
   */
  public function testUnsubscribeFromSmartGroupAsChild() : void {
    $this->setUpSmartGroup('smart');
    $this->setUpGroups('parent');
    $this->addParent('parent', ['smart']);
    $this->sendAndUnsubscribe('parent');
    $this->checkNotInSmartGroup('smart');
  }

  /**
   *
   * Helper functions
   *
   */
  private function setUpGroups(...$keys) {
    foreach ($keys as $key) {
      $this->groupIds[$key] = $this->groupCreate(['name' => $key, 'title' => $key]);
    }
  }

  private function setUpSmartGroup($key) : void {
    // Create smart group
    $this->groupIds[$key] = $this->smartGroupCreate(['form_values' => ['last_name' => 'Testy']], ['name' => $key, 'title' => $key, 'is_active' => 1], 'Individual');

    // Check contact is in smart group
    $isContactInGroup = (bool) \Civi\Api4\Contact::get(FALSE)
      ->addWhere('id', '=', $this->indivId)
      ->addWhere('groups', 'IN', [$this->groupIds[$key]])
      ->execute()
      ->count();
    $this->assertTrue($isContactInGroup, "Contact should be in smart group before unsubscribe");
  }

  private function addParent($parent, $kids) : void {
    foreach ($kids as $kid) {
      \Civi\Api4\Group::update(FALSE)
        ->addWhere('id', '=', $this->groupIds[$kid])
        ->addValue('parents', [$this->groupIds[$parent]])
        ->execute();
    }
  }

  private function addIndivToGroups(...$groups) {
    foreach ($groups as $group) {
      $this->callAPISuccess('GroupContact', 'create', ['contact_id' => $this->indivId, 'group_id' => $this->groupIds[$group]]);
    }
  }

  private function sendAndUnsubscribe(...$sentTo) : void {
    foreach ($sentTo as $group) {
      $sentToIds[] = $this->groupIds[$group];
    }
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
      ->addWhere('contact_id', '=', $this->indivId)
      ->execute()
      ->single();

    // Do the unsubscribe
    CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing(NULL, $queue_info['id'], $queue_info['hash']);
  }

  private function checkNotInSmartGroup($key) : void {
    // Check contact is no longer in smart group
    $isContactInGroup = (bool) \Civi\Api4\Contact::get(FALSE)
      ->addWhere('id', '=', $this->indivId)
      ->addWhere('groups', 'IN', [$this->groupIds[$key]])
      ->execute()
      ->count();
    $this->assertFalse($isContactInGroup, "Contact should not be in smart group after unsubscribe");

    $this->checkGroupContactStatus([$key => 'Removed']);
  }

  private function checkGroupContactStatus($statuses) {
    $myGroups = Civi\Api4\GroupContact::get(FALSE)->addWhere('contact_id', '=', $this->indivId)->execute()->indexBy('group_id');
    foreach ($statuses as $group => $status) {
      if ($status == 'Added' || $status == 'Removed') {
        if (!isset($myGroups[$this->groupIds[$group]])) {
          throw new Exception("No mygroups for $group");
        }
        $this->assertEquals($status, $myGroups[$this->groupIds[$group]]['status'], "Contact should be $status in group $group");
      }
      else {
        $this->assertTrue(!isset($myGroups[$this->groupIds[$group]]), "Contact should not be in $group");
      }
    }
  }

}
