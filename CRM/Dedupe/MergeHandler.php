<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2020                                |
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
 * @copyright CiviCRM LLC (c) 2004-2020
 *
 * This class exists primarily for the purposes of supporting code clean up in the Merger class.
 *
 * It is expected to be fast-moving and calling it outside the refactoring work is not advised.
 */
class CRM_Dedupe_MergeHandler {

  /**
   * ID of contact to be kept.
   *
   * @var int
   */
  protected $toKeepID;

  /**
   * ID of contact to be merged and deleted.
   *
   * @var int
   */
  protected $toRemoveID;

  /**
   * @return mixed
   */
  public function getToKeepID() {
    return $this->toKeepID;
  }

  /**
   * @param mixed $toKeepID
   */
  public function setToKeepID($toKeepID) {
    $this->toKeepID = $toKeepID;
  }

  /**
   * @return mixed
   */
  public function getToRemoveID() {
    return $this->toRemoveID;
  }

  /**
   * @param mixed $toRemoveID
   */
  public function setToRemoveID($toRemoveID) {
    $this->toRemoveID = $toRemoveID;
  }

  /**
   * CRM_Dedupe_MergeHandler constructor.
   *
   * @param int $toKeepID
   *   ID of contact to be kept.
   * @param int $toRemoveID
   *   ID of contact to be removed.
   */
  public function __construct(int $toKeepID, int $toRemoveID) {
    $this->setToKeepID($toKeepID);
    $this->setToRemoveID($toRemoveID);
  }

  /**
   * Get an array of tables that relate to the contact entity and will need consideration in a merge.
   *
   * The list of potential tables is filtered by tables which have data for the relevant contacts.
   */
  public function getTablesRelatedToTheMergePair() {
    $relTables = CRM_Dedupe_Merger::relTables();
    $activeRelTables = CRM_Dedupe_Merger::getActiveRelTables($this->toRemoveID);
    $activeMainRelTables = CRM_Dedupe_Merger::getActiveRelTables($this->toKeepID);
    foreach ($relTables as $name => $null) {
      if (!in_array($name, $activeRelTables, TRUE) &&
        !(($name === 'rel_table_users') && in_array($name, $activeMainRelTables, TRUE))
      ) {
        unset($relTables[$name]);
      }
    }
    return $relTables;
  }

}
