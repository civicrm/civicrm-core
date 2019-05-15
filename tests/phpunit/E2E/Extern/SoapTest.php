<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Verify that the SOAP bindings correctly parse and authenticate requests.
 * @group e2e
 */
class E2E_Extern_SoapTest extends CiviEndToEndTestCase {

  /**
   * @var string
   */
  public $url;

  /**
   * @var string
   */
  public $adminUser;

  /**
   * @var string
   */
  public $adminPass;

  public function setUp() {
    CRM_Core_Config::singleton(1, 1);

    global $_CV;
    $this->adminUser = $_CV['ADMIN_USER'];
    $this->adminPass = $_CV['ADMIN_PASS'];
    $this->url = CRM_Core_Resources::singleton()->getUrl('civicrm', 'extern/soap.php');

    foreach (array('adminUser', 'adminPass', 'url') as $prop) {
      if (empty($this->{$prop})) {
        $this->markTestSkipped("Failed to lookup SOAP URL, user, or password. Have you configured `cv` for testing?");
      }
    }
  }

  /**
   * Send a request with bad credentials.
   *
   * @expectedException SoapFault
   */
  public function testAuthenticationBadPassword() {
    $client = $this->createClient();
    $client->authenticate($this->adminUser, mt_rand());
  }

  /**
   * Send a request with bad credentials.
   *
   * @expectedException SoapFault
   */
  public function testAuthenticationBadKey() {
    $client = $this->createClient();
    $key = $client->authenticate($this->adminUser, $this->adminPass);
    $client->get_contact(mt_rand(), array());
  }

  /**
   * A basic test for one SOAP function.
   */
  public function testGetContact() {
    $client = $this->createClient();
    $key = $client->authenticate($this->adminUser, $this->adminPass);
    $contacts = $client->get_contact($key, array(
      'contact_id' => 101,
      'return.display_name' => 1,
    ));
    $this->assertEquals($contacts['is_error'], 0);
    $this->assertEquals($contacts['count'], 1);
    $this->assertEquals($contacts['values'][101]['contact_id'], 101);
  }

  /**
   * @return \SoapClient
   */
  protected function createClient() {
    return new SoapClient(NULL, array(
      'location' => $this->url,
      'uri' => 'urn:civicrm',
      'trace' => 1,
    ));
  }

}
