<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Core\Attribute\CiviEventListener;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\ExtraJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;

/**
 * @service civi.api4.civiCaseSchema
 */
class CaseSchemaMapSubscriber extends \Civi\Core\Service\AutoService {

  /**
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  #[CiviEventListener(event: 'api.schema_map.build')]
  public function onSchemaBuild(SchemaMapBuildEvent $event): void {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_activity');

    $link = (new ExtraJoinable('civicrm_case', 'id', 'case_id'))
      ->setBaseTable('civicrm_activity')
      ->setJoinType(Joinable::JOIN_TYPE_MANY_TO_ONE)
      ->addCondition('`{target_table}`.`id` = (SELECT `civicrm_case_activity`.`case_id` FROM `civicrm_case_activity` WHERE `civicrm_case_activity`.`activity_id` = `{base_table}`.`id` LIMIT 1)');
    $table->addTableLink('id', $link);

    $caseTable = $schema->getTableByName('civicrm_case');
    if ($caseTable) {
      $caseManagerSubquery = \Civi\Api4\Service\Spec\Provider\CaseManagerSpecProvider::getCaseManagerSubquery('`{base_table}`');
      $caseManagerLink = (new ExtraJoinable('civicrm_contact', 'id', 'case_manager_id'))
        ->setBaseTable('civicrm_case')
        ->setJoinType(Joinable::JOIN_TYPE_MANY_TO_ONE)
        ->addCondition("`{target_table}`.`id` = $caseManagerSubquery");
      $caseTable->addTableLink('id', $caseManagerLink);
    }

  }

}
