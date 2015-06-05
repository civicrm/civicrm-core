<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Business objects for managing price fields values.
 *
 */
class CRM_Price_BAO_PriceFieldValue extends CRM_Price_DAO_PriceFieldValue {

  /**
   * Insert/update a new entry in the database.
   *
   * @param array $params
   *   (reference), array $ids.
   *
   * @param $ids
   *
   * @return CRM_Price_DAO_PriceFieldValue
   */
  public static function add(&$params, $ids = array()) {

    $fieldValueBAO = new CRM_Price_BAO_PriceFieldValue();
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
   * @return CRM_Price_DAO_PriceFieldValue
   */
  public static function create(&$params, $ids = array()) {
    $id = CRM_Utils_Array::value('id', $params, CRM_Utils_Array::value('id', $ids));
    if (!is_array($params) || empty($params)) {
      return NULL;
    }
    if (!$id && empty($params['name'])) {
      $params['name'] = strtolower(CRM_Utils_String::munge($params['label'], '_', 242));
    }

    if ($id && !empty($params['weight'])) {
      if (isset($params['name'])) {
        unset($params['name']);
      }

      $oldWeight = NULL;
      if ($id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'weight', 'id');
      }

      $fieldValues = array('price_field_id' => CRM_Utils_Array::value('price_field_id', $params, 0));
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceFieldValue', $oldWeight, $params['weight'], $fieldValues);
    }
    else {
      if (!$id) {
        CRM_Core_DAO::setCreateDefaults($params, self::getDefaults());
        if (empty($params['name'])) {
          $params['name'] = CRM_Utils_String::munge(CRM_Utils_Array::value('label', $params), '_', 64);
        }
      }
    }
    return self::add($params, $ids);
  }

  /**
   * Get defaults for new entity.
   * @return array
   */
  public static function getDefaults() {
    return array(
      'is_active' => 1,
      'weight' => 1,
    );

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
   * @return CRM_Price_DAO_PriceFieldValue
   */
  public static function retrieve(&$params, &$defaults) {
    return CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_PriceFieldValue', $params, $defaults);
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
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    $addWhere = "financial_type_id IN (0)";
    if (!empty($financialTypes)) {
      $addWhere = "financial_type_id IN (" . implode(',' , array_keys($financialTypes)) . ")";
    }
    $fieldValueDAO = new CRM_Price_DAO_PriceFieldValue();
    $fieldValueDAO->price_field_id = $fieldId;
    $fieldValueDAO->whereAdd($addWhere);
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
    return CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'label');
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
    return CRM_Core_DAO::setFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'is_active', $is_active);
  }

  /**
   * Delete all values of the given field id.
   *
   * @param int $fieldId
   *   Price field id.
   *
   *
   */
  public static function deleteValues($fieldId) {
    if (!$fieldId) {
      return;
    }

    $fieldValueDAO = new CRM_Price_DAO_PriceFieldValue();
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

    $fieldValueDAO = new CRM_Price_DAO_PriceFieldValue();
    $fieldValueDAO->id = $id;
    return $fieldValueDAO->delete();
  }

  /**
   * Update civicrm_price_field_value.financial_type_id
   * when financial_type_id of contribution_page or event is changed
   *
   * @param int $entityId
   *   Id.
   * @param string $entityTable table.
   *   Entity table.
   * @param string $financialTypeID type id.
   *   Financial type id.
   *
   */
  public static function updateFinancialType($entityId, $entityTable, $financialTypeID) {
    if (!$entityId || !$entityTable || !$financialTypeID) {
      return;
    }
    $params = array(
      1 => array($entityId, 'Integer'),
      2 => array($entityTable, 'String'),
      3 => array($financialTypeID, 'Integer'),
    );
    // for event discount
    $join = $where = '';
    if ($entityTable == 'civicrm_event') {
      $join = " LEFT JOIN civicrm_discount cd ON cd.price_set_id = cps.id AND cd.entity_id = %1  AND cd.entity_table = %2 ";
      $where = ' OR cd.id IS NOT NULL ';
    }
    $sql = "UPDATE civicrm_price_set cps
LEFT JOIN civicrm_price_set_entity cpse ON cpse.price_set_id = cps.id AND cpse.entity_id = %1 AND cpse.entity_table = %2
LEFT JOIN civicrm_price_field cpf ON cpf.price_set_id = cps.id
LEFT JOIN civicrm_price_field_value cpfv ON cpf.id = cpfv.price_field_id
{$join}
SET cpfv.financial_type_id = CASE
  WHEN cpfv.membership_type_id IS NOT NULL
  THEN cpfv.financial_type_id
  ELSE %3
END,
cps.financial_type_id = %3
WHERE cpse.id IS NOT NULL {$where}";

    CRM_Core_DAO::executeQuery($sql, $params);
  }

}
