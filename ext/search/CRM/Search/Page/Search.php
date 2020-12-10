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
 * Angular base page for search admin
 */
class CRM_Search_Page_Search extends CRM_Core_Page {

  public function run() {

    Civi::resources()->addBundle('bootstrap3');

    // Load angular module
    $loader = new Civi\Angular\AngularLoader();
    $loader->setPageName('civicrm/search');
    $loader->useApp();
    $loader->load();

    parent::run();
  }

}
