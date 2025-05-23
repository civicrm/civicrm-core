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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Logging_Differ {
  private $db;
  private $log_conn_id;
  private $log_date;
  private $interval;

  /**
   * Class constructor.
   *
   * @param string $log_conn_id
   * @param string $log_date
   * @param string $interval
   */
  public function __construct($log_conn_id, $log_date, $interval = '10 SECOND') {
    $dsn = defined('CIVICRM_LOGGING_DSN') ? CRM_Utils_SQL::autoSwitchDSN(CIVICRM_LOGGING_DSN) : CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
    $dsn = DB::parseDSN($dsn);
    $this->db = $dsn['database'];
    $this->log_conn_id = $log_conn_id;
    $this->log_date = $log_date;
    $this->interval = self::filterInterval($interval);
  }

  /**
   * @param $tables
   *
   * @return array
   */
  public function diffsInTables($tables) {
    $diffs = [];
    foreach ($tables as $table) {
      $diff = $this->diffsInTable($table);
      if (!empty($diff)) {
        $diffs[$table] = $diff;
      }
    }
    return $diffs;
  }

  /**
   * @param string $table
   * @param int $contactID
   *
   * @return array
   */
  public function diffsInTable($table, $contactID = NULL) {
    $diffs = [];

    $params = [
      1 => [$this->log_conn_id, 'String'],
    ];

    $logging = new CRM_Logging_Schema();
    $addressCustomTables = $logging->entityCustomDataLogTables('Address');

    $contactIdClause = $join = '';
    if ($contactID) {
      $params[3] = [$contactID, 'Integer'];
      switch ($table) {
        case 'civicrm_contact':
          $contactIdClause = "AND id = %3";
          break;

        case 'civicrm_note':
          $contactIdClause = "AND (( entity_id = %3 AND entity_table = 'civicrm_contact' ) OR (entity_id IN (SELECT note.id FROM `{$this->db}`.log_civicrm_note note WHERE note.entity_id = %3 AND note.entity_table = 'civicrm_contact') AND entity_table = 'civicrm_note'))";
          break;

        case 'civicrm_entity_tag':
          $contactIdClause = "AND entity_id = %3 AND entity_table = 'civicrm_contact'";
          break;

        case 'civicrm_relationship':
          $contactIdClause = "AND (contact_id_a = %3 OR contact_id_b = %3)";
          break;

        case 'civicrm_activity':
          $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
          $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
          $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
          $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

          $join = "
LEFT JOIN civicrm_activity_contact at ON at.activity_id = lt.id AND at.contact_id = %3 AND at.record_type_id = {$targetID}
LEFT JOIN civicrm_activity_contact aa ON aa.activity_id = lt.id AND aa.contact_id = %3 AND aa.record_type_id = {$assigneeID}
LEFT JOIN civicrm_activity_contact source ON source.activity_id = lt.id AND source.contact_id = %3 AND source.record_type_id = {$sourceID} ";
          $contactIdClause = "AND (at.id IS NOT NULL OR aa.id IS NOT NULL OR source.id IS NOT NULL)";
          break;

        case 'civicrm_case':
          $contactIdClause = "AND id = (select case_id FROM civicrm_case_contact WHERE contact_id = %3 LIMIT 1)";
          break;

        default:
          if (array_key_exists($table, $addressCustomTables)) {
            $join = "INNER JOIN `{$this->db}`.`log_civicrm_address` et ON et.id = lt.entity_id";
            $contactIdClause = "AND contact_id = %3";
            break;
          }

          // allow tables to be extended by report hook query objects
          list($contactIdClause, $join) = CRM_Report_BAO_Hook::singleton()->logDiffClause($this, $table);

          if (empty($contactIdClause)) {
            $contactIdClause = "AND contact_id = %3";
          }
          if (str_contains($table, 'civicrm_value')) {
            $contactIdClause = "AND entity_id = %3";
          }
      }
    }

    $logDateClause = '';
    if ($this->log_date) {
      $params[2] = [$this->log_date, 'String'];
      $logDateClause = "
        AND lt.log_date BETWEEN DATE_SUB(%2, INTERVAL {$this->interval}) AND DATE_ADD(%2, INTERVAL {$this->interval})
      ";
    }

    // find ids in this table that were affected in the given connection (based on connection id and a ±10 s time period around the date)
    $sql = "
SELECT DISTINCT lt.id FROM `{$this->db}`.`log_$table` lt
{$join}
WHERE lt.log_conn_id = %1
    $logDateClause
    {$contactIdClause}";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $diffs = array_merge($diffs, $this->diffsInTableForId($table, $dao->id));
    }

    return $diffs;
  }

  /**
   * @param $table
   * @param int $id
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function diffsInTableForId($table, $id) {
    $diffs = [];

    $params = [
      1 => [$this->log_conn_id, 'String'],
      3 => [$id, 'Integer'],
    ];

    // look for all the changes in the given connection that happened less than {$this->interval} s later than log_date to the given id to catch multi-query changes
    $logDateClause = "";
    if ($this->log_date && $this->interval) {
      $logDateClause = " AND log_date >= %2 AND log_date < DATE_ADD(%2, INTERVAL {$this->interval})";
      $params[2] = [$this->log_date, 'String'];
    }

    $changedSQL = "SELECT * FROM `{$this->db}`.`log_$table` WHERE log_conn_id = %1 $logDateClause AND id = %3 ORDER BY log_date DESC LIMIT 1";

    $changedDAO = CRM_Core_DAO::executeQuery($changedSQL, $params);
    while ($changedDAO->fetch()) {
      if (empty($this->log_date) && !self::checkLogCanBeUsedWithNoLogDate($changedDAO->log_date)) {
        throw new CRM_Core_Exception('The connection date must be passed in to disambiguate this logging entry per CRM-18193');
      }
      $changed = $changedDAO->toArray();

      // return early if nothing found
      if (empty($changed)) {
        continue;
      }

      switch ($changed['log_action']) {
        case 'Delete':
          // the previous state is kept in the current state, current should keep the keys and clear the values
          $original = $changed;
          foreach ($changed as & $val) {
            $val = NULL;
          }
          $changed['log_action'] = 'Delete';
          break;

        case 'Insert':
          // the previous state does not exist
          $original = [];
          break;

        case 'Update':
          $params[2] = [$changedDAO->log_date, 'String'];
          // look for the previous state (different log_conn_id) of the given id
          $originalSQL = "SELECT * FROM `{$this->db}`.`log_$table` WHERE log_conn_id != %1 AND log_date < %2 AND id = %3 ORDER BY log_date DESC LIMIT 1";
          $original = $this->sqlToArray($originalSQL, $params);
          if (empty($original)) {
            // A blank original array is not possible for Update action, otherwise we 'll end up displaying all information
            // in $changed variable as updated-info
            $original = $changed;
          }

          break;
      }

      // populate $diffs with only the differences between $changed and $original
      $skipped = ['log_action', 'log_conn_id', 'log_date', 'log_user_id'];
      foreach (array_keys(array_diff_assoc($changed, $original)) as $diff) {
        if (in_array($diff, $skipped)) {
          continue;
        }

        if (($original[$diff] ?? NULL) === ($changed[$diff] ?? NULL)) {
          continue;
        }

        // hack: case_type_id column is a varchar with separator. For proper mapping to type labels,
        // we need to make sure separators are trimmed
        if ($diff == 'case_type_id') {
          foreach (['original', 'changed'] as $var) {
            if (!empty(${$var[$diff]})) {
              $holder =& $$var;
              $val = explode(CRM_Case_BAO_Case::VALUE_SEPARATOR, $holder[$diff]);
              $holder[$diff] = $val[1] ?? NULL;
            }
          }
        }

        $diffs[] = [
          'action' => $changed['log_action'],
          'id' => $id,
          'field' => $diff,
          'from' => $original[$diff] ?? NULL,
          'to' => $changed[$diff] ?? NULL,
          'table' => $table,
          'log_date' => $changed['log_date'],
          'log_conn_id' => $changed['log_conn_id'],
        ];
      }
    }

    return $diffs;
  }

  /**
   * Get the titles & metadata option values for the table.
   *
   * For custom fields the titles may change so we use the ones as at the reference date.
   *
   * @param string $table
   * @param string $referenceDate
   *
   * @return array
   */
  public function titlesAndValuesForTable($table, $referenceDate) {
    // static caches for subsequent calls with the same $table
    static $titles = [];
    static $values = [];

    if (!isset($titles[$table]) or !isset($values[$table])) {
      if (($tableDAO = CRM_Core_DAO_AllCoreTables::getClassForTable($table)) != FALSE) {
        // FIXME: these should be populated with pseudo constants as they
        // were at the time of logging rather than their current values
        // FIXME: Use *_BAO:buildOptions() method rather than pseudoconstants & fetch programmatically
        $values[$table] = [
          'contribution_page_id' => CRM_Contribute_PseudoConstant::contributionPage(),
          'contribution_status_id' => CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label'),
          'financial_type_id' => CRM_Contribute_PseudoConstant::financialType(),
          'country_id' => CRM_Core_PseudoConstant::country(),
          'gender_id' => CRM_Contact_DAO_Contact::buildOptions('gender_id'),
          'location_type_id' => CRM_Core_DAO_Address::buildOptions('location_type_id'),
          'payment_instrument_id' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          'phone_type_id' => CRM_Core_DAO_Phone::buildOptions('phone_type_id'),
          'preferred_communication_method' => CRM_Contact_BAO_Contact::buildOptions('preferred_communication_method'),
          'preferred_language' => CRM_Contact_BAO_Contact::buildOptions('preferred_language'),
          'prefix_id' => CRM_Contact_BAO_Contact::buildOptions('prefix_id'),
          'provider_id' => CRM_Core_DAO_IM::buildOptions('provider_id'),
          'state_province_id' => CRM_Core_PseudoConstant::stateProvince(),
          'suffix_id' => CRM_Contact_BAO_Contact::buildOptions('suffix_id'),
          'website_type_id' => CRM_Core_DAO_Website::buildOptions('website_type_id'),
          'activity_type_id' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          'case_type_id' => CRM_Case_PseudoConstant::caseType('title', FALSE),
          'priority_id' => CRM_Activity_DAO_Activity::buildOptions('priority_id'),
          'record_type_id' => CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'get'),
        ];

        // for columns that appear in more than 1 table
        switch ($table) {
          case 'civicrm_case':
            $values[$table]['status_id'] = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
            break;

          case 'civicrm_activity':
            $values[$table]['status_id'] = CRM_Core_PseudoConstant::activityStatus();
            break;
        }

        $dao = new $tableDAO();
        foreach ($dao->fields() as $field) {
          $titles[$table][$field['name']] = $field['title'] ?? NULL;

          if ($field['type'] == CRM_Utils_Type::T_BOOLEAN) {
            $values[$table][$field['name']] = ['0' => ts('false'), '1' => ts('true')];
          }
        }
      }
      elseif (substr($table, 0, 14) == 'civicrm_value_') {
        list($titles[$table], $values[$table]) = $this->titlesAndValuesForCustomDataTable($table, $referenceDate);
      }
      else {
        $titles[$table] = $values[$table] = [];
      }
    }

    return [$titles[$table], $values[$table]];
  }

  /**
   * @param $sql
   * @param array $params
   *
   * @return mixed
   */
  private function sqlToArray($sql, $params) {
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    return $dao->toArray();
  }

  /**
   * Get the field titles & option group values for the custom table as at the reference date.
   *
   * @param string $table
   * @param string $referenceDate
   *
   * @return array
   */
  private function titlesAndValuesForCustomDataTable($table, $referenceDate) {
    $titles = [];
    $values = [];

    $params = [
      1 => [$this->log_conn_id, 'String'],
      2 => [$referenceDate, 'String'],
      3 => [$table, 'String'],
    ];

    $sql = "SELECT id, title FROM `{$this->db}`.log_civicrm_custom_group WHERE log_date <= %2 AND table_name = %3 ORDER BY log_date DESC LIMIT 1";
    $cgDao = CRM_Core_DAO::executeQuery($sql, $params);
    $cgDao->fetch();

    $params[3] = [$cgDao->id, 'Integer'];
    $sql = "
SELECT column_name, data_type, label, name, option_group_id
FROM   `{$this->db}`.log_civicrm_custom_field
WHERE  log_date <= %2
AND    custom_group_id = %3
ORDER BY log_date
";
    $cfDao = CRM_Core_DAO::executeQuery($sql, $params);

    while ($cfDao->fetch()) {
      $titles[$cfDao->column_name] = "{$cgDao->title}: {$cfDao->label}";

      switch ($cfDao->data_type) {
        case 'Boolean':
          $values[$cfDao->column_name] = ['0' => ts('false'), '1' => ts('true')];
          break;

        case 'String':
          $values[$cfDao->column_name] = [];
          if (!empty($cfDao->option_group_id)) {
            $params[3] = [$cfDao->option_group_id, 'Integer'];
            $sql = "
SELECT   label, value
FROM     `{$this->db}`.log_civicrm_option_value
WHERE    log_date <= %2
AND      option_group_id = %3
ORDER BY log_date
";
            $ovDao = CRM_Core_DAO::executeQuery($sql, $params);
            while ($ovDao->fetch()) {
              $values[$cfDao->column_name][$ovDao->value] = $ovDao->label;
            }
          }
          break;
      }
    }

    return [$titles, $values];
  }

  /**
   * Get all changes made in the connection.
   *
   * @param array $tables
   *   Array of tables to inspect.
   * @param int $limit
   *   Limit result to x
   * @param int $offset
   *   Offset result to y
   *
   * @return array
   */
  public function getAllChangesForConnection($tables, $limit = 0, $offset = 0) {
    $params = [
      1 => [$this->log_conn_id, 'String'],
      2 => [$limit, 'Integer'],
      3 => [$offset, 'Integer'],
    ];

    foreach ($tables as $table) {
      if (empty($sql)) {
        $sql = " SELECT '{$table}' as table_name, id FROM {$this->db}.log_{$table} WHERE log_conn_id = %1";
      }
      else {
        $sql .= " UNION SELECT '{$table}' as table_name, id FROM {$this->db}.log_{$table} WHERE log_conn_id = %1";
      }
    }
    if ($limit) {
      $sql .= " LIMIT %2";
    }
    if ($offset) {
      $sql .= " OFFSET %3";
    }
    $diffs = [];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      if (empty($this->log_date)) {
        // look for available table in above query instead of looking for last table. this will avoid multiple loops
        $this->log_date = CRM_Core_DAO::singleValueQuery("SELECT log_date FROM {$this->db}.log_{$dao->table_name} WHERE log_conn_id = %1 LIMIT 1", $params);
      }
      $diffs = array_merge($diffs, $this->diffsInTableForId($dao->table_name, $dao->id));
    }
    return $diffs;
  }

  /**
   * Get count of all changes made in the connection.
   *
   * @param array $tables
   *   Array of tables to inspect.
   *
   * @return array
   */
  public function getCountOfAllContactChangesForConnection($tables) {
    $count = 0;
    $params = [1 => [$this->log_conn_id, 'String']];
    foreach ($tables as $table) {
      if (empty($sql)) {
        $sql = " SELECT '{$table}' as table_name, id FROM {$this->db}.log_{$table} WHERE log_conn_id = %1";
      }
      else {
        $sql .= " UNION SELECT '{$table}' as table_name, id FROM {$this->db}.log_{$table} WHERE log_conn_id = %1";
      }
    }
    $countSQL = " SELECT count(*) as countOfContacts FROM ({$sql}) count";
    $count = CRM_Core_DAO::singleValueQuery($countSQL, $params);
    return $count;
  }

  /**
   * Check that the log record relates to a unique log id.
   *
   * If the record was recorded using the old non-unique style then the
   * log_date
   * MUST be set to get the (fairly accurate) list of changes. In this case the
   * nasty 10 second interval rule is applied.
   *
   * See  CRM-18193 for a discussion of unique log id.
   *
   * @param string $change_date
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function checkLogCanBeUsedWithNoLogDate($change_date) {

    if (civicrm_api3('Setting', 'getvalue', ['name' => 'logging_all_tables_uniquid', 'group' => 'CiviCRM Preferences'])) {
      return TRUE;
    };
    $uniqueDate = civicrm_api3('Setting', 'getvalue', [
      'name' => 'logging_uniqueid_date',
      'group' => 'CiviCRM Preferences',
    ]);
    if (strtotime($uniqueDate) <= strtotime($change_date)) {
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Filter a MySQL interval expression.
   *
   * @param string $interval
   * @return string
   *   Normalized version of $interval
   * @throws \CRM_Core_Exception
   *   If the expression is invalid.
   * @see https://dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-add
   */
  private static function filterInterval($interval) {
    if (empty($interval)) {
      return $interval;
    }

    $units = ['MICROSECOND', 'SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR'];
    $interval = strtoupper($interval);
    if (preg_match('/^([0-9]+) ([A-Z]+)$/', $interval, $matches)) {
      if (in_array($matches[2], $units)) {
        return $interval;
      }
    }
    if (preg_match('/^\'([0-9: \.\-]+)\' ([A-Z]+)_([A-Z]+)$/', $interval, $matches)) {
      if (in_array($matches[2], $units) && in_array($matches[3], $units)) {
        return $interval;
      }
    }
    throw new CRM_Core_Exception("Malformed interval");
  }

}
