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
 * This class contains functions that are called using AJAX.
 */
class CRM_Batch_Page_AJAX {

  /**
   * Save record.
   */
  public static function batchSave() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    // save the entered information in 'data' column
    $batchId = CRM_Utils_Type::escape($_POST['batch_id'], 'Positive');

    unset($_POST['qfKey']);
    CRM_Core_DAO::setFieldValue('CRM_Batch_DAO_Batch', $batchId, 'data', json_encode(['values' => $_POST]));

    CRM_Utils_System::civiExit();
  }

  /**
   * This function uses the deprecated v1 datatable api and needs updating. See CRM-16353.
   * @deprecated
   */
  public static function getBatchList() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
    if ($context != 'financialBatch') {
      $sortMapper = [
        0 => 'title',
        1 => 'type_id.label',
        2 => 'item_count',
        3 => 'total',
        4 => 'status_id.label',
        5 => 'created_id.sort_name',
      ];
    }
    else {
      $sortMapper = [
        1 => 'title',
        2 => 'payment_instrument_id.label',
        3 => 'item_count',
        4 => 'total',
        5 => 'status_id.label',
        6 => 'created_id.sort_name',
      ];
    }
    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';

    $params = $_REQUEST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    if ($context != 'financialBatch') {
      // data entry status batches
      $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Batch_BAO_Batch', 'status_id', 'Data Entry');
    }

    $params['context'] = $context;

    // get batch list
    $batches = CRM_Batch_BAO_Batch::getBatchListSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];

    if ($context == 'financialBatch') {
      $selectorElements = [
        'check',
        'batch_name',
        'payment_instrument',
        'item_count',
        'total',
        'status',
        'created_by',
        'links',
      ];
    }
    else {
      $selectorElements = [
        'batch_name',
        'type',
        'item_count',
        'total',
        'status',
        'created_by',
        'links',
      ];
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($batches, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

}
