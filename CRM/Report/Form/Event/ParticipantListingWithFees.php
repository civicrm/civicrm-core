<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';
require_once 'CRM/Event/PseudoConstant.php';
require_once 'CRM/Core/OptionGroup.php';
require_once 'CRM/Event/BAO/Participant.php';
require_once 'CRM/Contact/BAO/Contact.php';
class CRM_Report_Form_Event_ParticipantListingWithFees extends CRM_Report_Form {

  protected $_eventID = NULL;  
  protected $_excludeFree = NULL;
  protected $_tableName = NULL;
  
  protected $_summary = NULL;
  
  protected $_extraColumns = NULL;

  protected $_customGroupExtends = array('Participant');
  
  function __construct() {
    static $_events;
    if (!isset($_events['all'])) {
      CRM_Core_PseudoConstant::populate($_events['all'], 'CRM_Event_DAO_Event', FALSE, 'title', 'is_active', "is_template IS NULL OR is_template = 0", 'end_date DESC');

    }
    
    // Build temp table
    $tablename = $this->setTableName();    

    $this->_columns = array(
      'civicrm_contact' =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' =>
        array(
          'sort_name_linked' =>
          array('title' => ts('Participant Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
            'dbAlias' => 'sort_name',
          ),
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'employer_id' =>
          array('title' => ts('Organization'),
          ),
        ),
        'grouping' => 'contact-fields',
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Participant Name'),
            'operator' => 'like',
          ),
        ),
        'order_bys' =>
        array(
          'sort_name' =>
          array('title' => ts('Last Name, First Name'), 'default' => '1', 'default_weight' => '0', 'default_order' => 'ASC'),
        ),
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
          array('title' => ts('Email'),
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
        'filters' =>
        array(
          'email' =>
          array('title' => ts('Participant E-mail'),
            'operator' => 'like',
          ),
        ),
      ),
      'civicrm_address' =>
      array(
        'dao' => 'CRM_Core_DAO_Address',
        'fields' =>
        array(
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' =>
          array('title' => ts('State/Province'),
          ),
          'country_id' =>
          array('title' => ts('Country'),
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_participant' =>
      array(
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' =>
        array('participant_id' => array('title' => 'Participant ID'),
          'participant_record' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'event_id' => array(
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'status_id' => array('title' => ts('Status'),
            'default' => TRUE,
          ),
          'role_id' => array('title' => ts('Role'),
            'default' => TRUE,
          ),
          'participant_fee_level' => NULL,
          'participant_fee_amount' => NULL,
          'participant_register_date' => array('title' => ts('Registration Date')),
        ),
        'grouping' => 'event-fields',
        'filters' =>
        array(
          'event_id' => array('name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $_events['all'],
          ),
          'sid' => array(
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ),
          'rid' => array(
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ),
          'participant_register_date' => array(
            'title' => ' Registration Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
         'contribution_id' => array('title' => 'Contribution ID'),
          'contribution_record' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'trxn_id' => NULL,
          'invoice_id' => NULL,
        ),
        'grouping' => 'event-fields',
      ),
      'civicrm_event' =>
      array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' =>
        array(
          'event_type_id' => array('title' => ts('Event Type')),
        ),
        'grouping' => 'event-fields',
      ),
    );
    $this->_options = array('blank_column_begin' => array('title' => ts('Blank column at the Begining'),
        'type' => 'checkbox',
      ),
      'blank_column_end' => array('title' => ts('Blank column at the End'),
        'type' => 'select',
        'options' => array(
          '' => '-select-',
          1 => ts('One'),
          2 => ts('Two'),
          3 => ts('Three'),
        ),
      ),
    );

    $this->_options = array(
      'exclude_free' => array('title' => ts('Exclude no-cost items'),
        'type' => 'checkbox',
      ),
    );
      
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {

            $alias = "{$tableName}_{$fieldName}";
            $select[] = "{$field['dbAlias']} as $alias";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }
    foreach ($this->_extraColumns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          $alias = "tmp_{$fieldName}";
          $select[] = "{$fieldName} as $alias";
          $this->_columnHeaders["tmp_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          //$this->_columnHeaders["tmp_{$fieldName}"]['no_display'] = CRM_Utils_Array::value('no_display', $field);
          $this->_columnHeaders["tmp_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          $this->_selectAliases[] = $alias;
        }
      }    
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  static
  function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_participant {$this->_aliases['civicrm_participant']}
             LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']} 
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND 
                       ({$this->_aliases['civicrm_event']}.is_template IS NULL OR  
                        {$this->_aliases['civicrm_event']}.is_template = 0)
             LEFT JOIN civicrm_participant_payment cpp 
                    ON {$this->_aliases['civicrm_participant']}.id = cpp.participant_id
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}  
                    ON cpp.contribution_id = {$this->_aliases['civicrm_contribution']}.id
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                    ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  )
             {$this->_aclFrom}
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                       {$this->_aliases['civicrm_address']}.is_primary = 1 
             LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']} 
                    ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                       {$this->_aliases['civicrm_email']}.is_primary = 1) ";
     //dpm($this->_tableName);
     if($this->_tableName) {
       $alias = $this->_aliases[$this->_tableName];
       $this->_from .= "
             LEFT JOIN  {$this->_tableName} {$alias} 
                    ON {$this->_aliases['civicrm_participant']}.id = {$alias}.participant_id
       ";
     }
  }

  function where() {
    $clauses = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;

          if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
            $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
            $from     = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
            $to       = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

            if ($relative || $from || $to) {
              $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
            }
          }
          else {
            $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);

            if ($fieldName == 'rid') {
              $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
              if (!empty($value)) {
                $clause = "( {$field['dbAlias']} REGEXP '[[:<:]]" . implode('[[:>:]]|[[:<:]]', $value) . "[[:>:]]' )";
              }
              $op = NULL;
            }

            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
              );
            }
          }

          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }
    if (empty($clauses)) {
      $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 ";
    }
    else {
      $this->_where = "WHERE {$this->_aliases['civicrm_participant']}.is_test = 0 AND " . implode(' AND ', $clauses);
    }
    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function postProcess() {

    // get ready with post process params
    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    
    // Add in the Price Set columns
    $this->getOptions($this->_formValues);
    $this->_extraColumns = $this->getExtraPriceSetColumns($this->_tableName);
    $this->_aliases[$this->_tableName] = substr($this->_tableName, 8) . '_civireport';
    $this->buildTempTable($this->_tableName, $this->_extraColumns);
    $this->fillTable($this->_eventID);
    
    // build query
    $sql = $this->buildQuery(TRUE);
    //dpm($sql);
    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($sql, $rows);
    //$rows[] = $this->getSummaryRow();

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_participant_event_id', $row)) {
        if ($value = $row['civicrm_participant_event_id']) {
          $rows[$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($value, FALSE);
          $url = CRM_Report_Utils_Report::getNextUrl('event/income',
            'reset=1&force=1&id_op=in&id_value=' . $value,
            $this->_absoluteUrl, $this->_id
          );
          $rows[$rowNum]['civicrm_participant_event_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_event_id_hover'] = ts("View Event Income Details for this Event");
        }
        $entryFound = TRUE;
      }

      // handle event type id
      if (array_key_exists('civicrm_event_event_type_id', $row)) {
        if ($value = $row['civicrm_event_event_type_id']) {
          $rows[$rowNum]['civicrm_event_event_type_id'] = $eventType[$value];
        }
        $entryFound = TRUE;
      }

      // handle participant status id
      if (array_key_exists('civicrm_participant_status_id', $row)) {
        if ($value = $row['civicrm_participant_status_id']) {
          $rows[$rowNum]['civicrm_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($value, FALSE, 'label');
        }
        $entryFound = TRUE;
      }

      // handle participant role id
      if (array_key_exists('civicrm_participant_role_id', $row)) {
        if ($value = $row['civicrm_participant_role_id']) {
          $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
          $value = array();
          foreach ($roles as $role) {
            $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = implode(', ', $value);
        }
        $entryFound = TRUE;
      }

      // Handel value seperator in Fee Level
      if (array_key_exists('civicrm_participant_participant_fee_level', $row)) {
        if ($value = $row['civicrm_participant_participant_fee_level']) {
          CRM_Event_BAO_Participant::fixEventLevel($value);
          $rows[$rowNum]['civicrm_participant_participant_fee_level'] = $value;
        }
        $entryFound = TRUE;
      }

      // Convert display name to link
      if (($displayName = CRM_Utils_Array::value('civicrm_contact_sort_name_linked', $row)) &&
        ($cid = CRM_Utils_Array::value('civicrm_contact_id', $row)) &&
        ($id = CRM_Utils_Array::value('civicrm_participant_participant_record', $row))
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          "reset=1&force=1&id_op=eq&id_value=$cid",
          $this->_absoluteUrl, $this->_id
        );

        $viewUrl = CRM_Utils_System::url("civicrm/contact/view/participant",
          "reset=1&id=$id&cid=$cid&action=view"
        );

        $contactTitle = ts('View Contact Details');
        $participantTitle = ts('View Participant Record');

        $rows[$rowNum]['civicrm_contact_sort_name_linked'] = "<a title='$contactTitle' href=$url>$displayName</a>";
        if ($this->_outputMode !== 'csv') {
          $rows[$rowNum]['civicrm_contact_sort_name_linked'] .= "<span style='float: right;'><a title='$participantTitle' href=$viewUrl>" . ts('View') . "</a></span>";
        }
        $entryFound = TRUE;
      }

      // Handle country id
      if (array_key_exists('civicrm_address_country_id', $row)) {
        if ($value = $row['civicrm_address_country_id']) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, TRUE);
        }
        $entryFound = TRUE;
      }

      // Handle state/province id
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        if ($value = $row['civicrm_address_state_province_id']) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($value, TRUE);
        }
        $entryFound = TRUE;
      }

      // Handle employer id
      if (array_key_exists('civicrm_contact_employer_id', $row)) {
        if ($value = $row['civicrm_contact_employer_id']) {
          $rows[$rowNum]['civicrm_contact_employer_id'] = CRM_Contact_BAO_Contact::displayName($value);
          $url = CRM_Utils_System::url('civicrm/contact/view',
            'reset=1&cid=' . $value, $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contact_employer_id_link'] = $url;
          $rows[$rowNum]['civicrm_contact_employer_id_hover'] = ts('View Contact Summary for this Contact.');
        }
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

  /**
   * Methods adapted from Eileen McNaughton's Price Set custom search.
   */
  function getExtraPriceSetColumns($tablename) {
    
    if (!$this->_eventID) {
      //dpm('returning early');
      return array();
    }
    
    $extra_columns = array(
      $tablename =>
      array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(),
        'grouping' => 'price-set-fields',
        )
      );



    // for the selected event, find the price set and all the columns associated with it.
    // create a column for each field and option group within it
    $dao = $this->priceSetDAO($this->_eventID); //$this->_formValues['event_id']);

    if ($dao->fetch() &&
      !$dao->price_set_id
    ) {
      return;
    }

    // get all the fields and all the option values associated with it
    require_once 'CRM/Price/BAO/Set.php';
    $priceSet = CRM_Price_BAO_Set::getSetDetail($dao->price_set_id);
    if (is_array($priceSet[$dao->price_set_id])) {
      foreach ($priceSet[$dao->price_set_id]['fields'] as $key => $value) {
        if (is_array($value['options'])) {
          foreach ($value['options'] as $oKey => $oValue) {
            $amount_float = floatval($oValue['amount']);
            // Skip free items
            if (!(abs($amount_float) <  0.005) || !$this->_excludeFree) {
              $field_name = "price_field_value_{$oValue['id']}";
              $title = CRM_Utils_Array::value('label', $value);
              $title .= ' - ' . $oValue['label'];  
              
              $extra_columns[$tablename]['fields'][$field_name] = array(
                'title' => $title,
                'required' =>true,
                'type' => CRM_Utils_Type::T_INT,
              );      
            }
          }
        }
      }
    }
    return $extra_columns;
  }
  
  function setTableName() {
    $randomNum        = md5(uniqid());
    $this->_tableName = "civicrm_temp_custom_{$randomNum}";
    return $this->_tableName;
  }
  
  function buildTempTable($tablename, $columns) {
    //dpm($columns); 
    $sql              = "
CREATE TEMPORARY TABLE {$tablename} (
    id int unsigned NOT NULL AUTO_INCREMENT
  , contact_id int unsigned NOT NULL
  , participant_id int unsigned NOT NULL
";
  
    foreach ($columns[$tablename]['fields'] as $fieldName => $field_defn) {
      $sql .= ", {$fieldName} int default 0\n";
    }

    $sql .= "
  , PRIMARY KEY ( id )
  , UNIQUE INDEX unique_participant_id ( participant_id )
) ENGINE=HEAP
";
  //dpm($sql);
    CRM_Core_DAO::executeQuery($sql);
  }

  function fillTable($eventID = NULL) {
    if (!$eventID) {
      return;
    }
    else {
      $sql = "
  REPLACE INTO {$this->_tableName}
  ( contact_id, participant_id )
  SELECT c.id, p.id
  FROM   civicrm_contact c,
         civicrm_participant p
  WHERE  p.contact_id = c.id
    AND  p.is_test    = 0
    AND  p.event_id = {$eventID}
    AND  p.status_id NOT IN (4,11,12)
    AND  ( c.is_deleted = 0 OR c.is_deleted IS NULL )
  ";
  //dpm($sql);
      CRM_Core_DAO::executeQuery($sql);
  
      $sql = "
  SELECT c.id as contact_id,
         p.id as participant_id, 
         l.price_field_value_id as price_field_value_id, 
         l.qty
  FROM   civicrm_contact c,
         civicrm_participant  p,
         civicrm_line_item    l       
  WHERE  c.id = p.contact_id
  AND    p.event_id = {$eventID}
  AND    p.id = l.entity_id
  AND    l.entity_table ='civicrm_participant'
  ORDER BY c.id, l.price_field_value_id;
  ";
  //dpm($sql);
      $dao = CRM_Core_DAO::executeQuery($sql);
  
      // first store all the information by option value id
      $rows = array();
      while ($dao->fetch()) {
        $contactID = $dao->contact_id;
        $participantID = $dao->participant_id;
        if (!isset($rows[$participantID])) {
          $rows[$participantID] = array();
        }
        if(array_key_exists("price_field_value_{$dao->price_field_value_id}", $this->_extraColumns[$this->_tableName]['fields'])) {
          $rows[$participantID][] = "price_field_value_{$dao->price_field_value_id} = {$dao->qty}";
        }      
      }
  
      foreach (array_keys($rows) as $participantID) {
        $values = implode(',', $rows[$participantID]);
        $sql = "
  UPDATE {$this->_tableName}
  SET $values
  WHERE participant_id = $participantID;
  ";
  //dpm($sql);
        CRM_Core_DAO::executeQuery($sql);
      }    
    }
  }

  function priceSetDAO($eventID = NULL) {

    // get all the events that have a price set associated with it
    $sql = "
SELECT e.id    as id,
       e.title as title,
       p.price_set_id as price_set_id
FROM   civicrm_event      e,
       civicrm_price_set_entity  p

WHERE  p.entity_table = 'civicrm_event'
AND    p.entity_id    = e.id
";

    $params = array();
    if ($eventID) {
      $params[1] = array($eventID, 'Integer');
      $sql .= " AND e.id = $eventID";
    }

    $dao = CRM_Core_DAO::executeQuery($sql,
      $params
    );
    return $dao;
  }
  
  function getOptions($formValues) {
    $this->_eventID = CRM_Utils_Array::value('event_id_value', $this->_formValues);
    //dpm($this->_eventID);
    
    $this->_excludeFree = CRM_Utils_Array::value('exclude_free', $this->_formValues['options']);
    //dpm($this->_excludeFree);
    
  }

  // override this method to build your own statistics
  function statistics(&$rows) {
    $statistics = array();
    // Add a row with the summary stuff
    $this->_rollup = TRUE;
    $count = count($rows);
    
    if ($this->_rollup && ($this->_rollup != '') && $this->_grandFlag) {
      $count++;
    }
    // Call parent statistics
    $this->countStat($statistics, $count);

    $this->groupByStat($statistics);

    $this->filterStat($statistics);

    return $statistics;
  }
  
  function getSummaryRow() {
    $sql_field_list = array();
    $skipcolumns = array( 'display_name','contact_id', 'participant_id',);
    foreach ( array_keys($this->_columnHeaders) as $colname ) {
      $first = substr($colname, 0, 4);
      $fieldname = substr($colname, 4, strlen($colname));
      
      if ($first == 'tmp_') {
        $sql_field_list[] = "SUM( {$fieldname} ) AS {$colname}";
      }
      else {
        $sql_field_list[] = "'' AS {$colname}";
      }
      
    }
    $sql_fields = implode(", ", $sql_field_list);
    $sql = "SELECT {$sql_fields} FROM {$this->_tableName}";

    $dao = CRM_Core_DAO::executeQuery( $sql,
                                      CRM_Core_DAO::$_nullArray );

    //CRM_Core_Error::debug('sql',$sql); 

    $totals = array( );
    while( $dao->fetch( ) ) {
      foreach ( array_keys($this->_columnHeaders) as $fieldName ) {
        $totals[ $fieldName ] = $dao->$fieldName;
      }
      $totals['civicrm_contact_sort_name_linked'] = "<b>TOTALS</b>";
    }

    return $totals;
  }
}


