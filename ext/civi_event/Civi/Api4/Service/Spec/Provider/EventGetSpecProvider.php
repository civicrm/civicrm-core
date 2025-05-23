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

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class EventGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $field = (new FieldSpec('remaining_participants', 'Event', 'Integer'))
      ->setTitle(ts('Remaining Participants'))
      ->setDescription(ts('Maximum participants minus registered participants'))
      ->setInputType('Number')
      ->setColumnName('max_participants')
      ->setSqlRenderer([__CLASS__, 'getRemainingParticipants']);
    $spec->addFieldSpec($field);
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Event' && $action === 'get';
  }

  /**
   * Subtracts max_participants from number of counted (non-test, non-deleted) participants.
   *
   * @param array $maxField
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * return string
   *
   * @throws \CRM_Core_Exception
   */
  public static function getRemainingParticipants(array $maxField, Api4SelectQuery $query): string {
    $statuses = \CRM_Event_BAO_Participant::buildOptions('status_id', NULL, ['is_counted' => 1]) ?: [0];
    $statusIds = implode(',', array_keys($statuses));
    $idField = $query->getFieldSibling($maxField, 'id');
    $whereClauses = [
      '`p`.`event_id` = ' . $idField['sql_name'],
      '`p`.`contact_id` = `c`.`id`',
      '`p`.`is_test` = 0',
      '`c`.`is_deleted` = 0',
      "`p`.status_id IN ($statusIds)",
    ];
    $participantRoleClause = \CRM_Event_BAO_Participant::getParticipantRoleClause();
    if ($participantRoleClause) {
      $whereClauses[] = 'role_id ' . $participantRoleClause;
    }
    return "IF($maxField[sql_name], (CAST($maxField[sql_name] AS SIGNED) - (SELECT COUNT(`p`.`id`) FROM `civicrm_participant` `p`, `civicrm_contact` `c`
      WHERE " . implode(' AND ', $whereClauses) . ")), NULL)";
  }

}
