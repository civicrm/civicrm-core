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

namespace Civi\Core;

/**
 * Class SqlTriggers
 * @package Civi\Core
 * @service sql_triggers
 *
 * This class manages creation and destruction of SQL triggers.
 */
class SqlTriggers extends \Civi\Core\Service\AutoService {

  /**
   * The name of the output file.
   *
   * @var string|null
   */
  private $file;

  /**
   * Queries written to file when the class is destructed.
   *
   * @var array
   */
  private $enqueuedQueries = [];

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
    $info = [];

    $logging = new \CRM_Logging_Schema();
    $logging->triggerInfo($info, $tableName, $force);

    \CRM_Core_I18n_Schema::triggerInfo($info, $tableName);
    \CRM_Contact_BAO_Contact::triggerInfo($info, $tableName);

    \CRM_Utils_Hook::triggerInfo($info, $tableName);

    // drop all existing triggers on all tables
    $logging->dropTriggers($tableName);

    // now create the set of new triggers
    $this->createTriggers($info, $tableName);
    $this->writeEnqueuedQueriesToFile();
  }

  /**
   * @param array $info
   *   per hook_civicrm_triggerInfo.
   * @param string $onlyTableName
   *   the specific table requiring a rebuild; or NULL to rebuild all tables.
   */
  public function createTriggers($info, $onlyTableName = NULL) {
    // Validate info array, should probably raise errors?
    if (is_array($info) == FALSE) {
      return;
    }

    $triggers = [];

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
        $tables = [$value['table']];
      }
      else {
        $tables = $value['table'];
      }

      if (is_string($value['event']) == TRUE) {
        $events = [strtolower($value['event'])];
      }
      else {
        $events = array_map('strtolower', $value['event']);
      }

      $whenName = strtolower($value['when']);

      foreach ($tables as $tableName) {
        if (!isset($triggers[$tableName])) {
          $triggers[$tableName] = [];
        }

        foreach ($events as $eventName) {
          $template_params = ['{tableName}', '{eventName}'];
          $template_values = [$tableName, $eventName];

          $sql = str_replace($template_params,
            $template_values,
            $value['sql']
          );
          // @todo See https://github.com/civicrm/civicrm-core/pull/23926#discussion_r912298758. It's not clear 'variables' is used anywhere, and isn't documented or unit tested.
          $variables = str_replace($template_params,
            $template_values,
            $value['variables'] ?? ''
          );

          if (!isset($triggers[$tableName][$eventName])) {
            $triggers[$tableName][$eventName] = [];
          }

          if (!isset($triggers[$tableName][$eventName][$whenName])) {
            // We're leaving out cursors, conditions, and handlers for now
            // they are kind of dangerous in this context anyway
            // better off putting them in stored procedures
            $triggers[$tableName][$eventName][$whenName] = [
              'variables' => [],
              'sql' => [],
            ];
          }

          if ($variables) {
            $triggers[$tableName][$eventName][$whenName]['variables'][] = $variables;
          }

          $triggers[$tableName][$eventName][$whenName]['sql'][] = $sql;
        }
      }
    }

    // Sort tables alphabetically in order to output in a consistent order
    // for sites that like to diff this output over time
    // (ie. with the logging_no_trigger_permission setting in place).
    ksort($triggers);
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
    $info = [];

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
  public function enqueueQuery($triggerSQL, $params = []) {
    if (\Civi::settings()->get('logging_no_trigger_permission')) {

      if (!file_exists($this->getFile())) {
        // Ugh. Need to let user know somehow. This is the first change.
        \CRM_Core_Session::setStatus(ts('The mysql commands you need to run are stored in %1', [
          1 => $this->getFile(),
        ]),
          '',
          'alert',
          ['expires' => 0]
        );
      }
      $query = \CRM_Core_DAO::composeQuery($triggerSQL, $params);
      $this->enqueuedQueries[$query] = $query;
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

  /**
   * Write queries to file when the class is destructed.
   *
   * Note this is already written out for a full rebuild but it is
   * possible (at least in terms of what is public) to call drop & create
   * separately so this ensures they are output.
   */
  public function __destruct() {
    $this->writeEnqueuedQueriesToFile();
  }

  /**
   * Write queries queued for write-to-file.
   */
  protected function writeEnqueuedQueriesToFile(): void {
    if (!empty($this->enqueuedQueries) && $this->getFile()) {
      $buf = "DELIMITER //\n";
      foreach ($this->enqueuedQueries as $query) {
        if (strpos($query, 'CREATE TRIGGER') === 0) {
          // The create triggers are long so put spaces between them. For the drops
          // condensed is more readable.
          $buf .= "\n";
        }
        $buf .= $query . " //\n";
      }
      $buf .= "DELIMITER ;\n";
      file_put_contents($this->getFile(), $buf, FILE_APPEND);
      $this->enqueuedQueries = [];
    }
  }

}
