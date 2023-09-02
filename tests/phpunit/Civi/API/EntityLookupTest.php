<?php
namespace Civi\API;

class EntityLookupTest extends \CiviUnitTestCase {

  use EntityLookupTrait;

  public function testLookupContacts() {
    $bob = $this->createTestEntity('Contact', ['first_name' => 'Bob', 'last_name' => 'One', 'gender_id:name' => 'Male', 'email_primary.email' => 'bob@one.test']);
    $jan = $this->createTestEntity('Contact', ['first_name' => 'Jan', 'last_name' => 'Two', 'gender_id:name' => 'Female', 'external_identifier' => uniqid()]);
    $this->define('Contact', 'Bob', ['id' => $bob['id']]);
    $this->define('Contact', 'Jan', ['external_identifier' => $jan['external_identifier']]);
    $this->assertEquals('One', $this->lookup('Bob', 'last_name'));
    $this->assertEquals('bob@one.test', $this->lookup('Bob', 'email_primary.email'));
    $this->assertEquals('Male', $this->lookup('Bob', 'gender_id:name'));
    $this->assertEquals('Two', $this->lookup('Jan', 'last_name'));
    $this->assertEquals('Female', $this->lookup('Jan', 'gender_id:name'));
  }

}
