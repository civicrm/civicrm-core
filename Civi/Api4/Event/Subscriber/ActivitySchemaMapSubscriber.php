<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\ExtraJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.api4.activitySchema
 */
class ActivitySchemaMapSubscriber extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  /**
   * @return array
   */
  public static function getSubscribedEvents() {
    return [
      'api.schema_map.build' => 'onSchemaBuild',
    ];
  }

  /**
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event): void {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_activity');

    $link = (new ExtraJoinable('civicrm_case', 'id', 'case_id'))
      ->setBaseTable('civicrm_activity')
      ->setJoinType(Joinable::JOIN_TYPE_MANY_TO_ONE)
      ->addCondition('`{target_table}`.`id` = (SELECT `civicrm_case_activity`.`case_id` FROM `civicrm_case_activity` WHERE `civicrm_case_activity`.`activity_id` = `{base_table}`.`id` LIMIT 1)');
    $table->addTableLink('id', $link);

  }

}
