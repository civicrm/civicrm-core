<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * Api Explorer
 */
class CRM_Admin_Page_APIExplorer extends CRM_Core_Page {

  /**
   * @return string
   */
  public function run() {
    CRM_Utils_System::setTitle('CiviCRM API');
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Admin/Page/APIExplorer.js')
      ->addScriptUrl('//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.js', 99)
      ->addStyleUrl('//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css', 99);

    $this->assign('operators', CRM_Core_DAO::acceptedSQLOperators());

    // List example directories
    global $civicrm_root;
    $examples = array();
    foreach (scandir($civicrm_root . 'api/v3/examples') as $item) {
      if ($item && strpos($item, '.') === FALSE) {
        $examples[] = $item;
      }
    }
    $this->assign('examples', $examples);

    return parent::run();
  }

  /**
   * Get user context.
   *
   * @return string
   *   user context.
   */
  public function userContext() {
    return 'civicrm/api/explorer';
  }

  /**
   * AJAX callback to fetch examples
   */
  public static function getExampleFile() {
    global $civicrm_root;
    if (!empty($_GET['entity']) && strpos($_GET['entity'], '.') === FALSE) {
      $examples = array();
      foreach (scandir($civicrm_root . 'api/v3/examples/' . $_GET['entity']) as $item) {
        $item = str_replace('.php', '', $item);
        if ($item && strpos($item, '.') === FALSE) {
          $examples[] = array('key' => $item, 'value' => $item);
        }
      }
      CRM_Utils_JSON::output($examples);
    }
    if (!empty($_GET['file']) && strpos($_GET['file'], '.') === FALSE) {
      $fileName = $civicrm_root . 'api/v3/examples/' . $_GET['file'] . '.php';
      if (file_exists($fileName)) {
        echo file_get_contents($fileName);
      }
      else {
        echo "Not found.";
      }
      CRM_Utils_System::civiExit();
    }
  }

}
