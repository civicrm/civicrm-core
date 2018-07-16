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
 * Class contains functions for phone.
 */
class CRM_Core_BAO_Phone extends CRM_Core_DAO_Phone {

  /**
   * Create phone object - note that the create function calls 'add' but
   * has more business logic
   *
   * @param array $params
   *
   * @return object
   * @throws API_Exception
   */
  public static function create($params) {
    // Ensure mysql phone function exists
    CRM_Core_DAO::checkSqlFunctionsExist();

    if (is_numeric(CRM_Utils_Array::value('is_primary', $params)) ||
      // if id is set & is_primary isn't we can assume no change
      empty($params['id'])
    ) {
      CRM_Core_BAO_Block::handlePrimary($params, get_class());
    }
    $phone = self::add($params);

    return $phone;
  }

  /**
   * Takes an associative array and adds phone.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   CRM_Core_BAO_Phone object on success, null otherwise
   */
  public static function add(&$params) {
    // Ensure mysql phone function exists
    CRM_Core_DAO::checkSqlFunctionsExist();

    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Phone', CRM_Utils_Array::value('id', $params), $params);

    $phone = new CRM_Core_DAO_Phone();
    $phone->copyValues($params);
    $phone->save();

    CRM_Utils_Hook::post($hook, 'Phone', $phone->id, $phone);
    return $phone;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *
   * @return array
   *   array of phone objects
   */
  public static function &getValues($entityBlock) {
    $getValues = CRM_Core_BAO_Block::getValues('phone', $entityBlock);
    return $getValues;
  }

  /**
   * Get all the phone numbers for a specified contact_id, with the primary being first
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   * @param null $type
   * @param array $filters
   *
   * @return array
   *   the array of phone ids which are potential numbers
   */
  public static function allPhones($id, $updateBlankLocInfo = FALSE, $type = NULL, $filters = array()) {
    if (!$id) {
      return NULL;
    }

    $cond = NULL;
    if ($type) {
      $phoneTypeId = array_search($type, CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'));
      if ($phoneTypeId) {
        $cond = " AND civicrm_phone.phone_type_id = $phoneTypeId";
      }
    }

    if (!empty($filters) && is_array($filters)) {
      foreach ($filters as $key => $value) {
        $cond .= " AND " . $key . " = " . $value;
      }
    }

    $query = "
   SELECT phone, civicrm_location_type.name as locationType, civicrm_phone.is_primary as is_primary,
     civicrm_phone.id as phone_id, civicrm_phone.location_type_id as locationTypeId,
     civicrm_phone.phone_type_id as phoneTypeId
     FROM civicrm_contact
LEFT JOIN civicrm_phone ON ( civicrm_contact.id = civicrm_phone.contact_id )
LEFT JOIN civicrm_location_type ON ( civicrm_phone.location_type_id = civicrm_location_type.id )
WHERE     civicrm_contact.id = %1 $cond
ORDER BY civicrm_phone.is_primary DESC,  phone_id ASC ";

    $params = array(
      1 => array(
        $id,
        'Integer',
      ),
    );

    $numbers = $values = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = array(
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'locationTypeId' => $dao->locationTypeId,
        'phoneTypeId' => $dao->phoneTypeId,
      );

      if ($updateBlankLocInfo) {
        $numbers[$count++] = $values;
      }
      else {
        $numbers[$dao->phone_id] = $values;
      }
    }
    return $numbers;
  }

  /**
   * Get all the phone numbers for a specified location_block id, with the primary phone being first.
   *
   * This is called from CRM_Core_BAO_Block as a calculated function.
   *
   * @param array $entityElements
   *   The array containing entity_id and.
   *   entity_table name
   *
   * @param null $type
   *
   * @return array
   *   the array of phone ids which are potential numbers
   */
  public static function allEntityPhones($entityElements, $type = NULL) {
    if (empty($entityElements)) {
      return NULL;
    }

    $cond = NULL;
    if ($type) {
      $phoneTypeId = array_search($type, CRM_Core_PseudoConstant::get('CRM_Core_DAO_Phone', 'phone_type_id'));
      if ($phoneTypeId) {
        $cond = " AND civicrm_phone.phone_type_id = $phoneTypeId";
      }
    }

    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];

    $sql = " SELECT phone, ltype.name as locationType, ph.is_primary as is_primary,
     ph.id as phone_id, ph.location_type_id as locationTypeId
FROM civicrm_loc_block loc, civicrm_phone ph, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   ph.id IN (loc.phone_id, loc.phone_2_id)
AND   ltype.id = ph.location_type_id
ORDER BY ph.is_primary DESC, phone_id ASC ";

    $params = array(
      1 => array(
        $entityId,
        'Integer',
      ),
    );
    $numbers = array();
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $numbers[$dao->phone_id] = array(
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'locationTypeId' => $dao->locationTypeId,
      );
    }
    return $numbers;
  }

  /**
   * Set NULL to phone, mapping, uffield
   *
   * @param $optionId
   *   Value of option to be deleted.
   */
  public static function setOptionToNull($optionId) {
    if (!$optionId) {
      return;
    }
    // Ensure mysql phone function exists
    CRM_Core_DAO::checkSqlFunctionsExist();

    $tables = array(
      'civicrm_phone',
      'civicrm_mapping_field',
      'civicrm_uf_field',
    );
    $params = array(
      1 => array(
        $optionId,
        'Integer',
      ),
    );

    foreach ($tables as $tableName) {
      $query = "UPDATE `{$tableName}` SET `phone_type_id` = NULL WHERE `phone_type_id` = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Call common delete function.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
    // Ensure mysql phone function exists
    CRM_Core_DAO::checkSqlFunctionsExist();
    return CRM_Contact_BAO_Contact::deleteObjectWithPrimary('Phone', $id);
  }

}
