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
 * Class to handle requests for APIv3 Rest Interface.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Page_API_3_Rest extends CRM_Core_Page {

  public function run() {
    if (defined('PANTHEON_ENVIRONMENT')) {
      ini_set('session.save_handler', 'files');
    }
    $rest = new CRM_Utils_REST();

    // Json-appropriate header will be set by CRM_Utils_Rest
    // But we need to set header here for non-json
    if (empty($_GET['json'])) {
      header('Content-Type: text/xml');
    }
    echo $rest->run();
    CRM_Utils_System::civiExit();
  }

}
