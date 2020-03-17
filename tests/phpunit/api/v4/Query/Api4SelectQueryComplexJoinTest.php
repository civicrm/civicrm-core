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
 * $Id$
 *
 */


namespace api\v4\Query;

use Civi\Api4\Query\Api4SelectQuery;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class Api4SelectQueryComplexJoinTest extends UnitTestCase {

  public function setUpHeadless() {
    $relatedTables = [
      'civicrm_address',
      'civicrm_email',
      'civicrm_phone',
      'civicrm_openid',
      'civicrm_im',
      'civicrm_website',
      'civicrm_activity',
      'civicrm_activity_contact',
    ];
    $this->cleanup(['tablesToTruncate' => $relatedTables]);
    $this->loadDataSet('SingleContact');
    return parent::setUpHeadless();
  }

  public function testWithComplexRelatedEntitySelect() {
    $query = new Api4SelectQuery('Contact', FALSE, civicrm_api4('Contact', 'getFields', ['includeCustom' => FALSE, 'checkPermissions' => FALSE, 'action' => 'get'], 'name'));
    $query->select[] = 'id';
    $query->select[] = 'display_name';
    $query->select[] = 'phones.*_id';
    $query->select[] = 'emails.email';
    $query->select[] = 'emails.location_type.name';
    $query->select[] = 'created_activities.contact_id';
    $query->select[] = 'created_activities.activity.subject';
    $query->select[] = 'created_activities.activity.activity_type.name';
    $query->where[] = ['first_name', '=', 'Single'];
    $query->where[] = ['id', '=', $this->getReference('test_contact_1')['id']];
    $results = $query->run();

    $testActivities = [
      $this->getReference('test_activity_1'),
      $this->getReference('test_activity_2'),
    ];
    $activitySubjects = array_column($testActivities, 'subject');

    $this->assertCount(1, $results);
    $firstResult = array_shift($results);
    $this->assertArrayHasKey('created_activities', $firstResult);
    $firstCreatedActivity = array_shift($firstResult['created_activities']);
    $this->assertArrayHasKey('activity', $firstCreatedActivity);
    $firstActivity = $firstCreatedActivity['activity'];
    $this->assertContains($firstActivity['subject'], $activitySubjects);
    $this->assertArrayHasKey('activity_type', $firstActivity);
    $activityType = $firstActivity['activity_type'];
    $this->assertArrayHasKey('name', $activityType);

    $this->assertArrayHasKey('name', $firstResult['emails'][0]['location_type']);
    $this->assertArrayHasKey('location_type_id', $firstResult['phones'][0]);
    $this->assertArrayHasKey('id', $firstResult['phones'][0]);
    $this->assertArrayNotHasKey('phone', $firstResult['phones'][0]);
  }

  public function testWithSelectOfOrphanDeepValues() {
    $query = new Api4SelectQuery('Contact', FALSE, civicrm_api4('Contact', 'getFields', ['includeCustom' => FALSE, 'checkPermissions' => FALSE, 'action' => 'get'], 'name'));
    $query->select[] = 'id';
    $query->select[] = 'first_name';
    // emails not selected
    $query->select[] = 'emails.location_type.name';
    $results = $query->run();
    $firstResult = array_shift($results);

    $this->assertEmpty($firstResult['emails']);
  }

  public function testOrderDoesNotMatter() {
    $query = new Api4SelectQuery('Contact', FALSE, civicrm_api4('Contact', 'getFields', ['includeCustom' => FALSE, 'checkPermissions' => FALSE, 'action' => 'get'], 'name'));
    $query->select[] = 'id';
    $query->select[] = 'first_name';
    // before emails selection
    $query->select[] = 'emails.location_type.name';
    $query->select[] = 'emails.email';
    $query->where[] = ['emails.email', 'IS NOT NULL'];
    $results = $query->run();
    $firstResult = array_shift($results);

    $this->assertNotEmpty($firstResult['emails'][0]['location_type']['name']);
  }

}
