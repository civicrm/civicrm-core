<?php

/**
 * Class CRM_Core_BAO_OpenIDTest
 * @group headless
 */
class CRM_Core_BAO_OpenIDTest extends CiviUnitTestCase {

  public function tearDown() {
    // If we truncate only contact, then stale domain and openid records will be left.
    // If we truncate none of these tables, then contactDelete() will incrementally
    // clean correctly.
    //$tablesToTruncate = array('civicrm_domain', 'civicrm_contact', 'civicrm_openid');
    //$this->quickCleanup($tablesToTruncate);
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * Add() method (create and update modes)
   */
  public function testAdd() {
    $contactId = $this->individualCreate();
    $this->assertDBRowExist('CRM_Contact_DAO_Contact', $contactId);

    $openIdURL = "http://test-username.civicrm.org/";
    $params = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURL,
      'is_primary' => 1,
    );

    $openObject = CRM_Core_BAO_OpenID::add($params);

    $openId = $openObject->id;

    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURL, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // Now call add() to modify an existing open-id record

    $params = array(
      'id' => $openId,
      'contact_id' => $contactId,
      'openid' => $openIdURL,
      'is_bulkmail' => 1,
      'allowed_to_login' => 1,
    );

    CRM_Core_BAO_OpenID::add($params);

    $allowedToLogin = $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openId, 'allowed_to_login', 'id',
      'Database check on updated OpenID record.'
    );
    $this->assertEquals($allowedToLogin, 1, 'Verify allowed_to_login value is 1.');

    $this->contactDelete($contactId);
    $this->assertDBRowNotExist('CRM_Contact_DAO_Contact', $contactId);
  }

  /**
   * IfAllowedToLogin() method (set and reset allowed_to_login)
   */
  public function testIfAllowedToLogin() {
    $contactId = $this->individualCreate();
    $this->assertDBRowExist('CRM_Contact_DAO_Contact', $contactId);
    $openIdURL = "http://test-username.civicrm.org/";

    $params = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURL,
      'is_primary' => 1,
    );

    $openObject = CRM_Core_BAO_OpenID::add($params);

    $openId = $openObject->id;
    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURL, 'id', 'openid',
      'Database check for created OpenID.'
    );

    $allowedToLogin = CRM_Core_BAO_OpenID::isAllowedToLogin($openIdURL);
    $this->assertEquals($allowedToLogin, FALSE, 'Verify allowed_to_login value is 0.');

    // Now call add() to modify an existing open-id record

    $params = array(
      'id' => $openId,
      'contact_id' => $contactId,
      'openid' => $openIdURL,
      'is_bulkmail' => 1,
      'allowed_to_login' => 1,
    );

    CRM_Core_BAO_OpenID::add($params);

    $allowedToLogin = CRM_Core_BAO_OpenID::isAllowedToLogin($openIdURL);

    $this->assertEquals($allowedToLogin, TRUE, 'Verify allowed_to_login value is 1.');
    $this->contactDelete($contactId);
    //domain contact doesn't really get deleted //
    $this->assertDBRowNotExist('CRM_Contact_DAO_Contact', $contactId);
  }

  /**
   * AllOpenIDs() method - get all OpenIDs for the given contact
   */
  public function testAllOpenIDs() {
    $contactId = $this->individualCreate();
    $this->assertDBRowExist('CRM_Contact_DAO_Contact', $contactId);

    // create first openid
    $openIdURLOne = "http://test-one-username.civicrm.org/";
    $params = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURLOne,
      'is_primary' => 1,
      'allowed_to_login' => 1,
    );

    $openObjectOne = CRM_Core_BAO_OpenID::add($params);

    $openIdOne = $openObjectOne->id;
    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURLOne, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // create second openid
    $openIdURLTwo = "http://test-two-username.civicrm.org/";
    $params = array(
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURLTwo,
    );

    $openObjectTwo = CRM_Core_BAO_OpenID::add($params);
    $openIdTwo = $openObjectTwo->id;

    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURLTwo, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // obtain all openids for the contact
    $openIds = CRM_Core_BAO_OpenID::allOpenIDs($contactId);

    // check number of openids for the contact
    $this->assertEquals(2, count($openIds), 'Checking number of returned open-ids.');

    // check first openid values
    $this->assertEquals($openIdURLOne, $openIds[$openIdOne]['openid'],
      'Confirm first openid value.'
    );
    $this->assertEquals(1, $openIds[$openIdOne]['is_primary'], 'Confirm is_primary field value.');
    $this->assertEquals(1, $openIds[$openIdOne]['allowed_to_login'], 'Confirm allowed_to_login field value.');

    // check second openid values
    $this->assertEquals($openIdURLTwo, $openIds[$openIdTwo]['openid'],
      'Confirm second openid value.'
    );
    $this->assertEquals(0, $openIds[$openIdTwo]['is_primary'], 'Confirm is_primary field value for second openid.');
    $this->assertEquals(0, $openIds[$openIdTwo]['allowed_to_login'], 'Confirm allowed_to_login field value for second openid.');

    $this->contactDelete($contactId);
    $this->assertDBRowNotExist('CRM_Contact_DAO_Contact', $contactId);
  }

}
