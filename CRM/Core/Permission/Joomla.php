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
    if ($userId === 0 || $userId === '0') {
      $userId = 0;
    }
    elseif (!$userId) {
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
      if (version_compare(JVERSION, '4.0', 'lt')) {
        $user = JFactory::getUser($userId);
      }
      else {
        if ($userId == NULL) {
          $user = \Joomla\CMS\Factory::getApplication()->getIdentity() ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0);
        }
        else {
          $user = \Joomla\CMS\Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($userId);
        }
      }
      $api_key = CRM_Utils_Request::retrieve('api_key', 'String');

      // If we are coming from REST we don't have a user but we do have the api_key for a user.
      if ($user->id === 0 && !is_null($api_key)) {
        $contact_id = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $api_key, 'id', 'api_key');
        $uid = ($contact_id) ? CRM_Core_BAO_UFMatch::getUFId($contact_id) : NULL;
        if (version_compare(JVERSION, '4.0', 'lt')) {
          $user = JFactory::getUser($uid);
        }
        else {
          if ($uid == NULL) {
            $user = \Joomla\CMS\Factory::getApplication()->getIdentity() ?? \Joomla\CMS\Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(0);
          }
          else {
            $user = \Joomla\CMS\Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById($uid);
          }
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
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $db = JFactory::getDbo();
    }
    else {
      $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    }
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
    if (version_compare(JVERSION, '4.0', 'lt')) {
      $db = JFactory::getDbo();
    }
    else {
      $db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
    }
    $query = $db->getQuery(TRUE);

    $query
      ->update($db->quoteName('#__assets'))
      ->set($db->quoteName('rules') . ' = ' . $db->quote(json_encode($associations)))
      ->where($db->quoteName('name') . ' = ' . $db->quote('com_civicrm'));

    $db->setQuery($query)->execute();
  }

}
