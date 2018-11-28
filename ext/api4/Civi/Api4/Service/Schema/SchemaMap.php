<?php

namespace Civi\Api4\Service\Schema;

use Civi\Api4\Service\Schema\Joinable\BridgeJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;

class SchemaMap {

  const MAX_JOIN_DEPTH = 3;

  /**
   * @var Table[]
   */
  protected $tables = [];

  /**
   * @param $baseTableName
   * @param $targetTableAlias
   *
   * @return Joinable[]
   *   Array of links to the target table, empty if no path found
   */
  public function getPath($baseTableName, $targetTableAlias) {
    $table = $this->getTableByName($baseTableName);
    $path = [];

    if (!$table) {
      return $path;
    }

    $this->findPaths($table, $targetTableAlias, 1, $path);

    foreach ($path as $index => $pathLink) {
      if ($pathLink instanceof BridgeJoinable) {
        $start = array_slice($path, 0, $index);
        $middle = [$pathLink->getMiddleLink()];
        $end = array_slice($path, $index, count($path) - $index);
        $path = array_merge($start, $middle, $end);
      }
    }

    return $path;
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

  /**
   * Recursive function to traverse the schema looking for a path
   *
   * @param Table $table
   *   The current table to base fromm
   * @param string $target
   *   The target joinable table alias
   * @param int $depth
   *   The current level of recursion which reflects the number of joins needed
   * @param Joinable[] $path
   *   (By-reference) The possible paths to the target table
   * @param Joinable[] $currentPath
   *   For internal use only to track the path to reach the target table
   */
  private function findPaths(Table $table, $target, $depth, &$path, $currentPath = []
  ) {
    static $visited = [];

    // reset if new call
    if ($depth === 1) {
      $visited = [];
    }

    $canBeShorter = empty($path) || count($currentPath) + 1 < count($path);
    $tooFar = $depth > self::MAX_JOIN_DEPTH;
    $beenHere = in_array($table->getName(), $visited);

    if ($tooFar || $beenHere || !$canBeShorter) {
      return;
    }

    // prevent circular reference
    $visited[] = $table->getName();

    foreach ($table->getExternalLinks() as $link) {
      if ($link->getAlias() === $target) {
        $path = array_merge($currentPath, [$link]);
      }
      else {
        $linkTable = $this->getTableByName($link->getTargetTable());
        if ($linkTable) {
          $nextStep = array_merge($currentPath, [$link]);
          $this->findPaths($linkTable, $target, $depth + 1, $path, $nextStep);
        }
      }
    }
  }

}
