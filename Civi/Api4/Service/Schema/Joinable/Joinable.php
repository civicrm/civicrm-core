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
   * @var string[]
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
  protected $deprecatedBy = FALSE;

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
   * @param string $targetTableAlias
   * @param array|null $openJoin
   *
   * @return array
   */
  public function getConditionsForJoin(string $baseTableAlias, string $targetTableAlias, ?array $openJoin) {
    $conditions = [];
    $baseColumn = $this->baseColumn;
    // Joining within a bridge, use the bridge table key instead,
    // because the custom fields are joined first and the base entity might not be added yet.
    if (!empty($openJoin['bridgeKey']) && $baseTableAlias === $openJoin['alias']) {
      $conditions = $openJoin['bridgeCondition'];
      $baseTableAlias = $openJoin['bridgeAlias'];
      $baseColumn = $openJoin['bridgeKey'];
    }
    // Custom field on bridge table itself; pass-through the $baseColumn as-is
    elseif (!empty($openJoin['bridgeKey']) && $baseTableAlias === $openJoin['bridgeAlias']) {
      $conditions = $openJoin['bridgeCondition'];
      $baseTableAlias = $openJoin['bridgeAlias'];
    }
    if ($this->baseColumn && $this->targetColumn) {
      $conditions[] = sprintf(
        '`%s`.`%s` =  `%s`.`%s`',
        $baseTableAlias,
        $baseColumn,
        $targetTableAlias,
        $this->targetColumn
      );
    }
    $this->addExtraJoinConditions($conditions, $baseTableAlias, $targetTableAlias);
    return $conditions;
  }

  /**
   * @param $conditions
   * @param string $baseTableAlias
   * @param string $targetTableAlias
   */
  protected function addExtraJoinConditions(&$conditions, string $baseTableAlias, string $targetTableAlias):void {
    foreach ($this->conditions as $condition) {
      $conditions[] = str_replace(['{base_table}', '{target_table}'], [$baseTableAlias, $targetTableAlias], $condition);
    }
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
   * @param string $condition
   *
   * @return $this
   */
  public function addCondition(string $condition) {
    $this->conditions[] = $condition;

    return $this;
  }

  /**
   * @param string[] $conditions
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
  public function isDeprecatedBy() {
    return $this->deprecatedBy;
  }

  /**
   * @param string|null $deprecatedBy
   * @return $this
   */
  public function setDeprecatedBy(?string $deprecatedBy = NULL) {
    $this->deprecatedBy = $deprecatedBy ?? $this->alias . '_id';
    return $this;
  }

  /**
   * @return array
   */
  public function toArray() {
    return get_object_vars($this);
  }

  public function getEntityFields(): array {
    $entityFields = [];
    if (!empty($this->entity)) {
      $gatherer = \Civi::container()->get('spec_gatherer');
      $allFields = $gatherer->getAllFields($this->entity, 'get');
      foreach ($allFields as $field) {
        if ($field['table_name'] === $this->targetTable) {
          // Serialized fields require a specialized join
          if ($this->serialize) {
            $field['serialize'] = \CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED;
            $field['sql_renderer'] = ['Civi\Api4\Query\Api4SelectQuery', 'renderSerializedJoin'];
          }
          $entityFields[] = $field;
        }
      }
    }
    return $entityFields;
  }

}
