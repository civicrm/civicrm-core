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
 * Base page for Afform admin
 */
class CRM_AfformAdmin_Page_Base extends CRM_Core_Page {

  public function run() {
    $breadCrumb = [
      'title' => ts('Form Builder'),
      'url' => CRM_Utils_System::url('civicrm/admin/afform', NULL, FALSE, '/'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    // Load angular module
    $loader = new Civi\Angular\AngularLoader();
    $loader->setPageName('civicrm/admin/afform');
    $loader->useApp();
    $loader->load();
    parent::run();
  }

}
