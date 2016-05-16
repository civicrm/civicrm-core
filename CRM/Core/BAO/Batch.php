<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_BAO_Batch extends CRM_Core_DAO_Batch {

  /**
   * Cache for the current batch object
   */
  static $_batch = NULL;

  /**
   * Create a new batch
   *
   * @return batch array
   * @access public
   */
  static
  function create(&$params) {
    if (!CRM_Utils_Array::value('id', $params)) {
      $params['name'] = CRM_Utils_String::titleToVar($params['title']);
    }

    $batch = new CRM_Core_DAO_Batch();
    $batch->copyValues($params);
    $batch->save();
    return $batch;
  }

  /**
   * Retrieve the information about the batch
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return array CRM_Core_BAO_Batch object on success, null otherwise
   * @access public
   * @static
   */
  static
  function retrieve(&$params, &$defaults) {
    $batch = new CRM_Core_DAO_Batch();
    $batch->copyValues($params);
    if ($batch->find(TRUE)) {
      CRM_Core_DAO::storeValues($batch, $defaults);
      return $batch;
    }
    return NULL;
  }

  /**
   * Get profile id associated with the batch type
   *
   * @param int   $batchTypeId batch type id
   *
   * @return int  $profileId   profile id
   * @static
   */
  static
  function getProfileId($batchTypeId) {
    //retrieve the profile specific to batch type

    switch ($batchTypeId) {
      case 1:
        //batch profile used for contribution
        $profileName = "contribution_batch_entry";
        break;

      case 2:
        //batch profile used for memberships
        $profileName = "membership_batch_entry";
    }

    // get and return the profile id
    return CRM_Core_DAO::getFieldValue('CRM_Core_BAO_UFGroup', $profileName, 'id', 'name');
  }

  /**
   * generate batch name
   *
   * @return batch name
   * @static
   */
  static
  function generateBatchName() {
    $sql = "SELECT max(id) FROM civicrm_batch";
    $batchNo = CRM_Core_DAO::singleValueQuery($sql) + 1;
    return ts('Batch %1', array(1 => $batchNo)) . ': ' . date('Y-m-d');
  }

  /**
   * create entity batch entry
   *
   * @return batch array
   * @access public
   */
  static
  function addBatchEntity(&$params) {
    $entityBatch = new CRM_Core_DAO_EntityBatch();
    $entityBatch->copyValues($params);
    $entityBatch->save();
    return $entityBatch;
  }

  /**
   * function to delete batch entry
   *
   * @param int $batchId batch id
   *
   * @return void
   * @access public
   */
  static
  function deleteBatch($batchId) {
    //delete batch entries from cache
    $cacheKeyString = CRM_Core_BAO_Batch::getCacheKeyForBatch($batchId);
    CRM_Core_BAO_Cache::deleteGroup('batch entry', $cacheKeyString, FALSE);

    // delete entry from batch table
    $batch = new CRM_Core_DAO_Batch();
    $batch->id = $batchId;
    $batch->delete();
  }

  /**
   * function to get cachekey for batch
   *
   * @param int $batchId batch id
   *
   * @retun string $cacheString
   * @static
   * @access public
   */
  static
  function getCacheKeyForBatch($batchId) {
    return "batch-entry-{$batchId}";
  }

  /**
   * This function is a wrapper for ajax batch selector
   *
   * @param  array   $params associated array for params record id.
   *
   * @return array   $batchList associated array of batch list
   * @access public
   */
  public function getBatchListSelector(&$params) {
    // format the params
    $params['offset']   = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort']     = CRM_Utils_Array::value('sortBy', $params);

    // get batches
    $batches = CRM_Core_BAO_Batch::getBatchList($params);

    // add total
    $params['total'] = CRM_Core_BAO_Batch::getBatchCount($params);

    // format params and add links
    $batchList = array();

    if (!empty($batches)) {
      foreach ($batches as $id => $value) {
        $batchList[$id]['batch_name'] = $value['title'];
        $batchList[$id]['batch_type'] = $value['batch_type'];
        $batchList[$id]['item_count'] = $value['item_count'];
        $batchList[$id]['total_amount'] = CRM_Utils_Money::format($value['total']);
        $batchList[$id]['status'] = $value['batch_status'];
        $batchList[$id]['created_by'] = $value['created_by'];
        $batchList[$id]['links'] = $value['action'];
      }
      return $batchList;
    }
  }

  /**
   * This function to get list of batches
   *
   * @param  array   $params associated array for params
   * @access public
   */
  static
  function getBatchList(&$params) {
    $config = CRM_Core_Config::singleton();

    $whereClause = self::whereClause($params, FALSE);

    if (!empty($params['rowCount']) &&
      $params['rowCount'] > 0
    ) {
      $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
    }

    $orderBy = ' ORDER BY batch.id desc';
    if (CRM_Utils_Array::value('sort', $params)) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Array::value('sort', $params);
    }

    $query = "
      SELECT batch.*, c.sort_name created_by 
      FROM  civicrm_batch batch 
      INNER JOIN civicrm_contact c ON batch.created_id = c.id
    WHERE {$whereClause}
    {$orderBy}
    {$limit}";

    $object = CRM_Core_DAO::executeQuery($query, $params, TRUE, 'CRM_Core_DAO_Batch');

    $links = self::links();

    $batchTypes = CRM_Core_PseudoConstant::getBatchType();
    $batchStatus = CRM_Core_PseudoConstant::getBatchStatus();

    $values = array();
    $creatorIds = array();
    while ($object->fetch()) {
      $newLinks = $links;
      $values[$object->id] = array();
      CRM_Core_DAO::storeValues($object, $values[$object->id]);
      $action = array_sum(array_keys($newLinks));

      if ($values[$object->id]['status_id'] == 2) {
        $newLinks = array();
      }

      $values[$object->id]['batch_type'] = $batchTypes[$values[$object->id]['type_id']];
      $values[$object->id]['batch_status'] = $batchStatus[$values[$object->id]['status_id']];
      $values[$object->id]['created_by'] = $object->created_by;

      $values[$object->id]['action'] = CRM_Core_Action::formLink(
        $newLinks,
        $action,
        array('id' => $object->id)
      );
    }

    return $values;
  }

  static
  function getBatchCount(&$params) {
    $whereClause = self::whereClause($params, FALSE);
    $query = " SELECT COUNT(*) FROM civicrm_batch batch WHERE {$whereClause}";
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  function whereClause(&$params, $sortBy = TRUE, $excludeHidden = TRUE) {
    $values = array();
    $clauses = array();

    $title = CRM_Utils_Array::value('title', $params);
    if ($title) {
      $clauses[] = "batch.title LIKE %1";
      if (strpos($title, '%') !== FALSE) {
        $params[1] = array($title, 'String', FALSE);
      }
      else {
        $params[1] = array($title, 'String', TRUE);
      }
    }

    $status = CRM_Utils_Array::value('status', $params);
    if ($status) {
      $clauses[] = 'batch.status_id = %3';
      $params[3] = array($status, 'Integer');
    }

    if (empty($clauses)) {
      return '1';
    }
    return implode(' AND ', $clauses);
  }

  /**
   * Function to define action links
   *
   * @return array $links array of action links
   * @access public
   */
  function links() {
    $links = array(
      CRM_Core_Action::COPY => array(
        'name' => ts('Enter records'),
        'url' => 'civicrm/batch/entry',
        'qs' => 'id=%%id%%&reset=1',
        'title' => ts('Bulk Data Entry'),
      ),
      CRM_Core_Action::UPDATE => array(
        'name' => ts('Edit'),
        'url' => 'civicrm/batch',
        'qs' => 'action=update&id=%%id%%&reset=1',
        'title' => ts('Edit Batch'),
      ),
      CRM_Core_Action::DELETE => array(
        'name' => ts('Delete'),
        'url' => 'civicrm/batch',
        'qs' => 'action=delete&id=%%id%%',
        'title' => ts('Delete Batch'),
      ),
    );

    return $links;
  }

  /**
   * function to get batch list
   *
   * @return array array of batches
   */
  static function getBatches() {
    $query = 'SELECT id, title
      FROM civicrm_batch
      WHERE type_id IN (1,2)
      AND status_id = 2
      ORDER BY id DESC';
    
    $batches = array();
    $dao = CRM_Core_DAO::executeQuery( $query );
    while ( $dao->fetch( ) ) {
      $batches[$dao->id] = $dao->title;
    }
    return $batches;
  }

}

