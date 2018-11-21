<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Test class for CRM_Contact_Page_View_UserDashBoard
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_UserDashBoardTest extends CiviUnitTestCase {

  use CRMTraits_Page_PageTestTrait;

  /**
   * Contact ID of logged in user.
   *
   * @var int
   */
  protected $contactID;

  /**
   * Prepare for test
   */
  public function setUp() {
    parent::setUp();
    $this->contactID = $this->createLoggedInUser();
    $this->listenForPageContent();
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match']);
    CRM_Utils_Hook::singleton()->reset();
  }

  /**
   * Test the content of the dashboard.
   */
  public function testDashboardContentEmptyContact() {
    $this->runUserDashboard();
    $expectedStrings = [
      'You are not currently subscribed to any Groups',
      'There are no contributions on record for you.',
      'There are no Pledges for your record.',
      'You are not registered for any current or upcoming Events.',
      'There are no memberships on record for you.',
      'You do not have any active Personal Campaign pages.',
    ];
    $this->assertPageContains($expectedStrings);
  }

  /**
   * Test the content of the dashboard.
   */
  public function testDashboardContentContributions() {
    $this->contributionCreate(['contact_id' => $this->contactID]);
    $this->runUserDashboard();
    $expectedStrings = [
      'Your Contribution(s)',
       '<table class="selector"><tr class="columnheader"><th>Total Amount</th><th>Financial Type</th><th>Received date</th><th>Receipt Sent</th><th>Status</th>',
      '</tr><tr id=\'rowid1\'class="odd-row"><td>$ 100.00 </td><td>Donation</td>',
      '</td><td></td><td>Completed</td></tr></table>',
    ];
    $this->assertPageContains($expectedStrings);
    $this->assertSmartyVariables(['invoicing' => NULL]);
  }

  /**
   * Test the content of the dashboard.
   */
  public function testDashboardContentContributionsWithInvoicingEnabled() {
    $this->contributionCreate(['contact_id' => $this->contactID]);
    $this->callAPISuccess('Setting', 'create', ['invoicing' => 1]);
    $this->runUserDashboard();
    $expectedStrings = [
      'Your Contribution(s)',
      '<table class="selector"><tr class="columnheader"><th>Total Amount</th><th>Financial Type</th><th>Received date</th><th>Receipt Sent</th><th>Status</th><th></th>',
      '<td>Completed</td><td><a class="button no-popup nowrap"href="/index.php?q=civicrm/contribute/invoice&amp;reset=1&amp;id=1&amp;cid=' . $this->contactID . '"><i class="crm-i fa-print"></i><span>Print Invoice</span></a></td></tr></table>',
    ];
    $this->assertPageContains($expectedStrings);
    $this->assertSmartyVariables(['invoicing' => TRUE]);
  }

  /**
   * Run the user dashboard.
   */
  protected function runUserDashboard() {
    $dashboard = new CRM_Contact_Page_View_UserDashBoard();
    $dashboard->run();
  }

}
