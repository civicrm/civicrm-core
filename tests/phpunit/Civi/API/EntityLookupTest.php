<?php
namespace Civi\API;

class EntityLookupTest extends \CiviUnitTestCase {

  use EntityLookupTrait;

  public function testLookupContacts() {
    $bob = $this->createTestEntity('Contact', ['first_name' => 'Bob', 'last_name' => 'One', 'gender_id:name' => 'Male', 'email_primary.email' => 'bob@one.test']);
    $jan = $this->createTestEntity('Contact', ['first_name' => 'Jan', 'last_name' => 'Two', 'gender_id:name' => 'Female', 'external_identifier' => uniqid()]);
    $this->define('Contact', 'Bob', ['id' => $bob['id']]);
    $this->assertFalse($this->isDefined('Jan'));
    $this->define('Contact', 'Jan', ['external_identifier' => $jan['external_identifier']]);
    $this->assertTrue($this->isDefined('Jan'));
    $this->assertEquals($bob['id'], $this->getDefinition('Bob')['identifier']['id']);
    $this->assertEquals('Contact', $this->getDefinition('Jan')['entityName']);
    $this->assertNull($this->getDefinition('Jim'));
    $this->assertFalse($this->isDefined('Jim'));
    $this->assertEquals('One', $this->lookup('Bob', 'last_name'));
    $this->assertEquals('bob@one.test', $this->lookup('Bob', 'email_primary.email'));
    $this->assertEquals('Male', $this->lookup('Bob', 'gender_id:name'));
    $this->assertEquals($jan['id'], $this->lookup('Jan', 'id'));
    $this->assertEquals('Two', $this->lookup('Jan', 'last_name'));
    $this->assertEquals('Female', $this->lookup('Jan', 'gender_id:name'));
  }

}
