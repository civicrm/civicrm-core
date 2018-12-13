<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Grant_StandaloneAddTest
 */
class WebTest_Grant_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStandaloneGrantAdd() {
    // Log in as admin first to verify permissions for CiviGrant
    $this->webtestLogin('admin');

    // Enable CiviGrant module if necessary
    $this->enableComponents("CiviGrant");

    // let's give full CiviGrant permissions to demo user (registered user).
    $permission = array('edit-2-access-civigrant', 'edit-2-edit-grants', 'edit-2-delete-in-civigrant');
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage('grant/add', 'reset=1&context=standalone', '_qf_Grant_upload');

    // create new contact using dialog
    $contact = $this->createDialogContact();

    // select grant Status
    $this->select("status_id", "value=1");

    // select grant type
    $this->select("grant_type_id", "value=1");

    // total amount
    $this->type("amount_total", "100");

    // amount requested
    $this->type("amount_requested", "100");

    // amount granted
    $this->type("amount_granted", "90");

    // fill in application received Date
    $this->webtestFillDate('application_received_date');

    // fill in decision Date
    $this->webtestFillDate('decision_date');

    // fill in money transferred date
    $this->webtestFillDate('money_transfer_date');

    // fill in grant due Date
    $this->webtestFillDate('grant_due_date');

    // check  grant report received.
    $this->check("grant_report_received");

    // grant  note
    $this->type("note", "Grant Note");

    // Clicking save.
    $this->clickLink("_qf_Grant_upload", "xpath=//div[@class='view-content']//table//tbody/tr[1]/td[8]/span/a[text()='View']", FALSE);

    //click through to the Grant view screen
    $this->click("xpath=//div[@class='view-content']//table/tbody/tr[1]/td[8]/span/a[text()='View']");

    $this->waitForElementPresent("_qf_GrantView_cancel-bottom");

    $expected = array(
      'Grant Status' => 'Submitted',
      'Grant Type' => 'Emergency',
      'Amount Requested' => '$ 100.00',
      'Amount Granted' => '$ 90.00',
      'Notes' => 'Grant Note',
    );

    $this->webtestVerifyTabularData($expected);
  }

}
