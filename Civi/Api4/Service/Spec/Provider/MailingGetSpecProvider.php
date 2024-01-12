<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class MailingGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   *
   * @throws \CRM_Core_Exception
   */
  public function modifySpec(RequestSpec $spec): void {
    $field = new FieldSpec('stats_intended_recipients', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Intended Recipients'))
      ->setDescription(ts('Total emails sent'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countIntendedRecipients']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_successful', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Succesful Deliveries'))
      ->setDescription(ts('Total emails delivered minus bounces'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countSuccessfulDeliveries']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_opens_total', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Total Opens'))
      ->setDescription(ts('Total tracked mailing opens'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_opens_unique', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Unique Opens'))
      ->setDescription(ts('Total unique tracked mailing opens'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_clicks_total', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Total Clicks'))
      ->setDescription(ts('Total mailing clicks'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_clicks_unique', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Unique Clicks'))
      ->setDescription(ts('Total unique mailing clicks'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_bounces', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Bounces'))
      ->setDescription(ts('Total mailing bounces'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_unsubscribes', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Unsubscribes'))
      ->setDescription(ts('Total mailing unsubscribes'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_optouts', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Opt Outs'))
      ->setDescription(ts('Total mailing opt outs'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_optouts_and_unsubscribes', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Opt Outs & Unsubscribes'))
      ->setDescription(ts('Total contacts who opted out or unsubscribed from a mailing'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_forwards', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Forwards'))
      ->setDescription(ts('Total mailing forwards'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);

    $field = new FieldSpec('stats_replies', 'Mailing', 'Integer');
    $field->setLabel(ts('Stats: Replies'))
      ->setDescription(ts('Total mailing replies'))
      ->setColumnName('id')
      ->setReadonly(TRUE)
      ->setSqlRenderer([__CLASS__, 'countMailingEvents']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action): bool {
    return $entity === 'Mailing' && $action === 'get';
  }

  /**
   * Generate SQL for counting mailing events
   *
   * @return string
   */
  public static function countMailingEvents(array $field): string {
    $unsubscribeType = $count = NULL;
    $queue = \CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $job = \CRM_Mailing_BAO_MailingJob::getTableName();
    $mailing = \CRM_Mailing_BAO_Mailing::getTableName();

    switch ($field['name']) {
      case 'stats_opens_total':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventOpened::getTableName();
        break;

      case 'stats_opens_unique':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventOpened::getTableName();
        $count = "DISTINCT $tableName.event_queue_id";
        break;

      case 'stats_clicks_total':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTableName();
        break;

      case 'stats_clicks_unique':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventTrackableURLOpen::getTableName();
        $count = "DISTINCT $tableName.event_queue_id,$tableName.trackable_url_id";
        break;

      case 'stats_bounces':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventBounce::getTableName();
        break;

      case 'stats_unsubscribes':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTableName();
        $unsubscribeType = 0;
        $count = "DISTINCT $tableName.event_queue_id,$tableName.org_unsubscribe";
        break;

      case 'stats_optouts':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTableName();
        $unsubscribeType = 1;
        $count = "DISTINCT $tableName.event_queue_id,$tableName.org_unsubscribe";
        break;

      case 'stats_optouts_and_unsubscribes':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventUnsubscribe::getTableName();
        $count = "DISTINCT $tableName.event_queue_id";
        break;

      case 'stats_forwards':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventForward::getTableName();
        break;

      case 'stats_replies':
        $tableName = \CRM_Mailing_Event_BAO_MailingEventReply::getTableName();
        break;

    }

    $count ??= "$tableName.event_queue_id";
    $query = "(
      SELECT      COUNT($count)
      FROM        $tableName
      INNER JOIN  $queue
              ON  $tableName.event_queue_id = $queue.id
      INNER JOIN  $job
              ON  $queue.job_id = $job.id
      INNER JOIN  $mailing
              ON  $job.mailing_id = $mailing.id
              AND $job.is_test = 0
      WHERE       $mailing.id = {$field['sql_name']}
      ";
    if (!is_null($unsubscribeType)) {
      $query .= " AND $tableName.org_unsubscribe = $unsubscribeType";
    }
    return $query . ")";
  }

  /**
   * Generate SQL for counting total intended recipients
   *
   * @return string
   */
  public static function countIntendedRecipients(array $field): string {
    $queue = \CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $mailing = \CRM_Mailing_BAO_Mailing::getTableName();
    $job = \CRM_Mailing_BAO_MailingJob::getTableName();

    return "(
      SELECT      COUNT($queue.id)
      FROM        $queue
      INNER JOIN  $job
              ON  $queue.job_id = $job.id
      INNER JOIN  $mailing
              ON  $job.mailing_id = $mailing.id
              AND $job.is_test = 0
      WHERE       $mailing.id = {$field['sql_name']}
      )";
  }

  /**
   * Generate SQL for counting total successful deliveries
   *
   * @return string
   */
  public static function countSuccessfulDeliveries(array $field): string {
    $delivered = \CRM_Mailing_Event_BAO_MailingEventDelivered::getTableName();
    $bounce = \CRM_Mailing_Event_BAO_MailingEventBounce::getTableName();
    $queue = \CRM_Mailing_Event_BAO_MailingEventQueue::getTableName();
    $mailing = \CRM_Mailing_BAO_Mailing::getTableName();
    $job = \CRM_Mailing_BAO_MailingJob::getTableName();

    return "(
      SELECT      COUNT($delivered.id)
      FROM        $delivered
      INNER JOIN  $queue
              ON  $delivered.event_queue_id = $queue.id
      LEFT JOIN   $bounce
              ON  $delivered.event_queue_id = $bounce.event_queue_id
      INNER JOIN  $job
              ON  $queue.job_id = $job.id
              AND $job.is_test = 0
      INNER JOIN  $mailing
              ON  $job.mailing_id = $mailing.id
      WHERE       $bounce.id IS null
          AND     $mailing.id = {$field['sql_name']}
      )";
  }

}
