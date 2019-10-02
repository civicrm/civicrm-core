<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */


namespace Civi\Api4\Service\Schema\Joinable;

use Civi\Api4\Utils\CoreUtil;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class Joinable {

  const JOIN_SIDE_LEFT = 'LEFT';
  const JOIN_SIDE_INNER = 'INNER';

  const JOIN_TYPE_ONE_TO_ONE = '1_to_1';
  const JOIN_TYPE_MANY_TO_ONE = 'n_to_1';
  const JOIN_TYPE_ONE_TO_MANY = '1_to_n';

  /**
   * @var string
   */
  protected $baseTable;

  /**
   * @var string
   */
  protected $baseColumn;

  /**
   * @var string
   */
  protected $targetTable;

  /**
   * @var string
   *
   * Name (or alias) of the target column)
   */
  protected $targetColumn;

  /**
   * @var string
   */
  protected $alias;

  /**
   * @var array
   */
  protected $conditions = [];

  /**
   * @var string
   */
  protected $joinSide = self::JOIN_SIDE_LEFT;

  /**
   * @var int
   */
  protected $joinType = self::JOIN_TYPE_ONE_TO_ONE;

  /**
   * @var string
   */
  protected $entity;

  /**
   * @var array
   */
  protected $entityFields;

  /**
   * @param $targetTable
   * @param $targetColumn
   * @param string|null $alias
   */
  public function __construct($targetTable, $targetColumn, $alias = NULL) {
    $this->targetTable = $targetTable;
    $this->targetColumn = $targetColumn;
    if (!$this->entity) {
      $this->entity = CoreUtil::getApiNameFromTableName($targetTable);
    }
    $this->alias = $alias ?: str_replace('civicrm_', '', $targetTable);
  }

  /**
   * Gets conditions required when joining to a base table
   *
   * @param string|null $baseTableAlias
   *   Name of the base table, if aliased.
   *
   * @return array
   */
  public function getConditionsForJoin($baseTableAlias = NULL) {
    $baseCondition = sprintf(
      '%s.%s =  %s.%s',
      $baseTableAlias ?: $this->baseTable,
      $this->baseColumn,
      $this->getAlias(),
      $this->targetColumn
    );

    return array_merge([$baseCondition], $this->conditions);
  }

  /**
   * @return string
   */
  public function getBaseTable() {
    return $this->baseTable;
  }

  /**
   * @param string $baseTable
   *
   * @return $this
   */
  public function setBaseTable($baseTable) {
    $this->baseTable = $baseTable;

    return $this;
  }

  /**
   * @return string
   */
  public function getBaseColumn() {
    return $this->baseColumn;
  }

  /**
   * @param string $baseColumn
   *
   * @return $this
   */
  public function setBaseColumn($baseColumn) {
    $this->baseColumn = $baseColumn;

    return $this;
  }

  /**
   * @return string
   */
  public function getAlias() {
    return $this->alias;
  }

  /**
   * @param string $alias
   *
   * @return $this
   */
  public function setAlias($alias) {
    $this->alias = $alias;

    return $this;
  }

  /**
   * @return string
   */
  public function getTargetTable() {
    return $this->targetTable;
  }

  /**
   * @return string
   */
  public function getTargetColumn() {
    return $this->targetColumn;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * @param $condition
   *
   * @return $this
   */
  public function addCondition($condition) {
    $this->conditions[] = $condition;

    return $this;
  }

  /**
   * @return array
   */
  public function getExtraJoinConditions() {
    return $this->conditions;
  }

  /**
   * @param array $conditions
   *
   * @return $this
   */
  public function setConditions($conditions) {
    $this->conditions = $conditions;

    return $this;
  }

  /**
   * @return string
   */
  public function getJoinSide() {
    return $this->joinSide;
  }

  /**
   * @param string $joinSide
   *
   * @return $this
   */
  public function setJoinSide($joinSide) {
    $this->joinSide = $joinSide;

    return $this;
  }

  /**
   * @return int
   */
  public function getJoinType() {
    return $this->joinType;
  }

  /**
   * @param int $joinType
   *
   * @return $this
   */
  public function setJoinType($joinType) {
    $this->joinType = $joinType;

    return $this;
  }

  /**
   * @return array
   */
  public function toArray() {
    return get_object_vars($this);
  }

  /**
   * @return \Civi\Api4\Service\Spec\FieldSpec[]
   */
  public function getEntityFields() {
    if (!$this->entityFields) {
      $bao = AllCoreTables::getClassForTable($this->getTargetTable());
      if ($bao) {
        foreach ($bao::fields() as $field) {
          $this->entityFields[] = \Civi\Api4\Service\Spec\SpecFormatter::arrayToField($field, $this->getEntity());
        }
      }
    }
    return $this->entityFields;
  }

  /**
   * @return \Civi\Api4\Service\Spec\FieldSpec|NULL
   */
  public function getField($fieldName) {
    foreach ($this->getEntityFields() as $field) {
      if ($field->getName() === $fieldName) {
        return $field;
      }
    }
    return NULL;
  }

}
