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

use Civi\Api4\Batch;

/**
 * This class contains all the function that are called using AJAX
 */
class CRM_Financial_Page_AJAX {

  /**
   * get financial accounts of required account relationship.
   * $financialAccountType array with key account relationship and value financial account type option groups
   *
   * @param $config
   */
  public static function jqFinancial($config) {
    if (!isset($_GET['_value']) ||
      empty($_GET['_value'])
    ) {
      CRM_Utils_System::civiExit();
    }
    $defaultId = NULL;
    if ($_GET['_value'] === 'select') {
      $result = CRM_Contribute_PseudoConstant::financialAccount();
    }
    else {
      $financialAccountType = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
      $financialAccountType = $financialAccountType[$_GET['_value']] ?? NULL;
      $result = CRM_Contribute_PseudoConstant::financialAccount(NULL, $financialAccountType);
      if ($financialAccountType) {
        $defaultId = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = $financialAccountType");
      }
    }
    $elements = [
      [
        'name' => ts('- select -'),
        'value' => 'select',
      ],
    ];

    if (!empty($result)) {
      foreach ($result as $id => $name) {
        $selectedArray = [];
        if ($id == $defaultId) {
          $selectedArray['selected'] = 'Selected';
        }
        $elements[] = [
          'name' => $name,
          'value' => $id,
        ] + $selectedArray;
      }
    }
    CRM_Utils_JSON::output($elements);
  }

  /**
   * @param $config
   */
  public static function jqFinancialRelation($config) {
    if (!isset($_GET['_value']) ||
      empty($_GET['_value'])
    ) {
      CRM_Utils_System::civiExit();
    }

    if ($_GET['_value'] !== 'select') {
      $financialAccountType = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations(TRUE);
      $financialAccountId = CRM_Utils_Request::retrieve('_value', 'Positive');
      $financialAccountTypeId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', $financialAccountId, 'financial_account_type_id');
    }
    $params['orderColumn'] = 'label';
    $result = CRM_Core_PseudoConstant::get('CRM_Financial_DAO_EntityFinancialAccount', 'account_relationship', $params);

    $elements = [
      [
        'name' => ts('- Select Financial Account Relationship -'),
        'value' => 'select',
      ],
    ];

    $countResult = count($financialAccountType[$financialAccountTypeId]);
    if (!empty($result)) {
      foreach ($result as $id => $name) {
        if (in_array($id, $financialAccountType[$financialAccountTypeId]) && $_GET['_value'] != 'select') {
          if ($countResult != 1) {
            $elements[] = [
              'name' => $name,
              'value' => $id,
            ];
          }
          else {
            $elements[] = [
              'name' => $name,
              'value' => $id,
              'selected' => 'Selected',
            ];
          }
        }
        elseif ($_GET['_value'] == 'select') {
          $elements[] = [
            'name' => $name,
            'value' => $id,
          ];
        }
      }
    }
    CRM_Utils_JSON::output($elements);
  }

  /**
   * @param $config
   */
  public static function jqFinancialType($config) {
    if (!isset($_GET['_value']) ||
      empty($_GET['_value'])
    ) {
      CRM_Utils_System::civiExit();
    }
    $productId = CRM_Utils_Request::retrieve('_value', 'Positive');
    $elements = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Product', $productId, 'financial_type_id');
    CRM_Utils_JSON::output($elements);
  }

