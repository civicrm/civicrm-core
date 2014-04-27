<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MailSettingsTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $id;
  public $DBResetRequired = FALSE;

  function setUp() {
    $this->params = array(
      'domain_id' => 1,
      'name' => "my mail setting",
      'domain' => 'setting.com',
      'local_part' => 'civicrm+',
      'server' => "localhost",
      'username' => 'sue',
      'password' => 'pass',
    );
    parent::setUp();
  }

  function tearDown() {}

  public function testCreateMailSettings() {
    $result = $this->callAPIAndDocument('MailSettings', 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testGetMailSettings() {

    $result = $this->callAPIAndDocument('MailSettings', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testDeleteMailSettings() {
    $entity = $this->callAPISuccess('MailSettings', 'get', $this->params);
    $this->assertEquals('setting.com', $entity['values'][$entity['id']]['domain'], 'In line ' . __LINE__);

    $result = $this->callAPIAndDocument('MailSettings', 'delete', array('id' => $entity['id']), __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess('MailSettings', 'get', array());
    $this->assertEquals('EXAMPLE.ORG', $checkDeleted['values'][$checkDeleted['id']]['domain'], 'In line ' . __LINE__);
  }

  public function testGetMailSettingsChainDelete() {
    $description = "demonstrates get + delete in the same call";
    $subfile     = 'ChainedGetDelete';
    $params      = array(
      'title' => "MailSettings title",
      'api.MailSettings.delete' => 1,
    );
    $result = $this->callAPISuccess('MailSettings', 'create', $this->params);
    $result = $this->callAPIAndDocument('MailSettings', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(0, $this->callAPISuccess('MailSettings', 'getcount', array()), 'In line ' . __LINE__);
  }
}

