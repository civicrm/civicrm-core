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
 * $Id$
 *
 */

/**
 * Business objects for managing price fields values.
 *
 */
class CRM_Upgrade_Snapshot_V4p2_Price_BAO_FieldValue extends CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue {

  /**
   * Insert/update a new entry in the database.
   *
   * @param array $params
   *   (reference), array $ids.
   *
   * @param $ids
   *
   * @return CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue
   */
  public static function &add(&$params, $ids) {

    $fieldValueBAO = new CRM_Upgrade_Snapshot_V4p2_Price_BAO_FieldValue();
    $fieldValueBAO->copyValues($params);

    if ($id = CRM_Utils_Array::value('id', $ids)) {
      $fieldValueBAO->id = $id;
    }
    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_price_field_value SET is_default = 0 WHERE  price_field_id = %1';
      $p = array(1 => array($params['price_field_id'], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $p);
    }

    $fieldValueBAO->save();
    return $fieldValueBAO;
  }

  /**
   * Creates a new entry in the database.
   *
   * @param array $params
   *   (reference), array $ids.
   *
   * @param $ids
   *
   * @return CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue
   */
  public static function create(&$params, $ids) {

    if (!is_array($params) || empty($params)) {
      return NULL;
    }

    if ($id = CRM_Utils_Array::value('id', $ids)) {
      if (isset($params['name'])) {
        unset($params['name']);
      }

      $oldWeight = NULL;
      if ($id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $id, 'weight', 'id');
      }

      $fieldValues = array('price_field_id' => CRM_Utils_Array::value('price_field_id', $params, 0));
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $oldWeight, $params['weight'], $fieldValues);
    }
    else {
      if (empty($params['name'])) {
        $params['name'] = CRM_Utils_String::munge(CRM_Utils_Array::value('label', $params), '_', 64);
      }
      if (empty($params['weight'])) {
        $params['weight'] = 1;
      }
    }
    $params['is_active'] = CRM_Utils_Array::value('is_active', $params, 0);

    return self::add($params, $ids);
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $params, $defaults);
  }

  /**
   * Retrive the all values for given field id.
   *
   * @param int $fieldId
   *   Price_field_id.
   * @param array $values
   *   (reference ) to hold the values.
   * @param string $orderBy
   *   For order by, default weight.
   * @param bool|int $isActive is_active, default false
   *
   * @return array
   *
   */
  public static function getValues($fieldId, &$values, $orderBy = 'weight', $isActive = FALSE) {
    $fieldValueDAO = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue();
    $fieldValueDAO->price_field_id = $fieldId;
    $fieldValueDAO->orderBy($orderBy, 'label');
    if ($isActive) {
      $fieldValueDAO->is_active = 1;
    }
    $fieldValueDAO->find();

    while ($fieldValueDAO->fetch()) {
      CRM_Core_DAO::storeValues($fieldValueDAO, $values[$fieldValueDAO->id]);
    }

    return $values;
  }

  /**
   * Get the price field option label.
   *
   * @param int $id
   *   Id of field option.
   *
   * @return string
   *   name
   *
   */
  public static function getOptionLabel($id) {
    return CRM_Core_DAO::getFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $id, 'label');
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return Object
   *   DAO object on success, null otherwise
   *
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue', $id, 'is_active', $is_active);
  }

  /**
   * Delete all values of the given field id.
   *
   * @param int $fieldId
   *   Price field id.
   *
   * @return bool
   *
   */
  public static function deleteValues($fieldId) {
    if (!$fieldId) {
      return FALSE;
    }

    $fieldValueDAO = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue();
    $fieldValueDAO->price_field_id = $fieldId;
    $fieldValueDAO->delete();
  }

  /**
   * Delete the value.
   *
   * @param int $id
   *   Id.
   *
   * @return bool
   *
   */
  public static function del($id) {
    if (!$id) {
      return FALSE;
    }

    $fieldValueDAO = new CRM_Upgrade_Snapshot_V4p2_Price_DAO_FieldValue();
    $fieldValueDAO->id = $id;
    return $fieldValueDAO->delete();
  }

}
