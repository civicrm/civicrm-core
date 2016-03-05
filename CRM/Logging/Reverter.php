<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Logging_Reverter {
  private $db;
  private $log_conn_id;
  private $log_date;

  /**
   * @param int $log_conn_id
   * @param $log_date
   */
  public function __construct($log_conn_id, $log_date) {
    $dsn = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->db = $dsn['database'];
    $this->log_conn_id = $log_conn_id;
    $this->log_date = $log_date;
  }

  /**
   * @param $tables
   */
  public function revert($tables) {
    // FIXME: split off the table → DAO mapping to a GenCode-generated class
    $daos = array(
      'civicrm_address' => 'CRM_Core_DAO_Address',
      'civicrm_contact' => 'CRM_Contact_DAO_Contact',
      'civicrm_email' => 'CRM_Core_DAO_Email',
      'civicrm_im' => 'CRM_Core_DAO_IM',
      'civicrm_openid' => 'CRM_Core_DAO_OpenID',
      'civicrm_phone' => 'CRM_Core_DAO_Phone',
      'civicrm_website' => 'CRM_Core_DAO_Website',
      'civicrm_contribution' => 'CRM_Contribute_DAO_Contribution',
      'civicrm_note' => 'CRM_Core_DAO_Note',
      'civicrm_relationship' => 'CRM_Contact_DAO_Relationship',
    );

    // get custom data tables, columns and types
    $ctypes = array();
    $dao = CRM_Core_DAO::executeQuery('SELECT table_name, column_name, data_type FROM civicrm_custom_group cg JOIN civicrm_custom_field cf ON (cf.custom_group_id = cg.id)');
    while ($dao->fetch()) {
      if (!isset($ctypes[$dao->table_name])) {
        $ctypes[$dao->table_name] = array('entity_id' => 'Integer');
      }
      $ctypes[$dao->table_name][$dao->column_name] = $dao->data_type;
    }

    $differ = new CRM_Logging_Differ($this->log_conn_id, $this->log_date);
    $diffs = $differ->diffsInTables($tables);

    $deletes = array();
    $reverts = array();
    foreach ($diffs as $table => $changes) {
      foreach ($changes as $change) {
        switch ($change['action']) {
          case 'Insert':
            if (!isset($deletes[$table])) {
              $deletes[$table] = array();
            }
            $deletes[$table][] = $change['id'];
            break;

          case 'Delete':
          case 'Update':
            if (!isset($reverts[$table])) {
              $reverts[$table] = array();
            }
            if (!isset($reverts[$table][$change['id']])) {
              $reverts[$table][$change['id']] = array('log_action' => $change['action']);
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

        case in_array($table, array_keys($daos)):
          $dao = new $daos[$table]();
          foreach ($row as $id => $changes) {
            $dao->id = $id;
            foreach ($changes as $field => $value) {
              if ($field == 'log_action') {
                continue;
              }
              if (empty($value) and $value !== 0 and $value !== '0') {
                $value = 'null';
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
            $inserts = array('id' => '%1');
            $updates = array();
            $params = array(1 => array($id, 'Integer'));
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
                $params[$counter] = array($value, $ctypes[$table][$field]);
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

    // CRM-7353: if nothing altered civicrm_contact, touch it; this will
    // make sure there’s an entry in log_civicrm_contact for this revert
    if (empty($diffs['civicrm_contact'])) {
      $query = "
                SELECT id FROM `{$this->db}`.log_civicrm_contact
                WHERE log_conn_id = %1 AND log_date BETWEEN DATE_SUB(%2, INTERVAL 10 SECOND) AND DATE_ADD(%2, INTERVAL 10 SECOND)
                ORDER BY log_date DESC LIMIT 1
            ";
      $params = array(
        1 => array($this->log_conn_id, 'Integer'),
        2 => array($this->log_date, 'String'),
      );
      $cid = CRM_Core_DAO::singleValueQuery($query, $params);
      if (!$cid) {
        return;
      }

      $dao = new CRM_Contact_DAO_Contact();
      $dao->id = $cid;
      if ($dao->find(TRUE)) {
        // CRM-8102: MySQL can’t parse its own dates
        $dao->birth_date = CRM_Utils_Date::isoToMysql($dao->birth_date);
        $dao->deceased_date = CRM_Utils_Date::isoToMysql($dao->deceased_date);
        $dao->save();
      }
    }
  }

}
