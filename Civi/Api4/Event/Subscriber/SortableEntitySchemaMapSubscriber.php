<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\ExtraJoinable;
use Civi\Api4\Utils\CoreUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class SortableEntitySchemaMapSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'api.schema_map.build' => 'onSchemaBuild',
    ];
  }

  /**
   * This creates a joinable which gets exposed and rendered by:
   * @see \Civi\Api4\Service\Spec\Provider\SortableEntitySpecProvider
   *
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event) {
    $schema = $event->getSchemaMap();
    foreach ($schema->getTables() as $table) {
      $entityName = $table->getEntityName();
      if (!$entityName || !CoreUtil::isType($entityName, 'SortableEntity')) {
        continue;
      }
      $weightColumn = CoreUtil::getInfoItem($entityName, 'order_by');
      $groupings = (array) CoreUtil::getInfoItem($entityName, 'group_weights_by');
      foreach (['previous' => '-', 'next' => '+'] as $type => $op) {
        $link = new ExtraJoinable($table->getName(), 'id', $type);
        $link->setBaseTable($table->getName());
        $link->setJoinType(ExtraJoinable::JOIN_TYPE_ONE_TO_ONE);
        foreach ($groupings as $grouping) {
          $link->addCondition("`{target_table}`.`$grouping` = `{base_table}`.`$grouping`");
        }
        $link->addCondition("`{target_table}`.`$weightColumn` = (`{base_table}`.`$weightColumn` $op 1)");
        $table->addTableLink('id', $link);
      }
    }
  }

}
