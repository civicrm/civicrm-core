<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
class WebTest_Utils_RestTest extends CiviSeleniumTestCase {
  protected $url;
  protected $api_key;
  protected $session_id;
  protected $nocms_contact_id;

  protected function assertAPIErrorCode($apiResult, $cmpvar, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $this->assertEquals($cmpvar, $apiResult['is_error'], $prefix . (empty($apiResult['error_message']) ? '' : $apiResult['error_message']));
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
  }

  protected function tearDown() {
    parent::tearDown();
    if (isset($this->nocms_contact_id)) {
      $deleteParams = array(
        "id" => $this->nocms_contact_id,
        "skip_undelete" => 1
      );
      $res = $this->webtest_civicrm_api("Contact", "delete", $deleteParams);
      unset($this->nocms_contact_id);
    }
  }

  /*
  function testValidLoginCMSUser() {
    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "q" => "civicrm/login",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "name" => $this->settings->adminUsername,
      "pass" => $this->settings->adminPassword
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 0);
  }

  function testInvalidPasswordLogin() {
    $client = CRM_Utils_HttpClient::singleton();
    $badPassword = $this->settings->adminPassword . "badpass";
    $params = array(
      "q" => "civicrm/login",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "name" => $this->settings->adminUsername,
      "pass" => $badPassword
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  function testValidCallPHPSessionID() {
    $this->_setUpAdminSessionIdAndApiKey();
    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "json" => "1",
      "PHPSESSID" => $this->session_id,
      "api_key" => $this->api_key,
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 0);
  }
  */

  function testValidCallAPIKey() {
    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "api_key" => $this->settings->adminApiKey,
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 0);
  }

  function testInvalidAPIKey() {
    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "api_key" => 'garbage_' . $this->settings->adminApiKey,
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  function testNotCMSUser() {
    $client = CRM_Utils_HttpClient::singleton();
    //Create contact with api_key
    $test_key = "testing1234";
    $contactParams = array(
      "api_key" => $test_key,
      "contact_type" => "Individual",
      "first_name" => "RestTester1"
    );
    $contact = $this->webtest_civicrm_api("Contact", "create", $contactParams);
    $this->nocms_contact_id = $contact["id"];

    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "api_key" => $test_key
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  /*
  protected function _setUpAdminSessionIdAndApiKey() {
    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "q" => "civicrm/login",
      "key" => $this->settings->siteKey,
      "json" => "1",
      "name" => $this->settings->adminUsername,
      "pass" => $this->settings->adminPassword
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertAPIErrorCode($result, 0);
    $this->api_key = $result["api_key"];
    $this->session_id = $result["PHPSESSID"];
    $this->assertTrue(isset($this->api_key), 'Failed to find admin API key');
    return $result;
  } // */
}
