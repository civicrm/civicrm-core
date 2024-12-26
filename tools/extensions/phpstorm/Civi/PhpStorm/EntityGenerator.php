<?php

namespace Civi\PhpStorm;

use Civi\Api4\Entity;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.api4
 */
class EntityGenerator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
    ];
  }

  public function generate() {
    $entities = array_keys(\Civi\Schema\EntityRepository::getEntities());
    sort($entities);

    $builder = new PhpStormMetadata('entityRepository', __CLASS__);
    $builder->registerArgumentsSet('entities', ...$entities);

    // Define arguments for core functions
    $builder->addExpectedArguments('\Civi::entity()', 0, 'entities');
    $builder->addExpectedArguments('\Civi\Schema\EntityRepository::getEntity()', 0, 'entities');

    $properties = ['add', 'class', 'description', 'icon', 'label_field', 'log', 'name', 'paths', 'primary_key', 'primary_keys', 'table', 'title', 'title_plural'];
    $builder->registerArgumentsSet('properties', ...$properties);
    $builder->addExpectedArguments('\Civi\Schema\EntityMetadataInterface::getProperty()', 0, 'properties');
    $builder->addExpectedArguments('\Civi\Schema\EntityProvider::getMeta()', 0, 'properties');

    $builder->write();
  }

}
