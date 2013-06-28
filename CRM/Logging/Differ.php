<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Logging_Differ {
  private $db;
  private $log_conn_id;
  private $log_date;
  private $interval;

  function __construct($log_conn_id, $log_date, $interval = '10 SECOND') {
    $dsn               = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->db          = $dsn['database'];
    $this->log_conn_id = $log_conn_id;
    $this->log_date    = $log_date;
    $this->interval    = $interval;
  }

  function diffsInTables($tables) {
    $diffs = array();
    foreach ($tables as $table) {
      $diff = $this->diffsInTable($table);
      if (!empty($diff)) {
        $diffs[$table] = $diff;
      }
    }
    return $diffs;
  }

  function diffsInTable($table, $contactID = null) {
    $diffs = array();

    $params = array(
      1 => array($this->log_conn_id, 'Integer'),
      2 => array($this->log_date, 'String'),
    );

    $contactIdClause = $join = '';
    if ( $contactID ) {
      $params[3] = array($contactID, 'Integer');
      switch ($table) {
      case 'civicrm_contact':
        $contactIdClause = "AND id = %3";
        break;
      case 'civicrm_note':
        $contactIdClause = "AND ( entity_id = %3 AND entity_table = 'civicrm_contact' ) OR (entity_id IN (SELECT note.id FROM `{$this->db}`.log_civicrm_note note WHERE note.entity_id = %3 AND note.entity_table = 'civicrm_contact') AND entity_table = 'civicrm_note')";
        break;
      case 'civicrm_entity_tag':
        $contactIdClause = "AND entity_id = %3 AND entity_table = 'civicrm_contact'";
        break;
      case 'civicrm_relationship':
        $contactIdClause = "AND (contact_id_a = %3 OR contact_id_b = %3)";
        break;
      case 'civicrm_activity':
        $join  = "
LEFT JOIN civicrm_activity_target at ON at.activity_id = lt.id     AND at.target_contact_id = %3
LEFT JOIN civicrm_activity_assignment aa ON aa.activity_id = lt.id AND aa.assignee_contact_id = %3
LEFT JOIN civicrm_activity source ON source.id = lt.id             AND source.source_contact_id = %3";
        $contactIdClause = "AND (at.id IS NOT NULL OR aa.id IS NOT NULL OR source.id IS NOT NULL)";
        break;
      case 'civicrm_case':
        $contactIdClause = "AND id = (select case_id FROM civicrm_case_contact WHERE contact_id = %3 LIMIT 1)";
        break;
      default:
        $contactIdClause = "AND contact_id = %3";
        if ( strpos($table, 'civicrm_value') !== false ) {
          $contactIdClause = "AND entity_id = %3";
        }
      }
    }

    // find ids in this table that were affected in the given connection (based on connection id and a ±10 s time period around the date)
    $sql = "
SELECT DISTINCT lt.id FROM `{$this->db}`.`log_$table` lt 
{$join} 
WHERE log_conn_id = %1 AND 
      log_date BETWEEN DATE_SUB(%2, INTERVAL {$this->interval}) AND DATE_ADD(%2, INTERVAL {$this->interval}) 
      {$contactIdClause}";
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $diffs = array_merge($diffs, $this->diffsInTableForId($table, $dao->id));
    }

    return $diffs;
  }

  private function diffsInTableForId($table, $id) {
    $diffs = array();

    $params = array(
      1 => array($this->log_conn_id, 'Integer'),
      2 => array($this->log_date, 'String'),
      3 => array($id, 'Integer'),
    );

    // look for all the changes in the given connection that happended less than {$this->interval} s later than log_date to the given id to catch multi-query changes
    $changedSQL = "SELECT * FROM `{$this->db}`.`log_$table` WHERE log_conn_id = %1 AND log_date >= %2 AND log_date < DATE_ADD(%2, INTERVAL {$this->interval}) AND id = %3 ORDER BY log_date DESC LIMIT 1";

    $changedDAO = CRM_Core_DAO::executeQuery($changedSQL, $params);
    while ($changedDAO->fetch( )) {
      $changed = $changedDAO->toArray();

      // return early if nothing found
      if (empty($changed)) {
        continue;
      }

      switch ($changed['log_action']) {
        case 'Delete':
          // the previous state is kept in the current state, current should keep the keys and clear the values
          $original = $changed;
          foreach ($changed as & $val) $val = NULL;
          $changed['log_action'] = 'Delete';
          break;

        case 'Insert':
          // the previous state does not exist
          $original = array();
          break;

        case 'Update':
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
      $skipped = array('log_action', 'log_conn_id', 'log_date', 'log_user_id');
      foreach (array_keys(array_diff_assoc($changed, $original)) as $diff) {
        if (in_array($diff, $skipped)) {
          continue;
        }

        if (CRM_Utils_Array::value($diff, $original) === CRM_Utils_Array::value($diff, $changed)) {
          continue;
        }
        
        // hack: case_type_id column is a varchar with separator. For proper mapping to type labels, 
        // we need to make sure separators are trimmed
        if ($diff == 'case_type_id') {
          foreach (array('original', 'changed') as $var)  {
            if (CRM_Utils_Array::value($diff, $$var)) {
              $holder =& $$var;
              $val = explode(CRM_Case_BAO_Case::VALUE_SEPARATOR, $holder[$diff]);
              $holder[$diff] = CRM_Utils_Array::value(1, $val);
            }
          }
        }

        $diffs[] = array(
          'action' => $changed['log_action'],
          'id' => $id,
          'field' => $diff,
          'from' => CRM_Utils_Array::value($diff, $original),
          'to' => CRM_Utils_Array::value($diff, $changed),
        );
      }
    }

    return $diffs;
  }

  function titlesAndValuesForTable($table) {
    // static caches for subsequent calls with the same $table
    static $titles = array();
    static $values = array();

    // FIXME: split off the table → DAO mapping to a GenCode-generated class
    static $daos = array(
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
      'civicrm_activity' => 'CRM_Activity_DAO_Activity',
      'civicrm_case' => 'CRM_Case_DAO_Case',
    );

    if (!isset($titles[$table]) or !isset($values[$table])) {

      if (in_array($table, array_keys($daos))) {
        // FIXME: these should be populated with pseudo constants as they
        // were at the time of logging rather than their current values
        $values[$table] = array(
          'contribution_page_id' => CRM_Contribute_PseudoConstant::contributionPage(),
          'contribution_status_id' => CRM_Contribute_PseudoConstant::contributionStatus(),
          'financial_type_id'              => CRM_Contribute_PseudoConstant::financialType(),
          'country_id' => CRM_Core_PseudoConstant::country(),
          'gender_id' => CRM_Core_PseudoConstant::gender(),
          'location_type_id' => CRM_Core_PseudoConstant::locationType(),
          'payment_instrument_id' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          'phone_type_id' => CRM_Core_PseudoConstant::phoneType(),
          'preferred_communication_method' => CRM_Core_PseudoConstant::pcm(),
          'preferred_language' => CRM_Core_PseudoConstant::languages(),
          'prefix_id' => CRM_Core_PseudoConstant::individualPrefix(),
          'provider_id' => CRM_Core_PseudoConstant::IMProvider(),
          'state_province_id' => CRM_Core_PseudoConstant::stateProvince(),
          'suffix_id' => CRM_Core_PseudoConstant::individualSuffix(),
          'website_type_id' => CRM_Core_PseudoConstant::websiteType(),
          'activity_type_id' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          'case_type_id' => CRM_Case_PseudoConstant::caseType('label', FALSE),
          'priority_id'  => CRM_Core_PseudoConstant::priority(),
        );

        // for columns that appear in more than 1 table
        switch ($table) {
          case 'civicrm_case':
            $values[$table]['status_id'] = CRM_Case_PseudoConstant::caseStatus('label', FALSE);
            break;
          case 'civicrm_activity':
            $values[$table]['status_id'] = CRM_Core_PseudoConstant::activityStatus( );
            break;
        }

        require_once str_replace('_', DIRECTORY_SEPARATOR, $daos[$table]) . '.php';
        eval("\$dao = new $daos[$table];");
        foreach ($dao->fields() as $field) {
          $titles[$table][$field['name']] = CRM_Utils_Array::value('title', $field);

          if ($field['type'] == CRM_Utils_Type::T_BOOLEAN) {
            $values[$table][$field['name']] = array('0' => ts('false'), '1' => ts('true'));
          }
        }
      }
      elseif (substr($table, 0, 14) == 'civicrm_value_') {
        list($titles[$table], $values[$table]) = $this->titlesAndValuesForCustomDataTable($table);
      }
    }

    return array($titles[$table], $values[$table]);
  }

  private function sqlToArray($sql, $params) {
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    return $dao->toArray();
  }

  private function titlesAndValuesForCustomDataTable($table) {
    $titles = array();
    $values = array();

    $params = array(
      1 => array($this->log_conn_id, 'Integer'),
      2 => array($this->log_date, 'String'),
      3 => array($table, 'String'),
    );

    $sql = "SELECT id, title FROM `{$this->db}`.log_civicrm_custom_group WHERE log_date <= %2 AND table_name = %3 ORDER BY log_date DESC LIMIT 1";
    $cgDao = CRM_Core_DAO::executeQuery($sql, $params);
    $cgDao->fetch();

    $params[3] = array($cgDao->id, 'Integer');
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
          $values[$cfDao->column_name] = array('0' => ts('false'), '1' => ts('true'));
          break;

        case 'String':
          $values[$cfDao->column_name] = array();
          if (!empty($cfDao->option_group_id)) {
            $params[3] = array($cfDao->option_group_id, 'Integer');
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

    return array($titles, $values);
  }
}

