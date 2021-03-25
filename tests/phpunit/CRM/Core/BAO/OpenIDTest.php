<?php

use Civi\Api4\OpenID;

/**
 * Class CRM_Core_BAO_OpenIDTest
 * @group headless
 */
class CRM_Core_BAO_OpenIDTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_openid', 'civicrm_email']);
    parent::tearDown();
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

    $openId = OpenID::create(FALSE)->setValues($params)->execute()->first()['id'];

    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURL, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // Now modify an existing open-id record

    $params = [
      'id' => $openId,
      'contact_id' => $contactId,
      'openid' => $openIdURL,
      'is_bulkmail' => 1,
      'allowed_to_login' => 1,
    ];

    OpenID::update(FALSE)->addWhere('id', '=', $openId)->setValues($params)->execute();

    $allowedToLogin = $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openId, 'allowed_to_login', 'id',
      'Database check on updated OpenID record.'
    );
    $this->assertEquals($allowedToLogin, 1, 'Verify allowed_to_login value is 1.');
  }

  /**
   * AllOpenIDs() method - get all OpenIDs for the given contact
   */
  public function testAllOpenIDs() {
    $contactId = $this->individualCreate();
    $this->assertDBRowExist('CRM_Contact_DAO_Contact', $contactId);

    // create first openid
    $openIdURLOne = "http://test-one-username.civicrm.org/";
    $params = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURLOne,
      'is_primary' => 1,
      'allowed_to_login' => 1,
    ];

    $openIdOne = OpenID::create(FALSE)->setValues($params)->execute()->first()['id'];
    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURLOne, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // create second openid
    $openIdURLTwo = "http://test-two-username.civicrm.org/";
    $params = [
      'contact_id' => $contactId,
      'location_type_id' => 1,
      'openid' => $openIdURLTwo,
    ];

    $openIdTwo = OpenID::create(FALSE)->setValues($params)->execute()->first()['id'];

    $this->assertDBNotNull('CRM_Core_DAO_OpenID', $openIdURLTwo, 'id', 'openid',
      'Database check for created OpenID.'
    );

    // obtain all openids for the contact
    $openIds = CRM_Core_BAO_OpenID::allOpenIDs($contactId);

    // check number of openids for the contact
    $this->assertCount(2, $openIds, 'Checking number of returned open-ids.');

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
  }

}
