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
class CRM_Report_Form_Extended extends CRM_Report_Form {
  protected $_addressField = FALSE;

  protected $_emailField = FALSE;

  protected $_summary = NULL;

  protected $_customGroupExtends = array();
  protected $_baseTable = 'civicrm_contact';

  /**
   *
   */
  /**
   *
   */
  function __construct() {
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    parent::select();
  }


  /*
   * From clause build where baseTable & fromClauses are defined
   */
  function from() {
    if (!empty($this->_baseTable)) {
      $this->buildACLClause($this->_aliases['civicrm_contact']);
      $this->_from = "FROM {$this->_baseTable}   {$this->_aliases[$this->_baseTable]}";
      $availableClauses = $this->getAvailableJoins();
      foreach ($this->fromClauses() as $fromClause) {
        $fn = $availableClauses[$fromClause]['callback'];
        $this->$fn();
      }
      if (strstr($this->_from, 'civicrm_contact')) {
        $this->_from .= $this->_aclFrom;
      }
    }
  }

  /*
   * Define any from clauses in use (child classes to override)
   */
  /**
   * @return array
   */
  function fromClauses() {
    return array();
  }

  function groupBy() {
    parent::groupBy();
    //@todo - need to re-visit this - bad behaviour from pa
    if ($this->_groupBy == 'GROUP BY') {
      $this->_groupBY = NULL;
    }
    // if a stat field has been selected the do a group by
    if (!empty($this->_statFields) && empty($this->_groupBy)) {
      $this->_groupBy[] = $this->_aliases[$this->_baseTable] . ".id";
    }
    //@todo - this should be in the parent function or at parent level - perhaps build query should do this?
    if (!empty($this->_groupBy) && is_array($this->_groupBy)) {
      $this->_groupBy = 'GROUP BY ' . implode(',', $this->_groupBy);
    }
  }

  function orderBy() {
    parent::orderBy();
  }

  /**
   * @param $rows
   *
   * @return array
   */
  function statistics(&$rows) {
    return parent::statistics($rows);
  }

