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

use Civi\Api4\Service\Schema\Joinable\Joinable;

class Table {

  /**
   * @var string
   */
  protected $name;

  /**
   * @var \Civi\Api4\Service\Schema\Joinable\Joinable[]
   *   Array of links to other tables
   */
  protected $tableLinks = [];

  /**
   * @param $name
   */
  public function __construct($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function setName($name) {
    $this->name = $name;

    return $this;
  }

  /**
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   */
  public function getTableLinks() {
    return $this->tableLinks;
  }

  /**
   * @return \Civi\Api4\Service\Schema\Joinable\Joinable[]
   *   Only those links that are not joining the table to itself
   */
  public function getExternalLinks() {
    return array_filter($this->tableLinks, function (Joinable $joinable) {
      return $joinable->getTargetTable() !== $this->getName();
    });
  }

  /**
   * @param \Civi\Api4\Service\Schema\Joinable\Joinable $linkToRemove
   */
  public function removeLink(Joinable $linkToRemove) {
    foreach ($this->tableLinks as $index => $link) {
      if ($link === $linkToRemove) {
        unset($this->tableLinks[$index]);
      }
    }
  }

  /**
   * @param string $baseColumn
   * @param \Civi\Api4\Service\Schema\Joinable\Joinable $joinable
   *
   * @return $this
   */
  public function addTableLink($baseColumn, Joinable $joinable) {
    $target = $joinable->getTargetTable();
    $targetCol = $joinable->getTargetColumn();
    $alias = $joinable->getAlias();

    if (!$this->hasLink($target, $targetCol, $alias)) {
      if (!$joinable->getBaseTable()) {
        $joinable->setBaseTable($this->getName());
      }
      if (!$joinable->getBaseColumn()) {
        $joinable->setBaseColumn($baseColumn);
      }
      $this->tableLinks[] = $joinable;
    }

    return $this;
  }

  /**
   * @param mixed $tableLinks
   *
   * @return $this
   */
  public function setTableLinks($tableLinks) {
    $this->tableLinks = $tableLinks;

    return $this;
  }

  /**
   * @param $target
   * @param $targetCol
   * @param $alias
   *
   * @return bool
   */
  private function hasLink($target, $targetCol, $alias) {
    foreach ($this->tableLinks as $link) {
      if ($link->getTargetTable() === $target
        && $link->getTargetColumn() === $targetCol
        && $link->getAlias() === $alias
      ) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
