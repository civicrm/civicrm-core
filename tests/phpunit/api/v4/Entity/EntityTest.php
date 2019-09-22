<?php

namespace api\v4\Entity;

use Civi\Api4\Entity;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class EntityTest extends UnitTestCase {

  public function testEntityGet() {
    $result = Entity::get()
      ->setCheckPermissions(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertArrayHasKey('Entity', $result,
      "Entity::get missing itself");
    $this->assertArrayHasKey('Participant', $result,
      "Entity::get missing Participant");
  }

  public function testEntity() {
    $result = Entity::getActions()
      ->setCheckPermissions(FALSE)
      ->execute()
      ->indexBy('name');
    $this->assertNotContains(
      'create',
      array_keys((array) $result),
      "Entity entity has more than basic actions");
  }

}
