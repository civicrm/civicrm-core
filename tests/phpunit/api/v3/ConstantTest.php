<?php
/**
 *  File for the TestConstant class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @version   $Id: ConstantTest.php 31254 2010-12-15 10:09:29Z eileen $
 * @package CiviCRM_APIv3
 * @subpackage API_Constant
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Test APIv3 civicrm_activity_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Constant
 * @group headless
 */
class api_v3_ConstantTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();
    $this->_apiversion = 3;
  }

  /**
   * Test civicrm_constant_get( ) for unknown constant
   */
  public function testUnknownConstant() {
    $result = $this->callAPIFailure('constant', 'get', array(
      'name' => 'thisTypeDoesNotExist',
    ));
  }

  /**
   * Test civicrm_constant_get( 'activityStatus' )
   */
  public function testActivityStatus() {

    $result = $this->callAPISuccess('constant', 'get', array(
      'name' => 'activityStatus',
    ));

    $this->assertTrue($result['count'] > 5, "In line " . __LINE__);
    $this->assertContains('Scheduled', $result['values'], "In line " . __LINE__);
    $this->assertContains('Completed', $result['values'], "In line " . __LINE__);
    $this->assertContains('Cancelled', $result['values'], "In line " . __LINE__);

  }

  /**
   * Test civicrm_constant_get( 'activityType' )
   */
  public function testActivityType() {
    $result = $this->callAPIAndDocument('constant', 'get', array(
      'name' => 'activityType',
    ), __FUNCTION__, __FILE__, NULL, NULL, 'get');
    $this->assertTrue($result['count'] > 2, "In line " . __LINE__);
    $this->assertContains('Meeting', $result['values'], "In line " . __LINE__);
  }

  /**
   * Test civicrm_address_getoptions( 'location_type_id' )
   */
  public function testLocationTypeGet() {
    // needed to get rid of cached values from previous tests
    CRM_Core_PseudoConstant::flush();

    $params = array(
      'field' => 'location_type_id',
    );
    $result = $this->callAPIAndDocument('address', 'getoptions', $params, __FUNCTION__, __FILE__);
    $this->assertTrue($result['count'] > 3, "In line " . __LINE__);
    $this->assertContains('Home', $result['values'], "In line " . __LINE__);
    $this->assertContains('Work', $result['values'], "In line " . __LINE__);
    $this->assertContains('Main', $result['values'], "In line " . __LINE__);
    $this->assertContains('Billing', $result['values'], "In line " . __LINE__);
  }

  /**
   * Test civicrm_phone_getoptions( 'phone_type_id' )
   */
  public function testPhoneType() {
    $params = array(
      'field' => 'phone_type_id',
    );
    $result = $this->callAPIAndDocument('phone', 'getoptions', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(5, $result['count'], "In line " . __LINE__);
    $this->assertContains('Phone', $result['values'], "In line " . __LINE__);
    $this->assertContains('Mobile', $result['values'], "In line " . __LINE__);
    $this->assertContains('Fax', $result['values'], "In line " . __LINE__);
    $this->assertContains('Pager', $result['values'], "In line " . __LINE__);
    $this->assertContains('Voicemail', $result['values'], "In line " . __LINE__);
  }

  /**
   * Test civicrm_constant_get( 'mailProtocol' )
   */
  public function testmailProtocol() {
    $params = array(
      'field' => 'protocol',
    );
    $result = $this->callAPIAndDocument('mail_settings', 'getoptions', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(4, $result['count'], "In line " . __LINE__);
    $this->assertContains('IMAP', $result['values'], "In line " . __LINE__);
    $this->assertContains('Maildir', $result['values'], "In line " . __LINE__);
    $this->assertContains('POP3', $result['values'], "In line " . __LINE__);
    $this->assertContains('Localdir', $result['values'], "In line " . __LINE__);
  }

}
