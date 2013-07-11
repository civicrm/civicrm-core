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
| License and the CiviCRM Licensing Exception along                  |
| with this program; if not, contact CiviCRM LLC                     |
| at info[AT]civicrm[DOT]org. If you have questions about the        |
| GNU Affero General Public License or the licensing of CiviCRM,     |
| see the CiviCRM license FAQ at http://civicrm.org/licensing        |
+--------------------------------------------------------------------+
*/

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_MailSettingsTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $params;
  protected $id;
  public $DBResetRequired = FALSE;

  function setUp() {
    $this->_apiversion = 3;
    $this->params = array(
      'domain_id' => 1,
      'name' => "my mail setting",
      'domain' => 'setting.com',
      'local_part' => 'civicrm+',
      'server' => "localhost",
      'username' => 'sue',
      'password' => 'pass',
      'version' => $this->_apiversion,
    );
    parent::setUp();
  }

  function tearDown() {}

  public function testCreateMailSettings() {
    $result = civicrm_api('MailSettings', 'create', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
  }

  public function testGetMailSettings() {

    $result = civicrm_api('MailSettings', 'get', $this->params);
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->id = $result['id'];
  }

  public function testDeleteMailSettings() {
    $entity = civicrm_api('MailSettings', 'get', $this->params);
    $this->assertEquals('setting.com', $entity['values'][$entity['id']]['domain'], 'In line ' . __LINE__);

    $result = civicrm_api('MailSettings', 'delete', array('version' => 3, 'id' => $entity['id']));
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api('MailSettings', 'get', array(
      'version' => 3,
      ));
    $this->assertEquals('EXAMPLE.ORG', $checkDeleted['values'][$checkDeleted['id']]['domain'], 'In line ' . __LINE__);
  }

  public function testGetMailSettingsChainDelete() {
    $description = "demonstrates get + delete in the same call";
    $subfile     = 'ChainedGetDelete';
    $params      = array(
      'version' => 3,
      'title' => "MailSettings title",
      'api.MailSettings.delete' => 1,
    );
    $result = civicrm_api('MailSettings', 'create', $this->params);
    $result = civicrm_api('MailSettings', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(0, civicrm_api('MailSettings', 'getcount', array('version' => 3)), 'In line ' . __LINE__);
  }
}

