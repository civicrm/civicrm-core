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
class CRM_OAuth_BAO_OAuthClient extends CRM_OAuth_DAO_OAuthClient {

  /**
   * Create a new OAuthClient based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_OAuth_DAO_OAuthClient|NULL
   *
   * public static function create($params) {
   * $className = 'CRM_OAuth_DAO_OAuthClient';
   * $entityName = 'OAuthClient';
   * $hook = empty($params['id']) ? 'create' : 'edit';
   *
   * CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
   * $instance = new $className();
   * $instance->copyValues($params);
   * $instance->save();
   * CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
   *
   * return $instance;
   * } */

}
