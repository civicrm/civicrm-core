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

use Civi\Api4\Group;
use Civi\Api4\GroupContact;
use Civi\Api4\Mailing;
use Civi\Api4\MailingGroup;
use Civi\Api4\SubscriptionHistory;

/**
 * Class CRM_Mailing_BAO_MailingTest
 */
class CRM_Mailing_BAO_ConfirmTest extends CiviUnitTestCase {

  /**
   * Cleanup after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_group', 'civicrm_group_contact', 'civicrm_mailing', 'civicrm_mailing_group', 'civicrm_mailing_event_subscribe']);
    parent::tearDown();
  }

  /**
   * Test confirm function, with group token.
   *
   * @throws CRM_Core_Exception
   */
  public function testConfirm(): void {
    $mailUtil = new CiviMailUtils($this);
    $contactID = $this->individualCreate();
    $groupID = Group::create()->setValues([
      'name' => 'Test Group',
      'title' => 'Test Group',
      'frontend_title' => 'Test Group',
    ])->execute()->first()['id'];
    GroupContact::create()->setValues(['contact_id' => $contactID, 'status' => 'Added', 'group_id' => $groupID])->execute();
    SubscriptionHistory::create()->setValues([
      'contact_id' => $contactID,
      'group_id' => $groupID,
      'method' => 'Email',
    ])->execute();
    $mailingID = Mailing::create()->execute()->first()['id'];
    MailingGroup::create()->setValues([
      'mailing_id' => $mailingID,
      'group_type' => 'Include',
      'entity_table' => 'civicrm_group',
      'entity_id' => $groupID,
    ])->execute()->first()['id'];

    $mailingComponentID = $this->callAPISuccess('MailingComponent', 'get', ['component_type' => 'Welcome'])['id'];
    $this->callAPISuccess('MailingComponent', 'create', [
      // Swap {welcome.group} to {group.frontend_title} which is the standardised token.
      // The intent is to make this version the default, but need to ensure it is required.
      'body_html' => 'Welcome. Your subscription to the {group.frontend_title} mailing list has been activated.',
      'body_text' => 'Welcome. Your subscription to the {group.frontend_title} mailing list has been activated.',
      'id' => $mailingComponentID,
    ]);
    $hash = 4;
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_mailing_event_subscribe (group_id, contact_id, hash) VALUES ($groupID, $contactID, $hash)
    ");

    CRM_Mailing_Event_BAO_MailingEventConfirm::confirm($contactID, 1, $hash);
    $mailUtil->checkAllMailLog([
      'From: "FIXME" <info@EXAMPLE.ORG>',
      'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
      'Subject: Your Subscription has been Activated',
      'Welcome. Your subscription to the Test Group mailing list has been activated.',
    ]);
  }

}
