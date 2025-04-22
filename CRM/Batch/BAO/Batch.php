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

/**
 * Batch BAO class.
 */
class CRM_Batch_BAO_Batch extends CRM_Batch_DAO_Batch implements \Civi\Core\HookInterface {

  /**
   * Cache for the current batch object.
   * @var object
   */
  public static $_batch = NULL;

  /**
   * Not sure this is the best way to do this. Depends on how exportFinancialBatch() below gets called.
   * Maybe a parameter to that function is better.
   * @var string
   */
  public static $_exportFormat = NULL;

  /**
   * @deprecated
   * @param array $params
   * @return CRM_Batch_DAO_Batch
   */
  public static function create(&$params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_pre().
   *
   * @param \Civi\Core\Event\PreEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event): void {
    if ($event->action === 'create') {
      // Supply defaults for `title`
      if (empty($event->params['title'])) {
        $event->params['title'] = $event->params['name'] ?? self::generateBatchName();
      }
    }
    if ($event->action === 'edit') {
      $event->params['modified_id'] ??= CRM_Core_Session::getLoggedInContactID();
    }
  }

  /**
   * Retrieve DB object and copy to defaults array.
   *
   * @param array $params
   *   Array of criteria values.
   * @param array|null $defaults
   *   Array to be populated with found values.
   *
   * @return self|null
   *   The DAO object, if found.
   *
   * @deprecated
   */
  public static function retrieve(array $params, ?array &$defaults = NULL) {
    $defaults ??= [];
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Get profile id associated with the batch type.
   *
   * @param int $batchTypeId
   *   Batch type id.
   *
   * @return int
   *   $profileId   profile id
   */
  public static function getProfileId($batchTypeId) {
    //retrieve the profile specific to batch type
    switch ($batchTypeId) {
      case 1:
      case 3:
        //batch profile used for pledges
        $profileName = "contribution_batch_entry";
        break;

      case 2:
        //batch profile used for memberships
        $profileName = "membership_batch_entry";
        break;
    }

    // get and return the profile id
    return CRM_Core_DAO::getFieldValue('CRM_Core_BAO_UFGroup', $profileName, 'id', 'name');
  }

  /**
   * Generate batch name.
   *
   * @return string
   *   batch name
   */
  public static function generateBatchName() {
    $sql = "SELECT max(id) FROM civicrm_batch";
    $batchNo = CRM_Core_DAO::singleValueQuery($sql) + 1;
    return ts('Batch %1', [1 => $batchNo]) . ': ' . date('Y-m-d');
  }

  /**
   * Delete batch entry.
   *
   * @param int $batchId
   *   Batch id.
   *
   * @return bool
   */
  public static function deleteBatch($batchId) {
    // delete entry from batch table
    CRM_Utils_Hook::pre('delete', 'Batch', $batchId);
    $batch = new CRM_Batch_DAO_Batch();
    $batch->id = $batchId;
    $batch->delete();
    CRM_Utils_Hook::post('delete', 'Batch', $batch->id, $batch);
    return TRUE;
  }

  /**
   * wrapper for ajax batch selector.
   *
   * @param array $params
   *   Associated array for params record id.
   *
   * @return array
   *   associated array of batch list
   */
  public static function getBatchListSelector(&$params) {
    // format the params
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;

    // get batches
    $batches = self::getBatchList($params);

    // get batch totals for open batches
    $fetchTotals = [];
    $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id', 'validate');
    $batchStatus = [
      array_search('Open', $batchStatus),
      array_search('Reopened', $batchStatus),
    ];
    if ($params['context'] == 'financialBatch') {
      foreach ($batches as $id => $batch) {
        if (in_array($batch['status_id'], $batchStatus)) {
          $fetchTotals[] = $id;
        }
      }
    }
    $totals = self::batchTotals($fetchTotals);

    // add count
    $params['total'] = self::getBatchCount($params);

    // format params and add links
    $batchList = [];

    foreach ($batches as $id => $value) {
      $batch = [];
      if ($params['context'] == 'financialBatch') {
        $batch['check'] = $value['check'];
      }
      $batch['batch_name'] = $value['title'];
      $batch['total'] = '';
      $batch['payment_instrument'] = $value['payment_instrument'];
      $batch['item_count'] = $value['item_count'] ?? NULL;
      $batch['type'] = $value['batch_type'] ?? NULL;
      if (!empty($value['total'])) {
        // CRM-21205
        $batch['total'] = CRM_Utils_Money::format($value['total'], $value['currency']);
      }

      // Compare totals with actuals
      if (isset($totals[$id])) {
        $batch['item_count'] = self::displayTotals($totals[$id]['item_count'], $batch['item_count']);
        $batch['total'] = self::displayTotals(CRM_Utils_Money::format($totals[$id]['total']), $batch['total']);
      }
      $batch['status'] = $value['batch_status'];
      $batch['created_by'] = $value['created_by'];
      $batch['links'] = $value['action'];
      $batchList[$id] = $batch;
    }
    return $batchList;
  }

  /**
   * Get list of batches.
   *
   * @param array $params
   *   Associated array for params.
   *
   * @return array
   */
  public static function getBatchList(&$params) {
    $apiParams = self::whereClause($params);

    if (!empty($params['rowCount']) && is_numeric($params['rowCount'])
      && is_numeric($params['offset']) && $params['rowCount'] > 0
    ) {
      $apiParams['options'] = ['offset' => $params['offset'], 'limit' => $params['rowCount']];
    }
    $apiParams['options']['sort'] = 'id DESC';
    if (!empty($params['sort'])) {
      $apiParams['options']['sort'] = CRM_Utils_Type::escape($params['sort'], 'String');
    }

    $return = [
      "id",
      "name",
      "title",
      "description",
      "created_date",
      "status_id",
      "modified_id",
      "modified_date",
      "type_id",
      "mode_id",
      "total",
      "item_count",
      "exported_date",
      "payment_instrument_id",
      "created_id.sort_name",
      "created_id",
    ];
    $apiParams['return'] = $return;
    $batches = civicrm_api3('Batch', 'get', $apiParams);
    $obj = new CRM_Batch_BAO_Batch();
    if (!empty($params['context'])) {
      $links = $obj->links($params['context']);
    }
    else {
      $links = $obj->links();
    }

    $batchTypes = CRM_Batch_DAO_Batch::buildOptions('type_id');
    $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id');
    $batchStatusByName = CRM_Batch_DAO_Batch::buildOptions('status_id', 'validate');
    $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();

    $results = [];
    foreach ($batches['values'] as $values) {
      $newLinks = $links;
      $action = array_sum(array_keys($newLinks));

      if ($values['status_id'] == array_search('Closed', $batchStatusByName) && $params['context'] != 'financialBatch') {
        $newLinks = [];
      }
      elseif ($params['context'] == 'financialBatch') {
        $values['check'] = "<input type='checkbox' id='check_" .
          $values['id'] .
          "' name='check_" .
          $values['id'] .
          "' value='1'  data-status_id='" .
          $values['status_id'] . "' class='select-row'></input>";

        switch ($batchStatusByName[$values['status_id']]) {
          case 'Open':
          case 'Reopened':
            CRM_Utils_Array::remove($newLinks, 'reopen', 'download');
            break;

          case 'Closed':
            CRM_Utils_Array::remove($newLinks, 'close', 'edit', 'download');
            break;

          case 'Exported':
            CRM_Utils_Array::remove($newLinks, 'close', 'edit', 'reopen', 'export');
        }
        if (!CRM_Batch_BAO_Batch::checkBatchPermission('edit', $values['created_id'])) {
          CRM_Utils_Array::remove($newLinks, 'edit');
        }
        if (!CRM_Batch_BAO_Batch::checkBatchPermission('close', $values['created_id'])) {
          CRM_Utils_Array::remove($newLinks, 'close', 'export');
        }
        if (!CRM_Batch_BAO_Batch::checkBatchPermission('reopen', $values['created_id'])) {
          CRM_Utils_Array::remove($newLinks, 'reopen');
        }
        if (!CRM_Batch_BAO_Batch::checkBatchPermission('export', $values['created_id'])) {
          CRM_Utils_Array::remove($newLinks, 'export', 'download');
        }
        if (!CRM_Batch_BAO_Batch::checkBatchPermission('delete', $values['created_id'])) {
          CRM_Utils_Array::remove($newLinks, 'delete');
        }
      }
      if (!empty($values['type_id'])) {
        $values['batch_type'] = $batchTypes[$values['type_id']];
      }
      $values['batch_status'] = $batchStatus[$values['status_id']];
      $values['created_by'] = $values['created_id.sort_name'];
      $values['payment_instrument'] = '';
      if (!empty($values['payment_instrument_id'])) {
        $values['payment_instrument'] = $paymentInstrument[$values['payment_instrument_id']];
      }
      $tokens = ['id' => $values['id'], 'status' => $values['status_id']];
      if ($values['status_id'] == array_search('Exported', $batchStatusByName)) {
        $aid = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Export Accounting Batch');
        $activityParams = ['source_record_id' => $values['id'], 'activity_type_id' => $aid];
        $exportActivity = CRM_Activity_BAO_Activity::retrieve($activityParams, $val);
        if ($exportActivity) {
          $fid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_EntityFile', $exportActivity->id, 'file_id', 'entity_id');
          $fileHash = CRM_Core_BAO_File::generateFileHash($exportActivity->id, $fid);
          $tokens = array_merge(['eid' => $exportActivity->id, 'fid' => $fid, 'fcs' => $fileHash], $tokens);
        }
        else {
          CRM_Utils_Array::remove($newLinks, 'export', 'download');
        }
      }
      $values['action'] = CRM_Core_Action::formLink(
        $newLinks,
        $action,
        $tokens,
        ts('more'),
        FALSE,
        'batch.selector.row',
        'Batch',
        $values['id']
      );
      // CRM-21205
      $values['currency'] = CRM_Batch_BAO_EntityBatch::getBatchCurrency($values['id']);
      $results[$values['id']] = $values;
    }

    return $results;
  }

  /**
   * Get count of batches.
   *
   * @param array $params
   *   Associated array for params.
   *
   * @return null|string
   */
  public static function getBatchCount(&$params) {
    $apiParams = self::whereClause($params);
    return civicrm_api3('Batch', 'getCount', $apiParams);
  }

  /**
   * Format where clause for getting lists of batches.
   *
   * @param array $params
   *   Associated array for params.
   *
   * @return string[]
   */
  public static function whereClause($params) {
    $clauses = [];
    // Exclude data-entry batches
    $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id', 'validate');
    if (empty($params['status_id'])) {
      $clauses['status_id'] = ['NOT IN' => ["Data Entry"]];
    }

    $return = [
      "id",
      "name",
      "title",
      "description",
      "created_date",
      "status_id",
      "modified_id",
      "modified_date",
      "type_id",
      "mode_id",
      "total",
      "item_count",
      "exported_date",
      "payment_instrument_id",
      "created_id.sort_name",
      "created_id",
    ];
    if (!CRM_Core_Permission::check("view all manual batches")) {
      if (CRM_Core_Permission::check("view own manual batches")) {
        $loggedInContactId = CRM_Core_Session::singleton()->get('userID');
        $params['created_id'] = $loggedInContactId;
      }
      else {
        $params['created_id'] = 0;
      }
    }
    foreach ($return as $field) {
      if (!isset($params[$field])) {
        continue;
      }
      $value = CRM_Utils_Type::escape($params[$field], 'String', FALSE);
      if (in_array($field, ['name', 'title', 'description', 'created_id.sort_name'])) {
        $clauses[$field] = ['LIKE' => "%{$value}%"];
      }
      elseif ($field == 'status_id' && $value == array_search('Open', $batchStatus)) {
        $clauses['status_id'] = ['IN' => ["Open", 'Reopened']];
      }
      else {
        $clauses[$field] = $value;
      }
    }
    return $clauses;
  }

  /**
   * Define action links.
   *
   * @param null $context
   *
   * @return array
   *   array of action links
   */
  public function links($context = NULL) {
    if ($context == 'financialBatch') {
      $links = [
        'transaction' => [
          'name' => ts('Transactions'),
          'url' => 'civicrm/batchtransaction',
          'qs' => 'reset=1&bid=%%id%%',
          'title' => ts('View/Add Transactions to Batch'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::VIEW),
        ],
        'edit' => [
          'name' => ts('Edit'),
          'url' => 'civicrm/financial/batch',
          'qs' => 'reset=1&action=update&id=%%id%%&context=1',
          'title' => ts('Edit Batch'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        'close' => [
          'name' => ts('Close'),
          'title' => ts('Close Batch'),
          'url' => '#',
          'extra' => 'rel="close"',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::CLOSE),
        ],
        'export' => [
          'name' => ts('Export'),
          'title' => ts('Export Batch'),
          'url' => 'civicrm/financial/batch/export',
          'qs' => 'reset=1&id=%%id%%&status=1',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::EXPORT),
        ],
        'reopen' => [
          'name' => ts('Re-open'),
          'title' => ts('Re-open Batch'),
          'url' => '#',
          'extra' => 'rel="reopen"',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::REOPEN),
        ],
        'delete' => [
          'name' => ts('Delete'),
          'title' => ts('Delete Batch'),
          'url' => '#',
          'extra' => 'rel="delete"',
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
        'download' => [
          'name' => ts('Download'),
          'url' => 'civicrm/file',
          'qs' => 'reset=1&id=%%fid%%&eid=%%eid%%&fcs=%%fcs%%',
          'title' => ts('Download Batch'),
          'weight' => 30,
        ],
      ];
    }
    else {
      $links = [
        CRM_Core_Action::COPY => [
          'name' => ts('Enter records'),
          'url' => 'civicrm/batch/entry',
          'qs' => 'id=%%id%%&reset=1',
          'title' => ts('Batch Data Entry'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::COPY),
        ],
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/batch',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Batch'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/batch',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Batch'),
          'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::DELETE),
        ],
      ];
    }
    return $links;
  }

  /**
   * Get batch list.
   *
   * @return array
   *   all batches excluding batches with data entry in progress
   */
  public static function getBatches() {
    $dataEntryStatusId = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Data Entry');
    $query = "SELECT id, title
      FROM civicrm_batch
      WHERE item_count >= 1
      AND status_id != {$dataEntryStatusId}
      ORDER BY title";

    $batches = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $batches[$dao->id] = $dao->title;
    }
    return $batches;
  }

  /**
   * Calculate sum of all entries in a batch.
   * Used to validate and update item_count and total when closing an accounting batch
   *
   * @param array $batchIds
   * @return array
   */
  public static function batchTotals($batchIds) {
    $totals = array_fill_keys($batchIds, ['item_count' => 0, 'total' => 0]);
    if ($batchIds) {
      $sql = "SELECT eb.batch_id, COUNT(tx.id) AS item_count, SUM(tx.total_amount) AS total
      FROM civicrm_entity_batch eb
      INNER JOIN civicrm_financial_trxn tx ON tx.id = eb.entity_id AND eb.entity_table = 'civicrm_financial_trxn'
      WHERE eb.batch_id IN (" . implode(',', $batchIds) . ")
      GROUP BY eb.batch_id";
      $dao = CRM_Core_DAO::executeQuery($sql);
      while ($dao->fetch()) {
        $totals[$dao->batch_id] = (array) $dao;
      }
    }
    return $totals;
  }

  /**
   * Format markup for comparing two totals.
   *
   * @param $actual
   *   calculated total
   * @param $expected
   *   user-entered total
   * @return string
   */
  public static function displayTotals($actual, $expected) {
    $class = 'actual-value';
    if ($expected && $expected != $actual) {
      $class .= ' crm-error';
    }
    $actualTitle = ts('Current Total');
    $output = "<span class='$class' title='$actualTitle'>$actual</span>";
    if ($expected) {
      $expectedTitle = ts('Expected Total');
      $output .= " / <span class='expected-value' title='$expectedTitle'>$expected</span>";
    }
    return $output;
  }

  /**
   * Function for exporting financial accounts, currently we support CSV and IIF format
   * @see http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+Specifications+-++Batches#CiviAccountsSpecifications-Batches-%C2%A0Overviewofimplementation
   *
   * @param array $batchIds
   *   Associated array of batch ids.
   * @param string $exportFormat
   *   Export format.
   * @param bool $downloadFile
   *   Download export file?.
   */
  public static function exportFinancialBatch($batchIds, $exportFormat, $downloadFile) {
    if (empty($batchIds)) {
      throw new CRM_Core_Exception(ts('No batches were selected.'));
    }
    if (empty($exportFormat)) {
      throw new CRM_Core_Exception(ts('No export format selected.'));
    }
    self::$_exportFormat = $exportFormat;

    // Instantiate appropriate exporter based on user-selected format.
    $exporterClass = "CRM_Financial_BAO_ExportFormat_" . self::$_exportFormat;
    if (class_exists($exporterClass)) {
      $exporter = new $exporterClass();
    }
    else {
      throw new CRM_Core_Exception("Could not locate exporter: $exporterClass");
    }
    $export = [];
    $exporter->_isDownloadFile = $downloadFile;
    foreach ($batchIds as $batchId) {
      // export only batches whose status is set to Exported.
      $result = civicrm_api3('Batch', 'getcount', [
        'id' => $batchId,
        'status_id' => "Exported",
      ]);
      if (!$result) {
        continue;
      }
      $export[$batchId] = $exporter->generateExportQuery($batchId);
    }
    if ($export) {
      $exporter->makeExport($export);
    }
  }

  /**
   * @param array $batchIds
   * @param $status
   */
  public static function closeReOpen($batchIds, $status) {
    $batchStatus = CRM_Batch_DAO_Batch::buildOptions('status_id');
    $params['status_id'] = CRM_Utils_Array::key($status, $batchStatus);
    foreach ($batchIds as $id) {
      $params['id'] = $id;
      self::writeRecord($params);
    }
    $url = CRM_Utils_System::url('civicrm/financial/financialbatches', "reset=1&batchStatus={$params['status_id']}");
    CRM_Utils_System::redirect($url);
  }

  /**
   * Retrieve financial items assigned for a batch.
   *
   * @param int $entityID
   * @param array $returnValues
   * @param bool $notPresent
   * @param array $params
   * @param bool $getCount
   *
   * @return CRM_Core_DAO
   */
  public static function getBatchFinancialItems($entityID, $returnValues, $notPresent = NULL, $params = NULL, $getCount = FALSE) {
    if (!$getCount) {
      if (!empty($params['rowCount']) &&
        $params['rowCount'] > 0
      ) {
        $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
      }
    }
    // action is taken depending upon the mode
    $select = 'civicrm_financial_trxn.id ';
    if (!empty($returnValues)) {
      $select .= " , " . implode(' , ', $returnValues);
    }

    $orderBy = " ORDER BY civicrm_financial_trxn.id";
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');
    }

    $from = "civicrm_financial_trxn
