<?php
namespace Civi\Angular\Page;

/**
 * This page is simply a container; any Angular modules defined by CiviCRM (or by CiviCRM extensions)
 * will be activated on this page.
 *
 * @link https://issues.civicrm.org/jira/browse/CRM-14479
 */
class Main extends \CRM_Core_Page {

  /**
   * Run the page
   */
  public function run() {
    $this->registerResources();
    return parent::run();
  }

  /**
   * Register resources required by Angular.
   */
  public function registerResources() {
    $loader = \Civi::service('angularjs.loader');
    $loader->useApp([
      'activeRoute' => \CRM_Utils_Request::retrieve('route', 'String'),
      'defaultRoute' => NULL,
    ]);
  }

}
