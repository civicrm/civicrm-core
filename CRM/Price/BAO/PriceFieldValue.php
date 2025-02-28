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
 * Business objects for managing price fields values.
 *
 */
class CRM_Price_BAO_PriceFieldValue extends CRM_Price_DAO_PriceFieldValue {

  /**
   * Insert/update a new entry in the database.
   *
   * @param array $params
   *
   * @return CRM_Price_DAO_PriceFieldValue
   */
  public static function add($params) {
    $fieldValueBAO = self::writeRecord($params);

    if (!empty($params['is_default'])) {
      $priceFieldID = $params['price_field_id'] ?? CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceFieldValue', $fieldValueBAO->id, 'price_field_id');
      $query = 'UPDATE civicrm_price_field_value SET is_default = 0 WHERE price_field_id = %1 and id != %2';
      $p = [1 => [$priceFieldID, 'Integer'], 2 => [$fieldValueBAO->id, 'Integer']];
      CRM_Core_DAO::executeQuery($query, $p);
    }

    // Reset the cached values in this function.
    CRM_Price_BAO_PriceField::getOptions(0, FALSE, TRUE);
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
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(&$params, $ids = []) {
    $id = $params['id'] ?? $ids['id'] ?? NULL;
    if (!is_array($params) || empty($params)) {
      return NULL;
    }
    if (!$id && empty($params['name'])) {
      $params['name'] = strtolower(CRM_Utils_String::munge(($params['label'] ?? '_'), '_', 242));
    }

    if ($id && !empty($params['weight'])) {
      if (isset($params['name'])) {
        unset($params['name']);
      }

      $oldWeight = NULL;
      if ($id) {
        $oldWeight = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'weight', 'id');
      }
      $fieldValues = ['price_field_id' => $params['price_field_id'] ?? 0];
      $params['weight'] = CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceFieldValue', $oldWeight, $params['weight'], $fieldValues);
    }
    elseif (!$id) {
      CRM_Core_DAO::setCreateDefaults($params, self::getDefaults());
    }

    $financialType = $params['financial_type_id'] ?? NULL;
    if (!$financialType && $id) {
      $financialType = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $id, 'financial_type_id', 'id');
    }
    CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
    if (!empty($financialType) && !array_key_exists($financialType, $financialTypes) && $params['is_active']) {
      throw new CRM_Core_Exception("Financial Type for Price Field Option is either disabled or does not exist");
    }
    $params['id'] = $id;
    return self::add($params);
  }

  /**
   * Get defaults for new entity.
   * @return array
   */
  public static function getDefaults() {
    return [
      'is_active' => 1,
      'weight' => 1,
    ];

  }

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
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
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
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
   *
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
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
    $params = [
      1 => [$entityId, 'Integer'],
      2 => [$entityTable, 'String'],
      3 => [$financialTypeID, 'Integer'],
    ];
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