  /**
   * Callback to perform action on batch records.
   */
  public static function assignRemove() {
    $op = CRM_Utils_Type::escape($_POST['op'], 'String');
    $recordBAO = CRM_Utils_Type::escape($_POST['recordBAO'], 'String');
    $key = CRM_Utils_Request::retrieveValue('qfKey', 'String');
    $k = CRM_Core_Key::validate($key, 'CRM_Financial_Page_AJAX');
    if (!$k) {
      CRM_Utils_JSON::output(['status' => 'invalid key']);
    }
    foreach ($_POST['records'] as $record) {
      $recordID = CRM_Utils_Type::escape($record, 'Positive', FALSE);
      if ($recordID) {
        $records[] = $recordID;
      }
    }

    $entityID = CRM_Utils_Request::retrieve('entityID', 'Positive', CRM_Core_DAO::$_nullObject, FALSE, NULL, 'POST');
    $methods = [
      'assign' => 'create',
      'remove' => 'del',
      'reopen' => 'create',
      'close' => 'create',
      'delete' => 'deleteBatch',
    ];
    if ($op == 'close') {
      $totals = CRM_Batch_BAO_Batch::batchTotals($records);
    }
    $response = ['status' => 'record-updated-fail'];
    // first munge and clean the recordBAO and get rid of any non alpha numeric characters
    $recordBAO = CRM_Utils_String::munge($recordBAO);
    $recordClass = explode('_', $recordBAO);
    // make sure recordClass is in the CRM namespace and
    // at least 3 levels deep
    if ($recordClass[0] == 'CRM' && count($recordClass) >= 3) {
      foreach ($records as $recordID) {
        $params = [];
        switch ($op) {
          case 'assign':
          case 'remove':
            if ($op == 'remove' || $op == 'assign') {
              $params = [
                'entity_id' => $recordID,
                'entity_table' => 'civicrm_financial_trxn',
                'batch_id' => $entityID,
              ];
            }
            break;

          case 'close':
            // Update totals when closing a batch
            $params = $totals[$recordID];
          case 'reopen':
            $status = $op == 'close' ? 'Closed' : 'Reopened';
            $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id', 'validate');
            $params['status_id'] = CRM_Utils_Array::key($status, $batchStatus);
            $session = CRM_Core_Session::singleton();
            $params['modified_date'] = date('YmdHis');
            $params['modified_id'] = $session->get('userID');
            $params['id'] = $recordID;
            break;

          case 'delete':
            $params = $recordID;
            break;
        }

        if (method_exists($recordBAO, $methods[$op]) & !empty($params)) {
          try {
            $updated = call_user_func_array([$recordBAO, $methods[$op]], [&$params]);
          }
          catch (\CRM_Core_Exception $e) {
            $errorMessage = $e->getMessage();
          }
          if ($updated) {
            $redirectStatus = $updated->status_id;
            if ($batchStatus[$updated->status_id] == "Reopened") {
              $redirectStatus = array_search("Open", $batchStatus);
            }
            $response = [
              'status' => 'record-updated-success',
              'status_id' => $redirectStatus,
            ];
          }
          if ($errorMessage ?? FALSE) {
            $response = ['status' => $errorMessage];
          }
        }
      }
    }
    CRM_Utils_JSON::output($response);
  }

