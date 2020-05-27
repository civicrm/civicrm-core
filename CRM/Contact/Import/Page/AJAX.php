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
 * This class contains all the function that are called using AJAX.
 */
class CRM_Contact_Import_Page_AJAX {

  /**
   * Show import status.
   */
  public static function status() {
    // make sure we get an id
    if (!isset($_GET['id'])) {
      return;
    }

    $config = CRM_Core_Config::singleton();
    $file = "{$config->uploadDir}status_{$_GET['id']}.txt";
    if (file_exists($file)) {
      $str = file_get_contents($file);
      echo $str;
    }
    else {
      $status = "<div class='description'>&nbsp; " . ts('No processing status reported yet.') . "</div>";
      echo json_encode([0, $status]);
    }
    CRM_Utils_System::civiExit();
  }

}
