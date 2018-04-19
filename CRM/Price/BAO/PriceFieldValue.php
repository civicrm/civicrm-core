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

/**
 * Business objects for managing price fields values.
 *
 */
class CRM_Price_BAO_PriceFieldValue extends CRM_Price_DAO_PriceFieldValue {

  /**
   * Insert/update a new entry in the database.
   *
   * @param array $params
   *
   * @param array $ids
   *  Deprecated variable.
   *
   * @return CRM_Price_DAO_PriceFieldValue
   */
  public static function add(&$params, $ids = array()) {

    $fieldValueBAO = new CRM_Price_BAO_PriceFieldValue();
    $fieldValueBAO->copyValues($params);

    if ($id = CRM_Utils_Array::value('id', $ids)) {
      $fieldValueBAO->id = $id;
      $prevLabel = self::getOptionLabel($id);
      if (!empty($params['label']) && $prevLabel != $params['label']) {
        self::updateAmountAndFeeLevel($id, $prevLabel, $params['label']);
      }
    }
    // CRM-16189
    $priceFieldID = CRM_Utils_Array::value('price_field_id', $params);
    if (!$priceFieldID) {
      $priceFieldID = CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceFieldValue', $id, 'price_field_id');
    }
    if (!empty($params['is_default'])) {
      $query = 'UPDATE civicrm_price_field_value SET is_default = 0 WHERE  price_field_id = %1';
      $p = array(1 => array($params['price_field_id'], 'Integer'));
      CRM_Core_DAO::executeQuery($query, $p);
    }

    $fieldValueBAO->save();
    // Reset the cached values in this function.
    CRM_Price_BAO_PriceField::getOptions(CRM_Utils_Array::value('price_field_id', $params), FALSE, TRUE);
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

    $financialType = CRM_Utils_Array::value('financial_type_id', $params, NULL);
    if (!$financialType && $id) {
      $financialType = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'financial_type_id', 'id');
    }
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    if (!empty($financialType) && !array_key_exists($financialType, $financialTypes) && $params['is_active']) {
      throw new CRM_Core_Exception("Financial Type for Price Field Option is either disabled or does not exist");
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
   * Retrieve all values for given field id.
   *
   * @param int $fieldId
   *   Price_field_id.
   * @param array $values
   *   (reference ) to hold the values.
   * @param string $orderBy
   *   For order by, default weight.
   * @param bool|int $isActive is_active, default false
   * @param bool $admin is this loading it for use on an admin page.
   *
   * @return array
   *
   */
  public static function getValues($fieldId, &$values, $orderBy = 'weight', $isActive = FALSE, $admin = FALSE) {
    $sql = "SELECT cs.id FROM civicrm_price_set cs INNER JOIN civicrm_price_field cp ON cp.price_set_id = cs.id 
              WHERE cs.name IN ('default_contribution_amount', 'default_membership_type_amount') AND cp.id = {$fieldId} ";
    $setId = CRM_Core_DAO::singleValueQuery($sql);
    $fieldValueDAO = new CRM_Price_DAO_PriceFieldValue();
    $fieldValueDAO->price_field_id = $fieldId;
    $addWhere = '';
    if (!$setId) {
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
      if (!$admin) {
        $addWhere = "financial_type_id IN (0)";
      }
      if (!empty($financialTypes) && !$admin) {
        $addWhere = "financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ")";
      }
      if (!empty($addWhere)) {
        $fieldValueDAO->whereAdd($addWhere);
      }
    }
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

  /**
   * Update price option label in line_item, civicrm_contribution and civicrm_participant.
   *
   * @param int $id - id of the price_field_value
   * @param string $prevLabel
   * @param string $newLabel
   *
   */
  public static function updateAmountAndFeeLevel($id, $prevLabel, $newLabel) {
    // update price field label in line item.
    $lineItem = new CRM_Price_DAO_LineItem();
    $lineItem->price_field_value_id = $id;
    $lineItem->label = $prevLabel;
    $lineItem->find();
    while ($lineItem->fetch()) {
      $lineItemParams['id'] = $lineItem->id;
      $lineItemParams['label'] = $newLabel;
      CRM_Price_BAO_LineItem::create($lineItemParams);

      // update amount and fee level in civicrm_contribution and civicrm_participant
      $params = array(
        1 => array(CRM_Core_DAO::VALUE_SEPARATOR . $prevLabel . ' -', 'String'),
        2 => array(CRM_Core_DAO::VALUE_SEPARATOR . $newLabel . ' -', 'String'),
      );
      // Update contribution
      if (!empty($lineItem->contribution_id)) {
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_contribution` SET `amount_level` = REPLACE(amount_level, %1, %2) WHERE id = {$lineItem->contribution_id}", $params);
      }
      // Update participant
      if ($lineItem->entity_table == 'civicrm_participant') {
        CRM_Core_DAO::executeQuery("UPDATE `civicrm_participant` SET `fee_level` = REPLACE(fee_level, %1, %2) WHERE id = {$lineItem->entity_id}", $params);
      }
    }
  }

}
