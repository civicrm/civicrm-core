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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

class ContactGetSpecProvider implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    $field = new FieldSpec('groups', 'Contact', 'Array');
    $field->setLabel(ts('In Groups'))
      ->setTitle(ts('Groups'))
      ->setColumnName('id')
      ->setDescription(ts('Groups (or sub-groups of groups) to which this contact belongs'))
      ->setType('Filter')
      ->setOperators(['IN', 'NOT IN'])
      ->addSqlFilter([__CLASS__, 'getContactGroupSql'])
      ->setOptionsCallback([__CLASS__, 'getGroupList']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'Contact' && $action === 'get';
  }

  /**
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param int $depth
   * return string
   */
  public static function getContactGroupSql(string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): string {
    $tempTable = \CRM_Utils_SQL_TempTable::build();
    $tempTable->createWithColumns('contact_id INT');
    $tableName = $tempTable->getName();
    \CRM_Contact_BAO_GroupContactCache::populateTemporaryTableWithContactsInGroups($value, $tableName);
    // SQL optimization - use INNER JOIN if the base table is Contact & this clause is not nested
    if ($fieldAlias === '`a`.`id`' && $operator === "IN" && !$depth) {
      $query->getQuery()->join($tableName, "INNER JOIN `$tableName` ON $fieldAlias = `$tableName`.contact_id");
      return '1';
    }
    // Else use IN or NOT IN (this filter only supports those 2 operators)
    else {
      return "$fieldAlias $operator (SELECT contact_id FROM `$tableName`)";
    }
  }

  /**
   * Callback function to build option lists groups pseudo-field.
   *
   * @param \Civi\Api4\Service\Spec\FieldSpec $spec
   * @param array $values
   * @param bool|array $returnFormat
   * @param bool $checkPermissions
   * @return array
   */
  public static function getGroupList($spec, $values, $returnFormat, $checkPermissions) {
    $groups = $checkPermissions ? \CRM_Core_PseudoConstant::group() : \CRM_Core_PseudoConstant::allGroup(NULL, FALSE);
    $options = \CRM_Utils_Array::makeNonAssociative($groups, 'id', 'label');
    if ($options && is_array($returnFormat) && in_array('name', $returnFormat)) {
      $groupIndex = array_flip(array_keys($groups));
      $dao = \CRM_Core_DAO::executeQuery('SELECT id, name FROM civicrm_group WHERE id IN (%1)', [
        1 => [implode(',', array_keys($groups)), 'CommaSeparatedIntegers'],
      ]);
      while ($dao->fetch()) {
        $options[$groupIndex[$dao->id]]['name'] = $dao->name;
      }
    }
    return $options;
  }

}