  /**
   * This function uses the deprecated v1 datatable api and needs updating. See CRM-16353.
   * @deprecated
   *
   * @return string|wtf??
   */
  public static function getFinancialTransactionsList() {
    $sortMapper = [
      0 => '',
      1 => '',
      2 => 'sort_name',
      3 => 'amount',
      4 => 'trxn_id',
      5 => 'transaction_date',
      6 => 'receive_date',
      7 => 'payment_method',
      8 => 'status',
      9 => 'name',
    ];

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $return = isset($_REQUEST['return']) ? CRM_Utils_Type::escape($_REQUEST['return'], 'Boolean') : FALSE;
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
    $entityID = isset($_REQUEST['entityID']) ? CRM_Utils_Type::escape($_REQUEST['entityID'], 'String') : NULL;
    $notPresent = isset($_REQUEST['notPresent']) ? CRM_Utils_Type::escape($_REQUEST['notPresent'], 'String') : NULL;
    $statusID = isset($_REQUEST['statusID']) ? CRM_Utils_Type::escape($_REQUEST['statusID'], 'String') : NULL;
    $search = isset($_REQUEST['search']);

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $returnvalues = [
      'civicrm_financial_trxn.payment_instrument_id as payment_method',
      'civicrm_contribution.contact_id as contact_id',
      'civicrm_contribution.id as contributionID',
      'contact_a.sort_name',
      'civicrm_financial_trxn.total_amount as amount',
      'civicrm_financial_trxn.trxn_id as trxn_id',
      'contact_a.contact_type',
      'contact_a.contact_sub_type',
      'civicrm_financial_trxn.trxn_date as transaction_date',
      'civicrm_contribution.receive_date as receive_date',
      'civicrm_financial_type.label',
      'civicrm_financial_trxn.currency as currency',
      'civicrm_financial_trxn.status_id as status',
      'civicrm_financial_trxn.check_number as check_number',
      'civicrm_financial_trxn.card_type_id',
      'civicrm_financial_trxn.pan_truncation',
    ];

    $columnHeader = [
      'contact_type' => '',
      'sort_name' => ts('Contact Name'),
      'amount' => ts('Amount'),
      'trxn_id' => ts('Trxn ID'),
      'transaction_date' => ts('Transaction Date'),
      'receive_date' => ts('Contribution Date'),
      'payment_method' => ts('Payment Method'),
      'status' => ts('Status'),
      'label' => ts('Type'),
    ];

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;
    $params['total'] = 0;

    // get batch list
    if (isset($notPresent)) {
      $financialItem = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params);
      if ($search) {
        $unassignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params, TRUE);
      }
      else {
        $unassignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, $notPresent, NULL, TRUE);
      }
      while ($unassignedTransactions->fetch()) {
        $unassignedTransactionsCount[] = $unassignedTransactions->id;
      }
      if (!empty($unassignedTransactionsCount)) {
        $params['total'] = count($unassignedTransactionsCount);
      }

    }
    else {
      $financialItem = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues, NULL, $params);
      $assignedTransactions = CRM_Batch_BAO_Batch::getBatchFinancialItems($entityID, $returnvalues);
      while ($assignedTransactions->fetch()) {
        $assignedTransactionsCount[] = $assignedTransactions->id;
      }
      if (!empty($assignedTransactionsCount)) {
        $params['total'] = count($assignedTransactionsCount);
      }
    }
    $financialitems = [];
    if ($statusID) {
      $batchStatus = CRM_Core_PseudoConstant::getName('CRM_Batch_DAO_Batch', 'status_id', $statusID);
    }
    while ($financialItem->fetch()) {
      $row[$financialItem->id] = [];
      foreach ($columnHeader as $columnKey => $columnValue) {
        if ($financialItem->contact_sub_type && $columnKey === 'contact_type') {
          $row[$financialItem->id][$columnKey] = $financialItem->contact_sub_type;
          continue;
        }
        $row[$financialItem->id][$columnKey] = $financialItem->$columnKey;
        if ($columnKey === 'sort_name' && $financialItem->$columnKey && $financialItem->contact_id) {
          $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=" . $financialItem->contact_id);
          $row[$financialItem->id][$columnKey] = '<a href="' . $url . '">' . $financialItem->$columnKey . '</a>';
        }
        elseif ($columnKey == 'payment_method' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Batch_BAO_Batch', 'payment_instrument_id', $financialItem->$columnKey);
          if ($row[$financialItem->id][$columnKey] == 'Check') {
            $checkNumber = $financialItem->check_number ? ' (' . $financialItem->check_number . ')' : '';
            $row[$financialItem->id][$columnKey] = $row[$financialItem->id][$columnKey] . $checkNumber;
          }
        }
        elseif ($columnKey === 'amount' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = Civi::format()->money($financialItem->$columnKey, $financialItem->currency);
        }
        elseif ($columnKey === 'transaction_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'receive_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'status' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $financialItem->$columnKey);
        }
      }
      if (isset($batchStatus) && in_array($batchStatus, ['Open', 'Reopened'])) {
        if (isset($notPresent)) {
          $js = "enableActions('x')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_x_" . $financialItem->id . "' name='mark_x_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            (new CRM_Financial_Form_BatchTransaction())->links(),
            NULL,
            [
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
            ],
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
        else {
          $js = "enableActions('y')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_y_" . $financialItem->id . "' name='mark_y_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            (new CRM_Financial_Page_BatchTransaction())->links(),
            NULL,
            [
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
            ],
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
      }
      else {
        $row[$financialItem->id]['check'] = NULL;
        $tempBAO = new CRM_Financial_Page_BatchTransaction();
        $links = $tempBAO->links();
        unset($links['remove']);
        $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
          $links,
          NULL,
          [
            'id' => $financialItem->id,
            'contid' => $financialItem->contributionID,
            'cid' => $financialItem->contact_id,
          ],
          ts('more'),
          FALSE,
          'financialItem.batch.row',
          'FinancialItem',
          $financialItem->id
        );
      }
      if ($financialItem->contact_id) {
        $row[$financialItem->id]['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage(!empty($row[$financialItem->id]['contact_sub_type']) ? $row[$financialItem->id]['contact_sub_type'] : CRM_Utils_Array::value('contact_type', $row[$financialItem->id]), FALSE, $financialItem->contact_id);
      }
      // @todo: Is this right? Shouldn't it be adding to the array as we loop?
      $financialitems = $row;
    }

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = [
      'check',
      'contact_type',
      'sort_name',
      'amount',
      'trxn_id',
      'transaction_date',
      'receive_date',
      'payment_method',
      'status',
      'label',
      'action',
    ];

    if ($return) {
      return CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  public static function bulkAssignRemove() {
    $checkIDs = $_REQUEST['ID'];
    $entityID = CRM_Utils_Type::escape($_REQUEST['entityID'], 'String');
    $action = CRM_Utils_Type::escape($_REQUEST['action'], 'String');
    foreach ($checkIDs as $key => $value) {
      if ((substr($value, 0, 7) == "mark_x_" && $action == 'Assign') || (substr($value, 0, 7) == "mark_y_" && $action == 'Remove')) {
        $contributions = explode("_", $value);
        $cIDs[] = $contributions[2];
      }
    }

    foreach ($cIDs as $value) {
      if ($action == 'Remove' || $action == 'Assign') {
        $params = [
          'entity_id' => $value,
          'entity_table' => 'civicrm_financial_trxn',
          'batch_id' => $entityID,
        ];
        try {
          if ($action == 'Assign') {
            CRM_Batch_BAO_EntityBatch::create($params);
          }
          else {
            $delete = \Civi\Api4\EntityBatch::delete(FALSE);
            foreach ($params as $field => $val) {
              $delete->addWhere($field, '=', $val);
            }
            $delete->execute()->count();
          }
        }
        catch (\CRM_Core_Exception $e) {
          $errorMessage = $e->getMessage();
        }
      }
    }
    if ($errorMessage ?? FALSE) {
      $status = ['status' => $errorMessage];
    }
    else {
      $status = ['status' => 'record-updated-success'];
    }
    CRM_Utils_JSON::output($status);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public static function getBatchSummary(): void {
    CRM_Utils_JSON::output(self::makeBatchSummary(CRM_Utils_Type::escape($_REQUEST['batchID'], 'Integer')));
  }

  /**
   * Get a summary of the batch..
   *
   * @param $batchID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function makeBatchSummary(int $batchID): array {
    // We use permissions false because the old function did that & we
    // have not tested to ensure permissions are correct - but ideally
    // we would setCheckPermissions = TRUE.
    $batchInfo = Batch::get(FALSE)
      ->addWhere('id', '=', $batchID)
      ->addSelect(
        'description',
        'item_count',
        'total',
        'created_date',
        'created_id.display_name',
        'status_id:label',
        'payment_instrument_id:label'
      )
      ->execute()->first();
    $batchTotals = CRM_Batch_BAO_Batch::batchTotals([$batchID]);
    return [
      'created_by' => $batchInfo['created_id.display_name'],
      'status' => $batchInfo['status_id:label'],
      'description' => $batchInfo['description'],
      'payment_instrument' => $batchInfo['payment_instrument_id:label'],
      'item_count' => $batchInfo['item_count'],
      'assigned_item_count' => $batchTotals[$batchID]['item_count'],
      'total' => Civi::format()->money($batchInfo['total']),
      'assigned_total' => Civi::format()->money($batchTotals[$batchID]['total']),
      'opened_date' => CRM_Utils_Date::customFormat($batchInfo['created_date']),
    ];
  }

}
