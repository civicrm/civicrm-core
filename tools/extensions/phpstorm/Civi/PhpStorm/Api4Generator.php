<?php

namespace Civi\PhpStorm;

use Civi\Api4\Entity;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.phpstorm.api4
 */
class Api4Generator extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'civi.phpstorm.flush' => 'generate',
      'hook_civicrm_post::CustomGroup' => 'generate',
    ];
  }

  public function generate() {
    /*
     * FIXME: PHPSTORM_META doesn't seem to support compound dynamic arguments
     * so even if you give it separate lists like
     * ```
     * expectedArguments(\civicrm_api4('Contact'), 1, 'a', 'b');
     * expectedArguments(\civicrm_api4('Case'), 1, 'c', 'd');
     * ```
     * It doesn't differentiate them and always offers a,b,c,d for every entity.
     * If they ever fix that upstream we could fetch a different list of actions per entity,
     * but for now there's no point.
     */

    $entities = Entity::get(FALSE)->addSelect('name')->execute()->column('name');
    $actions = ['get', 'save', 'create', 'update', 'delete', 'replace', 'revert', 'export', 'autocomplete', 'getFields', 'getActions', 'getLinks', 'checkAccess'];
    $properties = Entity::getFields(FALSE)->addOrderBy('name')->execute()->column('name');
    $entityTypes = Entity::getFields(FALSE)->addWhere('name', '=', 'type')->setLoadOptions(TRUE)->execute()->first()['options'] ?? [];

    $builder = new PhpStormMetadata('api4', __CLASS__);
    $builder->registerArgumentsSet('api4Entities', ...$entities);
    $builder->registerArgumentsSet('api4Actions', ...$actions);
    $builder->registerArgumentsSet('api4Properties', ...$properties);
    $builder->registerArgumentsSet('api4EntityTypes', ...array_values($entityTypes));

    // Define arguments for core functions
    $builder->addExpectedArguments('\civicrm_api4()', 0, 'api4Entities');
    $builder->addExpectedArguments('\civicrm_api4()', 1, 'api4Actions');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getBAOFromApiName()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getApiClass()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getInfoItem()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getInfoItem()', 1, 'api4Properties');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getIdFieldName()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getSearchFields()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getTableName()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getCustomGroupExtends()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getRefCount()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::getRefCountTotal()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::isType()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::isType()', 1, 'api4EntityTypes');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::checkAccessDelegated()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Api4\Utils\CoreUtil::checkAccessDelegated()', 1, 'api4Actions');
    $builder->addExpectedArguments('\Civi\API\EntityLookupTrait::define()', 0, 'api4Entities');

    // Define arguments for unit test functions
    $builder->addExpectedArguments('\Civi\Test\Api4TestTrait::createTestRecord()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Test\Api4TestTrait::saveTestRecords()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Test\EntityTrait::createTestEntity()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Test\EntityTrait::setTestEntity()', 0, 'api4Entities');
    $builder->addExpectedArguments('\Civi\Test\EntityTrait::setTestEntityID()', 0, 'api4Entities');

    $builder->write();
  }

}
