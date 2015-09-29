<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

/**
 * Output navigation script tag
 *
 * @param array $params
 *   - is_default: bool, true if this is normal/default instance of the menu (which may be subject to CIVICRM_DISABLE_DEFAULT_MENU)
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   HTML
 */
function smarty_function_crmNavigationMenu($params, &$smarty) {
  $config = CRM_Core_Config::singleton();
  //check if logged in user has access CiviCRM permission and build menu
  $buildNavigation = !CRM_Core_Config::isUpgradeMode() && CRM_Core_Permission::check('access CiviCRM');
  if (defined('CIVICRM_DISABLE_DEFAULT_MENU') && CRM_Utils_Array::value('is_default', $params, FALSE)) {
    $buildNavigation = FALSE;
  }
  if ($config->userFrameworkFrontend) {
    $buildNavigation = FALSE;
  }
  if ($buildNavigation) {
    $session = CRM_Core_Session::singleton();
    $contactID = $session->get('userID');
    if ($contactID) {
      // These params force the browser to refresh the js file when switching user, domain, or language
      // We don't put them as a query string because some browsers will refuse to cache a page with a ? in the url
      // @see CRM_Admin_Page_AJAX::getNavigationMenu
      $lang = $config->lcMessages;
      $domain = CRM_Core_Config::domainID();
      $key = CRM_Core_BAO_Navigation::getCacheKey($contactID);
      $src = CRM_Utils_System::url("civicrm/ajax/menujs/$contactID/$lang/$domain/$key");
      // CRM-15493 QFkey needed for quicksearch bar - must be unique on each page refresh so adding it directly to markup
      $qfKey = CRM_Core_Key::get('CRM_Contact_Controller_Search', TRUE);
      return '<script id="civicrm-navigation-menu" type="text/javascript" src="' . $src . '" data-qfkey=' . json_encode($qfKey) . '></script>';
    }
  }
  return '';
}
