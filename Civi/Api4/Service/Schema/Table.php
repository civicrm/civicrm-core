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
