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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use api\v4\Api4TestBase;
use Civi\Api4\GroupContact;
use Civi\Api4\GroupSubscription;
use Civi\Api4\MailingEventSubscribe;
use Civi\Api4\SubscriptionHistory;

/**
 * @group headless
 */
class GroupSubscriptionTest extends Api4TestBase {

  public function testCreateAndGet(): void {
    $contact = $this->createTestRecord('Contact');
    $group = $this->createTestRecord('Group', ['visibility' => 'User and User Admin Only']);
    $groupName = $group['name'];

    // Check contact has not been subscribed to group
    $subscription = GroupSubscription::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->first();
    $this->assertFalse($subscription[$groupName]);

    // Subscribe
    GroupSubscription::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue($groupName, TRUE)
      ->setMethod('Web')
      ->execute();

    // Call again with NULL - should have no effect
    GroupSubscription::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue($groupName, NULL)
      ->setMethod('Form')
      ->execute();

    // Check contact has been subscribed to group
    $subscription = GroupSubscription::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->first();
    $this->assertTrue($subscription[$groupName]);

    // Verify subscription history
    $history = SubscriptionHistory::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->single();
    $this->assertEquals('Web', $history['method']);
    $this->assertEquals('Added', $history['status']);

    // Unsubscribe
    GroupSubscription::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue($groupName, FALSE)
      ->setMethod('Form')
      ->execute();

    // Check contact has been unsubscribed
    $subscription = GroupSubscription::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->first();
    $this->assertFalse($subscription[$groupName]);

    // Verify subscription history
    $history = SubscriptionHistory::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addWhere('status', '=', 'Removed')
      ->execute()->single();
    $this->assertEquals('Form', $history['method']);

    // Re-subscribe
    GroupSubscription::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue($groupName, TRUE)
      ->execute();

    // Check contact has been subscribed to group
    $subscription = GroupSubscription::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->first();
    $this->assertTrue($subscription[$groupName]);

    // Verify subscription history
    $history = SubscriptionHistory::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->addWhere('status', '=', 'Added')
      ->addOrderBy('id', 'DESC')
      ->execute();
    $this->assertEquals('API', $history[0]['method']);
    $this->assertEquals('Added', $history[0]['status']);
  }

  public function testDoubleOptIn(): void {
    \CRM_Core_BAO_ConfigSetting::enableComponent('CiviMail');

    $contact = $this->createTestRecord('Contact', ['email_primary.email' => 'ex@m.ple']);
    $publicGroup = $this->createTestRecord('Group', ['visibility' => 'Public Pages'])['name'];
    $privateGroup = $this->createTestRecord('Group', ['visibility' => 'User and User Admin Only'])['name'];

    GroupSubscription::create(FALSE)
      ->addValue('contact_id', $contact['id'])
      ->addValue($publicGroup, TRUE)
      ->addValue($privateGroup, TRUE)
      ->execute();

    // Check contact has been subscribed to private group and pending in public group
    $subscription = GroupSubscription::get(FALSE)
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->first();
    $this->assertFalse($subscription[$publicGroup]);
    $this->assertTrue($subscription[$privateGroup]);

    $groupContact = GroupContact::get(FALSE)
      ->addSelect('status', 'group_id.name')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute()->column('status', 'group_id.name');
    $this->assertEquals('Pending', $groupContact[$publicGroup]);
    $this->assertEquals('Added', $groupContact[$privateGroup]);

    $mailingEvent = MailingEventSubscribe::get(FALSE)
      ->addSelect('group_id.name')
      ->addWhere('contact_id', '=', $contact['id'])
      ->execute();
    $this->assertCount(1, $mailingEvent);
    $this->assertEquals($publicGroup, $mailingEvent[0]['group_id.name']);
  }

}
