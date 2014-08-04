<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Logging_Differ {
  private $db;
  private $log_conn_id;
  private $log_date;
  private $interval;

  /**
   * @param $log_conn_id
   * @param $log_date
   * @param string $interval
   */
  function __construct($log_conn_id, $log_date, $interval = '10 SECOND') {
    $dsn               = defined('CIVICRM_LOGGING_DSN') ? DB::parseDSN(CIVICRM_LOGGING_DSN) : DB::parseDSN(CIVICRM_DSN);
    $this->db          = $dsn['database'];
    $this->log_conn_id = $log_conn_id;
    $this->log_date    = $log_date;
    $this->interval    = $interval;
  }

  /**
   * @param $tables
   *
   * @return array
   */
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

  /**
   * @param $table
   * @param null $contactID
   *
   * @return array
   */
  function diffsInTable($table, $contactID = null) {
    $diffs = array();

    $params = array(
      1 => array($this->log_conn_id, 'Integer'),
      2 => array($this->log_date, 'String'),
    );

    $logging = new CRM_Logging_Schema;
    $addressCustomTables = $logging->entityCustomDataLogTables('Address');

    $contactIdClause = $join = '';
    if ( $contactID ) {
      $params[3] = array($contactID, 'Integer');
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
        $activityContacts = CRM_Core_OptionGroup::values('activity_contacts', FALSE, FALSE, FALSE, NULL, 'name');
        $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
        $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
        $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

        $join  = "
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
          $join  = "INNER JOIN `{$this->db}`.`log_civicrm_address` et ON et.id = lt.entity_id";
          $contactIdClause = "AND contact_id = %3";
          break;
        }

        // allow tables to be extended by report hook query objects
        list($contactIdClause, $join) = CRM_Report_BAO_Hook::singleton()->logDiffClause($this, $table);

        if (empty($contactIdClause)) {
          $contactIdClause = "AND contact_id = %3";
        }
        if ( strpos($table, 'civicrm_value') !== false ) {
          $contactIdClause = "AND entity_id = %3";
        }
      }
    }

    // find ids in this table that were affected in the given connection (based on connection id and a ±10 s time period around the date)
    $sql = "
SELECT DISTINCT lt.id FROM `{$this->db}`.`log_$table` lt
{$join}
WHERE lt.log_conn_id = %1 AND
      lt.log_date BETWEEN DATE_SUB(%2, INTERVAL {$this->interval}) AND DATE_ADD(%2, INTERVAL {$this->interval})
      {$contactIdClause}";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $diffs = array_merge($diffs, $this->diffsInTableForId($table, $dao->id));
    }

    return $diffs;
  }

  /**
   * @param $table
   * @param $id
   *
   * @return array
   */
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
            if (!empty($$var[$diff])) {
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

  /**
   * @param $table
   *
   * @return array
   */
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
        // FIXME: Use *_BAO:buildOptions() method rather than pseudoconstants & fetch programmatically
        $values[$table] = array(
          'contribution_page_id' => CRM_Contribute_PseudoConstant::contributionPage(),
          'contribution_status_id' => CRM_Contribute_PseudoConstant::contributionStatus(),
          'financial_type_id'              => CRM_Contribute_PseudoConstant::financialType(),
          'country_id' => CRM_Core_PseudoConstant::country(),
          'gender_id' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          'location_type_id' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id'),
          'payment_instrument_id' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          'phone_type_id' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'),
          'preferred_communication_method' => CRM_Contact_BAO_Contact::buildOptions('preferred_communication_method'),
          'preferred_language' => CRM_Contact_BAO_Contact::buildOptions('preferred_language'),
          'prefix_id' => CRM_Contact_BAO_Contact::buildOptions('prefix_id'),
          'provider_id' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_IM', 'provider_id'),
          'state_province_id' => CRM_Core_PseudoConstant::stateProvince(),
          'suffix_id' => CRM_Contact_BAO_Contact::buildOptions('suffix_id'),
          'website_type_id' => CRM_Core_PseudoConstant::get('CRM_Core_DAO_Website', 'website_type_id'),
          'activity_type_id' => CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE),
          'case_type_id' => CRM_Case_PseudoConstant::caseType('title', FALSE),
          'priority_id'  => CRM_Core_PseudoConstant::get('CRM_Activity_DAO_Activity', 'priority_id'),
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

        $dao = new $daos[$table];
        foreach ($dao->fields() as $field) {
          $titles[$table][$field['name']] = CRM_Utils_Array::value('title', $field);

          if ($field['type'] == CRM_Utils_Type::T_BOOLEAN) {
            $values[$table][$field['name']] = array('0' => ts('false'), '1' => ts('true'));
          }
        }
      }
      elseif (substr($table, 0, 14) == 'civicrm_value_') {
        list($titles[$table], $values[$table]) = $this->titlesAndValuesForCustomDataTable($table);
      } else {
        $titles[$table] = $values[$table] = array();
      }
    }

    return array($titles[$table], $values[$table]);
  }

  /**
   * @param $sql
   * @param $params
   *
   * @return mixed
   */
  private function sqlToArray($sql, $params) {
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $dao->fetch();
    return $dao->toArray();
  }

  /**
   * @param $table
   *
   * @return array
   */
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

