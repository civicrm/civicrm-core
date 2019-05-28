<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Report_Form_Pledge_Pbnp extends CRM_Report_Form {
  protected $_charts = [
    '' => 'Tabular',
    'barChart' => 'Bar Chart',
    'pieChart' => 'Pie Chart',
  ];
  public $_drilldownReport = ['pledge/summary' => 'Link to Detail Report'];

  protected $_customGroupExtends = [
    'Pledge',
  ];

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'sort_name' => [
            'title' => ts('Constituent Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ],
          'id' => [
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_pledge' => [
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' => [
          'pledge_create_date' => [
            'title' => ts('Pledge Made'),
            'required' => TRUE,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'required' => TRUE,
          ],
          'frequency_unit' => [
            'title' => ts('Frequency Unit'),
          ],
          'installments' => [
            'title' => ts('Installments'),
          ],
          'amount' => [
            'title' => ts('Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ],
          'currency' => [
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'status_id' => [
            'title' => ts('Status'),
          ],
        ],
        'filters' => [
          'pledge_create_date' => [
            'title' => ts('Pledge Made'),
            'operatorType' => CRM_Report_Form::OP_DATE,
          ],
          'pledge_amount' => [
            'title' => ts('Pledged Amount'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ],
          'currency' => [
            'title' => ts('Currency'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ],
          'financial_type_id' => [
            'title' => ts('Financial Type'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Contribute_PseudoConstant::financialType(),
          ],
          'pledge_status_id' => [
            'name' => 'status_id',
            'title' => ts('Pledge Status'),
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('pledge_status'),
          ],
          'installments' => [
            'title' => ts('Installments'),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
        'grouping' => 'pledge-fields',
      ],
      'civicrm_pledge_payment' => [
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' => [
          'scheduled_date' => [
            'title' => ts('Next Payment Due'),
            'type' => CRM_Utils_Type::T_DATE,
            'required' => TRUE,
          ],
          'scheduled_amount' => [
            'type' => CRM_Utils_Type::T_MONEY,
            'title' => ts('Next Payment Amount'),
          ],
        ],
        'filters' => [
          'scheduled_date' => [
            'title' => ts('Next Payment Due'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
        'grouping' => 'pledge-fields',
      ],
      'civicrm_address' => [
        'dao' => 'CRM_Core_DAO_Address',
        'fields' => [
          'street_address' => NULL,
          'city' => NULL,
          'postal_code' => NULL,
          'state_province_id' => [
            'title' => ts('State/Province'),
          ],
          'country_id' => [
            'title' => ts('Country'),
            'default' => TRUE,
          ],
        ],
        'grouping' => 'contact-fields',
      ],
      'civicrm_email' => [
        'dao' => 'CRM_Core_DAO_Email',
        'fields' => ['email' => NULL],
        'grouping' => 'contact-fields',
      ],
    ];

    // If we have a campaign, build out the relevant elements
    $this->addCampaignFields('civicrm_pledge');

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_pledge_currency';
    parent::__construct();
  }

  public function preProcess() {
    $this->assign('reportTitle', ts('Pledged but not Paid Report'));
    parent::preProcess();
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
          }
        }
      }
    }
    $this->_selectClauses = $select;
    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = NULL;

    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $pendingStatus = array_search('Pending', $allStatus);
    foreach ([
      'Pending',
      'In Progress',
      'Overdue',
    ] as $statusKey) {
      if ($key = CRM_Utils_Array::key($statusKey, $allStatus)) {
        $unpaidStatus[] = $key;
      }
    }

    $statusIds = implode(', ', $unpaidStatus);

    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
             INNER JOIN civicrm_pledge  {$this->_aliases['civicrm_pledge']}
                        ON ({$this->_aliases['civicrm_pledge']}.contact_id =
                            {$this->_aliases['civicrm_contact']}.id)  AND
                            {$this->_aliases['civicrm_pledge']}.status_id IN ( {$statusIds} )\n";

    // Note that the derived query protects us from providing inaccurate data in the edge case where pledge
    // payments have been edited such that they are not in id order. This might be better as a temp table.
    $this->_from .= "LEFT JOIN (SELECT * FROM civicrm_pledge_payment ORDER BY scheduled_date) as {$this->_aliases['civicrm_pledge_payment']}
                        ON ({$this->_aliases['civicrm_pledge']}.id =
                            {$this->_aliases['civicrm_pledge_payment']}.pledge_id AND  {$this->_aliases['civicrm_pledge_payment']}.status_id = {$pendingStatus} ) ";

    $this->joinAddressFromContact();
    $this->joinEmailFromContact();

  }

  public function groupBy() {
    $groupBy = [
      "{$this->_aliases['civicrm_pledge']}.contact_id",
      "{$this->_aliases['civicrm_pledge']}.id",
      "{$this->_aliases['civicrm_pledge']}.currency",
    ];
    $this->_groupBy = CRM_Contact_BAO_Query::getGroupByFromSelectColumns($this->_selectClauses, $groupBy);
  }

  public function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_pledge']}.contact_id, {$this->_aliases['civicrm_pledge']}.id";
  }

  public function postProcess() {
    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    parent::PostProcess();
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;
    $checkList = [];
    $display_flag = $prev_cid = $cid = 0;

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_contact_id', $row)) {
          if ($cid = $row['civicrm_contact_id']) {
            if ($rowNum == 0) {
              $prev_cid = $cid;
            }
            else {
              if ($prev_cid == $cid) {
                $display_flag = 1;
                $prev_cid = $cid;
              }
              else {
                $display_flag = 0;
                $prev_cid = $cid;
              }
            }

            if ($display_flag) {
              foreach ($row as $colName => $colVal) {
                if (in_array($colName, $this->_noRepeats)) {
                  unset($rows[$rowNum][$colName]);
                }
              }
            }
            $entryFound = TRUE;
          }
        }
      }

      //handle the Financial Type Ids
      if (array_key_exists('civicrm_pledge_financial_type_id', $row)) {
        if ($value = $row['civicrm_pledge_financial_type_id']) {
          $rows[$rowNum]['civicrm_pledge_financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($value, FALSE);
        }
        $entryFound = TRUE;
      }

      //handle the Status Ids
      if (array_key_exists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Pledge_BAO_Pledge', 'status_id', $value);
        }
        $entryFound = TRUE;
      }

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('pledge/summary',
          'reset=1&force=1&id_op=eq&id_value=' .
          $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Pledge Details for this contact");
        $entryFound = TRUE;
      }

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_pledge_campaign_id', $row)) {
        if ($value = $row['civicrm_pledge_campaign_id']) {
          $rows[$rowNum]['civicrm_pledge_campaign_id'] = $this->campaigns[$value];
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, NULL, NULL) ? TRUE : $entryFound;
      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }

}
