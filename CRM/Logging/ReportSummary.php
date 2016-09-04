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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 */
class CRM_Logging_ReportSummary extends CRM_Report_Form {
  protected $cid;

  protected $_logTables = array();

  protected $loggingDB;

  /**
   * The log table currently being processed.
   *
   * @var string
   */
  protected $currentLogTable;

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

    $this->_logTables = array(
      'log_civicrm_contact' => array(
        'fk' => 'id',
      ),
      'log_civicrm_email' => array(
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ),
      'log_civicrm_phone' => array(
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ),
      'log_civicrm_address' => array(
        'fk' => 'contact_id',
        'log_type' => 'Contact',
      ),
      'log_civicrm_note' => array(
        'fk' => 'entity_id',
        'entity_table' => TRUE,
        'bracket_info' => array(
          'table' => 'log_civicrm_note',
          'column' => 'subject',
        ),
      ),
      'log_civicrm_note_comment' => array(
        'fk' => 'entity_id',
        'table_name' => 'log_civicrm_note',
        'joins' => array(
          'table' => 'log_civicrm_note',
          'join' => "entity_log_civireport.entity_id = fk_table.id AND entity_log_civireport.entity_table = 'civicrm_note'",
        ),
        'entity_table' => TRUE,
        'bracket_info' => array(
          'table' => 'log_civicrm_note',
          'column' => 'subject',
        ),
      ),
      'log_civicrm_group_contact' => array(
        'fk' => 'contact_id',
        'bracket_info' => array(
          'entity_column' => 'group_id',
          'table' => 'log_civicrm_group',
          'column' => 'title',
        ),
        'action_column' => 'status',
        'log_type' => 'Group',
      ),
      'log_civicrm_entity_tag' => array(
        'fk' => 'entity_id',
        'bracket_info' => array(
          'entity_column' => 'tag_id',
          'table' => 'log_civicrm_tag',
          'column' => 'name',
        ),
        'entity_table' => TRUE,
      ),
      'log_civicrm_relationship' => array(
        'fk' => 'contact_id_a',
        'bracket_info' => array(
          'entity_column' => 'relationship_type_id',
          'table' => 'log_civicrm_relationship_type',
          'column' => 'label_a_b',
        ),
      ),
      'log_civicrm_activity_contact' => array(
        'fk' => 'contact_id',
        'table_name' => 'log_civicrm_activity_contact',
        'log_type' => 'Activity',
        'field' => 'activity_id',
        'extra_joins' => array(
          'table' => 'log_civicrm_activity',
          'join' => 'extra_table.id = entity_log_civireport.activity_id',
        ),

        'bracket_info' => array(
          'entity_column' => 'activity_type_id',
          'options' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          'lookup_table' => 'log_civicrm_activity',
        ),
      ),
      'log_civicrm_case' => array(
        'fk' => 'contact_id',
        'joins' => array(
          'table' => 'log_civicrm_case_contact',
          'join' => 'entity_log_civireport.id = fk_table.case_id',
        ),
        'bracket_info' => array(
          'entity_column' => 'case_type_id',
          'options' => CRM_Case_BAO_Case::buildOptions('case_type_id', 'search'),
        ),
      ),
    );

    $logging = new CRM_Logging_Schema();

    // build _logTables for contact custom tables
    $customTables = $logging->entityCustomDataLogTables('Contact');
    foreach ($customTables as $table) {
      $this->_logTables[$table] = array(
        'fk' => 'entity_id',
        'log_type' => 'Contact',
      );
    }

    // build _logTables for address custom tables
    $customTables = $logging->entityCustomDataLogTables('Address');
    foreach ($customTables as $table) {
      $this->_logTables[$table] = array(
        // For join of fk_table with contact table.
        'fk' => 'contact_id',
        'joins' => array(
          // fk_table
          'table' => 'log_civicrm_address',
          'join' => 'entity_log_civireport.entity_id = fk_table.id',
        ),
        'log_type' => 'Contact',
      );
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
    $this->_whereClauses = array();

    parent::where();
    $this->_where .= " AND (entity_log_civireport.log_action != 'Initialization')";
  }

