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
 * Permissions class for Standalone.
 *
 * Note that CRM_Core_Permission_Base is unrelated to CRM_Core_Permission
 * This class, and the _Base class, is to do with CMS permissions, whereas
 * the CRM_Core_Permission class deals with Civi-specific permissioning.
 *
 */
class CRM_Core_Permission_Standalone extends CRM_Core_Permission_Base {

  /**
   * permission mapping to stub check() calls
   * @var array
   */
  public $permissions = NULL;

  /**
   * Given a permission string, check for access requirements.
   *
   * Note this differs from CRM_Core_Permission::check() which handles
   * composite permissions (ORs etc) and Contacts.
   *
   * Some codepaths assume to be able to check a permission through this class;
   * others through CRM_Core_Permission::check().
   *
   * @param string $str
   *   The permission to check.
   * @param int $userId
   *
   * @return bool
   *   true if yes, else false
   */
  public function check($str, $userId = NULL) {
    return \Civi\Standalone\Security::singleton()->checkPermission($this, $str, $userId);
  }

}
