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

  public function getSoapEndPoints() {
    $endPoints = [];
    if (CIVICRM_UF !== 'Drupal8') {
      $endPoints['traditional'] = CRM_Core_Resources::singleton()->getUrl('civicrm', 'extern/soap.php');
    }
    $endPoints['normal'] = CRM_Utils_System::url('civicrm/soap', NULL, TRUE, NULL, FALSE, TRUE);
    return $endPoints;
  }

  public function setUp() {
    CRM_Core_Config::singleton(1, 1);

    global $_CV;
    $this->adminUser = $_CV['ADMIN_USER'];
    $this->adminPass = $_CV['ADMIN_PASS'];

    foreach (array('adminUser', 'adminPass') as $prop) {
      if (empty($this->{$prop})) {
        $this->markTestSkipped("Failed to lookup user, or password. Have you configured `cv` for testing?");
      }
    }
  }

  /**
   * Send a request with bad credentials.
   *
   * @expectedException SoapFault
   */
  public function testAuthenticationBadPassword() {
    foreach ($this->getSoapEndPoints() as $key => $url) {
      $this->url = $url;
      if (empty($this->url)) {
        $this->markTestSkipped("Failed to set url. Have you configured `cv` for testing?");
      }
      $client = $this->createClient();
      $client->authenticate($this->adminUser, mt_rand());
    }
  }

  /**
   * Send a request with bad credentials.
   *
   * @expectedException SoapFault
   */
  public function testAuthenticationBadKey() {
    foreach ($this->getSoapEndPoints() as $key => $url) {
      $this->url = $url;
      if (empty($this->url)) {
        $this->markTestSkipped("Failed to set url. Have you configured `cv` for testing?");
      }
      $client = $this->createClient();
      $key = $client->authenticate($this->adminUser, $this->adminPass);
      $client->get_contact(mt_rand(), []);
    }
  }

  /**
   * A basic test for one SOAP function.
   */
  public function testGetContact() {
    foreach ($this->getSoapEndPoints() as $key => $url) {
      $this->url = $url;
      if (empty($this->url)) {
        $this->markTestSkipped("Failed to set url. Have you configured `cv` for testing?");
      }
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
