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
class CRM_Report_Form_Contribute_DeferredRevenue extends CRM_Report_Form {

  /**
   * Holds Deferred Financial Account
   * @var array
   */
  protected $_deferredFinancialAccount = [];

  /**
   */
  public function __construct() {
    $this->_exposeContactID = FALSE;
    $this->_deferredFinancialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    $this->_columns = [
      'civicrm_financial_trxn' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => [
          'status_id' => [
            'title' => ts('Transaction'),
          ],
          'trxn_date' => [
            'title' => ts('Transaction Date'),
            'type' => CRM_Utils_Type::T_DATE,
            'required' => TRUE,
          ],
          'total_amount' => [
            'title' => ts('Transaction Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => TRUE,
            'dbAlias' => 'SUM(financial_trxn_1_civireport.total_amount )',
          ],
        ],
        'filters' => [
          'trxn_date' => [
            'title' => ts('Transaction Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
      ],
      'civicrm_contribution' => [
        'dao' => 'CRM_Contribute_DAO_Contribution',
        'fields' => [
          'id' => [
            'title' => ts('Contribution ID'),
          ],
          'contribution_id' => [
            'title' => ts('Contribution ID'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'contribution_civireport.id',
          ],
          'contact_id' => [
            'title' => ts('Contact ID'),
          ],
          'source' => [
            'title' => ts('Contribution Source'),
          ],
          'receive_date' => [
            'title' => ts('Receive Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'cancel_date' => [
            'name' => 'contribution_cancel_date',
            'title' => ts('Cancel Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'revenue_recognition_date' => [
            'title' => ts('Revenue Recognition Date'),
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
        'filters' => [
          'receive_date' => [
            'title' => ts('Receive Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'receipt_date' => ['operatorType' => CRM_Report_Form::OP_DATE],
          'cancel_date' => [
            'title' => ts('Cancel Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
            'name' => 'contribution_cancel_date',
          ],
          'revenue_recognition_date' => [
            'title' => ts('Revenue Recognition Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'revenue_recognition_date_toggle' => [
            'title' => ts("Current month's revenue?"),
            'type' => CRM_Utils_Type::T_BOOLEAN,
            'default' => 0,
            'pseudofield' => TRUE,
          ],
        ],
      ],
      'civicrm_financial_account' => [
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => [
          'name' => [
            'title' => ts('Deferred Account'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'id' => [
            'title' => ts('Deferred Account ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'accounting_code' => [
            'title' => ts('Deferred Accounting Code'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
        ],
        'filters' => [
          'id' => [
            'title' => ts('Deferred Financial Account'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => $this->_deferredFinancialAccount,
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
      'civicrm_financial_account_1' => [
        'dao' => 'CRM_Financial_DAO_FinancialAccount',
        'fields' => [
          'name' => [
            'title' => ts('Revenue Account'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'id' => [
            'title' => ts('Revenue Account ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'accounting_code' => [
            'title' => ts('Revenue Accounting code'),
            'no_display' => TRUE,
            'required' => TRUE,
          ],
        ],
      ],
      'civicrm_financial_item' => [
        'dao' => 'CRM_Financial_DAO_FinancialItem',
        'fields' => [
          'status_id' => [
            'title' => ts('Status'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'id' => [
            'title' => ts('Financial Item ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
          'description' => [
            'title' => ts('Item'),
          ],
        ],
      ],
      'civicrm_financial_trxn_1' => [
        'dao' => 'CRM_Financial_DAO_FinancialTrxn',
        'fields' => [
          'total_amount' => [
            'title' => ts('Deferred Transaction Amount'),
            'type' => CRM_Utils_Type::T_MONEY,
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_1_civireport.total_amount)',
          ],
          'trxn_date' => [
            'title' => ts('Deferred Transaction Date'),
            'required' => TRUE,
            'no_display' => TRUE,
            'dbAlias' => 'GROUP_CONCAT(financial_trxn_1_civireport.trxn_date)',
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
      ],
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'display_name' => [
            'title' => ts('Contact Name'),
          ],
          'id' => [
            'title' => ts('Contact ID'),
            'required' => TRUE,
            'no_display' => TRUE,
          ],
        ],
      ],
      'civicrm_membership' => [
        'dao' => 'CRM_Member_DAO_Membership',
        'fields' => [
          'start_date' => [
            'title' => ts('Start Date'),
            'dbAlias' => 'IFNULL(membership_civireport.start_date, event_civireport.start_date)',
            'type' => CRM_Utils_Type::T_DATE,
          ],
          'end_date' => [
            'title' => ts('End Date'),
            'dbdbAlias' => 'IFNULL(membership_civireport.end_date, event_civireport.end_date)',
            'type' => CRM_Utils_Type::T_DATE,
          ],
        ],
      ],
      'civicrm_event' => [
        'dao' => 'CRM_Event_DAO_Event',
      ],
      'civicrm_participant' => [
        'dao' => 'CRM_Event_DAO_Participant',
      ],
      'civicrm_batch' => [
        'dao' => 'CRM_Batch_DAO_EntityBatch',
        'grouping' => 'contri-fields',
        'fields' => [
          'batch_id' => [
            'title' => ts('Batch Title'),
            'dbAlias' => "GROUP_CONCAT(DISTINCT batch_civireport.batch_id
                                    ORDER BY batch_civireport.batch_id SEPARATOR ',')",

          ],
        ],
        'filters' => [
          'batch_id' => [
            'title' => ts('Batch Title'),
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Batch_BAO_Batch::getBatches(),
            'type' => CRM_Utils_Type::T_INT,
          ],
        ],
      ],
    ];
    parent::__construct();
  }

  /**
   * Pre process function.
   *
   * Called prior to build form.
   */
  public function preProcess() {
    parent::preProcess();
  }

  /**
   * Build from clause.
   */
  public function from() {
    $deferredRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $revenueRelationship = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));
    $this->_from = "
      FROM civicrm_financial_item {$this->_aliases['civicrm_financial_item']}
      INNER JOIN civicrm_entity_financial_account entity_financial_account_deferred
        ON {$this->_aliases['civicrm_financial_item']}.financial_account_id = entity_financial_account_deferred.financial_account_id
        AND entity_financial_account_deferred.entity_table = 'civicrm_financial_type'
        AND entity_financial_account_deferred.account_relationship = {$deferredRelationship}
      INNER JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account']}
        ON entity_financial_account_deferred.financial_account_id = {$this->_aliases['civicrm_financial_account']}.id
      INNER JOIN civicrm_entity_financial_account entity_financial_account_revenue
        ON entity_financial_account_deferred.entity_id = entity_financial_account_revenue.entity_id
        AND entity_financial_account_deferred.entity_table= entity_financial_account_revenue.entity_table
      INNER JOIN civicrm_financial_account {$this->_aliases['civicrm_financial_account_1']}
        ON entity_financial_account_revenue.financial_account_id = {$this->_aliases['civicrm_financial_account_1']}.id
        AND {$revenueRelationship} = entity_financial_account_revenue.account_relationship
      INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_item
        ON entity_financial_trxn_item.entity_id = {$this->_aliases['civicrm_financial_item']}.id
        AND entity_financial_trxn_item.entity_table = 'civicrm_financial_item'
      INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn_1']}
        ON {$this->_aliases['civicrm_financial_trxn_1']}.to_financial_account_id = {$this->_aliases['civicrm_financial_account']}.id
        AND {$this->_aliases['civicrm_financial_trxn_1']}.id =  entity_financial_trxn_item.financial_trxn_id
      INNER JOIN civicrm_entity_financial_trxn financial_trxn_contribution
        ON financial_trxn_contribution.financial_trxn_id = {$this->_aliases['civicrm_financial_trxn_1']}.id
        AND financial_trxn_contribution.entity_table = 'civicrm_contribution'
      INNER JOIN civicrm_entity_financial_trxn entity_financial_trxn_contribution
        ON entity_financial_trxn_contribution.entity_id = {$this->_aliases['civicrm_financial_item']}.id
        AND entity_financial_trxn_contribution.entity_table = 'civicrm_financial_item'
      INNER JOIN civicrm_financial_trxn {$this->_aliases['civicrm_financial_trxn']}
        ON {$this->_aliases['civicrm_financial_trxn']}.id = entity_financial_trxn_contribution.financial_trxn_id
        AND ({$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id NOT IN (" . implode(',', array_keys($this->_deferredFinancialAccount)) . ")
        OR {$this->_aliases['civicrm_financial_trxn']}.from_financial_account_id IS NULL)
      INNER JOIN civicrm_contribution {$this->_aliases['civicrm_contribution']}
        ON {$this->_aliases['civicrm_contribution']}.id = financial_trxn_contribution.entity_id
      INNER JOIN civicrm_line_item line_item
        ON line_item.contribution_id = {$this->_aliases['civicrm_contribution']}.id
        AND line_item.financial_type_id = entity_financial_account_deferred.entity_id
      LEFT JOIN civicrm_participant {$this->_aliases['civicrm_participant']}
        ON CASE
          WHEN line_item.entity_table = 'civicrm_participant'
          THEN line_item.entity_id = {$this->_aliases['civicrm_participant']}.id
          ELSE {$this->_aliases['civicrm_participant']}.id = 0
        END
      LEFT JOIN civicrm_event {$this->_aliases['civicrm_event']}
        ON {$this->_aliases['civicrm_participant']}.event_id = {$this->_aliases['civicrm_event']}.id";

    if ($this->isTableSelected('civicrm_contact')) {
      $this->_from .= "
        INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']}
          ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id";
    }

    if ($this->isTableSelected('civicrm_membership')) {
      $this->_from .= "
        LEFT JOIN  civicrm_membership {$this->_aliases['civicrm_membership']}
          ON CASE
            WHEN line_item.entity_table = 'civicrm_membership'
            THEN line_item.entity_id = {$this->_aliases['civicrm_membership']}.id
            ELSE {$this->_aliases['civicrm_membership']}.id = 0
          END";
    }

    if ($this->isTableSelected('civicrm_batch')) {
      $this->_from .= "
        LEFT JOIN civicrm_entity_batch {$this->_aliases['civicrm_batch']}
          ON {$this->_aliases['civicrm_batch']}.entity_id = {$this->_aliases['civicrm_financial_trxn']}.id
          AND {$this->_aliases['civicrm_batch']}.entity_table = 'civicrm_financial_trxn'";
    }
  }

  /**
   * Post process function.
   */
  public function postProcess() {
    $this->_noFields = TRUE;
    parent::postProcess();
  }

  /**
   * Set limit.
   *
   * @param int|null $rowCount
   */
  public function limit($rowCount = NULL) {
    $rowCount ??= $this->getRowCount();
    $this->_limit = NULL;
  }

  /**
   * Build where clause.
   */
  public function where() {
    parent::where();
    $startDate = date('Y-m-01');
    $endDate = (new DateTime('+11 month'))->format('Y-m-t');
    $this->_where .= " AND {$this->_aliases['civicrm_financial_trxn_1']}.trxn_date BETWEEN '{$startDate}' AND '{$endDate}'";
  }

  /**
   * Build group by clause.
   */
  public function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_financial_account']}.id,  {$this->_aliases['civicrm_financial_account_1']}.id, {$this->_aliases['civicrm_financial_item']}.id";
    $this->_select = CRM_Contact_BAO_Query::appendAnyValueToSelect(
      $this->_selectClauses,
      [
        "{$this->_aliases['civicrm_financial_account_1']}.id",
        "{$this->_aliases['civicrm_financial_item']}.id",
      ]
    );
  }

  /**
   * Modify column headers.
   */
  public function modifyColumnHeaders() {
    // Re-order the columns in a custom order defined below.
    $sortArray = [
      'civicrm_batch_batch_id',
      'civicrm_financial_trxn_status_id',
      'civicrm_financial_trxn_trxn_date',
      'civicrm_contribution_receive_date',
      'civicrm_contribution_cancel_date',
      'civicrm_contribution_revenue_recognition_date',
      'civicrm_financial_trxn_total_amount',
      'civicrm_financial_item_description',
      'civicrm_contribution_contact_id',
      'civicrm_contact_display_name',
      'civicrm_contribution_source',
    ];
    // Only re-order selected columns.
    $sortArray = array_flip(array_intersect_key(array_flip($sortArray), $this->_columnHeaders));

    // Re-ordering.
    $this->_columnHeaders = array_merge(array_flip($sortArray), $this->_columnHeaders);

    // Add months to the columns.
    if ($this->_params['revenue_recognition_date_toggle_value']) {
      $this->_columnHeaders[date('M, Y', strtotime(date('Y-m-d')))] = [
        'title' => date('M, Y', strtotime(date('Y-m-d'))),
        'type' => CRM_Utils_Type::T_DATE,
      ];
    }
    else {
      for ($i = 0; $i < 12; $i++) {
        $this->_columnHeaders[date('M, Y', strtotime(date('Y-m-d') . "+{$i} month"))] = [
          'title' => date('M, Y', strtotime(date('Y-m-d') . "+{$i} month")),
          'type' => CRM_Utils_Type::T_DATE,
        ];
      }
    }
  }

  /**
   * Build output rows.
   *
   * @param string $sql
   * @param array $rows
   *
   * @throws \CRM_Core_Exception
   */
  public function buildRows($sql, &$rows) {
    $dao = CRM_Core_DAO::executeQuery($sql);

    // use this method to modify $this->_columnHeaders
    $this->modifyColumnHeaders();

    // Get custom date format.
    $dateFormat = Civi::settings()->get('dateformatFinancialBatch');

    if (!is_array($rows)) {
      $rows = [];
    }

    while ($dao->fetch()) {
      $row = [];
      foreach ($this->_columnHeaders as $key => $value) {
        $arraykey = $dao->civicrm_financial_account_id . '_' . $dao->civicrm_financial_account_1_id;

        if (property_exists($dao, $key)) {
          if (($value['type'] ?? 0) & CRM_Utils_Type::T_DATE) {
            $row[$key] = CRM_Utils_Date::customFormat($dao->$key, $dateFormat);
          }
          elseif (($value['type'] ?? 0) & CRM_Utils_Type::T_MONEY) {
            $values = [];
            foreach (explode(',', $dao->$key) as $moneyValue) {
              $values[] = CRM_Utils_Money::format($moneyValue);
            }
            $row[$key] = implode(',', $values);
          }
          else {
            $row[$key] = $dao->$key;
          }
        }

        $rows[$arraykey]['rows'][$dao->civicrm_financial_item_id] = $row;
        $rows[$arraykey]['label'] = "Deferred Revenue Account: {$dao->civicrm_financial_account_name} ({$dao->civicrm_financial_account_accounting_code}), Revenue Account: {$dao->civicrm_financial_account_1_name} {$dao->civicrm_financial_account_1_accounting_code}";
        $trxnDate = explode(',', $dao->civicrm_financial_trxn_1_trxn_date);
        $trxnAmount = explode(',', $dao->civicrm_financial_trxn_1_total_amount);
        foreach ($trxnDate as $trxnKey => $date) {
          $keyDate = date('M, Y', strtotime($date));
          $rows[$arraykey]['rows'][$dao->civicrm_financial_item_id][$keyDate] = CRM_Utils_Money::format($trxnAmount[$trxnKey]);
        }
      }
    }
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
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    foreach ($rows as &$entry) {
      foreach ($entry['rows'] as $rowNum => &$row) {

        // convert transaction status id to status name
        $status = $row['civicrm_financial_trxn_status_id'] ?? NULL;
        if ($status) {
          $row['civicrm_financial_trxn_status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_FinancialTrxn', 'status_id', $status);
          $entryFound = TRUE;
        }

        // convert batch id to batch title
        $batchId = $row['civicrm_batch_batch_id'] ?? NULL;
        if ($batchId) {
          $row['civicrm_batch_batch_id'] = $this->getLabels($batchId, 'CRM_Batch_BAO_EntityBatch', 'batch_id');
          $entryFound = TRUE;
        }

        // add hotlink for contribution
        $amount = $row['civicrm_financial_trxn_total_amount'] ?? NULL;
        if ($amount) {
          $contributionUrl = CRM_Utils_System::url("civicrm/contact/view/contribution",
            'reset=1&action=view&cid=' . $row['civicrm_contact_id'] . '&id=' . $row['civicrm_contribution_contribution_id'],
            $this->_absoluteUrl
          );
          $row['civicrm_financial_trxn_total_amount'] = "<a href={$contributionUrl}>{$amount}</a>";
          $contributionId = $row['civicrm_contribution_id'] ?? NULL;
          if ($contributionId) {
            $row['civicrm_contribution_id'] = "<a href={$contributionUrl}>{$contributionId}</a>";
          }
          $entryFound = TRUE;
        }

        // add hotlink for contact
        $contactName = $row['civicrm_contact_display_name'] ?? NULL;
        if ($contactName) {
          $contactUrl = CRM_Utils_System::url("civicrm/contact/view",
            'reset=1&cid=' . $row['civicrm_contact_id'],
            $this->_absoluteUrl
          );
          $row['civicrm_contact_display_name'] = "<a href={$contactUrl}>{$contactName}</a>";
          $entryFound = TRUE;
        }

        $contactId = $row['civicrm_contribution_contact_id'] ?? NULL;
        if ($contactId) {
          $contactUrl = CRM_Utils_System::url("civicrm/contact/view",
            'reset=1&cid=' . $row['civicrm_contact_id'],
            $this->_absoluteUrl
          );
          $row['civicrm_contribution_contact_id'] = "<a href={$contactUrl}>{$contactId}</a>";
          $entryFound = TRUE;
        }
      }
    }
  }

}
