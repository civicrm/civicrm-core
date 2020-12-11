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


namespace api\v4\Action;

use Civi\Api4\Activity;
use Civi\Api4\Contact;
use Civi\Api4\Relationship;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class DateTest extends UnitTestCase {

  public function testRelationshipDate() {
    $c1 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'one')
      ->execute()
      ->first()['id'];
    $c2 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'two')
      ->execute()
      ->first()['id'];
    $r = Relationship::create()
      ->addValue('contact_id_a', $c1)
      ->addValue('contact_id_b', $c2)
      ->addValue('relationship_type_id', 1)
      ->addValue('start_date', 'now')
      ->addValue('end_date', 'now + 1 week')
      ->execute()
      ->first()['id'];
    $result = Relationship::get()
      ->addWhere('start_date', '=', 'now')
      ->addWhere('end_date', '>', 'now + 1 day')
      ->execute()
      ->indexBy('id');
    $this->assertArrayHasKey($r, $result);
    $result = Relationship::get()
      ->addWhere('start_date', '<', 'now')
      ->execute()
      ->indexBy('id');
    $this->assertArrayNotHasKey($r, $result);
  }

  public function testRelativeDateRanges() {
    $c1 = Contact::create()
      ->addValue('first_name', 'c')
      ->addValue('last_name', 'one')
      ->execute()
      ->first()['id'];
    $act = Activity::save()
      ->setDefaults(['activity_type_id:name' => 'Meeting', 'source_contact_id' => $c1])
      ->addRecord(['activity_date_time' => 'now - 3 year'])
      ->addRecord(['activity_date_time' => 'now - 1 year'])
      ->addRecord(['activity_date_time' => 'now - 1 month'])
      ->addRecord(['activity_date_time' => 'now'])
      ->addRecord(['activity_date_time' => 'now + 1 month'])
      ->addRecord(['activity_date_time' => 'now + 1 year'])
      ->addRecord(['activity_date_time' => 'now + 3 year'])
      ->execute()->column('id');

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '>', 'previous.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertContains($act[3], $result);
    $this->assertContains($act[4], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '>', 'this.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertNotContains($act[2], $result);
    $this->assertNotContains($act[3], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '>=', 'this.year')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertContains($act[3], $result);
    $this->assertContains($act[4], $result);
    $this->assertContains($act[5], $result);
    $this->assertContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '<', 'previous.year')
      ->execute()->column('id');
    $this->assertContains($act[0], $result);
    $this->assertNotContains($act[4], $result);
    $this->assertNotContains($act[5], $result);
    $this->assertNotContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', '=', 'next.month')
      ->execute()->column('id');
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[1], $result);
    $this->assertNotContains($act[2], $result);
    $this->assertContains($act[4], $result);
    $this->assertNotContains($act[5], $result);
    $this->assertNotContains($act[6], $result);

    $result = Activity::get(FALSE)->addSelect('id')
      ->addWhere('activity_date_time', 'BETWEEN', ['previous.year', 'this.year'])
      ->execute()->column('id');
    $this->assertContains($act[2], $result);
    $this->assertContains($act[3], $result);
    $this->assertNotContains($act[0], $result);
    $this->assertNotContains($act[6], $result);
  }

}