  function postProcess() {
    if (!empty($this->_aclTable) && !empty($this->_aliases[$this->_aclTable])) {
      $this->buildACLClause($this->_aliases[$this->_aclTable]);
    }
    parent::postProcess();
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    parent::alterDisplay($rows);

    //THis is all generic functionality which can hopefully go into the parent class
    // it introduces the option of defining an alter display function as part of the column definition
    // @tod tidy up the iteration so it happens in this function
    list($firstRow) = $rows;
    // no result to alter
    if (empty($firstRow)) {
      return;
    }
    $selectedFields = array_keys($firstRow);

    $alterfunctions = $altermap = array();
    foreach ($this->_columns as $tablename => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $field => $specs) {
          if (in_array($tablename . '_' . $field, $selectedFields) && array_key_exists('alter_display', $specs)) {
            $alterfunctions[$tablename . '_' . $field] = $specs['alter_display'];
            $altermap[$tablename . '_' . $field] = $field;
          }
        }
      }
    }
    if (empty($alterfunctions)) {
      // - no manipulation to be done
      return;
    }

    foreach ($rows as $index => & $row) {
      foreach ($row as $selectedfield => $value) {
        if (array_key_exists($selectedfield, $alterfunctions)) {
          $rows[$index][$selectedfield] = $this->$alterfunctions[$selectedfield]($value, $row, $selectedfield, $altermap[$selectedfield]);
        }
      }
    }
  }

  /**
   * @return array
   */
  function getLineItemColumns() {
    return array(
      'civicrm_line_item' =>
      array(
        'dao' => 'CRM_Price_BAO_LineItem',
        'fields' =>
        array(
          'qty' =>
          array('title' => ts('Quantity'),
            'type' => CRM_Utils_Type::T_INT,
            'statistics' =>
            array('sum' => ts('Total Quantity Selected')),
          ),
          'unit_price' =>
          array('title' => ts('Unit Price'),
          ),
          'line_total' =>
          array('title' => ts('Line Total'),
            'type' => CRM_Utils_Type::T_MONEY,
            'statistics' =>
            array('sum' => ts('Total of Line Items')),
          ),
        ),
        'participant_count' =>
        array('title' => ts('Participant Count'),
          'statistics' =>
          array('sum' => ts('Total Participants')),
        ),
        'filters' =>
        array(
          'qty' =>
          array('title' => ts('Quantity'),
            'type' => CRM_Utils_Type::T_INT,
            'operator' => CRM_Report_Form::OP_INT,
          ),
        ),
        'group_bys' =>
        array(
          'price_field_id' =>
          array('title' => ts('Price Field'),
          ),
          'price_field_value_id' =>
          array('title' => ts('Price Field Option'),
          ),
          'line_item_id' =>
          array('title' => ts('Individual Line Item'),
            'name' => 'id',
        ),
      ),
      ),
    );
  }

  /**
   * @return array
   */
  function getPriceFieldValueColumns() {
    return array(
      'civicrm_price_field_value' =>
      array(
        'dao' => 'CRM_Price_BAO_PriceFieldValue',
        'fields' => array(
          'price_field_value_label' =>
          array('title' => ts('Price Field Value Label'),
            'name' => 'label',
          ),
        ),
        'filters' =>
        array(
          'price_field_value_label' =>
          array('title' => ts('Price Fields Value Label'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
            'name' => 'label',
          ),
        ),
        'order_bys' =>
        array(
          'label' =>
          array('title' => ts('Price Field Value Label'),
          ),
        ),
        'group_bys' =>
        //note that we have a requirement to group by label such that all 'Promo book' lines
        // are grouped together across price sets but there may be a separate need to group
        // by id so that entries in one price set are distinct from others. Not quite sure what
        // to call the distinction for end users benefit
        array(
          'price_field_value_label' =>
          array('title' => ts('Price Field Value Label'),
            'name' => 'label',
          ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getPriceFieldColumns() {
    return array(
      'civicrm_price_field' =>
      array(
        'dao' => 'CRM_Price_BAO_PriceField',
        'fields' =>
        array(
          'price_field_label' =>
          array('title' => ts('Price Field Label'),
            'name' => 'label',
          ),
        ),
        'filters' =>
        array(
          'price_field_label' =>
          array('title' => ts('Price Field Label'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
            'name' => 'label',
          ),
        ),
        'order_bys' =>
        array(
          'price_field_label' =>
          array('title' => ts('Price Field Label'),
                'name' => 'label',
          ),
        ),
        'group_bys' =>
        array(
          'price_field_label' =>
          array('title' => ts('Price Field Label'),
            'name' => 'label',
          ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getParticipantColumns() {
    static $_events = array();
    if (!isset($_events['all'])) {
      CRM_Core_PseudoConstant::populate($_events['all'], 'CRM_Event_DAO_Event', FALSE, 'title', 'is_active', "is_template IS NULL OR is_template = 0", 'end_date DESC');
    }
    return array(
      'civicrm_participant' =>
      array(
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' =>
        array('participant_id' => array('title' => 'Participant ID'),
          'participant_record' => array(
            'name' => 'id',
            'title' => 'Participant Id',
          ),
          'event_id' => array('title' => ts('Event ID'),
            'type' => CRM_Utils_Type::T_STRING,
            'alter_display' => 'alterEventID',
          ),
          'status_id' => array('title' => ts('Status'),
            'alter_display' => 'alterParticipantStatus',
          ),
          'role_id' => array('title' => ts('Role'),
            'alter_display' => 'alterParticipantRole',
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
        'order_bys' =>
        array(
          'event_id' =>
          array('title' => ts('Event'), 'default_weight' => '1', 'default_order' => 'ASC'),
        ),
        'group_bys' =>
        array(
          'event_id' =>
          array('title' => ts('Event')),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getMembershipColumns() {
    return array(
      'civicrm_membership' => array(
        'dao' => 'CRM_Member_DAO_Membership',
        'grouping' => 'member-fields',
        'fields' => array(
          'membership_type_id' => array(
            'title' => 'Membership Type',
            'required' => TRUE,
            'alter_display' => 'alterMembershipTypeID',
          ),
          'status_id' => array(
            'title' => 'Membership Status',
            'required' => TRUE,
            'alter_display' => 'alterMembershipStatusID',
          ),
          'join_date' => NULL,
          'start_date' => array(
            'title' => ts('Current Cycle Start Date'),
          ),
          'end_date' => array(
            'title' => ts('Current Membership Cycle End Date'),
          ),
        ),
        'group_bys' => array(
          'membership_type_id' => array(
            'title' => ts('Membership Type'),
          ),
        ),
        'filters' => array(
          'join_date' => array(
            'type' => CRM_Utils_Type::T_DATE,
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getMembershipTypeColumns() {
    return array(
      'civicrm_membership_type' => array(
        'dao' => 'CRM_Member_DAO_MembershipType',
        'grouping' => 'member-fields',
        'filters' => array(
          'gid' => array(
            'name' => 'id',
            'title' => ts('Membership Types'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'type' => CRM_Utils_Type::T_INT + CRM_Utils_Type::T_ENUM,
            'options' => CRM_Member_PseudoConstant::membershipType(),
          ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getEventColumns() {
    return array(
      'civicrm_event' => array(
        'dao' => 'CRM_Event_DAO_Event',
        'fields' =>
        array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'title' => array('title' => ts('Event Title'),
            'required' => TRUE,
          ),
          'event_type_id' => array('title' => ts('Event Type'),
            'required' => TRUE,
            'alter_display' => 'alterEventType',
          ),
          'fee_label' => array('title' => ts('Fee Label')),
          'event_start_date' => array('title' => ts('Event Start Date'),
        ),
          'event_end_date' => array('title' => ts('Event End Date')),
          'max_participants' => array('title' => ts('Capacity'),
            'type' => CRM_Utils_Type::T_INT,
          ),
        ),
        'grouping' => 'event-fields',
        'filters' => array(
          'event_type_id' => array(
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ),
          'event_title' => array(
            'name' => 'title',
            'title' => ts('Event Title'),
            'operatorType' => CRM_Report_Form::OP_STRING,
          ),
        ),
        'order_bys' => array(
          'event_type_id' => array(
            'title' => ts('Event Type'),
            'default_weight' => '2',
            'default_order' => 'ASC',
          ),
        ),
        'group_bys' => array(
          'event_type_id' => array(
          'title' => ts('Event Type'),
      ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getContributionColumns() {
    return array(
      'civicrm_contribution' =>
      array(
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' =>
        array(
          'contribution_id' => array(
            'name' => 'id',
          ),
          'financial_type_id' => array('title' => ts('Financial Type'),
            'default' => TRUE,
            'alter_display' => 'alterContributionType',
          ),
          'payment_instrument_id' => array('title' => ts('Payment Instrument'),
            'alter_display' => 'alterPaymentType',
          ),
          'source' => array('title' => 'Contribution Source'),
          'trxn_id' => NULL,
          'receive_date' => array('default' => TRUE),
          'receipt_date' => NULL,
          'fee_amount' => NULL,
          'net_amount' => NULL,
          'total_amount' => array('title' => ts('Amount'),
            'statistics' =>
            array('sum' => ts('Total Amount')),
            'type' => CRM_Utils_Type::T_MONEY,
          ),
        ),
        'filters' =>
        array(
          'receive_date' =>
          array('operatorType' => CRM_Report_Form::OP_DATE),
          'financial_type_id' =>
          array('title' => ts('Financial Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ),
          'payment_instrument_id' =>
          array('title' => ts('Payment Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ),
          'contribution_status_id' =>
          array('title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::contributionStatus(),
          ),
          'total_amount' =>
          array('title' => ts('Contribution Amount')),
        ),
        'order_bys' =>
        array(
          'payment_instrument_id' =>
          array('title' => ts('Payment Instrument'),
          ),
         'financial_type_id' =>
          array('title' => ts('Financial Type'),
        ),
        ),
        'group_bys' =>
        array('financial_type_id' =>
          array('title' => ts('Financial Type')),
          'payment_instrument_id' =>
          array('title' => ts('Payment Instrument')),
          'contribution_id' =>
          array('title' => ts('Individual Contribution'),
            'name' => 'id',
        ),
          'source' => array('title' => 'Contribution Source'),
        ),
        'grouping' => 'contribution-fields',
      ),
    );
  }

  /**
   * @return array
   */
  function getContactColumns() {
    return array(
      'civicrm_contact' => array(
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array(
          'display_name' => array(
            'title' => ts('Contact Name'),
          ),
          'id' => array(
            'title' => ts('Contact ID'),
            'alter_display' => 'alterContactID',
          ),
          'first_name' => array(
            'title' => ts('First Name'),
          ),
          'last_name' => array(
            'title' => ts('Last Name'),
          ),
          'nick_name' => array(
            'title' => ts('Nickname'),
            'alter_display' => 'alterNickname',
          ),
        ),
        'filters' => array(
          'id' => array(
            'title' => ts('Contact ID'),
          )
          ,
          'sort_name' => array(
            'title' => ts('Contact Name'),
          ),
        ),
        'grouping' => 'contact-fields',
        'order_bys' => array(
          'sort_name' => array(
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ),
        ),
      ),
    );
  }

  /**
   * @return array
   */
  function getCaseColumns() {
    return array(
      'civicrm_case' => array(
        'dao' => 'CRM_Case_DAO_Case',
        'fields' => array(
          'id' => array(
            'title' => ts('Case ID'),
            'required' => false
          ),
          'subject' => array(
            'title' => ts('Case Subject'),
            'default' => true
          ),
          'status_id' => array(
            'title' => ts('Status'),
            'default' => true
          ),
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'default' => true
          ),
          'case_start_date' => array(
            'title' => ts('Case Start Date'),
            'name' => 'start_date',
            'default' => true
          ),
          'case_end_date' => array(
            'title' => ts('Case End Date'),
            'name' => 'end_date',
            'default' => true
          ),
          'case_duration' => array(
            'name' => 'duration',
            'title' => ts('Duration (Days)'),
            'default' => false
          ),
          'case_is_deleted' => array(
            'name' => 'is_deleted',
            'title' => ts('Case Deleted?'),
            'default' => false,
            'type' => CRM_Utils_Type::T_INT
          )
        ),
        'filters' => array(
          'case_start_date' => array(
            'title' => ts('Case Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
            'name' => 'start_date',
          ),
          'case_end_date' => array(
            'title' => ts('Case End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
            'name' => 'end_date'
          ),
          'case_type_id' => array(
            'title' => ts('Case Type'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_types
          ),
          'case_status_id' => array(
            'title' => ts('Case Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->case_statuses,
            'name' => 'status_id'
          ),
          'case_is_deleted' => array(
            'title' => ts('Case Deleted?'),
            'type' => CRM_Report_Form::OP_INT,
            'operatorType' => CRM_Report_Form::OP_SELECT,
            'options' => $this->deleted_labels,
            'default' => 0,
            'name' => 'is_deleted'
          )
        )
      )
    );
  }

  /*
   * function for adding address fields to construct function in reports
   * @param array $options Options for the report
   * - prefix prefix to add (e.g. 'honor' when getting address details for honor contact
   * - prefix_label optional prefix lable eg. "Honoree " for front end
   * - group_by enable these fields for group by - default false
   * - order_by enable these fields for order by
   * - filters enable these fields for filtering
   * - defaults - (is this working?) values to pre-populate
   * @return array address fields for construct clause
   */
  /**
   * Get address columns to add to array
   * @param array $options
   *  - prefix Prefix to add to table (in case of more than one instance of the table)
   *  - prefix_label Label to give columns from this address table instance
   * @return array address columns definition
   */
  /**
   * @param array $options
   *
   * @return array
   */
  function getAddressColumns($options = array()) {
    $defaultOptions = array(
      'prefix' => '',
      'prefix_label' => '',
      'group_by' => false,
      'order_by' => true,
      'filters' => true,
      'defaults' => array(
        'country_id' => TRUE
      ),
     );

    $options = array_merge($defaultOptions,$options);

    $addressFields = array(
      $options['prefix'] . 'civicrm_address' => array(
        'dao' => 'CRM_Core_DAO_Address',
        'name' => 'civicrm_address',
        'alias' => $options['prefix'] . 'civicrm_address',
        'fields' => array(
          $options['prefix'] . 'name' => array(
            'title' => ts($options['prefix_label'] . 'Address Name'),
            'default' => CRM_Utils_Array::value('name', $options['defaults'], FALSE),
            'name' => 'name',
          ),
          $options['prefix'] . 'street_address' => array(
            'title' => ts($options['prefix_label'] . 'Street Address'),
            'default' => CRM_Utils_Array::value('street_address', $options['defaults'], FALSE),
            'name' => 'street_address',
          ),
          $options['prefix'] . 'supplemental_address_1' => array(
            'title' => ts($options['prefix_label'] . 'Supplementary Address Field 1'),
            'default' => CRM_Utils_Array::value('supplemental_address_1', $options['defaults'], FALSE),
            'name' => 'supplemental_address_1',
          ),
          $options['prefix'] . 'supplemental_address_2' => array(
            'title' => ts($options['prefix_label'] . 'Supplementary Address Field 2'),
            'default' => CRM_Utils_Array::value('supplemental_address_2', $options['defaults'], FALSE),
            'name' => 'supplemental_address_2',
          ),
          $options['prefix'] . 'street_number' => array(
            'name' => 'street_number',
            'title' => ts($options['prefix_label'] . 'Street Number'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_number', $options['defaults'], FALSE),
            'name' => 'street_number',
          ),
          $options['prefix'] . 'street_name' => array(
            'name' => 'street_name',
            'title' => ts($options['prefix_label'] . 'Street Name'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_name', $options['defaults'], FALSE),
            'name' => 'street_name',
          ),
          $options['prefix'] . 'street_unit' => array(
            'name' => 'street_unit',
            'title' => ts($options['prefix_label'] . 'Street Unit'),
            'type' => 1,
            'default' => CRM_Utils_Array::value('street_unit', $options['defaults'], FALSE),
            'name' => 'street_unit',
          ),
          $options['prefix'] . 'city' => array(
            'title' => ts($options['prefix_label'] . 'City'),
            'default' => CRM_Utils_Array::value('city', $options['defaults'], FALSE),
            'name' => 'city',
          ),
          $options['prefix'] . 'postal_code' => array(
            'title' => ts($options['prefix_label'] . 'Postal Code'),
            'default' => CRM_Utils_Array::value('postal_code', $options['defaults'], FALSE),
            'name' => 'postal_code',
          ),
          $options['prefix'] . 'county_id' => array(
            'title' => ts($options['prefix_label'] . 'County'),
            'default' => CRM_Utils_Array::value('county_id', $options['defaults'], FALSE),
            'alter_display' => 'alterCountyID',
            'name' => 'county_id',
          ),
          $options['prefix'] . 'state_province_id' => array(
            'title' => ts($options['prefix_label'] . 'State/Province'),
            'default' => CRM_Utils_Array::value('state_province_id', $options['defaults'], FALSE),
            'alter_display' => 'alterStateProvinceID',
            'name' =>  'state_province_id',
          ),
          $options['prefix'] . 'country_id' => array(
            'title' => ts($options['prefix_label'] . 'Country'),
            'default' => CRM_Utils_Array::value('country_id', $options['defaults'], FALSE),
            'alter_display' => 'alterCountryID',
            'name' => 'country_id',
          ),
        ),
        'grouping' => 'location-fields',
      ),
    );

    if ($options['filters']) {
      $addressFields[$options['prefix'] .'civicrm_address']['filters'] = array(
        $options['prefix'] . 'street_number' => array(
          'title' => ts($options['prefix_label'] . 'Street Number'),
          'type' => 1,
          'name' => 'street_number',
        ),
        $options['prefix'] . 'street_name' => array(
          'title' => ts($options['prefix_label'] . 'Street Name'),
          'name' => $options['prefix'] . 'street_name',
          'operator' => 'like',
        ),
        $options['prefix'] . 'postal_code' => array(
          'title' => ts($options['prefix_label'] . 'Postal Code'),
          'type' => 1,
          'name' => 'postal_code',
        ),
        $options['prefix'] . 'city' => array(
          'title' => ts($options['prefix_label'] . 'City'),
          'operator' => 'like',
          'name' => 'city',
        ),
        $options['prefix'] . 'county_id' => array(
          'name' => 'county_id',
          'title' => ts($options['prefix_label'] . 'County'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::county(),
        ),
        $options['prefix'] . 'state_province_id' => array(
          'name' => 'state_province_id',
          'title' => ts($options['prefix_label'] . 'State/Province'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::stateProvince(),
        ),
        $options['prefix'] . 'country_id' => array(
          'name' => 'country_id',
          'title' => ts($options['prefix_label'] . 'Country'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::country(),
        ),
      );
    }

    if ($options['order_by']) {
      $addressFields[$options['prefix'] . 'civicrm_address']['order_bys'] = array(
        $options['prefix'] . 'street_name' => array(
          'title' => ts($options['prefix_label'] . 'Street Name'),
          'name' => 'street_name',
        ),
        $options['prefix'] . 'street_number' => array(
          'title' => ts($options['prefix_label'] . 'Odd / Even Street Number'),
          'name' => 'street_number',
        ),
        $options['prefix'] . 'street_address' => array(
          'title' => ts($options['prefix_label'] . 'Street Address'),
          'name' => 'street_address',
        ),
        $options['prefix'] . 'city' => array(
          'title' => ts($options['prefix_label'] . 'City'),
          'name' => 'city',
        ),
        $options['prefix'] . 'postal_code' => array(
          'title' => ts($options['prefix_label'] . 'Post Code'),
          'name' => 'postal_code',
        ),
      );
    }

    if ($options['group_by']) {
      $addressFields['civicrm_address']['group_bys'] = array(
        $options['prefix'] . 'street_address' => array(
          'title' => ts($options['prefix_label'] . 'Street Address'),
          'name' => 'street_address',
        ),
        $options['prefix'] . 'city' => array(
          'title' => ts($options['prefix_label'] . 'City'),
          'name' => 'city',
        ),
        $options['prefix'] . 'postal_code' => array(
          'title' => ts($options['prefix_label'] . 'Post Code'),
          'name' => 'postal_code',
        ),
        $options['prefix'] . 'state_province_id' => array(
          'title' => ts($options['prefix_label'] . 'State/Province'),
          'name' => 'state_province_id',
        ),
        $options['prefix'] . 'country_id' => array(
          'title' => ts($options['prefix_label'] . 'Country'),
          'name' => 'country_id',
        ),
        $options['prefix'] . 'county_id' => array(
          'title' => ts($options['prefix_label'] . 'County'),
          'name' => 'county_id',
        ),
      );
    }
    return $addressFields;
  }

  /*
   * Get Information about advertised Joins
   */
  /**
   * @return array
   */
  function getAvailableJoins() {
    return array(
      'priceFieldValue_from_lineItem' => array(
        'leftTable' => 'civicrm_line_item',
        'rightTable' => 'civicrm_price_field_value',
        'callback' => 'joinPriceFieldValueFromLineItem',
      ),
      'priceField_from_lineItem' => array(
        'leftTable' => 'civicrm_line_item',
        'rightTable' => 'civicrm_price_field',
        'callback' => 'joinPriceFieldFromLineItem',
      ),
      'participant_from_lineItem' => array(
        'leftTable' => 'civicrm_line_item',
        'rightTable' => 'civicrm_participant',
        'callback' => 'joinParticipantFromLineItem',
      ),
      'contribution_from_lineItem' => array(
        'leftTable' => 'civicrm_line_item',
        'rightTable' => 'civicrm_contribution',
        'callback' => 'joinContributionFromLineItem',
      ),
      'membership_from_lineItem' => array(
        'leftTable' => 'civicrm_line_item',
        'rightTable' => 'civicrm_membership',
        'callback' => 'joinMembershipFromLineItem',
      ),
      'contribution_from_participant' => array(
        'leftTable' => 'civicrm_participant',
        'rightTable' => 'civicrm_contribution',
        'callback' => 'joinContributionFromParticipant',
      ),
      'contribution_from_membership' => array(
        'leftTable' => 'civicrm_membership',
        'rightTable' => 'civicrm_contribution',
        'callback' => 'joinContributionFromMembership',
      ),
      'membership_from_contribution' => array(
        'leftTable' => 'civicrm_contribution',
        'rightTable' => 'civicrm_membership',
        'callback' => 'joinMembershipFromContribution',
      ),
      'membershipType_from_membership' => array(
        'leftTable' => 'civicrm_membership',
        'rightTable' => 'civicrm_membership_type',
        'callback' => 'joinMembershipTypeFromMembership',
      ),
      'lineItem_from_contribution' => array(
        'leftTable' => 'civicrm_contribution',
        'rightTable' => 'civicrm_line_item',
        'callback' => 'joinLineItemFromContribution',
      ),
      'lineItem_from_membership' => array(
        'leftTable' => 'civicrm_membership',
        'rightTable' => 'civicrm_line_item',
        'callback' => 'joinLineItemFromMembership',
      ),
      'contact_from_participant' => array(
        'leftTable' => 'civicrm_participant',
        'rightTable' => 'civicrm_contact',
        'callback' => 'joinContactFromParticipant',
      ),
      'contact_from_membership' => array(
        'leftTable' => 'civicrm_membership',
        'rightTable' => 'civicrm_contact',
        'callback' => 'joinContactFromMembership',
      ),
      'contact_from_contribution' => array(
        'leftTable' => 'civicrm_contribution',
        'rightTable' => 'civicrm_contact',
        'callback' => 'joinContactFromContribution',
      ),
      'event_from_participant' => array(
        'leftTable' => 'civicrm_participant',
        'rightTable' => 'civicrm_event',
        'callback' => 'joinEventFromParticipant',
      ),
      'address_from_contact' => array(
        'leftTable' => 'civicrm_contact',
        'rightTable' => 'civicrm_address',
        'callback' => 'joinAddressFromContact',
      ),
    );
  }

  /*
   * Add join from contact table to address. Prefix will be added to both tables
   * as it's assumed you are using it to get address of a secondary contact
   */
  /**
   * @param string $prefix
   */
  function joinAddressFromContact( $prefix = '') {
    $this->_from .= " LEFT JOIN civicrm_address {$this->_aliases[$prefix . 'civicrm_address']}
      ON {$this->_aliases[$prefix . 'civicrm_address']}.contact_id = {$this->_aliases[$prefix . 'civicrm_contact']}.id";
  }

  function joinPriceFieldValueFromLineItem() {
    $this->_from .= " LEFT JOIN civicrm_price_field_value {$this->_aliases['civicrm_price_field_value']}
                          ON {$this->_aliases['civicrm_line_item']}.price_field_value_id = {$this->_aliases['civicrm_price_field_value']}.id";
  }

  function joinPriceFieldFromLineItem() {
    $this->_from .= "
       LEFT JOIN civicrm_price_field {$this->_aliases['civicrm_price_field']}
      ON {$this->_aliases['civicrm_line_item']}.price_field_id = {$this->_aliases['civicrm_price_field']}.id
     ";
  }

  /*
   * Define join from line item table to participant table
   */
  function joinParticipantFromLineItem() {
    $this->_from .= " LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
      ON ( {$this->_aliases['civicrm_line_item']}.entity_id = {$this->_aliases['civicrm_participant']}.id
      AND {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_participant')
    ";
  }

  /*
   * Define join from line item table to Membership table. Seems to be still via contribution
   * as the entity. Have made 'inner' to restrict does that make sense?
   */
  function joinMembershipFromLineItem() {
    $this->_from .= " INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
      ON ( {$this->_aliases['civicrm_line_item']}.entity_id = {$this->_aliases['civicrm_contribution']}.id
      AND {$this->_aliases['civicrm_line_item']}.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_membership_payment pp
      ON {$this->_aliases['civicrm_contribution']}.id = pp.contribution_id
      LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
      ON pp.membership_id = {$this->_aliases['civicrm_membership']}.id
    ";
  }

  /*
   * Define join from Participant to Contribution table
   */
  function joinContributionFromParticipant() {
    $this->_from .= " LEFT JOIN civicrm_participant_payment pp
        ON {$this->_aliases['civicrm_participant']}.id = pp.participant_id
        LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON pp.contribution_id = {$this->_aliases['civicrm_contribution']}.id
      ";
  }

  /*
   * Define join from Membership to Contribution table
   */
  function joinContributionFromMembership() {
    $this->_from .= " LEFT JOIN civicrm_membership_payment pp
        ON {$this->_aliases['civicrm_membership']}.id = pp.membership_id
        LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON pp.contribution_id = {$this->_aliases['civicrm_contribution']}.id
      ";
  }

  function joinParticipantFromContribution() {
    $this->_from .= " LEFT JOIN civicrm_participant_payment pp
                          ON {$this->_aliases['civicrm_contribution']}.id = pp.contribution_id
        LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
                          ON pp.participant_id = {$this->_aliases['civicrm_participant']}.id";
  }

  function joinMembershipFromContribution() {
    $this->_from .= "
       LEFT JOIN civicrm_membership_payment pp
      ON {$this->_aliases['civicrm_contribution']}.id = pp.contribution_id
      LEFT JOIN civicrm_membership {$this->_aliases['civicrm_membership']}
      ON pp.membership_id = {$this->_aliases['civicrm_membership']}.id";
  }

  function joinMembershipTypeFromMembership() {
    $this->_from .= "
       LEFT JOIN civicrm_membership_type {$this->_aliases['civicrm_membership_type']}
      ON {$this->_aliases['civicrm_membership']}.membership_type_id = {$this->_aliases['civicrm_membership_type']}.id
      ";
  }

  function joinContributionFromLineItem() {

    // this can be stored as a temp table & indexed for more speed. Not done at this state.
    // another option is to cache it but I haven't tried to put that code in yet (have used it before for one hour caching
    $this->_from .= "  LEFT JOIN (SELECT line_item_civireport.id as lid, contribution_civireport_direct.*
FROM civicrm_line_item line_item_civireport
LEFT JOIN civicrm_contribution contribution_civireport_direct
                       ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = contribution_civireport_direct.id AND line_item_civireport.entity_table = 'civicrm_contribution')


WHERE  contribution_civireport_direct.id IS NOT NULL

UNION SELECT line_item_civireport.id as lid, contribution_civireport.*
  FROM civicrm_line_item line_item_civireport
  LEFT JOIN civicrm_participant participant_civireport
                          ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = participant_civireport.id AND line_item_civireport.entity_table = 'civicrm_participant')

LEFT JOIN civicrm_participant_payment pp
                          ON participant_civireport.id = pp.participant_id
        LEFT JOIN civicrm_contribution contribution_civireport
                          ON pp.contribution_id = contribution_civireport.id

UNION SELECT line_item_civireport.id as lid,contribution_civireport.*
  FROM civicrm_line_item line_item_civireport
  LEFT JOIN civicrm_membership membership_civireport
                          ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id =membership_civireport.id AND line_item_civireport.entity_table = 'civicrm_membership')

LEFT JOIN civicrm_membership_payment pp
                          ON membership_civireport.id = pp.membership_id
        LEFT JOIN civicrm_contribution contribution_civireport
                          ON pp.contribution_id = contribution_civireport.id
) as {$this->_aliases['civicrm_contribution']}
  ON {$this->_aliases['civicrm_contribution']}.lid = {$this->_aliases['civicrm_line_item']}.id
 ";
  }

  function joinLineItemFromContribution() {

    // this can be stored as a temp table & indexed for more speed. Not done at this stage.
    // another option is to cache it but I haven't tried to put that code in yet (have used it before for one hour caching
    $this->_from .= "
       LEFT JOIN (
SELECT contribution_civireport_direct.id AS contid, line_item_civireport.*
FROM civicrm_contribution contribution_civireport_direct
LEFT JOIN civicrm_line_item line_item_civireport ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = contribution_civireport_direct.id AND line_item_civireport.entity_table = 'civicrm_contribution')
WHERE line_item_civireport.id IS NOT NULL

UNION
SELECT contribution_civireport_direct.id AS contid, line_item_civireport.*
FROM civicrm_contribution contribution_civireport_direct
LEFT JOIN civicrm_participant_payment pp ON contribution_civireport_direct.id = pp.contribution_id
LEFT JOIN civicrm_participant p ON pp.participant_id = p.id
LEFT JOIN civicrm_line_item line_item_civireport ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = p.id AND line_item_civireport.entity_table = 'civicrm_participant')
WHERE line_item_civireport.id IS NOT NULL

UNION

SELECT contribution_civireport_direct.id AS contid, line_item_civireport.*
FROM civicrm_contribution contribution_civireport_direct
LEFT JOIN civicrm_membership_payment pp ON contribution_civireport_direct.id = pp.contribution_id
LEFT JOIN civicrm_membership p ON pp.membership_id = p.id
LEFT JOIN civicrm_line_item line_item_civireport ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = p.id AND line_item_civireport.entity_table = 'civicrm_membership')
WHERE   line_item_civireport.id IS NOT NULL
) as {$this->_aliases['civicrm_line_item']}
  ON {$this->_aliases['civicrm_line_item']}.contid = {$this->_aliases['civicrm_contribution']}.id


  ";
  }

  function joinLineItemFromMembership() {

    // this can be stored as a temp table & indexed for more speed. Not done at this stage.
    // another option is to cache it but I haven't tried to put that code in yet (have used it before for one hour caching
    $this->_from .= "
       LEFT JOIN (
SELECT contribution_civireport_direct.id AS contid, line_item_civireport.*
FROM civicrm_contribution contribution_civireport_direct
LEFT JOIN civicrm_line_item line_item_civireport
ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = contribution_civireport_direct.id AND line_item_civireport.entity_table = 'civicrm_contribution')

WHERE   line_item_civireport.id IS NOT NULL

UNION

SELECT contribution_civireport_direct.id AS contid, line_item_civireport.*
FROM civicrm_contribution contribution_civireport_direct
LEFT JOIN civicrm_membership_payment pp ON contribution_civireport_direct.id = pp.contribution_id
LEFT JOIN civicrm_membership p ON pp.membership_id = p.id
LEFT JOIN civicrm_line_item line_item_civireport ON (line_item_civireport.line_total > 0 AND line_item_civireport.entity_id = p.id AND line_item_civireport.entity_table = 'civicrm_membership')
WHERE   line_item_civireport.id IS NOT NULL
) as {$this->_aliases['civicrm_line_item']}
  ON {$this->_aliases['civicrm_line_item']}.contid = {$this->_aliases['civicrm_contribution']}.id
  ";
  }

  function joinContactFromParticipant() {
    $this->_from .= " LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                          ON {$this->_aliases['civicrm_participant']}.contact_id = {$this->_aliases['civicrm_contact']}.id";
  }

  function joinContactFromMembership() {
    $this->_from .= " LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                          ON {$this->_aliases['civicrm_membership']}.contact_id = {$this->_aliases['civicrm_contact']}.id";
  }

  function joinContactFromContribution() {
    $this->_from .= " LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                          ON {$this->_aliases['civicrm_contribution']}.contact_id = {$this->_aliases['civicrm_contact']}.id";
  }

  function joinEventFromParticipant() {
    $this->_from .= "  LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND
                       ({$this->_aliases['civicrm_event']}.is_template IS NULL OR
                        {$this->_aliases['civicrm_event']}.is_template = 0)";
  }

  /*
    * Retrieve text for financial type from pseudoconstant
    */
  /**
   * @param $value
   * @param $row
   *
   * @return string
   */
  function alterNickName($value, &$row) {
    if(empty($row['civicrm_contact_id'])){
      return;
    }
    $contactID = $row['civicrm_contact_id'];
    return "<div id=contact-{$contactID} class='crm-entity'>
           <span class='crm-editable crmf-nick_name crm-editable-enabled' data-action='create'>
           " . $value . "</span></div>";
  }

  /*
   * Retrieve text for contribution type from pseudoconstant
   */
  /**
   * @param $value
   * @param $row
   *
   * @return array|string
   */
  function alterContributionType($value, &$row) {
    return is_string(CRM_Contribute_PseudoConstant::financialType($value, FALSE)) ? CRM_Contribute_PseudoConstant::financialType($value, FALSE) : '';
  }

  /*
   * Retrieve text for contribution status from pseudoconstant
   */
  /**
   * @param $value
   * @param $row
   *
   * @return array
   */
  function alterContributionStatus($value, &$row) {
    return CRM_Contribute_PseudoConstant::contributionStatus($value);
  }

  /*
   * Retrieve text for payment instrument from pseudoconstant
   */
  /**
   * @param $value
   * @param $row
   *
   * @return array
   */
  function alterEventType($value, &$row) {
    return CRM_Event_PseudoConstant::eventType($value);
  }

  /**
   * @param $value
   * @param $row
   *
   * @return array|string
   */
  function alterEventID($value, &$row) {
    return is_string(CRM_Event_PseudoConstant::event($value, FALSE)) ? CRM_Event_PseudoConstant::event($value, FALSE) : '';
  }

  /**
   * @param $value
   * @param $row
   *
   * @return array|string
   */
  function alterMembershipTypeID($value, &$row) {
    return is_string(CRM_Member_PseudoConstant::membershipType($value, FALSE)) ? CRM_Member_PseudoConstant::membershipType($value, FALSE) : '';
  }

  /**
   * @param $value
   * @param $row
   *
   * @return array|string
   */
  function alterMembershipStatusID($value, &$row) {
    return is_string(CRM_Member_PseudoConstant::membershipStatus($value, FALSE)) ? CRM_Member_PseudoConstant::membershipStatus($value, FALSE) : '';
  }

  /**
   * @param $value
   * @param $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return array
   */
  function alterCountryID($value, &$row, $selectedfield, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedfield . '_link'] = $url;
    $row[$selectedfield . '_hover'] = ts("%1 for this country.", array(1 => $value));
    $countries =  CRM_Core_PseudoConstant::country($value, FALSE);
    if(!is_array($countries)){
      return $countries;
    }
  }

  /**
   * @param $value
   * @param $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return array
   */
  function alterCountyID($value, &$row,$selectedfield, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedfield . '_link'] = $url;
    $row[$selectedfield . '_hover'] = ts("%1 for this county.", array(1 => $value));
    $counties = CRM_Core_PseudoConstant::county($value, FALSE);
    if(!is_array($counties)){
      return $counties;
    }
  }

  /**
   * @param $value
   * @param $row
   * @param $selectedfield
   * @param $criteriaFieldName
   *
   * @return array
   */
  function alterStateProvinceID($value, &$row, $selectedfield, $criteriaFieldName) {
    $url = CRM_Utils_System::url(CRM_Utils_System::currentPath(), "reset=1&force=1&{$criteriaFieldName}_op=in&{$criteriaFieldName}_value={$value}", $this->_absoluteUrl);
    $row[$selectedfield . '_link'] = $url;
    $row[$selectedfield . '_hover'] = ts("%1 for this state.", array(1 => $value));

    $states =  CRM_Core_PseudoConstant::stateProvince($value, FALSE);
    if(!is_array($states)){
      return $states;
    }
  }

  /**
   * @param $value
   * @param $row
   * @param $fieldname
   *
   * @return mixed
   */
  function alterContactID($value, &$row, $fieldname) {
    $row[$fieldname . '_link'] = CRM_Utils_System::url("civicrm/contact/view", 'reset=1&cid=' . $value, $this->_absoluteUrl);
    return $value;
  }

  /**
   * @param $value
   *
   * @return array
   */
  function alterParticipantStatus($value) {
    if (empty($value)) {
      return;
    }
    return CRM_Event_PseudoConstant::participantStatus($value, FALSE, 'label');
  }

  /**
   * @param $value
   *
   * @return string
   */
  function alterParticipantRole($value) {
    if (empty($value)) {
      return;
    }
    $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $value);
    $value = array();
    foreach ($roles as $role) {
      $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
    }
    return implode(', ', $value);
  }

  /**
   * @param $value
   *
   * @return mixed
   */
  function alterPaymentType($value) {
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();
    return $paymentInstruments[$value];
  }
}

