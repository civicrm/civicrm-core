<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Verify that the REST API bindings correctly parse and authenticate requests.
 */
class WebTest_Utils_RestTest extends CiviSeleniumTestCase {
  protected $url;
  protected $api_key;
  protected $session_id;
  protected $nocms_contact_id;
  protected $old_api_keys;

  /**
   * @param $apiResult
   * @param $cmpvar
   * @param string $prefix
   */
  protected function assertAPIErrorCode($apiResult, $cmpvar, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $this->assertEquals($cmpvar, $apiResult['is_error'], $prefix . (empty($apiResult['error_message']) ? '' : $apiResult['error_message']));
    //$this->assertEquals($cmpvar, $apiResult['is_error'], $prefix . print_r($apiResult, TRUE));
  }

  protected function setUp() {
    parent::setUp();
    //URL should eventually be adapted for multisite
    $this->url = "{$this->settings->sandboxURL}/{$this->sboxPath}sites/all/modules/civicrm/extern/rest.php";

    if (!property_exists($this->settings, 'siteKey') || empty($this->settings->siteKey)) {
      $this->markTestSkipped('CiviSeleniumSettings is missing siteKey');
    }
    if (!property_exists($this->settings, 'adminApiKey') || empty($this->settings->adminApiKey)) {
      $this->markTestSkipped('CiviSeleniumSettings is missing adminApiKey');
    }

    $this->old_api_keys = array();
  }

  protected function tearDown() {
    if (!empty($this->old_api_keys)) {
      foreach ($this->old_api_keys as $cid => $apiKey) {
        $this->webtest_civicrm_api('Contact', 'create', array(
          'id' => $cid,
          'api_key' => $apiKey,
        ));
      }
    }
    parent::tearDown();
    if (isset($this->nocms_contact_id)) {
      $deleteParams = array(
        "id" => $this->nocms_contact_id,
        "skip_undelete" => 1,
      );
      $res = $this->webtest_civicrm_api("Contact", "delete", $deleteParams);
      unset($this->nocms_contact_id);
    }
  }

  /**
   * Build a list of test cases. Each test case defines a set of REST query
   * parameters and an expected outcome for the REST request (eg is_error=>1 or is_error=>0).
   *
   * @return array; each item is a list of parameters for testAPICalls
   */
  public function apiTestCases() {
    $cases = array();

    // entity,action: omit apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "entity" => "Contact",
        "action" => "get",
        "key" => $this->settings->siteKey,
        "json" => "1",
      ),
      1, // is_error
    );

    // entity,action: valid apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "entity" => "Contact",
        "action" => "get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => $this->settings->adminApiKey,
      ),
      0, // is_error
    );

    // entity,action: bad apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "entity" => "Contact",
        "action" => "get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => 'garbage_' . $this->settings->adminApiKey,
      ),
      1, // is_error
    );

    // entity,action: valid apiKey, invalid entity+action
    $cases[] = array(
      array(// query
        "entity" => "Contactses",
        "action" => "get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => $this->settings->adminApiKey,
      ),
      1, // is_error
    );

    // q=civicrm/entity/action: omit apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "q" => "civicrm/contact/get",
        "key" => $this->settings->siteKey,
        "json" => "1",
      ),
      1, // is_error
    );

    // q=civicrm/entity/action: valid apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "q" => "civicrm/contact/get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => $this->settings->adminApiKey,
      ),
      0, // is_error
    );

    // q=civicrm/entity/action: invalid apiKey, valid entity+action
    $cases[] = array(
      array(// query
        "q" => "civicrm/contact/get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => 'garbage_' . $this->settings->adminApiKey,
      ),
      1, // is_error
    );

    // q=civicrm/entity/action: valid apiKey, invalid entity+action
    $cases[] = array(
      array(// query
        "q" => "civicrm/contactses/get",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => $this->settings->adminApiKey,
      ),
      1, // is_error
    );

    // q=civicrm/entity/action: valid apiKey, invalid entity+action
    // XXX Actually Ping is valid, no?
    $cases[] = array(
      array(// query
        "q" => "civicrm/ping",
        "key" => $this->settings->siteKey,
        "json" => "1",
        "api_key" => $this->settings->adminApiKey,
      ),
      0, // is_error
    );

    return $cases;
  }

  /**
   * @dataProvider apiTestCases
   * @param $query
   * @param $is_error
   */
  public function testAPICalls($query, $is_error) {
    $this->updateAdminApiKey();

    $client = CRM_Utils_HttpClient::singleton();
    list($status, $data) = $client->post($this->url, $query);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    if ($result === NULL) {
      $msg = print_r(array('query' => $query, 'response data' => $data), TRUE);
      $this->assertNotNull($result, $msg);
    }
    $this->assertAPIErrorCode($result, $is_error);
  }

  /**
   * Submit a request with an API key that exists but does not correspond to.
   * a real user. Submit in "?entity=X&action=X" notation
   */
  public function testNotCMSUser_entityAction() {
    $client = CRM_Utils_HttpClient::singleton();

    //Create contact with api_key
    $test_key = "testing1234";
    $contactParams = array(
      "api_key" => $test_key,
      "contact_type" => "Individual",
      "first_name" => "RestTester1",
    );
    $contact = $this->webtest_civicrm_api("Contact", "create", $contactParams);
    $this->nocms_contact_id = $contact["id"];

    // The key associates with a real contact but not a real user
    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "api_key" => $test_key,
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  /**
   * Submit a request with an API key that exists but does not correspond to
   * a real user. Submit in "?q=civicrm/$entity/$action" notation
   */
  public function testNotCMSUser_q() {
    $client = CRM_Utils_HttpClient::singleton();

    //Create contact with api_key
    $test_key = "testing1234";
    $contactParams = array(
      "api_key" => $test_key,
      "contact_type" => "Individual",
      "first_name" => "RestTester1",
    );
    $contact = $this->webtest_civicrm_api("Contact", "create", $contactParams);
    $this->nocms_contact_id = $contact["id"];

    // The key associates with a real contact but not a real user
    $params = array(
      "q" => "civicrm/contact/get",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "api_key" => $test_key,
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  protected function updateAdminApiKey() {
    $this->webtestLogin($this->settings->adminUsername, $this->settings->adminPassword);
    $adminContact = $this->webtestGetLoggedInContact();
    $this->webtestLogout();

    $this->old_api_keys[$adminContact['id']] = CRM_Core_DAO::singleValueQuery('SELECT api_key FROM civicrm_contact WHERE id = %1', array(
      1 => array($adminContact['id'], 'Positive'),
    ));

    //$this->old_admin_api_key = $this->webtest_civicrm_api('Contact', 'get', array(
    //  'id' => $adminContact['id'],
    //  'return' => 'api_key',
    //));

    $this->webtest_civicrm_api('Contact', 'create', array(
      'id' => $adminContact['id'],
      'api_key' => $this->settings->adminApiKey,
    ));
  }

}
