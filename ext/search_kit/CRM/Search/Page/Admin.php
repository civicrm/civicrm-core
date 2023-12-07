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
 * Angular base page for search admin
 */
class CRM_Search_Page_Admin extends CRM_Core_Page {

  public function run() {
    $breadCrumb = [
      'title' => E::ts('SearchKit'),
      'url' => CRM_Utils_System::url('civicrm/admin/search', NULL, FALSE, '/list'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    // Load angular module
    Civi::service('angularjs.loader')
      ->useApp(['defaultRoute' => '/list']);

    parent::run();
  }

}
