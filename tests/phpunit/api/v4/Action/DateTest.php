<?php

namespace api\v4\Action;

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

}
