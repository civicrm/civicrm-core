<?php

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Event\Events;
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
      Events::SCHEMA_MAP_BUILD => 'onSchemaBuild',
    ];
  }

  /**
   * @param \Civi\Api4\Event\SchemaMapBuildEvent $event
   */
  public function onSchemaBuild(SchemaMapBuildEvent $event): void {
    $schema = $event->getSchemaMap();
    $table = $schema->getTableByName('civicrm_activity');

    $caseLink = (new ExtraJoinable('civicrm_case', 'id', 'case_id'))
      ->setBaseTable('civicrm_activity')
      ->setJoinType(Joinable::JOIN_TYPE_MANY_TO_ONE)
      ->addCondition('`{target_table}`.`id` = '
        . '(SELECT `civicrm_case_activity`.`case_id` '
        . 'FROM `civicrm_case_activity` '
        . 'WHERE `civicrm_case_activity`.`activity_id` = `{base_table}`.`id` '
        . 'LIMIT 1)');
    $table->addTableLink('id', $caseLink);

    $contactLinkTypes = [
      'source_contact_id' => 'Activity Source',
      'target_contact_id' => 'Activity Targets',
      'assignee_contact_id' => 'Activity Assignees',
    ];
    foreach ($contactLinkTypes as $fieldName => $typeName) {
      $contactLinkTypes[$fieldName] = \CRM_Core_PseudoConstant::getKey(
        'CRM_Activity_BAO_ActivityContact',
        'record_type_id',
        $typeName);
    }

    $sourceContactLink = (new ExtraJoinable(
      'civicrm_contact',
      'id',
      'source_contact_id'))
      ->setBaseTable('civicrm_activity')
      ->setJoinType(Joinable::JOIN_TYPE_ONE_TO_ONE)
      ->addCondition('`{target_table}`.`id` = '
        . '(SELECT `civicrm_activity_contact`.`contact_id` '
        . 'FROM `civicrm_activity_contact` '
        . 'WHERE `civicrm_activity_contact`.`activity_id` = `{base_table}`.`id` '
        . 'AND record_type_id = ' . $contactLinkTypes['source_contact_id']
        . ' LIMIT 1)');
    $table->addTableLink('id', $sourceContactLink);

    foreach (['target_contact_id', 'assignee_contact_id'] as $fieldName) {
      $otherContactLink = (new ExtraJoinable(
        'civicrm_contact',
        'id',
        $fieldName))
        ->setBaseTable('civicrm_activity')
        ->setJoinType(Joinable::JOIN_TYPE_ONE_TO_MANY)
        ->addCondition('`{target_table}`.`id` = '
          . '(SELECT `civicrm_activity_contact`.`contact_id` '
          . 'FROM `civicrm_activity_contact` '
          . 'WHERE `civicrm_activity_contact`.`activity_id` = `{base_table}`.`id` '
          . 'AND record_type_id = ' . $contactLinkTypes[$fieldName] . ')');
      $table->addTableLink('id', $otherContactLink);
    }
  }

}
