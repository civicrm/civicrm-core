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

/**
 * This class exists primarily for the purposes of supporting code clean up in the Merger class.
 *
 * It is expected to be fast-moving and calling it outside the refactoring work is not advised.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * Migration info array.
   *
   * This is a nasty bunch of data used in mysterious ways. We want to work to make it more
   * sensible but for now we store it.
   *
   * @var array
   */
  protected $migrationInfo = [];

  /**
   * @return array
   */
  public function getMigrationInfo(): array {
    return $this->migrationInfo;
  }

  /**
   * @param array $migrationInfo
   */
  public function setMigrationInfo(array $migrationInfo) {
    $this->migrationInfo = $migrationInfo;
  }

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

  /**
   * Get the location blocks designated to be moved during the merge.
   *
   * Note this is a refactoring step and future refactors should develop a more coherent array
   *
   * @return array
   *   The format is ['address' => [0 => ['is_replace' => TRUE]], 'email' => [0...],[1....]
   *   where the entity is address, the internal indexing for the addresses on both contact is 1
   *   and the intent to replace the existing address is TRUE.
   */
  public function getLocationBlocksToMerge(): array {
    $locBlocks = [];
    foreach ($this->getMigrationInfo() as $key => $value) {
      $isLocationField = (substr($key, 0, 14) === 'move_location_' and $value != NULL);
      if (!$isLocationField) {
        continue;
      }
      $locField = explode('_', $key);
      $fieldName = $locField[2];
      $fieldCount = $locField[3];

      // Set up the operation type (add/overwrite)
      // Ignore operation for websites
      // @todo Tidy this up
      $operation = 0;
      if ($fieldName !== 'website') {
        $operation = $this->getMigrationInfo()['location_blocks'][$fieldName][$fieldCount]['operation'] ?? NULL;
      }
      // default operation is overwrite.
      if (!$operation) {
        $operation = 2;
      }
      $locBlocks[$fieldName][$fieldCount]['is_replace'] = $operation === 2;
    }
    return $locBlocks;
  }

}
