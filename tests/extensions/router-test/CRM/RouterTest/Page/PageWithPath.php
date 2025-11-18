<?php
declare(strict_types = 1);

use CRM_RouterTest_ExtensionUtil as E;

class CRM_RouterTest_Page_PageWithPath extends CRM_Core_Page {

  public function run($path = NULL) {
    if ($path !== ['civicrm', 'route-test', 'page-with-path']) {
      throw new \Exception("Expected path array");
    }

    parent::run();
  }

}
