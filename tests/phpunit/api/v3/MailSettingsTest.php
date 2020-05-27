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
 * Class api_v3_MailSettingsTest
 *
 * @group headless
 */
class api_v3_MailSettingsTest extends CiviUnitTestCase {

  protected $_apiversion = 3;

  protected $params;

  protected $id;

  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->params = [
      'domain_id' => 1,
      'name' => "my mail setting",
      'domain' => 'setting.com',
      'localpart' => 'civicrm+',
      'server' => "localhost",
      'username' => 'sue',
      'password' => 'pass',
      'is_default' => 1,
    ];
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test creation.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateMailSettings($version) {
    $this->_apiversion = $version;
    $this->callAPISuccessGetCount('mail_settings', [], 1);
    $result = $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('MailSettings', 'delete', ['id' => $result['id']]);
    $this->callAPISuccessGetCount('mail_settings', [], 1);
  }

  /**
   * Test caches cleared adequately.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testCreateUpdateMailSettings($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('MailSettings', 'create', $this->params);
    $this->assertEquals('setting.com', CRM_Core_BAO_MailSettings::defaultDomain());
    $this->callAPISuccess('mail_settings', 'create', ['id' => $result['id'], 'domain' => 'updated.com']);
    $this->assertEquals('updated.com', CRM_Core_BAO_MailSettings::defaultDomain());
    $this->callAPISuccess('MailSettings', 'delete', ['id' => $result['id']]);
    $this->callAPISuccessGetCount('mail_settings', [], 1);
  }

  /**
   * Test get method.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetMailSettings($version) {
    $this->_apiversion = $version;
    $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $result = $this->callAPIAndDocument('MailSettings', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('MailSettings', 'delete', ['id' => $result['id']]);
    $this->callAPISuccessGetCount('mail_settings', [], 1);
  }

  /**
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteMailSettings($version) {
    $this->_apiversion = $version;
    $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $entity = $this->callAPISuccess('MailSettings', 'get', $this->params);
    $this->assertEquals('setting.com', $entity['values'][$entity['id']]['domain']);
    $this->callAPIAndDocument('MailSettings', 'delete', ['id' => $entity['id']], __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess('MailSettings', 'get', []);
    $this->assertEquals('EXAMPLE.ORG', $checkDeleted['values'][$checkDeleted['id']]['domain']);
  }

  /**
   * Test chained delete.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetMailSettingsChainDelete($version) {
    $this->_apiversion = $version;
    $description = "Demonstrates get + delete in the same call.";
    $subFile = 'ChainedGetDelete';
    $params = [
      'name' => "delete this setting",
      'api.MailSettings.delete' => 1,
    ];
    $this->callAPISuccess('MailSettings', 'create', ['name' => "delete this setting"] + $this->params);
    $result = $this->callAPIAndDocument('MailSettings', 'get', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals(0, $this->callAPISuccess('MailSettings', 'getcount', ['name' => "delete this setting"]));
  }

}
