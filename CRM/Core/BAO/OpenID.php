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
   * @return CRM_Core_DAO_OpenID
   */
  public static function add($params) {
    return self::writeRecord($params);
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
