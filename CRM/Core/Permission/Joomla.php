<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 *
 */
class CRM_Core_Permission_Joomla extends CRM_Core_Permission_Base {
  /**
   * given a permission string, check for access requirements
   *
   * @param string $str the permission to check
   *
   * @return boolean true if yes, else false
   * @access public
   */
  function check($str) {
    $config = CRM_Core_Config::singleton();

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
      $permission = JFactory::getUser()->authorise($translated[0], $translated[1]);
      return $permission;
    }
    else {
      // This function is supposed to return a boolean. What does '(1)' mean?
      return '(1)';
    }
  }

  /**
   * @param $perm
   *
   * @internal param string $name e.g. "administer CiviCRM", "cms:access user record", "Drupal:administer content", "Joomla:example.action:com_some_asset"
   * @return ALWAYS_DENY_PERMISSION|ALWAYS_ALLOW_PERMISSION|array(0 => $joomlaAction, 1 => $joomlaAsset)
   */
  function translateJoomlaPermission($perm) {
    if ($perm === CRM_Core_Permission::ALWAYS_DENY_PERMISSION || $perm === CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION) {
      return $perm;
    }

    list ($civiPrefix, $name) = CRM_Utils_String::parsePrefix(':', $perm, NULL);
    switch($civiPrefix) {
      case 'Joomla':
        return explode(':', $name);
      case 'cms':
        // FIXME: This needn't be DENY, but we don't currently have any translations.
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
      case NULL:
        return array('civicrm.' . CRM_Utils_String::munge(strtolower($name)), 'com_civicrm');
      default:
        return CRM_Core_Permission::ALWAYS_DENY_PERMISSION;
    }
  }

  /**
   * Given a roles array, check for access requirements
   *
   * @param array $array the roles to check
   *
   * @return boolean true if yes, else false
   * @static
   * @access public
   */
  function checkGroupRole($array) {
    return FALSE;
  }
}

