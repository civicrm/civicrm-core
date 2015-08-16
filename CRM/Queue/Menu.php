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
 * This file hard-codes the path entries for the queueing UI, which
 * allows us to use these paths during upgrades.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */

require_once 'CRM/Core/I18n.php';

/**
 * Class CRM_Queue_Menu
 */
class CRM_Queue_Menu {

  /**
   * @param string $path
   *   The path for which we are trying to locate the route.
   * @param array $menuPath
   *   The route.
   */
  public static function alter($path, &$menuPath) {
    switch ($path) {
      case 'civicrm/queue/runner':
      case 'civicrm/upgrade/queue/runner':
        $menuPath['path'] = $path;
        $menuPath['title'] = 'Queue Runner';
        $menuPath['page_callback'] = 'CRM_Queue_Page_Runner';
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
        break;

      case 'civicrm/queue/ajax/runNext':
      case 'civicrm/upgrade/queue/ajax/runNext':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = array('CRM_Queue_Page_AJAX', 'runNext');
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
        break;

      case 'civicrm/queue/ajax/skipNext':
      case 'civicrm/upgrade/queue/ajax/skipNext':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = array('CRM_Queue_Page_AJAX', 'skipNext');
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
        break;

      case 'civicrm/queue/ajax/onEnd':
      case 'civicrm/upgrade/queue/ajax/onEnd':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = array('CRM_Queue_Page_AJAX', 'onEnd');
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = array('CRM_Core_Permission', 'checkMenu');
        break;

      default:
        // unrecognized
    }
  }

}
