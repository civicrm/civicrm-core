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
class api_v3_DashboardContactTest extends CiviUnitTestCase {
  protected $_params;
  protected $_params2;
  protected $_entity = 'dashboard_contact';
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

  public function testDashboardContactCreate() {
    $dashParams = array(
      'version' => 3,
      'label' => 'New Dashlet element',
      'name' => 'New Dashlet element',
      'url' => 'civicrm/report/list&compid=99&reset=1',
      'fullscreen_url' => 'civicrm/report/list&compid=99&reset=1&context=dashletFullscreen',
    );
    $dashresult = $this->callAPISuccess('dashboard', 'create', $dashParams);
    $contact = $this->callAPISuccess('contact', 'create', array(
      'first_name' => 'abc1',
      'contact_type' => 'Individual',
      'last_name' => 'xyz1',
      'email' => 'abc@abc.com',
    ));
    $oldCount = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_dashboard_contact where contact_id = {$contact['id']} AND is_active = 1 AND dashboard_id = {$dashresult['id']}");
    $params = array(
      'version' => 3,
      'contact_id' => $contact['id'],
      'dashboard_id' => $dashresult['id'],
      'is_active' => 1,
    );
    $dashboradContact = $this->callAPISuccess('dashboard_contact', 'create', $params);
    $newCount = CRM_Core_DAO::singleValueQuery("select count(*) from civicrm_dashboard_contact where contact_id = {$contact['id']} AND is_active = 1 AND dashboard_id = {$dashresult['id']}");
    $this->assertEquals($oldCount + 1, $newCount);
  }

}
