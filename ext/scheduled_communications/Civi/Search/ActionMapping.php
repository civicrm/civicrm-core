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

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace Civi\Search;

use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Api4\Query\Api4Query;
use Civi\Api4\Query\SqlExpression;
use Civi\Api4\SavedSearch;
use Civi\Api4\Utils\CoreUtil;

/**
 * This enables scheduled-reminders to be run based on a SavedSearch.
 */
class ActionMapping extends \Civi\ActionSchedule\MappingBase {

  use SavedSearchInspectorTrait;

  /**
   * @return string
   */
  public function getName(): string {
    return 'saved_search';
  }

  public function getEntityName(): string {
    return 'SavedSearch';
  }

  public function getEntityTable(\CRM_Core_DAO_ActionSchedule $actionSchedule): string {
    $this->loadSavedSearch($actionSchedule->entity_value);
    return CoreUtil::getTableName($this->savedSearch['api_entity']);
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Saved Search'))
      ->setInputAttr('multiple', FALSE);
    $spec->getFieldByName('entity_status')
      ->setLabel(ts('Contact ID Field'))
      ->setInputAttr('multiple', FALSE)
      ->setRequired(TRUE);
  }

  /**
   *
   * @return array
   */
  public function getValueLabels(): array {
    return SavedSearch::get(FALSE)
      ->addSelect('id', 'name', 'label')
      ->addOrderBy('label')
      ->addWhere('api_entity', 'IS NOT NULL')
      ->addWhere('is_current', '=', TRUE)
      // Limit to searches that have something to do with contacts
      // FIXME: Matching `api_params LIKE %contact%` is a cheap trick with no real understanding of the appropriateness of the SavedSearch for use as a Scheduled Reminder.
      ->addClause('OR', ['api_entity', 'IN', ['Contact', 'Individual', 'Household', 'Organization']], ['api_params', 'LIKE', '%contact%'])
      ->execute()->getArrayCopy();
  }

  /**
   * @param array|null $entityValue
   * @return array
   */
  public function getStatusLabels(?array $entityValue): array {
    if (!$entityValue) {
      return [];
    }
    $this->loadSavedSearch(\CRM_Utils_Array::first($entityValue));
    $fieldNames = [];
    foreach ($this->getSelectClause() as $columnAlias => $columnInfo) {
      // TODO: It would be nice to return only fields with an FK to contact.id
      // For now returning fields of type Int or unknown
      if (in_array($columnInfo['dataType'], ['Integer', NULL], TRUE)) {
        $fieldNames[$columnAlias] = $this->getColumnLabel($columnInfo['expr']);
      }
    }
    return $fieldNames;
  }

  /**
   * @param array|null $entityValue
   * @return array
   */
  public function getDateFields(?array $entityValue = NULL): array {
    if (!$entityValue) {
      return [];
    }
    $this->loadSavedSearch(\CRM_Utils_Array::first($entityValue));
    $fieldNames = [];
    foreach ($this->getSelectClause() as $columnAlias => $columnInfo) {
      // Only return date fields
      // For now also including fields of unknown type since SQL functions sometimes don't know their return type
      if (in_array($columnInfo['dataType'], ['Date', 'Timestamp', NULL], TRUE)) {
        $fieldNames[$columnAlias] = $this->getColumnLabel($columnInfo['expr']);
      }
    }
    return $fieldNames;
  }

  public static function getLimitToOptions(): array {
    return [
      [
        'id' => 3,
        'name' => 'copy',
        'label' => ts('Send copy to'),
      ],
      [
        'id' => 4,
        'name' => 'reroute',
        'label' => ts('Send instead to'),
      ],
    ];
  }

  /**
   * @param $schedule
   * @return bool
   */
  public function resetOnTriggerDateChange($schedule): bool {
    return FALSE;
  }

  /**
   * Generate a query to locate recipients.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   *
   * @param array $defaultParams
   *
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams): \CRM_Utils_SQL_Select {
    $this->loadSavedSearch($schedule->entity_value);
    $this->savedSearch['api_params']['checkPermissions'] = FALSE;
    $mainTableAlias = Api4Query::MAIN_TABLE_ALIAS;
    // This mapping type requires exactly one 'entity_status': the name of the contact.id field.
    $contactIdFieldName = $schedule->entity_status;
    // The RecipientBuilder needs to know the name of the Contact table.
    // Check if Contact is the main table or an explicit join
    if ($contactIdFieldName === 'id' || str_ends_with($contactIdFieldName, '.id')) {
      $contactPrefix = substr($contactIdFieldName, 0, strrpos($contactIdFieldName, 'id'));
      $contactJoin = $this->getJoin($contactPrefix);
      $contactTable = $contactJoin['alias'] ?? $mainTableAlias;
    }
    // Else if contact id is an FK field, use implicit join syntax
    else {
      $contactPrefix = $contactIdFieldName . '.';
    }
    // Exclude deceased and deleted contacts
    $this->savedSearch['api_params']['where'][] = [$contactPrefix . 'is_deceased', '=', FALSE];
    $this->savedSearch['api_params']['where'][] = [$contactPrefix . 'is_deleted', '=', FALSE];
    // Refresh search query with new api params
    $this->loadSavedSearch();
    $apiQuery = $this->getQuery();
    // If contact id is an FK field, find table name by rendering the id field and stripping off the field name
    if (!isset($contactTable)) {
      $contactIdSql = SqlExpression::convert($contactPrefix . 'id')->render($apiQuery);
      $contactTable = str_replace('.`id`', '', $contactIdSql);
    }
    $apiQuery->getSql();
    $sqlSelect = \CRM_Utils_SQL_Select::from($apiQuery->getQuery()->getFrom());
    $sqlSelect->merge($apiQuery->getQuery(), ['joins', 'wheres']);
    $sqlSelect->param($defaultParams);
    $sqlSelect['casAddlCheckFrom'] = $sqlSelect->getFrom();
    $sqlSelect['casContactIdField'] = SqlExpression::convert($contactIdFieldName)->render($apiQuery);
    $sqlSelect['casEntityIdField'] = '`' . $mainTableAlias . '`.`' . CoreUtil::getIdFieldName($this->savedSearch['api_entity']) . '`';
    $sqlSelect['casContactTableAlias'] = $contactTable;
    if ($schedule->absolute_date) {
      $sqlSelect['casDateField'] = "'" . \CRM_Utils_Type::escape($schedule->absolute_date, 'String') . "'";
    }
    else {
      $sqlSelect['casDateField'] = $this->getSelectExpression($schedule->start_action_date)['expr']->render($apiQuery);
    }
    return $sqlSelect;
  }

  public function sendToAdditional($entityId): bool {
    return FALSE;
  }

}
