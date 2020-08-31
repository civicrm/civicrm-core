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
 * Test class for class.api.php
 *
 * @package CiviCRM_APIv3
 * @group headless
 */
class class_api_test extends CiviUnitTestCase {

  /**
   * Test that error doesn't occur for non-existent file.
   */
  public function testConstructor() {
    require_once 'api/class.api.php';

    // Check no params is local
    $api = new civicrm_api3();
    $this->assertEquals(TRUE, $api->local);

    // Be sure to include keys otherwise these calls die()
    $keys = ['key' => 'my_site_key', 'api_key' => 'my_api_key'];

    // Check empty server string is local
    $api = new civicrm_api3(['server' => ''] + $keys);
    $this->assertEquals(TRUE, $api->local);

    // Check non-empty server string is remote, check default uri
    $api = new civicrm_api3(['server' => 'http://my_server'] + $keys);
    $this->assertEquals(FALSE, $api->local);
    $this->assertEquals('http://my_server/sites/all/modules/civicrm/extern/rest.php', $api->uri);

    // Check path in uri
    $api = new civicrm_api3(['server' => 'http://my_server', 'path' => 'wibble'] + $keys);
    $this->assertEquals('http://my_server/wibble', $api->uri);

  }

}
