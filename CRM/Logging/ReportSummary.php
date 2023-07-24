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
 * Class CRM_Logging_ReportSummary
 */
class CRM_Logging_ReportSummary extends CRM_Report_Form {
  protected $cid;

  protected $_logTables = [];

  protected $loggingDB;

  /**
   * Clause used in the final run of buildQuery but not when doing preliminary work.
   *
   * (We do this to all the api to run this report since it doesn't call postProcess).
   *
   * @var string
   */
  protected $logTypeTableClause;

  /**
   * The log table currently being processed.
   *
   * @var string
   */
  protected $currentLogTable;

  /**
   * Set within `$this->buildTemporaryTables`
   *
   * @var CRM_Utils_SQL_TempTable
   */
  protected $temporaryTable;

  /**
   * The name of the temporary table.
   * Set within `$this->buildTemporaryTables`
   *
   * @var string
   */
  protected $temporaryTableName;

  /**
   * Class constructor.
   */
  public function __construct() {
    // don’t display the ‘Add these Contacts to Group’ button
    $this->_add2groupSupported = FALSE;

    $dsn = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->loggingDB = $dsn['database'];

    // used for redirect back to contact summary
    $this->cid = CRM_Utils_Request::retrieve('cid', 'Integer');

    $this->_logTables = [
      'log_civicrm_contact' => [
        'fk' => 'id',
      ],
      'log_civicrm_email' => [
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ],
      'log_civicrm_phone' => [
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ],
      'log_civicrm_address' => [
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ],
      'log_civicrm_note' => [
        'fk' => 'entity_id',
        'entity_table' => TRUE,
        'bracket_info' => [
          'table' => 'log_civicrm_note',
          'column' => 'subject',
        ],
      ],
      'log_civicrm_note_comment' => [
        'fk' => 'entity_id',
        'table_name' => 'log_civicrm_note',
        'joins' => [
          'table' => 'log_civicrm_note',
          'join' => "entity_log_civireport.entity_id = fk_table.id AND entity_log_civireport.entity_table = 'civicrm_note'",
        ],
        'entity_table' => TRUE,
        'bracket_info' => [
          'table' => 'log_civicrm_note',
          'column' => 'subject',
        ],
      ],
      'log_civicrm_group_contact' => [
        'fk' => 'contact_id',
        'bracket_info' => [
          'entity_column' => 'group_id',
          'table' => 'log_civicrm_group',
          'column' => 'title',
        ],
        'action_column' => 'status',
        'log_type' => 'Group',
      ],
      'log_civicrm_entity_tag' => [
        'fk' => 'entity_id',
        'bracket_info' => [
          'entity_column' => 'tag_id',
          'table' => 'log_civicrm_tag',
          'column' => 'name',
        ],
        'entity_table' => TRUE,
      ],
      'log_civicrm_relationship' => [
        'fk' => 'contact_id_a',
        'bracket_info' => [
          'entity_column' => 'relationship_type_id',
          'table' => 'log_civicrm_relationship_type',
          'column' => 'label_a_b',
        ],
      ],
      'log_civicrm_activity_contact' => [
        'fk' => 'contact_id',
        'table_name' => 'log_civicrm_activity_contact',
        'log_type' => 'Activity Contact',
        'field' => 'activity_id',
        'extra_joins' => [
          'table' => 'log_civicrm_activity',
          'join' => 'extra_table.id = entity_log_civireport.activity_id',
        ],

        'bracket_info' => [
          'entity_column' => 'activity_type_id',
          'options' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          'lookup_table' => 'log_civicrm_activity',
        ],
      ],
      'log_civicrm_case' => [
        'fk' => 'contact_id',
        'joins' => [
          'table' => 'log_civicrm_case_contact',
          'join' => 'entity_log_civireport.id = fk_table.case_id',
        ],
        'bracket_info' => [
          'entity_column' => 'case_type_id',
          'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
        ],
      ],
    ];

    $logging = new CRM_Logging_Schema();

    // build _logTables for contact custom tables
    $customTables = $logging->entityCustomDataLogTables('Contact');
    foreach ($customTables as $table) {
      $this->_logTables[$table] = [
        'fk' => 'entity_id',
        'log_type' => 'Contact',
      ];
    }

    // build _logTables for address custom tables
    $customTables = $logging->entityCustomDataLogTables('Address');
    foreach ($customTables as $table) {
      $this->_logTables[$table] = [
        // For join of fk_table with contact table.
        'fk' => 'contact_id',
        'joins' => [
          // fk_table
          'table' => 'log_civicrm_address',
          'join' => 'entity_log_civireport.entity_id = fk_table.id',
        ],
        'log_type' => 'Contact',
      ];
    }

    // Allow log tables to be extended via report hooks.
    CRM_Report_BAO_Hook::singleton()->alterLogTables($this, $this->_logTables);

    parent::__construct();
  }

