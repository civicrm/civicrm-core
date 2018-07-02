<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
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
      $params = array('id' => $params);
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
