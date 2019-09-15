<?php

namespace api\v4\Query;

use Civi\Api4\Contact;
use api\v4\UnitTestCase;

/**
 * Class OneToOneJoinTest
 * @package api\v4\Query
 * @group headless
 */
class OneToOneJoinTest extends UnitTestCase {

  public function testOneToOneJoin() {
    $armenianContact = Contact::create()
      ->addValue('first_name', 'Contact')
      ->addValue('last_name', 'One')
      ->addValue('contact_type', 'Individual')
      ->addValue('preferred_language', 'hy_AM')
      ->execute()
      ->first();

    $basqueContact = Contact::create()
      ->addValue('first_name', 'Contact')
      ->addValue('last_name', 'Two')
      ->addValue('contact_type', 'Individual')
      ->addValue('preferred_language', 'eu_ES')
      ->execute()
      ->first();

    $contacts = Contact::get()
      ->addWhere('id', 'IN', [$armenianContact['id'], $basqueContact['id']])
      ->addSelect('preferred_language.label')
      ->addSelect('last_name')
      ->execute()
      ->indexBy('last_name')
      ->getArrayCopy();

    $this->assertEquals($contacts['One']['preferred_language.label'], 'Armenian');
    $this->assertEquals($contacts['Two']['preferred_language.label'], 'Basque');
  }

}
