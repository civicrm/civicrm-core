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

namespace Civi\Api4\Service\Schema;

class SchemaMap {

  /**
   * @var Table[]
   */
  protected $tables = [];

  /**
   * @param $baseTableName
   * @param $targetTableAlias
   *
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable|NULL
   *   Link to the target table
   * @throws \CRM_Core_Exception
   */
  public function getLink($baseTableName, $targetTableAlias): ?Joinable\Joinable {
    $table = $this->getTableByName($baseTableName);

    if (!$table) {
      throw new \CRM_Core_Exception("Table $baseTableName not found");
    }

    foreach ($table->getTableLinks() as $link) {
      if ($link->getAlias() === $targetTableAlias) {
        return $link;
      }
    }
    return NULL;
  }

  /**
   * @return Table[]
   */
  public function getTables() {
    return $this->tables;
  }

  /**
   * @param $name
   *
   * @return Table|null
   */
  public function getTableByName($name) {
    foreach ($this->tables as $table) {
      if ($table->getName() === $name) {
        return $table;
      }
    }

    return NULL;
  }

  /**
   * Adds a table to the schema map if it has not already been added
   *
   * @param Table $table
   *
   * @return $this
   */
  public function addTable(Table $table) {
    if (!$this->getTableByName($table->getName())) {
      $this->tables[] = $table;
    }

    return $this;
  }

  /**
   * @param array $tables
   */
  public function addTables(array $tables) {
    foreach ($tables as $table) {
      $this->addTable($table);
    }
  }

}
