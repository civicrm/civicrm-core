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
 * Dashboard page for managing Access Control.
 */
class CRM_Admin_Page_Access extends CRM_Core_Page {

  /**
   * @return string
   */
  public function run() {
    $urlParams = CRM_Utils_System::getCMSPermissionsUrlParams();
    if (isset($urlParams['ufAccessURL'])) {
      $this->assign('ufAccessURL', $urlParams['ufAccessURL']);
    }
    if (isset($urlParams['jAccessParams'])) {
      $this->assign('jAccessParams', $urlParams['jAccessParams']);
    }
    return parent::run();
  }

}
