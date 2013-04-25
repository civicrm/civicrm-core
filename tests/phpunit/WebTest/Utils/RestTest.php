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

  protected function assertAPIEquals($apiResult, $cmpvar, $prefix = '') {
    if (!empty($prefix)) {
      $prefix .= ': ';
    }
    $this->assertEquals($cmpvar, $apiResult['is_error'], $prefix . (empty($apiResult['error_message']) ? '' : $apiResult['error_message']));
  }

  protected function setUp() {
    parent::setUp();
    //URL should eventually be adapted for multisite
    $this->url = "{$this->settings->sandboxURL}/{$this->sboxPath}sites/all/modules/civicrm/extern/rest.php";

    $client = CRM_Utils_HttpClient::singleton();
    $params = array(
      "q" => "civicrm/login",
      "key" => $this->settings->sitekey,
      "json" => "1",
      "name" => $this->settings->adminUsername,
      "pass" => $this->settings->adminPassword
    );
    list($status, $data) = $client->post($this->url, $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertAPIEquals($result, 0);
    $this->api_key = $result["api_key"];
    $this->session_id = $result["PHPSESSID"];
    if(!isset($this->api_key)){
      $this->markTestSkipped('Admin does not have an associated API key');
    }
  }

  protected function tearDown() {
    parent::tearDown();
    if(isset($this->nocms_contact_id)){
      $deleteParams = array(
        "id" => $this->nocms_contact_id,
        "skip_undelete" => 1
      );
      $res = $this->webtest_civicrm_api("Contact", "delete", $deleteParams);
      unset($this->nocms_contact_id);
    }
  }

  function testValidLoginCMSUser() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
      $client = CRM_Utils_HttpClient::singleton();
      $params = array(
        "q" => "civicrm/login",
        "key" => $this->settings->sitekey,
        "json" => "1",
        "name" => $this->settings->adminUsername,
        "pass" => $this->settings->adminPassword
      );
      list($status, $data) = $client->post($this->url, $params);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $result = json_decode($data, TRUE);
      $this->assertNotNull($result);
      $this->assertAPIEquals($result, 0);
    }
  }

  function testInvalidPasswordLogin() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
      $client = CRM_Utils_HttpClient::singleton();
      $badPassword = $this->settings->adminPassword . "badpass";
      $params = array(
        "q" => "civicrm/login",
        "key" => $this->settings->sitekey,
        "json" => "1",
        "name" => $this->settings->adminUsername,
        "pass" => $badPassword 
      );
      list($status, $data) = $client->post($this->url, $params);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $result = json_decode($data, TRUE);
      $this->assertNotNull($result);
      $this->assertAPIEquals($result, 1);
    }
  }

  function testValidCallSiteKey() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
      $client = CRM_Utils_HttpClient::singleton();
      $params = array(
        "entity" => "Contact",
        "action" => "get",
        "key" => $this->settings->sitekey,
        "json" => "1",
        "api_key" => $this->api_key
      );
      list($status, $data) = $client->post($this->url, $params);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $result = json_decode($data, TRUE);
      $this->assertNotNull($result);
      $this->assertAPIEquals($result, 0);
    }
  }

  function testValidCallPHPSessionID() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
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
      $this->assertAPIEquals($result, 0);

    }
  }

  function testInvalidAPIKey() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
      $client = CRM_Utils_HttpClient::singleton();
      $params = array(
        "entity" => "Contact",
        "action" => "get",
        "key" => $this->settings->sitekey,
        "json" => "1",
        "api_key" => "zzzzzzzzzzzzzzaaaaaaaaaaaaaaaaabadasdasd"
      );
      list($status, $data) = $client->post($this->url, $params);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $result = json_decode($data, TRUE);
      $this->assertNotNull($result);
      $this->assertAPIEquals($result, 1);
    }
  }

  function testNotCMSUser() {
    if (property_exists($this->settings, 'sitekey') && !empty($this->settings->sitekey)){
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
        "key" => $this->settings->sitekey,
        "json" => "1",
        "api_key" => $test_key
      );
      list($status, $data) = $client->post($this->url, $params);
      $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
      $result = json_decode($data, TRUE);
      $this->assertNotNull($result);
      $this->assertAPIEquals($result, 1);
    }
  }

}
