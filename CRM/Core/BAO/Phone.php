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
 * Class contains functions for phone.
 */
class CRM_Core_BAO_Phone extends CRM_Core_DAO_Phone implements Civi\Core\HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_DAO_Phone
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Event fired before modifying a Phone.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      CRM_Core_BAO_Block::handlePrimary($event->params, __CLASS__);
    }
  }

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_DAO_Phone
   * @throws \CRM_Core_Exception
   */
  public static function add($params) {
    return self::create($params);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *
   * @return array
   *   array of phone objects
   * @throws \CRM_Core_Exception
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
   * @param bool $updateBlankLocInfo
   * @param string|null $type
   * @param array $filters
   *
   * @return array
   *   the array of phone ids which are potential numbers
   */
  public static function allPhones($id, $updateBlankLocInfo = FALSE, $type = NULL, $filters = []) {
    if (!$id) {
      return NULL;
    }

    $cond = NULL;
    if ($type) {
      $phoneTypeId = CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Phone', 'phone_type_id', $type);
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

    $params = [
      1 => [
        $id,
        'Integer',
      ],
    ];

    $numbers = $values = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'locationTypeId' => $dao->locationTypeId,
        'phoneTypeId' => $dao->phoneTypeId,
      ];

      if ($updateBlankLocInfo) {
        $numbers[$count++] = $values;
      }
      else {
        $numbers[$dao->phone_id ?? ''] = $values;
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
   *   The array containing entity_id and entity_table name
   * @param string|null $type
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
      $phoneTypeId = array_search($type, CRM_Core_DAO_Phone::buildOptions('phone_type_id'));
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

    $params = [
      1 => [
        $entityId,
        'Integer',
      ],
    ];
    $numbers = [];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $numbers[$dao->phone_id] = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->phone_id,
        'phone' => $dao->phone,
        'locationTypeId' => $dao->locationTypeId,
      ];
    }
    return $numbers;
  }

  /**
   * Set NULL to phone, mapping, uffield
   *
   * @param int $optionId
   *   Value of option to be deleted.
   */
  public static function setOptionToNull($optionId) {
    if (!$optionId) {
      return;
    }

    $tables = [
      'civicrm_phone',
      'civicrm_mapping_field',
      'civicrm_uf_field',
    ];
    $params = [
      1 => [
        $optionId,
        'Integer',
      ],
    ];

    foreach ($tables as $tableName) {
      $query = "UPDATE `{$tableName}` SET `phone_type_id` = NULL WHERE `phone_type_id` = %1";
      CRM_Core_DAO::executeQuery($query, $params);
    }
  }

  /**
   * Call common delete function.
   *
   * @see \CRM_Contact_BAO_Contact::on_hook_civicrm_post
   *
   * @param int $id
   * @deprecated
   * @return bool
   */
  public static function del($id) {
    CRM_Core_Error::deprecatedFunctionWarning('deleteRecord');
    return (bool) self::deleteRecord(['id' => $id]);
  }

  /**
   * Customize search criteria for SMS autocompletes
   *
   * @param \Civi\Core\Event\GenericHookEvent $e
   */
  public static function on_civi_search_autocompleteDefault(\Civi\Core\Event\GenericHookEvent $e) {
    $formName = $e->formName ?? '';
    if (!str_contains($formName, '_Form_Task_SMS') || !is_array($e->savedSearch) || $e->savedSearch['api_entity'] !== 'Phone') {
      return;
    }
    $e->savedSearch['api_params'] = [
      'version' => 4,
      'select' => [
        'id',
        'phone',
        'phone_numeric',
        'phone_type_id:label',
        'location_type_id:label',
        'contact_id.sort_name',
      ],
      'orderBy' => [],
      'where' => [
        ['phone_type_id:name', '=', 'Mobile'],
        ['contact_id.is_deleted', '=', FALSE],
        ['contact_id.is_deceased', '=', FALSE],
        ['contact_id.do_not_sms', '=', FALSE],
      ],
      'groupBy' => [],
      'join' => [],
      'having' => [],
    ];
  }

}
