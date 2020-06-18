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

  /**
   * Get an array of tables that have a dynamic reference to the contact table.
   *
   * This would be the case when the table uses entity_table + entity_id rather than an FK.
   *
   * There are a number of tables that 'could' but don't have contact related data so we
   * do a cached check for this, ensuring the query is only done once per batch run.
   *
   * @return array
   */
  public function getTablesDynamicallyRelatedToContactTable() {
    if (!isset(\Civi::$statics[__CLASS__]['dynamic'])) {
      \Civi::$statics[__CLASS__]['dynamic'] = [];
      foreach (CRM_Core_DAO::getDynamicReferencesToTable('civicrm_contact') as $tableName => $fields) {
        if ($tableName === 'civicrm_financial_item') {
          // It turns out that civicrm_financial_item does not have an index on entity_table (only as the latter
          // part of a entity_id/entity_table index which probably is not adding any value over & above entity_id
          // only. This means this is a slow query. The correct fix is probably to add a whitelist to
          // values for entity_table in the schema.
          continue;
        }
        foreach ($fields as $field) {
          $sql[] = "(SELECT '$tableName' as civicrm_table, '{$field[0]}' as field_name FROM $tableName WHERE {$field[1]} = 'civicrm_contact' LIMIT 1)";
        }
      }
      $sqlString = implode(' UNION ', $sql);
      if ($sqlString) {
        $result = CRM_Core_DAO::executeQuery($sqlString);
        while ($result->fetch()) {
          \Civi::$statics[__CLASS__]['dynamic'][$result->civicrm_table] = $result->field_name;
        }
      }
    }
    return \Civi::$statics[__CLASS__]['dynamic'];
  }

}
