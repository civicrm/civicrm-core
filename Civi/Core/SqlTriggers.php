<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

namespace Civi\Core;

/**
 * Class SqlTriggers
 * @package Civi\Core
 *
 * This class manages creation and destruction of SQL triggers.
 */
class SqlTriggers {

  /**
   * The name of the output file.
   *
   * @var string|NULL
   */
  private $file = NULL;

  /**
   * Build a list of triggers via hook and add them to (err, reconcile them
   * with) the database.
   *
   * @param string $tableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   * @param bool $force
   *
   * @see CRM-9716
   */
  public function rebuild($tableName = NULL, $force = FALSE) {
    $info = array();

    $logging = new \CRM_Logging_Schema();
    $logging->triggerInfo($info, $tableName, $force);

    \CRM_Core_I18n_Schema::triggerInfo($info, $tableName);
    \CRM_Contact_BAO_Contact::triggerInfo($info, $tableName);

    \CRM_Utils_Hook::triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);

    // now create the set of new triggers
    $this->createTriggers($info, $tableName);
  }

  /**
   * @param array $info
   *   per hook_civicrm_triggerInfo.
   * @param string $onlyTableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   */
  public function createTriggers(&$info, $onlyTableName = NULL) {
    // Validate info array, should probably raise errors?
    if (is_array($info) == FALSE) {
      return;
    }

    $triggers = array();

    // now enumerate the tables and the events and collect the same set in a different format
    foreach ($info as $value) {

      // clean the incoming data, skip malformed entries
      // TODO: malformed entries should raise errors or get logged.
      if (isset($value['table']) == FALSE ||
        isset($value['event']) == FALSE ||
        isset($value['when']) == FALSE ||
        isset($value['sql']) == FALSE
      ) {
        continue;
      }

      if (is_string($value['table']) == TRUE) {
        $tables = array($value['table']);
      }
      else {
        $tables = $value['table'];
      }

      if (is_string($value['event']) == TRUE) {
        $events = array(strtolower($value['event']));
      }
      else {
        $events = array_map('strtolower', $value['event']);
      }

      $whenName = strtolower($value['when']);

      foreach ($tables as $tableName) {
        if (!isset($triggers[$tableName])) {
          $triggers[$tableName] = array();
        }

        foreach ($events as $eventName) {
          $template_params = array('{tableName}', '{eventName}');
          $template_values = array($tableName, $eventName);

          $sql = str_replace($template_params,
            $template_values,
            $value['sql']
          );
          $variables = str_replace($template_params,
            $template_values,
            \CRM_Utils_Array::value('variables', $value)
          );

          if (!isset($triggers[$tableName][$eventName])) {
            $triggers[$tableName][$eventName] = array();
          }

          if (!isset($triggers[$tableName][$eventName][$whenName])) {
            // We're leaving out cursors, conditions, and handlers for now
            // they are kind of dangerous in this context anyway
            // better off putting them in stored procedures
            $triggers[$tableName][$eventName][$whenName] = array(
              'variables' => array(),
              'sql' => array(),
            );
          }

          if ($variables) {
            $triggers[$tableName][$eventName][$whenName]['variables'][] = $variables;
          }

          $triggers[$tableName][$eventName][$whenName]['sql'][] = $sql;
        }
      }
    }

    // now spit out the sql
    foreach ($triggers as $tableName => $tables) {
      if ($onlyTableName != NULL && $onlyTableName != $tableName) {
        continue;
      }
      foreach ($tables as $eventName => $events) {
        foreach ($events as $whenName => $parts) {
          $varString = implode("\n", $parts['variables']);
          $sqlString = implode("\n", $parts['sql']);
          $validName = \CRM_Core_DAO::shortenSQLName($tableName, 48, TRUE);
          $triggerName = "{$validName}_{$whenName}_{$eventName}";
          $triggerSQL = "CREATE TRIGGER $triggerName $whenName $eventName ON $tableName FOR EACH ROW BEGIN $varString $sqlString END";

          $this->enqueueQuery("DROP TRIGGER IF EXISTS $triggerName");
          $this->enqueueQuery($triggerSQL);
        }
      }
    }
  }

  /**
   * Wrapper function to drop triggers.
   *
   * @param string $tableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   */
  public function dropTriggers($tableName = NULL) {
    $info = array();

    $logging = new \CRM_Logging_Schema();
    $logging->triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);
  }

  /**
   * Enqueue a query which alters triggers.
   *
   * As this requires a high permission level we funnel the queries through here to
   * facilitate them being taken 'offline'.
   *
   * @param string $triggerSQL
   *   The sql to run to create or drop the triggers.
   * @param array $params
   *   Optional parameters to interpolate into the string.
   */
  public function enqueueQuery($triggerSQL, $params = array()) {
    if (\Civi::settings()->get('logging_no_trigger_permission')) {

      if (!file_exists($this->getFile())) {
        // Ugh. Need to let user know somehow. This is the first change.
        \CRM_Core_Session::setStatus(ts('The mysql commands you need to run are stored in %1', array(
            1 => $this->getFile(),
          )),
          '',
          'alert',
          array('expires' => 0)
        );
      }

      $buf = "\n";
      $buf .= "DELIMITER //\n";
      $buf .= \CRM_Core_DAO::composeQuery($triggerSQL, $params) . " //\n";
      $buf .= "DELIMITER ;\n";
      file_put_contents($this->getFile(), $buf, FILE_APPEND);
    }
    else {
      \CRM_Core_DAO::executeQuery($triggerSQL, $params, TRUE, NULL, FALSE, FALSE);
    }
  }

  /**
   * @return NULL|string
   */
  public function getFile() {
    if ($this->file === NULL) {
      $prefix = 'trigger' . \CRM_Utils_Request::id();
      $config = \CRM_Core_Config::singleton();
      $this->file = "{$config->configAndLogDir}CiviCRM." . $prefix . md5($config->dsn) . '.sql';
    }
    return $this->file;
  }

}
