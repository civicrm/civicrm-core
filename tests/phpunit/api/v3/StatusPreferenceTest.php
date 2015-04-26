<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Class api_v3_StatusPreferenceTest
 *
 * @package CiviCRM_APIv3
 */
class api_v3_StatusPreferenceTest extends CiviUnitTestCase {
  protected $_apiversion;
  protected $_contactID;
  protected $_locationType;
  protected $_params;


  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = array(
      'name' => 'test_check',
      'hush_until' => '20151212',
      'minimum_report_severity' => 4,
      'check_info' => NULL,
    );
  }

  public function testCreateStatusPreference() {
    $result = $this->callAPIAndDocument('StatusPreference', 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertNotNull($result['id'], 'In line ' . __LINE__);
    $this->assertEquals('test_check', $result['values'][1]['name'], 'In line ' . __LINE__);
    $this->assertEquals(4, $result['values'][1]['minimum_report_severity'], 'In line ' . __LINE__);

    $this->callAPISuccess('StatusPreference', 'delete', array('id' => $result['id']));
  }

  public function testDeleteStatusPreference() {
    //create one
    $create = $this->callAPISuccess('StatusPreference', 'create', $this->_params);

    $result = $this->callAPIAndDocument('StatusPreference', 'delete', array('id' => $create['id']), __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);

    $get = $this->callAPISuccess('StatusPreference', 'get', array(
      'id' => $create['id'],
    ));
    $this->assertEquals(0, $get['count'], 'Status Preference not successfully deleted In line ' . __LINE__);
  }

}
