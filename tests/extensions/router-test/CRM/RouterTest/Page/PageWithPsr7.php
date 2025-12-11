<?php
declare(strict_types = 1);

use CRM_RouterTest_ExtensionUtil as E;
use Psr\Http\Message\RequestInterface;

class CRM_RouterTest_Page_PageWithPsr7 extends CRM_Core_Page {

  public function run(?RequestInterface $request = NULL) {
    $expected = '/civicrm/route-test/page-with-psr7';
    $actual = $request->getUri()->getPath();
    if ($actual !== $expected) {
      throw new \Exception("PSR-7 reports wrong path. Actual=$actual Expected=$expected");
    }

    parent::run();
  }

}
