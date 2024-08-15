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

use CRM_Search_ExtensionUtil as E;

/**
 * Ajax callback used to reload admin settings when they may have changed.
 */
class CRM_Search_Page_AJAX extends CRM_Core_Page {

  public function run() {
    $response = \Civi\Search\Admin::getAdminSettings();
    CRM_Utils_JSON::output($response);
  }

}
