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
 * Verify that the REST API bindings correctly parse and authenticate requests.
 *
 * @group e2e
 */
class E2E_Extern_RestTest extends CiviEndToEndTestCase {
  protected $url;
  protected static $api_key;
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
    $this->assertEquals($cmpvar, $apiResult['is_error'],
      $prefix . (empty($apiResult['error_message']) ? '' : $apiResult['error_message']));
    //$this->assertEquals($cmpvar, $apiResult['is_error'], $prefix . print_r($apiResult, TRUE));
  }

  protected function setUp() {
    parent::setUp();

    if (empty($GLOBALS['_CV']['CIVI_SITE_KEY'])) {
      $this->markTestSkipped('Missing siteKey');
    }

    $this->old_api_keys = array();
  }

  protected function getRestUrl() {
    return CRM_Core_Resources::singleton()
      ->getUrl('civicrm', 'extern/rest.php');
  }

  protected function tearDown() {
    if (!empty($this->old_api_keys)) {
      foreach ($this->old_api_keys as $cid => $apiKey) {
        civicrm_api3('Contact', 'create', array(
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
      $res = civicrm_api3("Contact", "delete", $deleteParams);
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
      // query
      array(
        "entity" => "Contact",
        "action" => "get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
      ),
      // is_error
      1,
    );

    // entity,action: valid apiKey, valid entity+action
    $cases[] = array(
      // query
      array(
        "entity" => "Contact",
        "action" => "get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => self::getApiKey(),
      ),
      // is_error
      0,
    );

    // entity,action: bad apiKey, valid entity+action
    $cases[] = array(
      // query
      array(
        "entity" => "Contact",
        "action" => "get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => 'garbage_' . self::getApiKey(),
      ),
      // is_error
      1,
    );

    // entity,action: valid apiKey, invalid entity+action
    $cases[] = array(
      // query
      array(
        "entity" => "Contactses",
        "action" => "get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => self::getApiKey(),
      ),
      // is_error
      1,
    );

    // q=civicrm/entity/action: omit apiKey, valid entity+action
    $cases[] = array(
      // query
      array(
        "q" => "civicrm/contact/get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
      ),
      // is_error
      1,
    );

    // q=civicrm/entity/action: valid apiKey, valid entity+action
    $cases[] = array(
      // query
      array(
        "q" => "civicrm/contact/get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => self::getApiKey(),
      ),
      // is_error
      0,
    );

    // q=civicrm/entity/action: invalid apiKey, valid entity+action
    $cases[] = array(
      // query
      array(
        "q" => "civicrm/contact/get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => 'garbage_' . self::getApiKey(),
      ),
      // is_error
      1,
    );

    // q=civicrm/entity/action: valid apiKey, invalid entity+action
    $cases[] = array(
      // query
      array(
        "q" => "civicrm/contactses/get",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => self::getApiKey(),
      ),
      // is_error
      1,
    );

    // q=civicrm/entity/action: valid apiKey, invalid entity+action
    // XXX Actually Ping is valid, no?
    $cases[] = array(
      // query
      array(
        "q" => "civicrm/ping",
        "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
        "json" => "1",
        "api_key" => self::getApiKey(),
      ),
      // is_error
      0,
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
    list($status, $data) = $client->post($this->getRestUrl(), $query);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    if ($result === NULL) {
      $msg = print_r(array(
        'restUrl' => $this->getRestUrl(),
        'query' => $query,
        'response data' => $data,
      ), TRUE);
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
    $contact = civicrm_api3("Contact", "create", $contactParams);
    $this->nocms_contact_id = $contact["id"];

    // The key associates with a real contact but not a real user
    $params = array(
      "entity" => "Contact",
      "action" => "get",
      "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
      "json" => "1",
      "api_key" => $test_key,
    );
    list($status, $data) = $client->post($this->getRestUrl(), $params);
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
    $contact = civicrm_api3("Contact", "create", $contactParams);
    $this->nocms_contact_id = $contact["id"];

    // The key associates with a real contact but not a real user
    $params = array(
      "q" => "civicrm/contact/get",
      "key" => $GLOBALS['_CV']['CIVI_SITE_KEY'],
      "json" => "1",
      "api_key" => $test_key,
    );
    list($status, $data) = $client->post($this->getRestUrl(), $params);
    $this->assertEquals(CRM_Utils_HttpClient::STATUS_OK, $status);
    $result = json_decode($data, TRUE);
    $this->assertNotNull($result);
    $this->assertAPIErrorCode($result, 1);
  }

  protected function updateAdminApiKey() {
    /** @var int $adminContactId */
    $adminContactId = civicrm_api3('contact', 'getvalue', array(
      'id' => '@user:' . $GLOBALS['_CV']['ADMIN_USER'],
      'return' => 'id',
    ));

    $this->old_api_keys[$adminContactId] = CRM_Core_DAO::singleValueQuery('SELECT api_key FROM civicrm_contact WHERE id = %1', [
      1 => [$adminContactId, 'Positive'],
    ]);

    //$this->old_admin_api_key = civicrm_api3('Contact', 'get', array(
    //  'id' => $adminContactId,
    //  'return' => 'api_key',
    //));

    civicrm_api3('Contact', 'create', array(
      'id' => $adminContactId,
      'api_key' => self::getApiKey(),
    ));
  }

  protected static function getApiKey() {
    if (empty(self::$api_key)) {
      self::$api_key = mt_rand() . mt_rand();
    }
    return self::$api_key;
  }

}
