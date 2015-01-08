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


 /*
  *   !!!!!!!!!!!!!!!!!!!!
  *     NB: this is named detail but behaves like a summary report.
  *   It is also accessed through the Pledge Summary link in the UI
  *   This should presumably be changed.
  *   ~ Doten
  *   !!!!!!!!!!!!!!!!!!!!
  *
  */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Report_Form_Pledge_Detail extends CRM_Report_Form {

  protected $_summary = NULL;
  protected $_totalPaid = FALSE;
  protected $_pledgeStatuses = array();
  protected $_customGroupExtends = array(
    'Pledge',
    'Individual'
  );

  /**
   *
   */
  /**
   *
   */
  function __construct() {
  $this->_pledgeStatuses = CRM_Contribute_PseudoConstant::contributionStatus();
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
          'sort_name' =>
          array('title' => ts('Contact Name'),
            'required' => TRUE,
            'no_repeat' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'sort_name' =>
          array('title' => ts('Contact Name')),
          'id' =>
          array('no_display' => TRUE),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_email' =>
      array(
        'dao' => 'CRM_Core_DAO_Email',
        'fields' =>
        array(
          'email' =>
          array('no_repeat' => TRUE),
        ),
        'grouping' => 'contact-fields',
      ),
      'civicrm_pledge' =>
      array(
        'dao' => 'CRM_Pledge_DAO_Pledge',
        'fields' =>
        array(
          'id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'contact_id' =>
          array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'amount' =>
          array('title' => ts('Pledge Amount'),
            'required' => TRUE,
            'type' => CRM_Utils_Type::T_MONEY,
          ),
          'currency' =>
          array(
            'required' => TRUE,
            'no_display' => TRUE,
          ),
          'frequency_unit' =>
          array('title' => ts('Frequency Unit'),
          ),
          'installments' =>
          array('title' => ts('Installments'),
          ),
          'pledge_create_date' =>
          array('title' => ts('Pledge Made Date'),
          ),
          'start_date' =>
          array('title' => ts('Pledge Start Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'end_date' =>
          array('title' => ts('Pledge End Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ),
          'status_id' =>
          array('title' => ts('Pledge Status'),
            'required' => TRUE,
          ),
        ),
        'filters' =>
        array(
          'pledge_create_date' =>
          array(
            'title' => 'Pledge Made Date',
            'operatorType' => CRM_Report_Form::OP_DATE,
          ),
          'pledge_amount' =>
          array('title' => ts('Pledged Amount'),
            'operatorType' => CRM_Report_Form::OP_INT,
          ),
          'currency' =>
          array('title' => 'Currency',
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('currencies_enabled'),
            'default' => NULL,
            'type' => CRM_Utils_Type::T_STRING,
          ),
          'sid' =>
          array(
            'name' => 'status_id',
            'title' => ts('Pledge Status'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Core_OptionGroup::values('contribution_status'),
          ),

        ),
      ),
      'civicrm_pledge_payment' =>
      array(
        'dao' => 'CRM_Pledge_DAO_PledgePayment',
        'fields' =>
        array(
          'total_paid' =>
            array(
              'title' => ts('Total Amount Paid'),
              'type' => CRM_Utils_Type::T_MONEY,
            ),
          'balance_due' =>
            array(
              'title' => ts('Balance Due'),
              'default' => TRUE,
              'type' => CRM_Utils_Type::T_MONEY,
            ),

        ),
      ),
    )
    + $this->getAddressColumns(array('group_by' => FALSE))
    + $this->getPhoneColumns();
    // If we have a campaign, build out the relevant elements
    $this->_tagFilter = TRUE;
    if ($campaignEnabled && !empty($this->activeCampaigns)) {
    $this->_columns['civicrm_pledge']['fields']['campaign_id'] = array(
          'title' => 'Campaign',
          'default' => 'false',
    );
    $this->_columns['civicrm_pledge']['filters']['campaign_id'] = array('title' => ts('Campaign'),
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => $this->activeCampaigns,
    );
    $this->_columns['civicrm_pledge']['group_bys']['campaign_id'] = array('title' => ts('Campaign'));

    }

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    $this->_currencyColumn = 'civicrm_pledge_currency';
    parent::__construct();
  }

  function preProcess() {
    parent::preProcess();
  }

  function select() {
    parent::select();
  }
  /*
   * If we are retrieving total paid we need to define the inclusion of pledge_payment
   */
  /**
   * @param $tableName
   * @param $tableKey
   * @param $fieldName
   * @param $field
   *
   * @return bool|string
   */
  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    if($fieldName == 'total_paid'){
      $this->_totalPaid = TRUE; // add pledge_payment join
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = array(
        'title' => $field['title'],
        'type' => $field['type']
      );
      return "COALESCE(sum({$this->_aliases[$tableName]}.actual_amount), 0) as {$tableName}_{$fieldName}";
    }
    if($fieldName == 'balance_due'){
      $cancelledStatus = array_search('Cancelled', $this->_pledgeStatuses);
      $completedStatus = array_search('Completed', $this->_pledgeStatuses);
      $this->_totalPaid = TRUE; // add pledge_payment join
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = $field['title'];
      $this->_columnHeaders["{$tableName}_{$fieldName}"] = array(
        'title' => $field['title'],
        'type' => $field['type']
      );
        return "IF({$this->_aliases['civicrm_pledge']}.status_id IN({$cancelledStatus}, $completedStatus), 0, COALESCE({$this->_aliases['civicrm_pledge']}.amount, 0) - COALESCE(sum({$this->_aliases[$tableName]}.actual_amount),0)) as {$tableName}_{$fieldName}";
    }
    return FALSE;
  }

  function groupBy() {
    parent::groupBy();
    if (empty($this->_groupBy) && $this->_totalPaid) {
      $this->_groupBy = " GROUP BY {$this->_aliases['civicrm_pledge']}.id, {$this->_aliases['civicrm_pledge']}.currency" ;
    }
  }
  function from() {
    $this->_from = "
            FROM civicrm_pledge {$this->_aliases['civicrm_pledge']}
                 LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
                      ON ({$this->_aliases['civicrm_contact']}.id =
                          {$this->_aliases['civicrm_pledge']}.contact_id )
                 {$this->_aclFrom} ";

    if($this->_totalPaid){
      $this->_from .= "
        LEFT JOIN civicrm_pledge_payment {$this->_aliases['civicrm_pledge_payment']} ON
          {$this->_aliases['civicrm_pledge']}.id = {$this->_aliases['civicrm_pledge_payment']}.pledge_id
          AND {$this->_aliases['civicrm_pledge_payment']}.status_id = 1
      ";
    }

    $this->addPhoneFromClause();
    $this->addAddressFromClause();
    // include email field if email column is to be included
    if ($this->_emailField) {
      $this->_from .= "
                 LEFT JOIN civicrm_email {$this->_aliases['civicrm_email']}
                           ON ({$this->_aliases['civicrm_contact']}.id =
                               {$this->_aliases['civicrm_email']}.contact_id) AND
                               {$this->_aliases['civicrm_email']}.is_primary = 1\n";
    }
  }

  /**
   * @param $rows
   *
   * @return array
   */
  function statistics(&$rows) {
    $statistics = parent::statistics($rows);
   //regenerate the from field without extra left join on pledge payments
    $this->_totalPaid = FALSE;
    $this->from();
    $this->customDataFrom();
    if (!$this->_having) {
      $totalAmount = $average = array();
      $count = 0;
      $select = "
        SELECT COUNT({$this->_aliases['civicrm_pledge']}.amount )       as count,
          SUM({$this->_aliases['civicrm_pledge']}.amount )         as amount,
          ROUND(AVG({$this->_aliases['civicrm_pledge']}.amount), 2) as avg,
          {$this->_aliases['civicrm_pledge']}.currency as currency
        ";

      $group = "GROUP BY {$this->_aliases['civicrm_pledge']}.currency";
      $sql = "{$select} {$this->_from} {$this->_where} {$group}";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $count = $index = $totalCount = 0;
      // this will run once per currency
      while($dao->fetch()) {
        $totalAmount = CRM_Utils_Money::format($dao->amount, $dao->currency);
        $average =   CRM_Utils_Money::format($dao->avg, $dao->currency);
        $count = $dao->count;
        $totalCount .= $count;
        $statistics['counts']['amount' . $index] = array(
          'title' => ts('Total Amount Pledged (') . $dao->currency . ')',
          'value' => $totalAmount,
          'type' => CRM_Utils_Type::T_STRING,
        );
        $statistics['counts']['avg' . $index] = array(
          'title' => ts('Average (') . $dao->currency . ')',
          'value' => $average,
          'type' => CRM_Utils_Type::T_STRING,
        );
        $statistics['counts']['count' . $index] = array(
          'title' => ts('Total No Pledges (') . $dao->currency . ')',
          'value' => $count,
          'type' => CRM_Utils_Type::T_INT,
        );
        $index ++;
      }
      if($totalCount > $count) {
        $statistics['counts']['count' . $index] = array(
          'title' => ts('Total No Pledges'),
          'value' => $totalCount,
          'type' => CRM_Utils_Type::T_INT,
        );
      }
    }
    return $statistics;
  }

  function orderBy() {
    $this->_orderBy = "ORDER BY {$this->_aliases['civicrm_contact']}.sort_name, {$this->_aliases['civicrm_contact']}.id";
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
            if ($op) {
              $clause = $this->whereClause($field,
                $op,
                CRM_Utils_Array::value("{$fieldName}_value",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_min",
                  $this->_params
                ),
                CRM_Utils_Array::value("{$fieldName}_max",
                  $this->_params
                )
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
      $this->_where = "WHERE ({$this->_aliases['civicrm_pledge']}.is_test=0 ) ";
    }
    else {
      $this->_where = "WHERE  ({$this->_aliases['civicrm_pledge']}.is_test=0 )  AND
      " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);
    $sql   = $this->buildQuery();
    $rows  = $payment = array();

    $dao = CRM_Core_DAO::executeQuery($sql);

    // Set pager for the Main Query only which displays basic information
    $this->setPager();
    $this->assign('columnHeaders', $this->_columnHeaders);

    while ($dao->fetch()) {
      $pledgeID = $dao->civicrm_pledge_id;
      foreach ($this->_columnHeaders as $columnHeadersKey => $columnHeadersValue) {
        $row = array();
        if (property_exists($dao, $columnHeadersKey)) {
          $display[$pledgeID][$columnHeadersKey] = $dao->$columnHeadersKey;
        }
      }
      $pledgeIDArray[] = $pledgeID;
    }

    // Add Special headers
    $this->_columnHeaders['scheduled_date'] = array(
      'type' => CRM_Utils_Type::T_DATE,
      'title' => 'Next Payment Due',
     );
     $this->_columnHeaders['scheduled_amount'] = array(
        'type' => CRM_Utils_Type::T_MONEY,
        'title' => 'Next Payment Amount',
     );
     $this->_columnHeaders['status_id'] = NULL;

     /*
      * this is purely about ordering the total paid & balance due fields off to the end
      * of the table in case custom or address fields cause them to fall in the middle
      * (arguably the pledge amount should be moved to after these fields too)
      *
      */
     $tableHeaders = array(
       'civicrm_pledge_payment_total_paid',
       'civicrm_pledge_payment_balance_due'
     );

    foreach ($tableHeaders as $header) {
      //per above, unset & reset them so they move to the end
      if(isset($this->_columnHeaders[$header])){
        $headervalue = $this->_columnHeaders[$header];
        unset($this->_columnHeaders[$header]);
        $this->_columnHeaders[$header] = $headervalue;
      }
    }

    // To Display Payment Details of pledged amount
    // for pledge payments In Progress
    if (!empty($display)) {
      $sqlPayment = "
                 SELECT min(payment.scheduled_date) as scheduled_date,
                        payment.pledge_id,
                        payment.scheduled_amount,
                        pledge.contact_id

                  FROM civicrm_pledge_payment payment
                       LEFT JOIN civicrm_pledge pledge
                                 ON pledge.id = payment.pledge_id

                  WHERE payment.status_id = 2

                  GROUP BY payment.pledge_id";

      $daoPayment = CRM_Core_DAO::executeQuery($sqlPayment);

      while ($daoPayment->fetch()) {
        foreach ($pledgeIDArray as $key => $val) {
          if ($val == $daoPayment->pledge_id) {

            $display[$daoPayment->pledge_id]['scheduled_date'] = $daoPayment->scheduled_date;

            $display[$daoPayment->pledge_id]['scheduled_amount'] = $daoPayment->scheduled_amount;
          }
        }
      }
    }

    // Displaying entire data on the form
    if (!empty($display)) {
      foreach ($display as $key => $value) {
        $row = array();
        foreach ($this->_columnHeaders as $columnKey => $columnValue) {
          if (array_key_exists($columnKey, $value)) {
            $row[$columnKey] = !empty($value[$columnKey]) ? $value[$columnKey] : '';
          }
        }
        $rows[] = $row;
      }
    }

    unset($this->_columnHeaders['status_id']);
    unset($this->_columnHeaders['civicrm_pledge_id']);
    unset($this->_columnHeaders['civicrm_pledge_contact_id']);

    $this->formatDisplay($rows, FALSE);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * @param $rows
   */
  function alterDisplay(&$rows) {
    // custom code to alter rows
    $entryFound   = FALSE;
    $checkList    = array();
    $display_flag = $prev_cid = $cid = 0;

    foreach ($rows as $rowNum => $row) {
      if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
        // don't repeat contact details if its same as the previous row
        if (array_key_exists('civicrm_pledge_contact_id', $row)) {
          if ($cid = $row['civicrm_pledge_contact_id']) {
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

      // convert display name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_pledge_contact_id', $row)
      ) {
        $url = CRM_Utils_System::url("civicrm/contact/view",
          'reset=1&cid=' . $row['civicrm_pledge_contact_id'],
          $this->_absoluteUrl
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts("View Contact Summary for this Contact.");
        $entryFound = TRUE;
      }

      //handle status id
      if (array_key_exists('civicrm_pledge_status_id', $row)) {
        if ($value = $row['civicrm_pledge_status_id']) {
          $rows[$rowNum]['civicrm_pledge_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($value);
        }
        $entryFound = TRUE;
      }

      // If using campaigns, convert campaign_id to campaign title
      if (array_key_exists('civicrm_pledge_campaign_id', $row)) {
        if ($value = $row['civicrm_pledge_campaign_id']) {
          $rows[$rowNum]['civicrm_pledge_campaign_id'] = $this->activeCampaigns[$value];
        }
        $entryFound = TRUE;
      }

      $entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'pledge/detail', 'List all pledge(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }
}