  public function postProcess() {
    $this->beginPostProcess();
    $rows = array();

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
    $sql = "CREATE TEMPORARY TABLE civicrm_temp_civireport_logsummary ( {$tempColumns} ) ENGINE=HEAP";
    CRM_Core_DAO::executeQuery($sql);

    $logTypes = CRM_Utils_Array::value('log_type_value', $this->_params);
    unset($this->_params['log_type_value']);
    if (empty($logTypes)) {
      foreach (array_keys($this->_logTables) as $table) {
        $type = $this->getLogType($table);
        $logTypes[$type] = $type;
      }
    }

    $logTypeTableClause = '(1)';
    if ($logTypeTableValue = CRM_Utils_Array::value("log_type_table_value", $this->_params)) {
      $logTypeTableClause = $this->whereClause($this->_columns['log_civicrm_entity']['filters']['log_type_table'],
        $this->_params['log_type_table_op'], $logTypeTableValue, NULL, NULL);
      unset($this->_params['log_type_table_value']);
    }

    foreach ($this->_logTables as $entity => $detail) {
      if ((in_array($this->getLogType($entity), $logTypes) &&
          CRM_Utils_Array::value('log_type_op', $this->_params) == 'in') ||
        (!in_array($this->getLogType($entity), $logTypes) &&
          CRM_Utils_Array::value('log_type_op', $this->_params) == 'notin')
      ) {
        $this->currentLogTable = $entity;
        $sql = $this->buildQuery(FALSE);
        $sql = str_replace("entity_log_civireport.log_type as", "'{$entity}' as", $sql);
        $sql = "INSERT IGNORE INTO civicrm_temp_civireport_logsummary {$sql}";
        CRM_Core_DAO::executeQuery($sql);
      }
    }

    $this->currentLogTable = '';

    // add computed log_type column so that we can do a group by after that, which will help
    // alterDisplay() counts sync with pager counts
    $sql = "SELECT DISTINCT log_type FROM civicrm_temp_civireport_logsummary";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $replaceWith = array();
    while ($dao->fetch()) {
      $type = $this->getLogType($dao->log_type);
      if (!array_key_exists($type, $replaceWith)) {
        $replaceWith[$type] = array();
      }
      $replaceWith[$type][] = $dao->log_type;
    }
    foreach ($replaceWith as $type => $tables) {
      if (!empty($tables)) {
        $replaceWith[$type] = implode("','", $tables);
      }
    }

    $sql = "ALTER TABLE civicrm_temp_civireport_logsummary ADD COLUMN log_civicrm_entity_log_type_label varchar(64)";
    CRM_Core_DAO::executeQuery($sql);
    foreach ($replaceWith as $type => $in) {
      $sql = "UPDATE civicrm_temp_civireport_logsummary SET log_civicrm_entity_log_type_label='{$type}', log_date=log_date WHERE log_type IN('$in')";
      CRM_Core_DAO::executeQuery($sql);
    }

    // note the group by columns are same as that used in alterDisplay as $newRows - $key
    $this->limit();
    $sql = "{$this->_select}
FROM civicrm_temp_civireport_logsummary entity_log_civireport
WHERE {$logTypeTableClause}
GROUP BY log_civicrm_entity_log_date, log_civicrm_entity_log_type_label, log_civicrm_entity_log_conn_id, log_civicrm_entity_log_user_id, log_civicrm_entity_altered_contact_id, log_civicrm_entity_log_grouping
ORDER BY log_civicrm_entity_log_date DESC {$this->_limit}";
    $sql = str_replace('modified_contact_civireport.display_name', 'entity_log_civireport.altered_contact', $sql);
    $sql = str_replace('modified_contact_civireport.id', 'entity_log_civireport.altered_contact_id', $sql);
    $sql = str_replace(array(
      'modified_contact_civireport.',
      'altered_by_contact_civireport.',
    ), 'entity_log_civireport.', $sql);
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
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

        $entityID = CRM_Core_DAO::singleValueQuery($sql, array(
          1 => array(
            CRM_Utils_Date::isoToMysql($logDate),
            'Timestamp',
          ),
          2 => array($id, 'Integer'),
        ));
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
        return CRM_Core_DAO::singleValueQuery($sql, array(
          1 => array(CRM_Utils_Date::isoToMysql($logDate), 'Timestamp'),
          2 => array($entityID, 'Integer'),
        ));
      }
      else {
        if (array_key_exists('options', $this->_logTables[$entity]['bracket_info']) &&
          $entityID
        ) {
          return CRM_Utils_Array::value($entityID, $this->_logTables[$entity]['bracket_info']['options']);
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
      $newAction = CRM_Core_DAO::singleValueQuery($sql, array(
        1 => array($id, 'Integer'),
        2 => array($connId, 'String'),
      ));

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

}
