<?php

namespace Civi\Tests\Factories\Price;

use \Civi\Tests\Factories;

class Set extends Factories\Base
{
  static function build()
  {
    $entity_manager = \CRM_DB_EntityManager::singleton();
    $price_set = new \Civi\Price\Set();
    $domain = $entity_manager->getReference('Civi\Core\Domain', 1);
    $price_set->setDomain($domain);
    $price_set->setName("Test");
    $price_set->setTitle("Test");
    $civi_event_component = $entity_manager->getRepository('Civi\Core\Component')->findOneBy(array('name' => 'CiviEvent'));
    $price_set->setExtends($civi_event_component->getId());
    $price_set->addPriceField(Field::build());
    return $price_set;
  }
}
