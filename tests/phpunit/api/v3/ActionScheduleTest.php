<?php
/**
 *  File for the TestActionSchedule class
 *
 *  (PHP 5)
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
 *  Include class definitions
 */
require_once 'CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_action_schedule functions
 *
 *  @package CiviCRM_APIv3
 *  @subpackage API_ActionSchedule
 */

class api_v3_ActionScheduleTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'action_schedule';
  protected $_apiversion = 3;

  public $_eNoticeCompliant = TRUE;
  /**
   *  Test setup for every test
   *
   *  Connect to the database, truncate the tables that will be used
   *  and redirect stdin to a temporary file
   */
  public function setUp() {
    //  Connect to the database
    parent::setUp();

  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   *
   * @access protected
   */
  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_action_schedule',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  
  function testActionScheduleCreate() {
  	
  	$oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
  	$params = array(
      'title' => 'simpleAction',
      'entity_value' => '46',
    );
  	
  	$actionSchedule = $this->callAPISuccess('action_schedule', 'create', $params);
  	$this->assertTrue(is_numeric($actionSchedule['id']), "In line " . __LINE__);
  	$this->assertTrue($actionSchedule['id'] > 0, "In line " . __LINE__);
  	$newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_action_schedule');
  	$this->assertEquals($oldCount+1, $newCount);

  }


}
