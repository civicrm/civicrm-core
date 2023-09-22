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

namespace Civi\Schema\Traits;

/**
 * If a field is specifically stored in the database, then use SqlSpecTrait
 * to describe how to read/write the field.
 *
 * @package Civi\Schema\Traits
 */
trait SqlSpecTrait {

  /**
   * SQL table which stores this field.
   *
   * @var string
   */
  public $tableName;

  /**
   * SQL column which stores this field.
   *
   * @var string
   */
  public $columnName;

  /**
   * If set, limits the operators that can be used on this field for "get"
   * actions.
   *
   * @var string[]
   */
  public $operators;

  /**
   * @var callable
   */
  public $sqlRenderer;

  /**
   * Some fields use a callback to generate their SQL (for reading/searching).
   *
   * @var callable[]
   */
  public $sqlFilters;

  /**
   * @param string $tableName
   *
   * @return $this
   */
  public function setTableName($tableName) {
    $this->tableName = $tableName;
    return $this;
  }

  /**
   * @return string
   */
  public function getTableName() {
    return $this->tableName;
  }

  /**
   * @return string|NULL
   */
  public function getColumnName(): ?string {
    return $this->columnName;
  }

  /**
   * @param string|null $columnName
   *
   * @return $this
   */
  public function setColumnName(?string $columnName) {
    $this->columnName = $columnName;
    return $this;
  }

  /**
   * @param string[] $operators
   *
   * @return $this
   */
  public function setOperators($operators) {
    $this->operators = $operators;
    return $this;
  }

  /**
   * @param callable $sqlRenderer
   * @return $this
   */
  public function setSqlRenderer($sqlRenderer) {
    $this->sqlRenderer = $sqlRenderer;
    return $this;
  }

  /**
   * @param callable[] $sqlFilters
   *
   * @return $this
   */
  public function setSqlFilters($sqlFilters) {
    $this->sqlFilters = $sqlFilters;
    return $this;
  }

  /**
   * @param callable $sqlFilter
   *
   * @return $this
   */
  public function addSqlFilter($sqlFilter) {
    if (!$this->sqlFilters) {
      $this->sqlFilters = [];
    }
    $this->sqlFilters[] = $sqlFilter;

    return $this;
  }

}
