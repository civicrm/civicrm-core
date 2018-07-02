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
 * This class contains function for Open Id
 */
class CRM_Core_BAO_OpenID extends CRM_Core_DAO_OpenID {

  /**
   * Takes an associative array and adds OpenID.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   CRM_Core_BAO_OpenID object on success, null otherwise
   */
  public static function add(&$params) {
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'OpenID', CRM_Utils_Array::value('id', $params), $params);

    $openId = new CRM_Core_DAO_OpenID();
    $openId->copyValues($params);
    $openId->save();

    CRM_Utils_Hook::post($hook, 'OpenID', $openId->id, $openId);
    return $openId;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $entityBlock
   *   Input parameters to find object.
   *
   * @return mixed
   */
  public static function &getValues($entityBlock) {
    return CRM_Core_BAO_Block::getValues('openid', $entityBlock);
  }

  /**
   * Returns whether or not this OpenID is allowed to login.
   *
   * @param string $identity_url
   *   The OpenID to check.
   *
   * @return bool
   */
  public static function isAllowedToLogin($identity_url) {
    $openId = new CRM_Core_DAO_OpenID();
    $openId->openid = $identity_url;
    if ($openId->find(TRUE)) {
      return $openId->allowed_to_login == 1;
    }
    return FALSE;
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
    $params = array(1 => array($id, 'Integer'));

    $openids = $values = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $count = 1;
    while ($dao->fetch()) {
      $values = array(
        'locationType' => $dao->locationType,
        'is_primary' => $dao->is_primary,
        'id' => $dao->openid_id,
        'openid' => $dao->openid,
        'locationTypeId' => $dao->locationTypeId,
        'allowed_to_login' => $dao->allowed_to_login,
      );

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