INNER JOIN civicrm_entity_financial_trxn ON civicrm_entity_financial_trxn.financial_trxn_id = civicrm_financial_trxn.id
INNER JOIN civicrm_contribution ON (civicrm_contribution.id = civicrm_entity_financial_trxn.entity_id
  AND civicrm_entity_financial_trxn.entity_table='civicrm_contribution')
LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_table = 'civicrm_financial_trxn'
AND civicrm_entity_batch.entity_id = civicrm_financial_trxn.id
LEFT JOIN civicrm_financial_type ON civicrm_financial_type.id = civicrm_contribution.financial_type_id
LEFT JOIN civicrm_contact contact_a ON contact_a.id = civicrm_contribution.contact_id
LEFT JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id
";

    $searchFields = [
      'sort_name',
      'financial_type_id',
      'contribution_page_id',
      'contribution_payment_instrument_id',
      'contribution_trxn_id',
      'contribution_source',
      'contribution_currency_type',
      'contribution_pay_later',
      'contribution_recurring',
      'contribution_test',
      'contribution_thankyou_date_is_not_null',
      'contribution_receipt_date_is_not_null',
      'contribution_pcp_made_through_id',
      'contribution_pcp_display_in_roll',
      'contribution_amount_low',
      'contribution_amount_high',
      'contribution_in_honor_of',
      'contact_tags',
      'group',
      'receive_date_relative',
      'receive_date_high',
      'receive_date_low',
      'contribution_check_number',
      'contribution_status_id',
      'financial_trxn_card_type_id',
      'financial_trxn_pan_truncation',
    ];
    $values = $customJoins = [];

    // If a custom field was passed as a param,
    // we'll take it into account.
    if (!empty($params)) {
      foreach ($params as $name => $param) {
        if (str_starts_with($name, 'custom')) {
          $searchFields[] = $name;
        }
      }
    }

    foreach ($searchFields as $field) {
      if (isset($params[$field])) {
        $values[$field] = $params[$field];
        if ($field === 'sort_name') {
          $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id
          LEFT JOIN civicrm_email ON contact_b.id = civicrm_email.contact_id";
        }
        if ($field == 'contribution_in_honor_of') {
          $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id";
        }
        if ($field == 'contact_tags') {
          $from .= " LEFT JOIN civicrm_entity_tag `civicrm_entity_tag-{$params[$field]}` ON `civicrm_entity_tag-{$params[$field]}`.entity_id = contact_a.id";
        }
        if ($field == 'group') {
          $from .= " LEFT JOIN civicrm_group_contact `civicrm_group_contact-{$params[$field]}` ON contact_a.id = `civicrm_group_contact-{$params[$field]}`.contact_id ";
        }
        if ($field == 'receive_date_relative') {
          $relativeDate = explode('.', $params[$field]);
          $date = CRM_Utils_Date::relativeToAbsolute($relativeDate[0], $relativeDate[1]);
          $values['receive_date_low'] = $date['from'];
          $values['receive_date_high'] = $date['to'];
        }

        // Add left joins as they're needed to consider
        // conditions over custom fields.
        if (substr($field, 0, 6) == 'custom') {
          $customFieldParams = ['id' => explode('_', $field)[1]];
          $customFieldDefaults = [];
          $customField = CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customFieldDefaults);

          $customGroupParams = ['id' => $customField->custom_group_id];
          $customGroupDefaults = [];
          $customGroup = CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroupDefaults);

          $columnName = $customField->column_name;
          $tableName = $customGroup->table_name;

          if (!array_key_exists($tableName, $customJoins)) {
            $customJoins[$tableName] = "LEFT JOIN $tableName ON $tableName.entity_id = civicrm_contribution.id";
          }
        }
      }
    }

    $searchParams = CRM_Contact_BAO_Query::convertFormValues(
      $values,
      0,
      FALSE,
      NULL,
      [
        'financial_type_id',
        'contribution_soft_credit_type_id',
        'contribution_status_id',
        'contribution_page_id',
        'financial_trxn_card_type_id',
        'contribution_payment_instrument_id',
      ]
    );
    // @todo the use of defaultReturnProperties means the search will be inefficient
    // as slow-unneeded properties are included.
    $query = new CRM_Contact_BAO_Query($searchParams,
      CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE,
        FALSE
      ), NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE
    );

    if (count($customJoins) > 0) {
      $from .= " " . implode(" ", $customJoins);
    }

    if (!empty($query->_where[0])) {
      $where = implode(' AND ', $query->_where[0]) .
        " AND civicrm_entity_batch.batch_id IS NULL ";
      $where = str_replace('civicrm_contribution.payment_instrument_id', 'civicrm_financial_trxn.payment_instrument_id', $where);
    }
    else {
      if (!$notPresent) {
        $where = " civicrm_entity_batch.batch_id = {$entityID} ";
      }
      else {
        $where = " civicrm_entity_batch.batch_id IS NULL ";
      }
    }

    $sql = "
