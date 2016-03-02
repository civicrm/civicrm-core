<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
 */

/**
 * Class api_v3_MailSettingsTest
 * @group headless
 */
class api_v3_MailSettingsTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $id;
  public $DBResetRequired = FALSE;

  public function setUp() {
    $this->params = array(
      'domain_id' => 1,
      'name' => "my mail setting",
      'domain' => 'setting.com',
      'local_part' => 'civicrm+',
      'server' => "localhost",
      'username' => 'sue',
      'password' => 'pass',
      'is_default' => 1,
    );
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Test creation.
   */
  public function testCreateMailSettings() {
    $this->callAPISuccessGetCount('mail_settings', array(), 1);
    $result = $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('MailSettings', 'delete', array('id' => $result['id']));
    $this->callAPISuccessGetCount('mail_settings', array(), 1);
  }

  /**
   * Test caches cleared adequately.
   */
  public function testCreateUpdateMailSettings() {
    $result = $this->callAPISuccess('MailSettings', 'create', $this->params);
    $this->assertEquals('setting.com', CRM_Core_BAO_MailSettings::defaultDomain());
    $this->callAPISuccess('mail_settings', 'create', array('id' => $result['id'], 'domain' => 'updated.com'));
    $this->assertEquals('updated.com', CRM_Core_BAO_MailSettings::defaultDomain());
    $this->callAPISuccess('MailSettings', 'delete', array('id' => $result['id']));
    $this->callAPISuccessGetCount('mail_settings', array(), 1);
  }

  /**
   * Test get method.
   */
  public function testGetMailSettings() {
    $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $result = $this->callAPIAndDocument('MailSettings', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->callAPISuccess('MailSettings', 'delete', array('id' => $result['id']));
    $this->callAPISuccessGetCount('mail_settings', array(), 1);
  }

  public function testDeleteMailSettings() {
    $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $entity = $this->callAPISuccess('MailSettings', 'get', $this->params);
    $this->assertEquals('setting.com', $entity['values'][$entity['id']]['domain']);
    $this->callAPIAndDocument('MailSettings', 'delete', array('id' => $entity['id']), __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess('MailSettings', 'get', array());
    $this->assertEquals('EXAMPLE.ORG', $checkDeleted['values'][$checkDeleted['id']]['domain']);
  }

  /**
   * Test chained delete.
   */
  public function testGetMailSettingsChainDelete() {
    $description = "Demonstrates get + delete in the same call.";
    $subFile = 'ChainedGetDelete';
    $params = array(
      'title' => "MailSettings title",
      'api.MailSettings.delete' => 1,
    );
    $this->callAPISuccess('MailSettings', 'create', $this->params);
    $this->callAPIAndDocument('MailSettings', 'get', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $this->assertEquals(0, $this->callAPISuccess('MailSettings', 'getcount', array()));
  }

}
