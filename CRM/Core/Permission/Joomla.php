<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_Joomla extends CRM_Core_Permission_Base {

  /**
   * Given a permission string, check for access requirements
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $userId = NULL) {
    $config = CRM_Core_Config::singleton();
    // JFactory::getUser does strict type checking, so convert falesy values to NULL
    if (!$userId) {
      $userId = NULL;
    }

    $translated = $this->translateJoomlaPermission($str);
    if ($translated === CRM_Core_Permission::ALWAYS_DENY_PERMISSION) {
      return FALSE;
    }
    if ($translated === CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return TRUE;
    }

    // ensure that we are running in a joomla context
    // we've not yet figured out how to bootstrap joomla, so we should
    // not execute hooks if joomla is not loaded
    if (defined('_JEXEC')) {
      $user = JFactory::getUser($userId);
      $api_key    = CRM_Utils_Request::retrieve('api_key', 'String', $store, FALSE, NULL, 'REQUEST');

      // If we are coming from REST we don't have a user but we do have the api_key for a user.
      if ($user->id === 0 && !is_null($api_key)) {
        // This is a codeblock copied from /Civicrm/Utils/REST
        $uid = NULL;
        if (!$uid) {
          $store = NULL;

          $contact_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');

          if ($contact_id) {
            $uid = CRM_Core_BAO_UFMatch::getUFId($contact_id);
          }
          $user = JFactory::getUser($uid);

        }
      }

      return $user->authorise($translated[0], $translated[1]);

    }
    else {

      return FALSE;
    }
  }

  public function isModulePermissionSupported() {
    return TRUE;
  }

  /**
   * @param $perm
   *
   * @internal param string $name e.g. "administer CiviCRM", "cms:access user record", "Drupal:administer content", "Joomla:example.action:com_some_asset"
   * @return ALWAYS_DENY_PERMISSION|ALWAYS_ALLOW_PERMISSION|array(0 => $joomlaAction, 1 => $joomlaAsset)
   */
  public function translateJoomlaPermission($perm) {
    if ($perm === CRM_Core_Permission::ALWAYS_DENY_PERMISSION || $perm === CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return $perm;
    }

    list ($civiPrefix, $name) = CRM_Utils_String::parsePrefix(':', $perm, NULL);
    switch ($civiPrefix) {
      case 'Joomla':
        return explode(':', $name);

      case 'cms':
        // FIXME: This needn't be DENY, but we don't currently have any translations.
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;

      case NULL:
        return ['civicrm.' . CRM_Utils_String::munge(strtolower($name)), 'com_civicrm'];

      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array
   *   The roles to check.
   *
   * @return bool
   *   true if yes, else false
   */
  public function checkGroupRole($array) {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function upgradePermissions($permissions) {
    $translatedPerms = [];

    // Flipping the $permissions array gives us just the raw names of the
    // permissions. The descriptions, etc., are irrelevant for the purposes of
    // this method.
    foreach (array_flip($permissions) as $perm) {
      $translated = $this->translateJoomlaPermission($perm);
      $translatedPerms[] = $translated[0];
    }

    $associations = $this->getUserGroupPermsAssociations();
    $cmsPermsHaveGoneStale = FALSE;
    foreach (array_keys(get_object_vars($associations)) as $permName) {
      if (!in_array($permName, $translatedPerms)) {
        unset($associations->$permName);
        $cmsPermsHaveGoneStale = TRUE;
      }
    }

    if ($cmsPermsHaveGoneStale) {
      $this->updateGroupPermsAssociations($associations);
    }
  }

  /**
   * Fetches the associations between user groups and CiviCRM permissions.
   *
   * @see https://docs.joomla.org/Selecting_data_using_JDatabase
   * @return object
   *   Properties of the object are Joomla-fied permission names.
   */
  private function getUserGroupPermsAssociations() {
    $db = JFactory::getDbo();
    $query = $db->getQuery(TRUE);

    $query
      ->select($db->quoteName('rules'))
      ->from($db->quoteName('#__assets'))
      ->where($db->quoteName('name') . ' = ' . $db->quote('com_civicrm'));

    $db->setQuery($query);

    // Joomla gotcha: loadObject returns NULL in the case of no matches.
    $result = $db->loadObject();
    return $result ? json_decode($result->rules) : (object) [];
  }

  /**
   * Writes user-group/permissions associations back to Joomla.
   *
   * @see https://docs.joomla.org/Inserting,_Updating_and_Removing_data_using_JDatabase
   * @param object $associations
   *   Same format as the return of
   *   CRM_Core_Permission_Joomla->getUserGroupPermsAssociations().
   */
  private function updateGroupPermsAssociations($associations) {
    $db = JFactory::getDbo();
    $query = $db->getQuery(TRUE);

    $query
      ->update($db->quoteName('#__assets'))
      ->set($db->quoteName('rules') . ' = ' . $db->quote(json_encode($associations)))
      ->where($db->quoteName('name') . ' = ' . $db->quote('com_civicrm'));

    $db->setQuery($query)->execute();
  }

}
