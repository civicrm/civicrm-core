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
 * Page for displaying list of categories for Settings.
 */
class CRM_Admin_Page_Setting extends CRM_Core_Page {

  /**
   * Run page.
   *
   * @return string
   * @throws Exception
   */
  public function run() {
    CRM_Core_Error::fatal('This page is deprecated. If you have followed a link or have been redirected here, please change link or redirect to Admin Console (/civicrm/admin?reset=1)');
    CRM_Utils_System::setTitle(ts("Global Settings"));

    return parent::run();
  }

}
