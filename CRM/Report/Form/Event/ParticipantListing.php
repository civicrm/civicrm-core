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
class CRM_Report_Form_Event_ParticipantListing extends CRM_Report_Form {

  protected $_summary = NULL;

  protected $_contribField = FALSE;
  protected $_groupFilter = TRUE;
  protected $_tagFilter = TRUE;
  protected $_balance = FALSE;

  protected $_customGroupExtends = [
    'Participant',
    'Contact',
    'Individual',
    'Event',
  ];

  public $_drilldownReport = ['event/income' => 'Link to Detail Report'];

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;

    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => array_merge([
          // CRM-17115 - to avoid changing report output at this stage re-instate
          // old field name for sort name
          'sort_name_linked' => [
            'title' => ts('Participant Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
            'dbAlias' => 'contact_civireport.sort_name',
          ],
        ],
          $this->getBasicContactFields(),
          [
            'age_at_event' => [
              'title' => ts('Age at Event'),
              'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, event_civireport.start_date)',
            ],
          ]
        ),
        'grouping' => 'contact-fields',
        'order_bys' => [
          'sort_name' => [
            'title' => ts('Last Name, First Name'),
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
          'first_name' => [
            'name' => 'first_name',
            'title' => ts('First Name'),
          ],
          'gender_id' => [
            'name' => 'gender_id',
            'title' => ts('Gender'),
          ],
          'birth_date' => [
            'name' => 'birth_date',
            'title' => ts('Birth Date'),
          ],
          'age_at_event' => [
            'name' => 'age_at_event',
            'title' => ts('Age at Event'),
          ],
          'contact_type' => [
            'title' => ts('Contact Type'),
          ],
          'contact_sub_type' => [
            'title' => ts('Contact Subtype'),
          ],
        ],
        'filters' => CRM_Report_Form::getBasicContactFilters(),
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => [
          'email' => [
            'title' => ts('Email'),
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
        'filters' => [
          'email' => [
            'title' => ts('Participant E-mail'),
            'operator' => 'like',
          ],
        ],
      ],
    ];
    $this->_columns += $this->getAddressColumns();
    $this->_columns += [
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
        'fields' => [
          'participant_id' => ['title' => ts('Participant ID')],
          'participant_record' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
          ],
          'event_id' => [
            'default' => TRUE,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'status_id' => [
            'title' => ts('Status'),
            'default' => TRUE,
          ],
          'role_id' => [
            'title' => ts('Role'),
            'default' => TRUE,
          ],
          'fee_currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'registered_by_id' => [
            'title' => ts('Registered by Participant ID'),
          ],
          'registered_by_name' => [
            'title' => ts('Registered by Participant Name'),
            'name' => 'registered_by_id',
          ],
          'created_id' => [
            'title' => ts('Registered by Contact ID'),
          ],
          'registered_by_contact_name' => [
            'title' => ts('Registered by Contact Name'),
            'name' => 'created_id',
          ],
          'source' => [
            'title' => ts('Participant Source'),
          ],
          'participant_fee_level' => NULL,
          'participant_fee_amount' => ['title' => ts('Participant Fee')],
          'participant_register_date' => ['title' => ts('Registration Date')],
          'total_paid' => [
            'title' => ts('Total Paid'),
            'dbAlias' => 'IFNULL(SUM(ft.total_amount), 0)',
            'type' => 1024,
          ],
          'balance' => [
            'title' => ts('Balance'),
            'dbAlias' => 'participant_civireport.fee_amount - IFNULL(SUM(ft.total_amount), 0)',
            'type' => 1024,
          ],
        ],
        'grouping' => 'event-fields',
        'filters' => [
          'event_id' => [
            'name' => 'event_id',
            'title' => ts('Event'),
            'operatorType' => CRM_Report_Form::OP_ENTITYREF,
            'type' => CRM_Utils_Type::T_INT,
            'attributes' => [
              'entity' => 'Event',
              'select' => ['minimumInputLength' => 0],
            ],
          ],
          'sid' => [
            'name' => 'status_id',
            'title' => ts('Participant Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantStatus(NULL, NULL, 'label'),
          ],
          'rid' => [
            'name' => 'role_id',
            'title' => ts('Participant Role'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Event_PseudoConstant::participantRole(),
          ],
          'participant_register_date' => [
            'title' => ts('Registration Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'fee_currency' => [
            'title' => ts('Fee Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'registered_by_id' => [
            'title' => ts('Registered by Participant ID'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'source' => [
            'title' => ts('Participant Source'),
            'type' => CRM_Utils_Type::T_STRING,
            'operator' => 'like',
          ],
          'is_test' => [
            'title' => ts('Is Test'),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'default' => 0,
          ],
        ],
        'order_bys' => [
          'participant_register_date' => [
            'title' => ts('Registration Date'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
          'event_id' => [
            'title' => ts('Event'),
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
        ],
      ],
      'civicrm_phone' => [
        'dao' => 'CRM_Core_DAO_Phone',
        'fields' => [
          'phone' => [
            'title' => ts('Phone'),
            'default' => TRUE,
            'no_repeat' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
        'fields' => [
          'event_type_id' => [
            'title' => ts('Event Type'),
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
          ],
          'event_end_date' => [
            'title' => ts('Event End Date'),
          ],
        ],
        'grouping' => 'event-fields',
        'filters' => [
          'eid' => [
            'name' => 'event_type_id',
            'title' => ts('Event Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('event_type'),
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'event_end_date' => [
            'title' => ts('Event End Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
        ],
        'order_bys' => [
          'event_type_id' => [
            'title' => ts('Event Type'),
            'default_weight' => '2',
            'default_order' => 'ASC',
          ],
          'event_start_date' => [
            'title' => ts('Event Start Date'),
          ],
        ],
      ],
      'civicrm_note' => [
        'dao' => 'CRM_Core_DAO_Note',
        'fields' => [
          'participant_note' => [
            'name' => 'note',
            'title' => ts('Participant Note'),
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'contribution_id' => [
            'name' => 'id',
            'no_display' => TRUE,
            'required' => TRUE,
            'csv_display' => TRUE,
            'title' => ts('Contribution ID'),
          ],
          'financial_type_id' => ['title' => ts('Financial Type')],
          'receive_date' => ['title' => ts('Payment Date')],
          'contribution_status_id' => ['title' => ts('Contribution Status')],
          'payment_instrument_id' => ['title' => ts('Payment Type')],
          'contribution_source' => [
            'name' => 'source',
            'title' => ts('Contribution Source'),
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'trxn_id' => NULL,
          'fee_amount' => ['title' => ts('Transaction Fee')],
          'net_amount' => NULL,
        ],
        'grouping' => 'contrib-fields',
        'filters' => [
          'receive_date' => [
            'title' => ts('Payment Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ],
          'currency' => [
            'title' => ts('Contribution Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'payment_instrument_id' => [
            'title' => ts('Payment Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::paymentInstrument(),
          ],
          'contribution_status_id' => [
            'title' => ts('Contribution Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search'),
            'default' => NULL,
          ],
        ],
      ],
      'civicrm_line_item' => [
        'dao' => 'CRM_Price_DAO_LineItem',
        'grouping' => 'priceset-fields',
        'filters' => [
          'price_field_value_id' => [
            'name' => 'price_field_value_id',
            'title' => ts('Fee Level'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->getPriceLevels(),
          ],
        ],
      ],
    ];

    $this->_options = [
      'blank_column_begin' => [
        'title' => ts('Blank column at the Begining'),
        'type' => 'checkbox',
      ],
      'blank_column_end' => [
        'title' => ts('Blank column at the End'),
        'type' => 'select',
        'options' => [
          '' => ts('-select-'),
          1 => ts('One'),
          2 => ts('Two'),
          3 => ts('Three'),
        ],
      ],
    ];

    // CRM-17115 avoid duplication of sort_name - would be better to standardise name
    // & behaviour across reports but trying for no change at this point.
    $this->_columns['civicrm_contact']['fields']['sort_name']['no_display'] = TRUE;

    // If we have campaigns enabled, add those elements to both the fields, filters and sorting
    $this->addCampaignFields('civicrm_participant', FALSE, TRUE);

    $this->_currencyColumn = 'civicrm_participant_fee_currency';
    parent::__construct();
  }

  /**
   * Searches database for priceset values.
   *
   * @return array
   */
  public function getPriceLevels() {
    $query = "
SELECT CONCAT(cv.label, ' (', ps.title, ' - ', cf.label , ')') label, cv.id
FROM civicrm_price_field_value cv
LEFT JOIN civicrm_price_field cf
  ON cv.price_field_id = cf.id
LEFT JOIN civicrm_price_set_entity ce
  ON ce.price_set_id = cf.price_set_id
LEFT JOIN civicrm_price_set ps
  ON ce.price_set_id = ps.id
WHERE ce.entity_table = 'civicrm_event'
ORDER BY  cv.label
";
    $dao = CRM_Core_DAO::executeQuery($query);
    $elements = [];
    while ($dao->fetch()) {
      $elements[$dao->id] = "$dao->label\n";
    }

    return $elements;
  }

  public function preProcess() {
    parent::preProcess();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];

    //add blank column at the Start
    if (array_key_exists('options', $this->_params) &&
      !empty($this->_params['options']['blank_column_begin'])
    ) {
      $select[] = " '' as blankColumnBegin";
      $this->_columnHeaders['blankColumnBegin']['title'] = '_ _ _ _';
    }
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            if ($tableName == 'civicrm_contribution') {
              $this->_contribField = TRUE;
            }
            if ($fieldName == 'total_paid' || $fieldName == 'balance') {
              $this->_balance = TRUE;
              // modify the select if filtered by fee_level as the from clause
              // already selects the total_amount from civicrm_contribution table
              if (!empty($this->_params['price_field_value_id_value'])) {
                $field['dbAlias'] = str_replace('SUM(ft.total_amount)', 'ft.total_amount', $field['dbAlias']);
              }
            }
            $alias = "{$tableName}_{$fieldName}";
            $select[] = "{$field['dbAlias']} as $alias";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['no_display'] = $field['no_display'] ?? NULL;
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
            $this->_selectAliases[] = $alias;
          }
        }
      }
    }
    //add blank column at the end
    $blankcols = $this->_params['blank_column_end'] ?? NULL;
    if ($blankcols) {
      for ($i = 1; $i <= $blankcols; $i++) {
        $select[] = " '' as blankColumnEnd_{$i}";
        $this->_columnHeaders["blank_{$i}"]['title'] = "_ _ _ _";
      }
    }

    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param self $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = [];
    return $errors;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_participant {$this->_aliases['civicrm_participant']}
             LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
                    ON ({$this->_aliases['civicrm_event']}.id = {$this->_aliases['civicrm_participant']}.event_id ) AND
                        {$this->_aliases['civicrm_event']}.is_template = 0
             LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                    ON ({$this->_aliases['civicrm_participant']}.contact_id  = {$this->_aliases['civicrm_contact']}.id  )
             {$this->_aclFrom}
      ";

    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    // Include participant note.
    if ($this->isTableSelected('civicrm_note')) {
      $this->_from .= "
            LEFT JOIN civicrm_note {$this->_aliases['civicrm_note']}
                   ON ( {$this->_aliases['civicrm_note']}.entity_table = 'civicrm_participant' AND
                   {$this->_aliases['civicrm_participant']}.id = {$this->_aliases['civicrm_note']}.entity_id )";
    }

    if ($this->_contribField) {
      $this->_from .= "
             LEFT JOIN civicrm_participant_payment pp
                    ON ({$this->_aliases['civicrm_participant']}.id  = pp.participant_id)
             LEFT JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
                    ON (pp.contribution_id  = {$this->_aliases['civicrm_contribution']}.id)
      ";
    }
    if (!empty($this->_params['price_field_value_id_value'])) {
      $this->_from .= "
            LEFT JOIN civicrm_line_item line_item_civireport
                  ON line_item_civireport.entity_table = 'civicrm_participant' AND
                     line_item_civireport.entity_id = {$this->_aliases['civicrm_participant']}.id AND
                     line_item_civireport.qty > 0
      ";
    }
    if ($this->_balance) {
      $this->_from .= "
            LEFT JOIN civicrm_entity_financial_trxn eft
                  ON (eft.entity_id = {$this->_aliases['civicrm_contribution']}.id)
            LEFT JOIN civicrm_financial_trxn ft
                  ON (ft.id = eft.financial_trxn_id AND eft.entity_table = 'civicrm_contribution') AND
                     (ft.is_payment = 1)
      ";
    }
  }

  public function groupBy() {
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, "{$this->_aliases['civicrm_participant']}.id");
  }

  public function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::postProcess();
  }

  /**
   * @param array $rows
   * @param bool $entryFound
   * @param array $row
   * @param string $rowId
   * @param int $rowNum
   * @param array $types
   *
   * @return bool
   */
  private function _initBasicRow(&$rows, &$entryFound, $row, $rowId, $rowNum, $types) {
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
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $eventType = CRM_Core_OptionGroup::values('event_type');
    $financialTypes = CRM_Contribute_PseudoConstant::financialType();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

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
          $roleId = [];
          foreach ($roles as $role) {
            $roleId[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
          }
          $rows[$rowNum]['civicrm_participant_role_id'] = implode(', ', $roleId);
        }
        $entryFound = TRUE;
      }

      // Handle registered by name
      if (array_key_exists('civicrm_participant_registered_by_name', $row)) {
        $registeredById = $row['civicrm_participant_registered_by_name'];
        if ($registeredById) {
          $registeredByContactId = CRM_Core_DAO::getFieldValue("CRM_Event_DAO_Participant", $registeredById, 'contact_id', 'id');
          $rows[$rowNum]['civicrm_participant_registered_by_name'] = CRM_Contact_BAO_Contact::displayName($registeredByContactId);
          $rows[$rowNum]['civicrm_participant_registered_by_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $registeredByContactId, $this->_absoluteUrl);
          $rows[$rowNum]['civicrm_participant_registered_by_name_hover'] = ts('View Contact Summary for Contact that registered the participant.');
        }
      }

      // Handle registered by contact name
      if (array_key_exists('civicrm_participant_registered_by_contact_name', $row)) {
        $registeredByContactId = $row['civicrm_participant_registered_by_contact_name'];
        if ($registeredByContactId) {
          $rows[$rowNum]['civicrm_participant_registered_by_contact_name'] = CRM_Contact_BAO_Contact::displayName($registeredByContactId);
          $rows[$rowNum]['civicrm_participant_registered_by_contact_name_link'] = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $registeredByContactId, $this->_absoluteUrl);
          $rows[$rowNum]['civicrm_participant_registered_by_contact_name_hover'] = ts('View Contact Summary for Contact that registered the participant.');
        }
      }

      // Handle value separator in Fee Level
      if (array_key_exists('civicrm_participant_participant_fee_level', $row)) {
        $feeLevel = $row['civicrm_participant_participant_fee_level'];
        if ($feeLevel) {
          CRM_Event_BAO_Participant::fixEventLevel($feeLevel);
          $rows[$rowNum]['civicrm_participant_participant_fee_level'] = $feeLevel;
        }
        $entryFound = TRUE;
      }

      // Convert display name to link
      $displayName = $row['civicrm_contact_sort_name_linked'] ?? NULL;
      $cid = $row['civicrm_contact_id'] ?? NULL;
      $id = $row['civicrm_participant_participant_record'] ?? NULL;

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
        // Add a "View" link to the participant record if this isn't a CSV/PDF/printed document.
        if (empty($this->getOutputMode())) {
          $rows[$rowNum]['civicrm_contact_sort_name_linked'] .=
            "<span style='float: right;'><a title='$participantTitle' href=$viewUrl>" .
            ts('View') . "</a></span>";
        }
        $entryFound = TRUE;
      }

      // Convert campaign_id to campaign title
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_participant_campaign_id', $rowNum, $this->campaigns);

      // handle contribution status
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_contribution_status_id', $rowNum, $contributionStatus);

      // handle payment instrument
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_payment_instrument_id', $rowNum, $paymentInstruments);

      // handle financial type
      $this->_initBasicRow($rows, $entryFound, $row, 'civicrm_contribution_financial_type_id', $rowNum, $financialTypes);

      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'event/participantListing', 'View Event Income Details') ? TRUE : $entryFound;

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'event/ParticipantListing', 'List all participant(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
