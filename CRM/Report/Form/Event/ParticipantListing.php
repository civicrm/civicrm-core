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
class CRM_Report_Form_Event_ParticipantListing extends CRM_Report_Form_Event {

  protected $_summary = NULL;

  protected $_contribField = FALSE;
  protected $_lineitemField = FALSE;
  protected $_groupFilter = TRUE;
  protected $_tagFilter = TRUE;
  protected $activeCampaigns;

  protected $_customGroupExtends = array('Participant', 'Contact', 'Individual', 'Event');

  public $_drilldownReport = array('event/income' => 'Link to Detail Report');

  /**
   *
   */
  /**
   *
   */
  function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;

    // Check if CiviCampaign is a) enabled and b) has active campaigns
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($campaignEnabled) {
      $getCampaigns = CRM_Campaign_BAO_Campaign::getPermissionedCampaigns(NULL, NULL, TRUE, FALSE, TRUE);
      $this->activeCampaigns = $getCampaigns['campaigns'];
      asort($this->activeCampaigns);
    }

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
          'gender_id' =>
          array('title' => ts('Gender'),
          ),
          'birth_date' =>
          array('title' => ts('Birth Date'),
          ),
          'age' => array(
            'title'   => ts('Age'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
          ),
          'age_at_event' => array(
            'title'   => ts('Age at Event'),
            'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, event_civireport.start_date)',
          ),
          'employer_id' =>
          array('title' => ts('Organization'),
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' =>
        array(
          'sort_name' =>
          array('title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ),
          'gender_id' =>
          array(
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ),
          'birth_date' =>
          array(
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ),
          'age_at_event' =>
          array(
            'name' => 'age_at_event',
            'title' => ts('Age at Event'),
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Participant Name'),
            'operator' => 'like',
          ),
          'gender_id' =>
          array('title' => ts('Gender'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id'),
          ),
          'birth_date' => array(
            'title' => 'Birth Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type'         => CRM_Utils_Type::T_DATE
          ),
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
          'event_id' => array(
            'name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => array('entity' => 'event', 'select' => array('minimumInputLength' => 0)),
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
            'title' => 'Registration Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'fee_currency' =>
          array('title' => ts('Fee Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),

        ),
        'order_bys' =>
        array(
          'participant_register_date' =>
          array('title' => ts('Registration Date'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ),
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
        'fields' => array(
          'event_type_id' => array('title' => ts('Event Type')),
          'event_start_date' => array('title' => ts('Event Start Date')),
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
          'event_start_date' => array(
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
        'order_bys' =>
        array(
          'event_type_id' =>
          array('title' => ts('Event Type'), 'default_weight' => '2', 'default_order' => 'ASC'),
        ),
      ),
      'civicrm_contribution' => array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => array(
          'contribution_id' => array(
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
            'csv_display' => TRUE,
            'title' => ts('Contribution ID'),
          ),
          'financial_type_id' => array('title' => ts('Financial Type')),
          'receive_date' => array('title' => ts('Payment Date')),
          'contribution_status_id' => array('title' => ts('Contribution Status')),
          'payment_instrument_id' => array('title' => ts('Payment Type')),
          'contribution_source' => array(
            'name' => 'source',
            'title' => ts('Contribution Source'),
          ),
          'currency' => array(
            'required' => TRUE,
            'no_display' => TRUE
          ),
          'trxn_id' => NULL,
          'fee_amount' => array('title' => ts('Transaction Fee')),
          'net_amount' => NULL
        ),
        'grouping' => 'contrib-fields',
        'filters' => array(
          'receive_date' => array(
            'title' => 'Payment Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'financial_type_id' => array('title' => ts('Financial Type'),
                               'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                               'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
          'currency' => array('title' => ts('Contribution Currency'),
                      'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                      'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
                      'default' => NULL,
                      'type' => CRM_Utils_Type::T_STRING,
          ),
          'payment_instrument_id' => array('title' => ts('Payment Type'),
                                   'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                   'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ),
          'contribution_status_id' => array('title' => ts('Contribution Status'),
                                    'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                    'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
                                    'default' => NULL
          ),
        ),
      ),

      'civicrm_line_item' => array(
        'dao' => 'CRM_Price_DAO_LineItem',
        'grouping' => 'priceset-fields',
        'filters' => array(
          'price_field_value_id' => array(
            'name' => 'price_field_value_id',
            'title' => ts('Fee Level'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getPriceLevels(),
          ),
        ),
      ),
    );


    $this->_options = array(
      'blank_column_begin' => array(
        'title' => ts('Blank column at the Begining'),
        'type' => 'checkbox',
      ),
      'blank_column_end' => array(
        'title' => ts('Blank column at the End'),
        'type' => 'select',
        'options' => array(
          '' => '-select-',
          1 => ts('One'),
          2 => ts('Two'),
          3 => ts('Three'),
        ),
      ),
    );

    // If we have active campaigns add those elements to both the fields and filters
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
      $this->_columns['civicrm_participant']['fields']['campaign_id'] =
        array(
          'title' => ts('Campaign'),
          'default' => 'false',
        );
      $this->_columns['civicrm_participant']['filters']['campaign_id'] =
        array(
          'title' => ts('Campaign'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->activeCampaigns,
        );
      $this->_columns['civicrm_participant']['order_bys']['campaign_id'] =
        array('title' => ts('Campaign'));

    }

    $this->_currencyColumn = 'civicrm_participant_fee_currency';
    parent::__construct();
  }

  /**
   * @return array
   */
  function getPriceLevels() {
    $query = "
SELECT     DISTINCT cv.label, cv.id
FROM      civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce ON ce.price_set_id = cf.price_set_id
WHERE     ce.entity_table = 'civicrm_event'
GROUP BY  cv.label
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $elements = array();
    while ($dao->fetch()) {
      $elements[$dao->id] = "$dao->label\n";
    }

    return $elements;
  } //searches database for priceset values


  function preProcess() {
    parent::preProcess();
  }

  function select() {
    $select = array();
    $this->_columnHeaders = array();

    //add blank column at the Start
    if (array_key_exists('options', $this->_params) && !empty($this->_params['options']['blank_column_begin'])) {
      $select[] = " '' as blankColumnBegin";
      $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
    }
    foreach ($this->_columns as $tableName => $table) {
      if ($tableName == 'civicrm_line_item'){
        $this->_lineitemField = TRUE;
      }
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) || !empty($this->_params['fields'][$fieldName])) {
            if ($tableName == 'civicrm_contribution') {
              $this->_contribField = TRUE;
            }

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
    $blankcols = CRM_Utils_Array::value('blank_column_end', $this->_params);
    if ($blankcols) {
      for ($i = 1; $i <= $blankcols; $i++) {
        $select[] = " '' as blankColumnEnd_{$i}";
        $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  function from() {
    $this->_from = "
        FROM civicrm_participant {$this->_aliases['civicrm_participant']}
             LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND
                        {$this->_aliases['civicrm_event']}.is_template = 0
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
    if ($this->_contribField) {
      $this->_from .= "
             LEFT JOIN civicrm_participant_payment pp
                    ON ({$this->_aliases['civicrm_participant']}.id  = pp.participant_id)
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                    ON (pp.contribution_id  = {$this->_aliases['civicrm_contribution']}.id)
      ";
    }
    if ($this->_lineitemField){
      $this->_from .= "
            LEFT JOIN civicrm_line_item line_item_civireport
                  ON line_item_civireport.entity_table = 'civicrm_participant' AND
                     line_item_civireport.entity_id = {$this->_aliases['civicrm_participant']}.id
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

  function groupBy(){
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_participant']}.id";
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
    $rows = array();
    $this->buildRows($sql, $rows);

    // format result set.
    $this->formatDisplay($rows);

    // assign variables to templates
    $this->doTemplateAssignment($rows);

    // do print / pdf / instance stuff if needed
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   * @param $entryFound
   * @param $row
   * @param $rowId
   * @param $rowNum
   * @param $types
   */
  private function _initBasicRow(&$rows, &$entryFound, $row, $rowId, $rowNum, $types){
    if (!array_key_exists($rowId, $row)) {
      return FALSE;
    }

    $value = $row[$rowId];
    if ($value) {
      $rows[$rowNum][$rowId] = $types[$value];
    }
    $entryFound = TRUE;
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows

    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');

    $financialTypes  = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    $genders = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id', array('localize' => TRUE));

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert display name to links
      if (array_key_exists('civicrm_participant_event_id', $row)) {
        $eventId = $row['civicrm_participant_event_id'];
        if ($eventId) {
          $rows[$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($eventId, FALSE);

          $url = CRM_Report_Utils_Report::getNextUrl('event/income',
                 'reset=1&force=1&id_op=in&id_value=' . $eventId,
                 $this->_absoluteUrl, $this->_id, $this->_drilldownReport
          );
          $rows[$rowNum]['civicrm_participant_event_id_link'] = $url;
          $rows[$rowNum]['civicrm_participant_event_id_hover'] = ts("View Event Income Details for this Event");
        }
        $entryFound = TRUE;
      }

      // handle event type id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_event_event_type_id', $rowNum, $eventType);

      // handle participant status id
      if (array_key_exists('civicrm_participant_status_id', $row)) {
        $statusId = $row['civicrm_participant_status_id'];
        if ($statusId) {
          $rows[$rowNum]['civicrm_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($statusId, FALSE, 'label');
        }
        $entryFound = TRUE;
      }

      // handle participant role id
      if (array_key_exists('civicrm_participant_role_id', $row)) {
        $roleId = $row['civicrm_participant_role_id'];
        if ($roleId) {
          $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $roleId);
          $roleId = array();
          foreach ($roles as $role) {
            $roleId[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = implode(', ', $roleId);
        }
        $entryFound = TRUE;
      }

      // Handel value seperator in Fee Level
      if (array_key_exists('civicrm_participant_participant_fee_level', $row)) {
        $feeLevel = $row['civicrm_participant_participant_fee_level'];
        if ($feeLevel) {
          CRM_Event_BAO_Participant::fixEventLevel($feeLevel);
          $rows[$rowNum]['civicrm_participant_participant_fee_level'] = $feeLevel;
        }
        $entryFound = TRUE;
      }

      // Convert display name to link
      $displayName = CRM_Utils_Array::value('civicrm_contact_sort_name_linked', $row);
      $cid = CRM_Utils_Array::value('civicrm_contact_id', $row);
      $id = CRM_Utils_Array::value('civicrm_participant_participant_record', $row);

      if ($displayName && $cid && $id) {
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
        $countryId = $row['civicrm_address_country_id'];
        if ($countryId) {
          $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($countryId, TRUE);
        }
        $entryFound = TRUE;
       }

      // Handle state/province id
      if (array_key_exists('civicrm_address_state_province_id', $row)) {
        $provinceId = $row['civicrm_address_state_province_id'];
        if ($provinceId) {
          $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince($provinceId, TRUE);
        }
        $entryFound = TRUE;
      }

      // Handle employer id
      if (array_key_exists('civicrm_contact_employer_id', $row)) {
       $employerId = $row['civicrm_contact_employer_id'];
        if ($employerId) {
          $rows[$rowNum]['civicrm_contact_employer_id'] = CRM_Contact_BAO_Contact::displayName($employerId);
          $url = CRM_Utils_System::url('civicrm/contact/view',
                 'reset=1&cid=' . $employerId, $this->_absoluteUrl
          );
          $rows[$rowNum]['civicrm_contact_employer_id_link'] = $url;
          $rows[$rowNum]['civicrm_contact_employer_id_hover'] = ts('View Contact Summary for this Contact.');
        }
      }

      // Convert campaign_id to campaign title
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_campaign_id', $rowNum, $this->activeCampaigns);

      // handle contribution status
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_contribution_status_id', $rowNum, $contributionStatus);

      // handle payment instrument
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_payment_instrument_id', $rowNum, $paymentInstruments);

      // handle financial type
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_financial_type_id', $rowNum, $financialTypes);

      // handle gender id
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contact_gender_id', $rowNum, $genders);

      // display birthday in the configured custom format
      if (array_key_exists('civicrm_contact_birth_date', $row)) {
        $birthDate = $row['civicrm_contact_birth_date'];
        if ($birthDate) {
          $rows[$rowNum]['civicrm_contact_birth_date'] = CRM_Utils_Date::customFormat($birthDate, '%Y%m%d');
        }
        $entryFound = TRUE;
      }

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}
