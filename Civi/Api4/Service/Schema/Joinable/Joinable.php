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

namespace Civi\Api4\Service\Schema\Joinable;

use Civi\Api4\Utils\CoreUtil;

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
   * @var int
   */
  protected $serialize;

  /**
   * @var bool
   */
  protected $deprecated = FALSE;

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
   * @param string $baseTableAlias
   * @param string $tableAlias
   *
   * @return array
   */
  public function getConditionsForJoin(string $baseTableAlias, string $tableAlias) {
    $baseCondition = sprintf(
      '`%s`.`%s` =  `%s`.`%s`',
      $baseTableAlias,
      $this->baseColumn,
      $tableAlias,
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
   * @return int|NULL
   */
  public function getSerialize():? int {
    return $this->serialize;
  }

  /**
   * @param int|null $serialize
   *
   * @return $this
   */
  public function setSerialize(?int $serialize) {
    $this->serialize = $serialize;

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
   * @return bool
   */
  public function isDeprecated() {
    return $this->deprecated;
  }

  /**
   * @param bool $deprecated
   *
   * @return $this
   */
  public function setDeprecated(bool $deprecated = TRUE) {
    $this->deprecated = $deprecated;
    return $this;
  }

  /**
   * @return array
   */
  public function toArray() {
    return get_object_vars($this);
  }

  /**
   * @return \Civi\Api4\Service\Spec\RequestSpec
   */
  public function getEntityFields() {
    /** @var \Civi\Api4\Service\Spec\SpecGatherer $gatherer */
    $gatherer = \Civi::container()->get('spec_gatherer');
    $spec = $gatherer->getSpec($this->entity, 'get', FALSE);
    // Serialized fields require a specialized join
    if ($this->serialize) {
      foreach ($spec as $field) {
        // The callback function expects separated values as output
        $field->setSerialize(\CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED);
        $field->setSqlRenderer(['Civi\Api4\Query\Api4SelectQuery', 'renderSerializedJoin']);
      }
    }
    return $spec;
  }

  /**
   * @return array
   */
  public function getEntityFieldNames() {
    $fieldNames = [];
    foreach ($this->getEntityFields() as $fieldSpec) {
      $fieldNames[] = $fieldSpec->getName();
    }
    return $fieldNames;
  }

  /**
   * @return \Civi\Api4\Service\Spec\FieldSpec|NULL
   */
  public function getField($fieldName) {
    return $this->getEntityFields()->getFieldByName($fieldName);
  }

}