SELECT {$select}
FROM   {$from}
WHERE  {$where}
       {$orderBy}
";

    if (isset($limit)) {
      $sql .= "{$limit}";
    }

    $result = CRM_Core_DAO::executeQuery($sql);
    return $result;
  }

  /**
   * Get batch names.
   * @param string $batchIds
   *
   * @return array
   *   array of batches
   */
  public static function getBatchNames($batchIds) {
    $query = 'SELECT id, title
      FROM civicrm_batch
      WHERE id IN (' . $batchIds . ')';

    $batches = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $batches[$dao->id] = $dao->title;
    }
    return $batches;
  }

  /**
   * Function get batch statuses.
   *
   * @param string $batchIds
   *
   * @return array
   *   array of batches
   */
  public static function getBatchStatuses($batchIds) {
    $query = 'SELECT id, status_id
      FROM civicrm_batch
      WHERE id IN (' . $batchIds . ')';

    $batches = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $batches[$dao->id] = $dao->status_id;
    }
    return $batches;
  }

  /**
   * Function to check permission for batch.
   *
   * @param string $action
   * @param int $batchCreatedId
   *   batch created by contact id
   *
   * @return bool
   */
  public static function checkBatchPermission($action, $batchCreatedId = NULL) {
    if (CRM_Core_Permission::check("{$action} all manual batches")) {
      return TRUE;
    }
    if (CRM_Core_Permission::check("{$action} own manual batches")) {
      $loggedInContactId = CRM_Core_Session::singleton()->get('userID');
      if ($batchCreatedId == $loggedInContactId) {
        return TRUE;
      }
      elseif (CRM_Utils_System::isNull($batchCreatedId)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
