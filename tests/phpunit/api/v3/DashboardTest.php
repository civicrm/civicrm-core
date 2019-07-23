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
 *  Test APIv3 civicrm_action_schedule functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_ActionSchedule
 * @group headless
 */
class api_v3_DashboardTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'dashboard';
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
    $this->useTransaction(TRUE);
  }

  public function testDashboardCreate() {
    $oldCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_dashboard');
    $params = [
      'label' => 'New Dashlet element',
      'name' => 'New Dashlet element',
      'url' => 'civicrm/report/list&reset=1&compid=99',
      'fullscreen_url' => 'civicrm/report/list&compid=99&reset=1&context=dashletFullscreen',
    ];
    $dashboard = $this->callAPISuccess('dashboard', 'create', $params);
    $this->assertTrue(is_numeric($dashboard['id']), "In line " . __LINE__);
    $this->assertTrue($dashboard['id'] > 0, "In line " . __LINE__);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_dashboard');
    $this->assertEquals($oldCount + 1, $newCount);
    $this->DashboardDelete($dashboard['id'], $oldCount);
    $this->assertEquals($dashboard['values'][$dashboard['id']]['is_active'], 1);
  }

  /**
   * CRM-19534.
   *
   * Ensure that Dashboard create works fine for non admins
   */
  public function testDashboardCreateByNonAdmins() {
    $loggedInContactID = $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    $params = [
      'label' => 'New Dashlet element',
      'name' => 'New Dashlet element',
      'url' => 'civicrm/report/list&reset=1&compid=99',
      'fullscreen_url' => 'civicrm/report/list&compid=99&reset=1&context=dashletFullscreen',
    ];
    $dashboard = $this->callAPISuccess('dashboard', 'create', $params);
    $this->assertTrue(is_numeric($dashboard['id']), "In line " . __LINE__);
    $this->assertTrue($dashboard['id'] > 0, "In line " . __LINE__);

    $this->callAPISuccess('dashboard', 'create', $params);
    $this->assertEquals($dashboard['values'][$dashboard['id']]['is_active'], 1);
  }

  /**
   * CRM-19217.
   *
   * Ensure that where is_active is specifically set to 0 is_active returns 0.
   */
  public function testDashboardCreateNotActive() {
    $params = [
      'label' => 'New Dashlet element',
      'name' => 'New Dashlet element',
      'url' => 'civicrm/report/list&reset=1&compid=99&snippet=5',
      'fullscreen_url' => 'civicrm/report/list&compid=99&reset=1&snippet=5&context=dashletFullscreen',
      'is_active' => 0,
    ];
    $dashboard = $this->callAPISuccess('dashboard', 'create', $params);
    $this->assertEquals($dashboard['values'][$dashboard['id']]['is_active'], 0);
  }

  /**
   * @param int $id
   * @param $oldCount
   */
  public function DashboardDelete($id, $oldCount) {
    $params = [
      'id' => $id,
    ];
    $dashboardget = $this->callAPISuccess('dashboard', 'get', $params);
    $this->assertEquals($id, $dashboardget['id']);
    $dashboard = $this->callAPISuccess('dashboard', 'delete', $params);
    $newCount = CRM_Core_DAO::singleValueQuery('select count(*) from civicrm_dashboard');
    $this->assertEquals($oldCount, $newCount);
  }

}
