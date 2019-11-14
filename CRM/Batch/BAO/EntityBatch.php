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
class CRM_Batch_BAO_EntityBatch extends CRM_Batch_DAO_EntityBatch {

  /**
   * Create entity batch entry.
   *
   * @param array $params
   * @return array
   */
  public static function create(&$params) {
    $op = 'edit';
    $entityId = CRM_Utils_Array::value('id', $params);
    if (!$entityId) {
      $op = 'create';
    }
    CRM_Utils_Hook::pre($op, 'EntityBatch', $entityId, $params);
    $entityBatch = new CRM_Batch_DAO_EntityBatch();
    $entityBatch->copyValues($params);
    $entityBatch->save();
    CRM_Utils_Hook::post($op, 'EntityBatch', $entityBatch->id, $entityBatch);
    return $entityBatch;
  }

  /**
   * Remove entries from entity batch.
   * @param array|int $params
   * @return CRM_Batch_DAO_EntityBatch
   */
  public static function del($params) {
    if (!is_array($params)) {
      $params = ['id' => $params];
    }
    $entityBatch = new CRM_Batch_DAO_EntityBatch();
    $entityId = CRM_Utils_Array::value('id', $params);
    CRM_Utils_Hook::pre('delete', 'EntityBatch', $entityId, $params);
    $entityBatch->copyValues($params);
    $entityBatch->delete();
    CRM_Utils_Hook::post('delete', 'EntityBatch', $entityBatch->id, $entityBatch);
    return $entityBatch;
  }

}
