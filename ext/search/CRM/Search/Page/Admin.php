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
class CRM_Search_Page_Admin extends CRM_Core_Page {

  /**
   * @var string[]
   */
  private $allowedEntities = [];

  public function run() {
    $breadCrumb = [
      'title' => ts('Search Kit'),
      'url' => CRM_Utils_System::url('civicrm/search'),
    ];
    CRM_Utils_System::appendBreadCrumb([$breadCrumb]);

    $schema = \Civi\Search\Admin::getSchema();

    // If user does not have permission to search any entity, bye bye.
    if (!$schema) {
      CRM_Utils_System::permissionDenied();
    }

    // Add client-side vars for the search UI
    $vars = [
      'schema' => $schema,
      'links' => \Civi\Search\Admin::getLinks(array_column($schema, 'name')),
    ];

    Civi::resources()
      ->addBundle('bootstrap3')
      ->addVars('search', $vars);

    // Load angular module
    $loader = new Civi\Angular\AngularLoader();
    $loader->setModules(['searchAdmin']);
    $loader->setPageName('civicrm/search');
    $loader->useApp([
      'defaultRoute' => '/create/Contact',
    ]);
    $loader->load();
    parent::run();
  }

}
