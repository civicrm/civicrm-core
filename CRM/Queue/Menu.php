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
 * This file hard-codes the path entries for the queueing UI, which
 * allows us to use these paths during upgrades.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

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
        $menuPath['access_callback'] = ['CRM_Core_Permission', 'checkMenu'];
        break;

      case 'civicrm/queue/ajax/runNext':
      case 'civicrm/upgrade/queue/ajax/runNext':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = ['CRM_Queue_Page_AJAX', 'runNext'];
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = ['CRM_Core_Permission', 'checkMenu'];
        break;

      case 'civicrm/queue/ajax/skipNext':
      case 'civicrm/upgrade/queue/ajax/skipNext':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = ['CRM_Queue_Page_AJAX', 'skipNext'];
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = ['CRM_Core_Permission', 'checkMenu'];
        break;

      case 'civicrm/queue/ajax/onEnd':
      case 'civicrm/upgrade/queue/ajax/onEnd':
        $menuPath['path'] = $path;
        $menuPath['page_callback'] = ['CRM_Queue_Page_AJAX', 'onEnd'];
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = ['CRM_Core_Permission', 'checkMenu'];
        break;

      case 'civicrm/queue/monitor':
        // Not supported: case 'civicrm/upgrade/queue/monitor':
        $menuPath['path'] = $path;
        $menuPath['title'] = 'Queue Monitor';
        $menuPath['page_callback'] = 'CRM_Queue_Page_Monitor';
        $menuPath['access_arguments'][0][] = 'access CiviCRM';
        $menuPath['access_callback'] = ['CRM_Core_Permission', 'checkMenu'];
        break;

      default:
        // unrecognized
    }
  }

}
