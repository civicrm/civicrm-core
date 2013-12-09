<?php

namespace Civi\Tests\Factories;

class Base
{
  static function create()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $object = static::build();
    $entity_manager->persist($object);
    $entity_manager->flush();
    return $object;
  }
}
