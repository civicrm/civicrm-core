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
    $params = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURL,
      'is_primary' => 1,
    ];

    $openObject = CRM_Core_BAO_OpenID::add($params);

    $openId = $openObject->id;

    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURL, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // Now call add() to modify an existing open-id record

    $params = [
      'id' => $openId,
      'contact_id' => $contactId,
      'openid' => $openIdURL,
      'is_bulkmail' => 1,
      'allowed_to_login' => 1,
    ];

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

    $params = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURL,
      'is_primary' => 1,
    ];

    $openObject = CRM_Core_BAO_OpenID::add($params);

    $openId = $openObject->id;
    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURL, 'id', 'openid',
      'Database check for created OpenID.'
    );

    $allowedToLogin = CRM_Core_BAO_OpenID::isAllowedToLogin($openIdURL);
    $this->assertEquals($allowedToLogin, FALSE, 'Verify allowed_to_login value is 0.');

    // Now call add() to modify an existing open-id record

    $params = [
      'id' => $openId,
      'contact_id' => $contactId,
      'openid' => $openIdURL,
      'is_bulkmail' => 1,
      'allowed_to_login' => 1,
    ];

    CRM_Core_BAO_OpenID::add($params);

    $allowedToLogin = CRM_Core_BAO_OpenID::isAllowedToLogin($openIdURL);

    $this->assertEquals($allowedToLogin, TRUE, 'Verify allowed_to_login value is 1.');
    $this->contactDelete($contactId);
    //domain contact doesn't really get deleted //
    $this->assertDBRowNotExist('CRM_Contact_DAO_Contact', $contactId);
  }

}
