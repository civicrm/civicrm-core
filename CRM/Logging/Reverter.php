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
 */
class CRM_Logging_Reverter {
  private $db;
  private $log_conn_id;
  private $log_date;

  /**
   * The diffs to be reverted.
   *
   * @var array
   */
  private $diffs = [];

  /**
   * Class constructor.
   *
   * @param string $log_conn_id
   * @param $log_date
   */
  public function __construct($log_conn_id, $log_date) {
    $dsn = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->db = $dsn['database'];
    $this->log_conn_id = $log_conn_id;
    $this->log_date = $log_date;
  }

  /**
   *
   * Calculate a set of diffs based on the connection_id and changes at a close time.
   *
   * @param array $tables
   */
  public function calculateDiffsFromLogConnAndDate($tables) {
    $differ = new CRM_Logging_Differ($this->log_conn_id, $this->log_date);
    $this->diffs = $differ->diffsInTables($tables);
  }

  /**
   * Setter for diffs.
   *
   * @param array $diffs
   */
  public function setDiffs($diffs) {
    $this->diffs = $diffs;
  }

  /**
   * Revert changes in the array of diffs in $this->diffs.
   */
  public function revert() {

    // get custom data tables, columns and types
    $ctypes = [];
    $dao = CRM_Core_DAO::executeQuery('SELECT table_name, column_name, data_type FROM civicrm_custom_group cg JOIN civicrm_custom_field cf ON (cf.custom_group_id = cg.id)');
    while ($dao->fetch()) {
      if (!isset($ctypes[$dao->table_name])) {
        $ctypes[$dao->table_name] = ['entity_id' => 'Integer'];
      }
      $ctypes[$dao->table_name][$dao->column_name] = $dao->data_type;
    }

    $diffs = $this->diffs;
    $deletes = [];
    $reverts = [];
    foreach ($diffs as $table => $changes) {
      foreach ($changes as $change) {
        switch ($change['action']) {
          case 'Insert':
            if (!isset($deletes[$table])) {
              $deletes[$table] = [];
            }
            $deletes[$table][] = $change['id'];
            break;

          case 'Delete':
          case 'Update':
            if (!isset($reverts[$table])) {
              $reverts[$table] = [];
            }
            if (!isset($reverts[$table][$change['id']])) {
              $reverts[$table][$change['id']] = ['log_action' => $change['action']];
            }
            $reverts[$table][$change['id']][$change['field']] = $change['from'];
            break;
        }
      }
    }

    // revert inserts by deleting
    foreach ($deletes as $table => $ids) {
      CRM_Core_DAO::executeQuery("DELETE FROM `$table` WHERE id IN (" . implode(', ', array_unique($ids)) . ')');
    }

    // revert updates by updating to previous values
    foreach ($reverts as $table => $row) {
      switch (TRUE) {
        // DAO-based tables

        case (($tableDAO = CRM_Core_DAO_AllCoreTables::getClassForTable($table)) != FALSE):
          $dao = new $tableDAO();
          foreach ($row as $id => $changes) {
            $dao->id = $id;
            foreach ($changes as $field => $value) {
              if ($field == 'log_action') {
                continue;
              }
              if (empty($value) and $value !== 0 and $value !== '0') {
                $value = 'null';
              }
              // Date reaches this point in ISO format (possibly) so strip out stuff
              // if it does have hyphens of colons demarking the date & it regexes as being a date
              // or datetime format.
              if (preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $value)) {
                $value = str_replace('-', '', $value);
                $value = str_replace(':', '', $value);
              }
              $dao->$field = $value;
            }
            $changes['log_action'] == 'Delete' ? $dao->insert() : $dao->update();

            $dao->reset();
          }
          break;

        // custom data tables

        case in_array($table, array_keys($ctypes)):
          foreach ($row as $id => $changes) {
            $inserts = ['id' => '%1'];
            $updates = [];
            $params = [1 => [$id, 'Integer']];
            $counter = 2;
            foreach ($changes as $field => $value) {
              // don’t try reverting a field that’s no longer there
              if (!isset($ctypes[$table][$field])) {
                continue;
              }
              $fldVal = "%{$counter}";
              switch ($ctypes[$table][$field]) {
                case 'Date':
                  $value = substr(CRM_Utils_Date::isoToMysql($value), 0, 8);
                  break;

                case 'Timestamp':
                  $value = CRM_Utils_Date::isoToMysql($value);
                  break;

                case 'Boolean':
                  if ($value === '') {
                    $fldVal = 'DEFAULT';
                  }
              }
              $inserts[$field] = "%$counter";
              $updates[] = "{$field} = {$fldVal}";
              if ($fldVal != 'DEFAULT') {
                $params[$counter] = [$value, $ctypes[$table][$field]];
              }
              $counter++;
            }
            if ($changes['log_action'] == 'Delete') {
              $sql = "INSERT INTO `$table` (" . implode(', ', array_keys($inserts)) . ') VALUES (' . implode(', ', $inserts) . ')';
            }
            else {
              $sql = "UPDATE `$table` SET " . implode(', ', $updates) . ' WHERE id = %1';
            }
            CRM_Core_DAO::executeQuery($sql, $params);
          }
          break;
      }
    }

  }

}
