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
class ActivitySpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @inheritDoc
   */
  public function modifySpec(RequestSpec $spec) {
    $action = $spec->getAction();

    if (in_array($action, ['create', 'update'], TRUE)) {
      // The database default '1' is problematic as the option list is user-configurable,
      // so activity type '1' doesn't necessarily exist. Best make the field required.
      $spec->getFieldByName('activity_type_id')
        ->setDefaultValue(NULL)
        ->setRequired($action === 'create');
    }

    if (in_array($action, ['get', 'create', 'update'], TRUE)) {
      $field = new FieldSpec('source_contact_id', 'Activity', 'Integer');
      $field->setTitle(ts('Source Contact'));
      $field->setLabel(ts('Added by'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contact who created this activity.'));
      $field->setRequired($action === 'create');
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setDataType('Integer');
      $field->setOperators(['=', '!=', '<>', 'IN', 'NOT IN']);
      $field->addSqlFilter([__CLASS__, 'getActivityContactFilterSql']);
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);

      $field = new FieldSpec('target_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Target Contacts'));
      $field->setLabel(ts('With Contacts'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contacts involved in this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $field->setDataType('Integer');
      $field->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
      $field->setOperators(['CONTAINS', 'NOT CONTAINS', 'CONTAINS ONE OF', 'NOT CONTAINS ONE OF', 'IS NULL', 'IS NOT NULL']);
      $field->addSqlFilter([__CLASS__, 'getActivityContactFilterSql']);
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);

      $field = new FieldSpec('assignee_contact_id', 'Activity', 'Array');
      $field->setTitle(ts('Assignee Contacts'));
      $field->setLabel(ts('Assigned to'));
      $field->setColumnName('id');
      $field->setDescription(ts('Contacts assigned to this activity.'));
      $field->setFkEntity('Contact');
      $field->setInputType('EntityRef');
      $field->setInputAttrs(['multiple' => TRUE]);
      $field->setDataType('Integer');
      $field->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA);
      $field->setOperators(['CONTAINS', 'NOT CONTAINS', 'CONTAINS ONE OF', 'NOT CONTAINS ONE OF', 'IS NULL', 'IS NOT NULL']);
      $field->addSqlFilter([__CLASS__, 'getActivityContactFilterSql']);
      $field->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);
    }

    if ($action === 'get') {
      // Field for all activity contacts (source, target, assignee)
      $field = (new FieldSpec('all_contact_id', 'Activity', 'Array'))
        ->setTitle(ts('Activity Contacts'))
        ->setLabel(ts('All Activity Contacts'))
        ->setColumnName('id')
        ->setDescription(ts('All contacts involved in the activity (added by, with, or assigned to).'))
        ->setType('Extra')
        ->setFkEntity('Contact')
        ->setOperators(['CONTAINS', 'NOT CONTAINS', 'CONTAINS ONE OF', 'NOT CONTAINS ONE OF', 'IS NULL', 'IS NOT NULL'])
        ->setInputType('EntityRef')
        ->setInputAttrs(['multiple' => TRUE])
        ->setSerialize(\CRM_Core_DAO::SERIALIZE_COMMA)
        ->setDataType('Integer')
        ->addSqlFilter([__CLASS__, 'getActivityContactFilterSql'])
        ->setSqlRenderer([__CLASS__, 'renderSqlForActivityContactIds']);
      $spec->addFieldSpec($field);

      $field = (new FieldSpec('target_contact_count', 'Activity', 'Integer'))
        ->setTitle(ts('Target Contact Count'))
        ->setColumnName('id')
        ->setDescription(ts('Number of target contacts involved in this activity.'))
        ->setType('Extra')
        ->setSqlRenderer([__CLASS__, 'getActivityContactCountSql']);
      $spec->addFieldSpec($field);

      $field = (new FieldSpec('assignee_contact_count', 'Activity', 'Integer'))
        ->setTitle(ts('Assignee Contact Count'))
        ->setColumnName('id')
        ->setDescription(ts('Number of contacts assigned to this activity.'))
        ->setType('Extra')
        ->setSqlRenderer([__CLASS__, 'getActivityContactCountSql']);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * @inheritDoc
   */
  public function applies($entity, $action) {
    return $entity === 'Activity';
  }

  public static function renderSqlForActivityContactIds(array $field, Api4SelectQuery $query): string {
    $recordTypeClause = self::getRecordTypeClause($field['name']);

    // This will be used in a SELECT clause, but in WHERE clauses it's overridden by self::getActivityContactFilterSql.
    return "(SELECT GROUP_CONCAT(`civicrm_activity_contact`.`contact_id`)
              FROM `civicrm_activity_contact`
              WHERE `civicrm_activity_contact`.`activity_id` = {$field['sql_name']}
              $recordTypeClause)";
  }

  public static function getActivityContactFilterSql(array $field, string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): string {
    // $fieldAlias contains the rendered subquery from self::renderSqlForActivityContactIds.
    // We'll replace that with a more efficient subquery for a WHERE clause.
    $fieldAlias = $field['sql_name'];

    $recordTypeClause = self::getRecordTypeClause($field['name']);
    $contactIdClause = '1';

    if (!in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
      // `user_contact_id`, etc has already been converted to an id by `FormattingUtil::formatInputValue`
      $cids = implode(',', (array) $value);
      \CRM_Utils_Type::validate($cids, 'CommaSeparatedIntegers', TRUE);
      $contactIdClause = "`civicrm_activity_contact`.`contact_id` IN ($cids)";
    }

    // CONTAINS ONE OF & NOT CONTAINS ONE OF have already been decomposed by `Api4Query::treeWalkClauses`
    $op = in_array($operator, ['CONTAINS', 'IN', '=', 'IS NOT NULL']) ? 'IN' : 'NOT IN';
    return "$fieldAlias $op (SELECT activity_id FROM `civicrm_activity_contact` WHERE $contactIdClause $recordTypeClause)";
  }

  public static function getActivityContactCountSql(array $field, Api4SelectQuery $query): string {
    $recordTypeClause = self::getRecordTypeClause($field['name']);

    return "(SELECT COUNT(*)
     FROM `civicrm_activity_contact`
     WHERE `civicrm_activity_contact`.`activity_id` = {$field['sql_name']}
     $recordTypeClause)";
  }

  private static function getRecordTypeClause(string $fieldName): string {
    if ($fieldName === 'all_contact_id') {
      return '';
    }
    $recordTypeId = self::getRecordTypeId($fieldName);
    return "AND `civicrm_activity_contact`.`record_type_id` = $recordTypeId";
  }

  private static function getRecordTypeId(string $fieldName): int {
    $recordTypes = [
      'source' => 'Activity Source',
      'target' => 'Activity Targets',
      'assignee' => 'Activity Assignees',
    ];
    $key = explode('_', $fieldName)[0];
    return \CRM_Core_PseudoConstant::getKey(
      'CRM_Activity_BAO_ActivityContact',
      'record_type_id',
      $recordTypes[$key]);
  }

}
