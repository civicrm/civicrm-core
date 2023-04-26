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
 * This class contain function for IM handling
 */
class CRM_Core_BAO_IM extends CRM_Core_DAO_IM implements Civi\Core\HookInterface {
  use CRM_Contact_AccessTrait;

  /**
   * @deprecated
   *
   * @param array $params
   * @return CRM_Core_DAO_IM
   * @throws CRM_Core_Exception
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Event fired before modifying an IM.
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
   * @return CRM_Core_DAO_IM
   * @throws CRM_Core_Exception
   */
  public static function add($params) {
    return self::create($params);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock input parameters to find object
   *
   * @return bool
   */
  public static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('im', $entityBlock);
  }

  /**
   * Get all the ims for a specified contact_id, with the primary im being first
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   the array of im details
   */
  public static function allIMs($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_im.name as im, civicrm_location_type.name as locationType, civicrm_im.is_primary as is_primary,
civicrm_im.id as im_id, civicrm_im.location_type_id as locationTypeId,
civicrm_im.provider_id as providerId
FROM      civicrm_contact
LEFT JOIN civicrm_im ON ( civicrm_im.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_im.location_type_id = civicrm_location_type.id )
WHERE
  civicrm_contact.id = %1
ORDER BY
  civicrm_im.is_primary DESC, im_id ASC ";
    $params = [1 => [$id, 'Integer']];

    $ims = $values = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->im_id,
        'name' => $dao->im,
        'locationTypeId' => $dao->locationTypeId,
        'providerId' => $dao->providerId,
      ];

      if ($updateBlankLocInfo) {
        $ims[$count++] = $values;
      }
      else {
        $ims[$dao->im_id] = $values;
      }
    }
    return $ims;
  }

  /**
   * Get all the ims for a specified location_block id, with the primary im being first
   *
   * @param array $entityElements
   *   The array containing entity_id and.
   *   entity_table name
   *
   * @return array
   *   the array of im details
   */
  public static function allEntityIMs(&$entityElements) {
    if (empty($entityElements)) {
      return NULL;
    }
    $entityId = $entityElements['entity_id'];
    $entityTable = $entityElements['entity_table'];
    $sql = "SELECT cim.name as im, ltype.name as locationType, cim.is_primary as is_primary, cim.id as im_id, cim.location_type_id as locationTypeId
FROM civicrm_loc_block loc, civicrm_im cim, civicrm_location_type ltype, {$entityTable} ev
WHERE ev.id = %1
AND   loc.id = ev.loc_block_id
AND   cim.id IN (loc.im_id, loc.im_2_id)
AND   ltype.id = cim.location_type_id
ORDER BY cim.is_primary DESC, im_id ASC ";

    $params = [1 => [$entityId, 'Integer']];

    $ims = [];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    while ($dao->fetch()) {
      $ims[$dao->im_id] = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->im_id,
        'name' => $dao->im,
        'locationTypeId' => $dao->locationTypeId,
      ];
    }
    return $ims;
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

}
