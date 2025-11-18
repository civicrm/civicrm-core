<?php
declare(strict_types = 1);

use CRM_RouterTest_ExtensionUtil as E;
use Psr\Http\Message\RequestInterface;

class CRM_RouterTest_Page_PageWithPsr7 extends CRM_Core_Page {

  public function run(?RequestInterface $request = NULL) {
    if ($request->getUri()->getPath() !== 'civicrm/route-test/page-with-psr7') {
      throw new \Exception("Expected path array");
    }

    parent::run();
  }

}