  public function groupBy() {
    $this->_groupBy = 'GROUP BY entity_log_civireport.log_conn_id, entity_log_civireport.log_user_id, EXTRACT(DAY_MICROSECOND FROM entity_log_civireport.log_date), entity_log_civireport.id';
  }

  /**
   * Adjust query for the activity_contact table.
   *
   * As this is just a join table the ID we REALLY care about is the activity id.
   *
   * @param string $tableName
   * @param string $tableKey
   * @param string $fieldName
   * @param string $field
   *
   * @return string
   */
  public function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if ($this->currentLogTable == 'log_civicrm_activity_contact' && $fieldName == 'id') {
      $alias = "{$tableName}_{$fieldName}";
      $select[] = "{$tableName}.activity_id as $alias";
      $this->_selectAliases[] = $alias;
      return "activity_id";
    }
    if ($fieldName == 'log_grouping') {
      if ($this->currentLogTable != 'log_civicrm_activity_contact') {
        return 1;
      }
      $mergeActivityID = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Contact Merged');
      return " IF (entity_log_civireport.log_action = 'Insert' AND extra_table.activity_type_id = $mergeActivityID , GROUP_CONCAT(entity_log_civireport.contact_id), 1) ";
    }
  }

  public function where() {
    // reset where clause as its called multiple times, every time insert sql is built.
    $this->_whereClauses = [];

    parent::where();
    $this->_where .= " AND (entity_log_civireport.log_action != 'Initialization')";
  }

  /**
   * Get log type.
   *
   * @param string $entity
   *
   * @return string
   */
  public function getLogType($entity) {
    if (!empty($this->_logTables[$entity]['log_type'])) {
      return $this->_logTables[$entity]['log_type'];
    }
    $logType = ucfirst(substr($entity, strrpos($entity, '_') + 1));
    return $logType;
  }

  /**
   * Get entity value.
   *
   * @param int $id
   * @param $entity
   * @param $logDate
   *
   * @return mixed|null|string
   */
  public function getEntityValue($id, $entity, $logDate) {
    if (!empty($this->_logTables[$entity]['bracket_info'])) {
      if (!empty($this->_logTables[$entity]['bracket_info']['entity_column'])) {
        $logTable = !empty($this->_logTables[$entity]['table_name']) ? $this->_logTables[$entity]['table_name'] : $entity;
        if (!empty($this->_logTables[$entity]['bracket_info']['lookup_table'])) {
          $logTable = $this->_logTables[$entity]['bracket_info']['lookup_table'];
        }
        $sql = "
SELECT {$this->_logTables[$entity]['bracket_info']['entity_column']}
  FROM `{$this->loggingDB}`.{$logTable}
 WHERE  log_date <= %1 AND id = %2 ORDER BY log_date DESC LIMIT 1";

        $entityID = CRM_Core_DAO::singleValueQuery($sql, [
          1 => [
            CRM_Utils_Date::isoToMysql($logDate),
            'Timestamp',
          ],
          2 => [$id, 'Integer'],
        ]);
      }
      else {
        $entityID = $id;
      }

      if ($entityID && $logDate &&
        array_key_exists('table', $this->_logTables[$entity]['bracket_info'])
      ) {
        $sql = "
SELECT {$this->_logTables[$entity]['bracket_info']['column']}
FROM  `{$this->loggingDB}`.{$this->_logTables[$entity]['bracket_info']['table']}
WHERE  log_date <= %1 AND id = %2 ORDER BY log_date DESC LIMIT 1";
        return CRM_Core_DAO::singleValueQuery($sql, [
          1 => [CRM_Utils_Date::isoToMysql($logDate), 'Timestamp'],
          2 => [$entityID, 'Integer'],
        ]);
      }
      else {
        if (array_key_exists('options', $this->_logTables[$entity]['bracket_info']) &&
          $entityID
        ) {
          return $this->_logTables[$entity]['bracket_info']['options'][$entityID] ?? NULL;
        }
      }
    }
    return NULL;
  }

  /**
   * Get entity action.
   *
   * @param int $id
   * @param int $connId
   * @param $entity
   * @param $oldAction
   *
   * @return null|string
   */
  public function getEntityAction($id, $connId, $entity, $oldAction) {
    if (!empty($this->_logTables[$entity]['action_column'])) {
      $sql = "select {$this->_logTables[$entity]['action_column']} from `{$this->loggingDB}`.{$entity} where id = %1 AND log_conn_id = %2";
      $newAction = CRM_Core_DAO::singleValueQuery($sql, [
        1 => [$id, 'Integer'],
        2 => [$connId, 'String'],
      ]);

      switch ($entity) {
        case 'log_civicrm_group_contact':
          if ($oldAction !== 'Update') {
            $newAction = $oldAction;
          }
          if ($oldAction == 'Insert') {
            $newAction = 'Added';
          }
          break;
      }
      return $newAction;
    }
    return NULL;
  }

  /**
   * Build the temporary tables for the query.
   */
  protected function buildTemporaryTables() {
    $tempColumns = "id int(10),  log_civicrm_entity_log_grouping varchar(32)";
    if (!empty($this->_params['fields']['log_action'])) {
      $tempColumns .= ", log_action varchar(64)";
    }
    $tempColumns .= ", log_type varchar(64), log_user_id int(10), log_date timestamp";
    if (!empty($this->_params['fields']['altered_contact'])) {
      $tempColumns .= ", altered_contact varchar(128)";
    }
    $tempColumns .= ", altered_contact_id int(10), log_conn_id varchar(17), is_deleted tinyint(4)";
    if (!empty($this->_params['fields']['display_name'])) {
      $tempColumns .= ", display_name varchar(128)";
    }

    // temp table to hold all altered contact-ids
    $this->temporaryTable = CRM_Utils_SQL_TempTable::build()->setCategory('logsummary')->setMemory()->createwithColumns($tempColumns);
    $this->addToDeveloperTab($this->temporaryTable->getCreateSql());
    $this->temporaryTableName = $this->temporaryTable->getName();

    $logTypes = $this->_params['log_type_value'] ?? NULL;
    unset($this->_params['log_type_value']);
    if (empty($logTypes)) {
      foreach (array_keys($this->_logTables) as $table) {
        $type = $this->getLogType($table);
        $logTypes[$type] = $type;
      }
    }

    $logTypeTableClause = '(1)';
    $logTypeTableValue = $this->_params["log_type_table_value"] ?? NULL;
    if ($logTypeTableValue) {
      $logTypeTableClause = $this->whereClause($this->_columns['log_civicrm_entity']['filters']['log_type_table'],
        $this->_params['log_type_table_op'], $logTypeTableValue, NULL, NULL);
      unset($this->_params['log_type_table_value']);
    }

    foreach ($this->_logTables as $entity => $detail) {
      if ((in_array($this->getLogType($entity), $logTypes) &&
          ($this->_params['log_type_op'] ?? NULL) == 'in') ||
        (!in_array($this->getLogType($entity), $logTypes) &&
          ($this->_params['log_type_op'] ?? NULL) == 'notin')
      ) {
        $this->currentLogTable = $entity;
        $sql = $this->buildQuery(FALSE);
        $sql = str_replace("entity_log_civireport.log_type as", "'{$entity}' as", $sql);
        $sql = "INSERT IGNORE INTO {$this->temporaryTableName} {$sql}";
        CRM_Core_DAO::disableFullGroupByMode();
        CRM_Core_DAO::executeQuery($sql);
        CRM_Core_DAO::reenableFullGroupByMode();
        $this->addToDeveloperTab($sql);
      }
    }

    $this->currentLogTable = '';

    // add computed log_type column so that we can do a group by after that, which will help
    // alterDisplay() counts sync with pager counts
    $sql = "SELECT DISTINCT log_type FROM {$this->temporaryTableName}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);
    $replaceWith = [];
    while ($dao->fetch()) {
      $type = $this->getLogType($dao->log_type);
      if (!array_key_exists($type, $replaceWith)) {
        $replaceWith[$type] = [];
      }
      $replaceWith[$type][] = $dao->log_type;
    }
    foreach ($replaceWith as $type => $tables) {
      if (!empty($tables)) {
        $replaceWith[$type] = implode("','", $tables);
      }
    }

    $sql = "ALTER TABLE {$this->temporaryTableName} ADD COLUMN log_civicrm_entity_log_type_label varchar(64)";
    CRM_Core_DAO::executeQuery($sql);
    $this->addToDeveloperTab($sql);
    foreach ($replaceWith as $type => $in) {
      $sql = "UPDATE {$this->temporaryTableName} SET log_civicrm_entity_log_type_label='{$type}', log_date=log_date WHERE log_type IN('$in')";
      CRM_Core_DAO::executeQuery($sql);
      $this->addToDeveloperTab($sql);
    }
    $this->logTypeTableClause = $logTypeTableClause;
  }

  /**
   * Common processing, also via api/unit tests.
   */
  public function beginPostProcessCommon() {
    parent::beginPostProcessCommon();
    $this->buildTemporaryTables();
  }

  /**
   * Build the report query.
   *
   * We override this in order to be able to run from the api.
   *
   * @param bool $applyLimit
   *
   * @return string
   */
  public function buildQuery($applyLimit = TRUE) {
    if (!$this->logTypeTableClause) {
      return parent::buildQuery($applyLimit);
    }
    // note the group by columns are same as that used in alterDisplay as $newRows - $key
    $this->limit();
    $this->orderBy();
    $sql = "{$this->_select}
FROM {$this->temporaryTableName} entity_log_civireport
WHERE {$this->logTypeTableClause}
GROUP BY log_civicrm_entity_log_date, log_civicrm_entity_log_type_label, log_civicrm_entity_log_conn_id, log_civicrm_entity_log_user_id, log_civicrm_entity_altered_contact_id, log_civicrm_entity_log_grouping
{$this->_orderBy}
{$this->_limit} ";
    $sql = str_replace('modified_contact_civireport.display_name', 'entity_log_civireport.altered_contact', $sql);
    $sql = str_replace('modified_contact_civireport.id', 'entity_log_civireport.altered_contact_id', $sql);
    $sql = str_replace([
      'modified_contact_civireport.',
      'altered_by_contact_civireport.',
    ], 'entity_log_civireport.', $sql);
    return $sql;
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   */
  public function buildRows($sql, &$rows) {
    parent::buildRows($sql, $rows);
    // Clean up the temp table - mostly for the unit test.
    $this->temporaryTable->drop();
  }

}
