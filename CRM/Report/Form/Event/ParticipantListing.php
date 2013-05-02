<?php
// $Id$

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
class CRM_Report_Form_Event_ParticipantListing extends CRM_Report_Form_Event {

  protected $_summary = NULL;

  protected $_customGroupExtends = array(
    'Participant'); 
  public $_drilldownReport = array('event/income' => 'Link to Detail Report');

  function __construct() {
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
            'dbAlias' => 'contact_civireport.sort_name',
          ),
		  'first_name' => array('title' => ts('First Name'),
          ),
		  'last_name' => array('title' => ts('Last Name'),
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
          'fee_currency' => array(
             'required' => TRUE,
             'no_display' => TRUE,
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
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getEventFilterOptions(),
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
          'fee_currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),

        ),
        'order_bys' =>
        array(
          'event_id' =>
          array('title' => ts('Event'), 'default_weight' => '1', 'default_order' => 'ASC'),
        ),
      ),
      'civicrm_phone' =>
      array(
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' =>
        array(
          'phone' =>
          array('title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_event' =>
      array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' =>
        array(
          'event_type_id' => array('title' => ts('Event Type')),
        ),
        'grouping' => 'event-fields',
        'filters' =>
        array(
          'eid' => array(
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ),
        ),
        'order_bys' =>
        array(
          'event_type_id' =>
          array('title' => ts('Event Type'), 'default_weight' => '2', 'default_order' => 'ASC'),
        ),
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
    $this->_currencyColumn = 'civicrm_participant_fee_currency';
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();

    //add blank column at the Start
    if (array_key_exists('options', $this->_params) && 
        CRM_Utils_Array::value('blank_column_begin', $this->_params['options'])) {
      $select[] = " '' as blankColumnBegin";
      $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
    }
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
    //add blank column at the end
    if ($blankcols = CRM_Utils_Array::value('blank_column_end', $this->_params)) {
      for ($i = 1; $i <= $blankcols; $i++) {
        $select[] = " '' as blankColumnEnd_{$i}";
        $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  static function formRule($fields, $files, $self) {
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
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} 
                    ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  )
             {$this->_aclFrom}
             LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']}
                    ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_address']}.contact_id AND 
                       {$this->_aliases['civicrm_address']}.is_primary = 1 
             LEFT JOIN  civicrm_email {$this->_aliases['civicrm_email']} 
                    ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND
                       {$this->_aliases['civicrm_email']}.is_primary = 1) 
             LEFT  JOIN civicrm_phone  {$this->_aliases['civicrm_phone']} 
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND
                         {$this->_aliases['civicrm_phone']}.is_primary = 1 		   
			";
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
    // build query
    $sql = $this->buildQuery(TRUE);

    // build array of result based on column headers. This method also allows
    // modifying column headers before using it to build result set i.e $rows.
    $this->buildRows($sql, $rows);

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
            $this->_absoluteUrl, $this->_id, $this->_drilldownReport
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
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );

        $viewUrl = CRM_Utils_System::url("civicrm/contact/view/participant",
          "reset=1&id=$id&cid=$cid&action=view&context=participant"
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
}

