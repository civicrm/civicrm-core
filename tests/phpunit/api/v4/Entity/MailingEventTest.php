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
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MailingEventTest extends Api4TestBase implements TransactionalInterface {

  public function testMailingStats(): void {
    $cid1 = $this->createTestRecord('Contact')['id'];
    $cid2 = $this->createTestRecord('Contact')['id'];
    $eid1 = $this->createTestRecord('Email', ['contact_id' => $cid1])['id'];
    $eid2 = $this->createTestRecord('Email', ['contact_id' => $cid2])['id'];
    $mid1 = $this->createTestRecord('Mailing')['id'];
    $mid2 = $this->createTestRecord('Mailing')['id'];
    $parentJobIDs = $this->saveTestRecords('MailingJob', [
      'records' => [
        ['mailing_id' => $mid1, 'is_test' => FALSE],
        ['mailing_id' => $mid2, 'is_test' => TRUE],
      ],
    ])->column('id');

    $childJobIDs = $this->saveTestRecords('MailingJob', [
      'records' => [
        ['mailing_id' => $mid1, 'parent_id' => $parentJobIDs[0], 'job_type' => 'child', 'is_test' => 'false'],
        ['mailing_id' => $mid2, 'parent_id' => $parentJobIDs[1], 'job_type' => 'child', 'is_test' => 'false'],
      ],
    ])->column('id');

    $queueIDs = $this->saveTestRecords('MailingEventQueue',
      [
        'records' => [
          ['job_id' => $childJobIDs[0], 'mailing_id' => $mid1, 'contact_id' => $cid1, 'email_id' => $eid1],
          ['job_id' => $childJobIDs[0], 'mailing_id' => $mid1, 'contact_id' => $cid2, 'email_id' => $eid2],
          ['job_id' => $childJobIDs[1], 'mailing_id' => $mid2, 'contact_id' => $cid1, 'email_id' => $eid1],
          ['job_id' => $childJobIDs[1], 'mailing_id' => $mid2, 'contact_id' => $cid2, 'email_id' => $eid2],
        ],
      ])->column('id');

    $onceEachQueue =
      [
        'records' => [
          ['event_queue_id' => $queueIDs[0]],
          ['event_queue_id' => $queueIDs[1]],
        ],
      ];

    $twiceOneQueue =
      [
        'records' => [
          ['event_queue_id' => $queueIDs[0]],
          ['event_queue_id' => $queueIDs[0]],
        ],
      ];

    $this->saveTestRecords('MailingEventDelivered', $onceEachQueue);
    $this->createTestRecord('MailingEventBounce', ['event_queue_id' => $queueIDs[1]]);
    $this->saveTestRecords('MailingEventOpened', $twiceOneQueue);
    $trackableURLIDs = $this->saveTestRecords('MailingTrackableURL',
      [
        'records' => [
          ['mailing_id' => $mid1],
          ['mailing_id' => $mid1],
        ],
      ])->column('id');

    $this->saveTestRecords('MailingEventTrackableURLOpen',
      [
        'records' => [
          ['event_queue_id' => $queueIDs[0], 'trackable_url_id' => $trackableURLIDs[0]],
          ['event_queue_id' => $queueIDs[0], 'trackable_url_id' => $trackableURLIDs[1]],
          ['event_queue_id' => $queueIDs[0], 'trackable_url_id' => $trackableURLIDs[1]],
          ['event_queue_id' => $queueIDs[1], 'trackable_url_id' => $trackableURLIDs[1]],
        ],
      ]);

    $this->saveTestRecords('MailingEventForward', $twiceOneQueue);
    $this->saveTestRecords('MailingEventReply', $twiceOneQueue);
    $this->saveTestRecords('MailingEventUnsubscribe',
      [
        'records' => [
          ['event_queue_id' => $queueIDs[0], 'org_unsubscribe' => 0],
          ['event_queue_id' => $queueIDs[0], 'org_unsubscribe' => 0],
          ['event_queue_id' => $queueIDs[1], 'org_unsubscribe' => 1],
          ['event_queue_id' => $queueIDs[1], 'org_unsubscribe' => 1],
        ],
      ]);

    $mailings = \Civi\Api4\Mailing::get(FALSE)
      ->addSelect('stats_intended_recipients', 'stats_successful', 'stats_opens_total', 'stats_opens_unique',
        'stats_clicks_total', 'stats_clicks_unique', 'stats_bounces', 'stats_unsubscribes', 'stats_optouts',
        'stats_optouts_and_unsubscribes', 'stats_forwards', 'stats_replies')
      ->addWhere('id', 'IN', [$mid1, $mid2])
      ->addOrderBy('id', 'ASC')
      ->execute();

    $this->assertEquals(2, $mailings[0]['stats_intended_recipients']);
    $this->assertEquals(2, $mailings[1]['stats_intended_recipients']);
    $this->assertEquals(1, $mailings[0]['stats_bounces']);
    $this->assertEquals(0, $mailings[1]['stats_bounces']);
    $this->assertEquals(1, $mailings[0]['stats_successful']);
    $this->assertEquals(0, $mailings[1]['stats_successful']);
    $this->assertEquals(2, $mailings[0]['stats_opens_total']);
    $this->assertEquals(0, $mailings[1]['stats_opens_total']);
    $this->assertEquals(1, $mailings[0]['stats_opens_unique']);
    $this->assertEquals(0, $mailings[1]['stats_opens_unique']);
    $this->assertEquals(4, $mailings[0]['stats_clicks_total']);
    $this->assertEquals(0, $mailings[1]['stats_clicks_total']);
    $this->assertEquals(3, $mailings[0]['stats_clicks_unique']);
    $this->assertEquals(0, $mailings[1]['stats_clicks_unique']);
    $this->assertEquals(2, $mailings[0]['stats_forwards']);
    $this->assertEquals(0, $mailings[1]['stats_forwards']);
    $this->assertEquals(2, $mailings[0]['stats_replies']);
    $this->assertEquals(0, $mailings[1]['stats_replies']);
    $this->assertEquals(1, $mailings[0]['stats_unsubscribes']);
    $this->assertEquals(0, $mailings[1]['stats_unsubscribes']);
    $this->assertEquals(1, $mailings[0]['stats_optouts']);
    $this->assertEquals(0, $mailings[1]['stats_optouts']);
    $this->assertEquals(2, $mailings[0]['stats_optouts_and_unsubscribes']);
    $this->assertEquals(0, $mailings[1]['stats_optouts_and_unsubscribes']);
  }

}
