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

  /**
   * @return array
   *   ~~Ex: ['my_provider' => 'My Provider']~~
   *   Ex: ['my_provider' => 'my_provider']
   */
  public static function getProviders() {
    if (!isset(Civi::$statics[__FUNCTION__])) {
      if (!class_exists('\Civi\Api4\OAuthProvider')) {
        return [];
      }
      $ps = Civi\Api4\OAuthProvider::get(FALSE)
        ->setSelect(['name', 'title'])
        ->execute();
      $titles = [];
      foreach ($ps as $p) {
        $titles[$p['name']] = $p['name'];
        // $titles[$p['name']] = $p['title'];
      }
      Civi::$statics[__FUNCTION__] = $titles;
    }
    return Civi::$statics[__FUNCTION__];
  }

  /**
   * Determine the "redirect_uri". When using authorization-code flow, the
   * OAuth2 provider will redirect back to our "redirect_uri".
   *
   * @return string
   */
  public static function getRedirectUri() {
    return \Civi::settings()->get('oauthClientRedirectUrl') ?:
      \CRM_Utils_System::url('civicrm/oauth-client/return', NULL, TRUE, NULL, FALSE, FALSE, TRUE);
  }

}
