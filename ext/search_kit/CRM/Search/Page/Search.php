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
class CRM_Search_Page_Search extends CRM_Core_Page {

  public function run() {

    Civi::service('angularjs.loader')->useApp();

    if (CRM_Core_Permission::check('administer CiviCRM')) {
      CRM_Utils_System::appendBreadCrumb([['title' => E::ts('SearchKit'), 'url' => CRM_Utils_System::url('civicrm/admin/search')]]);
    }

    parent::run();
  }

}
