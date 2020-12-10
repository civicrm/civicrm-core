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
 * This class contains function for Open Id
 */
class CRM_Core_BAO_OpenID extends CRM_Core_DAO_OpenID {

  /**
   * Create or update OpenID record.
   *
   * @param array $params
   *
   * @return CRM_Core_DAO_OpenID
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public static function create($params) {
    CRM_Core_BAO_Block::handlePrimary($params, __CLASS__);
    return self::writeRecord($params);
  }

  /**
   * Create or update OpenID record.
   *
   * @deprecated
   *
   * @param array $params
   *
   * @return \CRM_Core_DAO|\CRM_Core_DAO_IM
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public static function add($params) {
    CRM_Core_Error::deprecatedFunctionWarning('use the v4 api');
    return self::create($params);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *   Input parameters to find object.
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  public static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('openid', $entityBlock);
  }

  /**
   * Get all the openids for a specified contact_id, with the primary openid being first
   *
   * @param int $id
   *   The contact id.
   *
   * @param bool $updateBlankLocInfo
   *
   * @return array
   *   the array of openid's
   */
  public static function allOpenIDs($id, $updateBlankLocInfo = FALSE) {
    if (!$id) {
      return NULL;
    }

    $query = "
SELECT civicrm_openid.openid, civicrm_location_type.name as locationType, civicrm_openid.is_primary as is_primary,
civicrm_openid.allowed_to_login as allowed_to_login, civicrm_openid.id as openid_id,
civicrm_openid.location_type_id as locationTypeId
FROM      civicrm_contact
LEFT JOIN civicrm_openid ON ( civicrm_openid.contact_id = civicrm_contact.id )
LEFT JOIN civicrm_location_type ON ( civicrm_openid.location_type_id = civicrm_location_type.id )
WHERE
  civicrm_contact.id = %1
ORDER BY
  civicrm_openid.is_primary DESC,  openid_id ASC ";
    $params = [1 => [$id, 'Integer']];

    $openids = $values = [];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = [
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->openid_id,
        'openid' => $dao->openid,
        'locationTypeId' => $dao->locationTypeId,
        'allowed_to_login' => $dao->allowed_to_login,
      ];

      if ($updateBlankLocInfo) {
        $openids[$count++] = $values;
      }
      else {
        $openids[$dao->openid_id] = $values;
      }
    }
    return $openids;
  }

  /**
   * Call common delete function.
   *
   * @param int $id
   *
   * @return bool
   */
  public static function del($id) {
    return CRM_Contact_BAO_Contact::deleteObjectWithPrimary('OpenID', $id);
  }

}
