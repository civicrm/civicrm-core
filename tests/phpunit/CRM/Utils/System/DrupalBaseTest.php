<?php

/**
 * Class CRM_Utils_DrupalBaseTest
 * @group headless
 */
class CRM_Utils_DrupalBaseTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testFormatResourceUrl() {
    global $base_url;
    $url = $base_url . "/sites/all/modules/civicrm/js/crm.menubar.js";
  }

}
