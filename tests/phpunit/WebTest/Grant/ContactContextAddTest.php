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
 * Class WebTest_Grant_ContactContextAddTest
 */
class WebTest_Grant_ContactContextAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testContactContextAddTest() {
    // Log in as admin first to verify permissions for CiviGrant
    $this->webtestLogin('admin');

    // Enable CiviGrant module if necessary
    $this->enableComponents("CiviGrant");

    // let's give full CiviGrant permissions to demo user (registered user).
    $permission = array('edit-2-access-civigrant', 'edit-2-edit-grants', 'edit-2-delete-in-civigrant');
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    // create unique name
    $name = substr(sha1(rand()), 0, 7);
    $firstName = 'Grant' . $name;
    $lastName = 'L' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName);

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add grant from contact summary
    $this->click("xpath=//div[@class='crm-actions-ribbon']/ul[@id='actions']/li[@class='crm-contact-activity crm-summary-block']/div/a[@id='crm-contact-actions-link']");
    $this->waitForElementPresent('crm-contact-actions-list');

    // wait for add Grant link
    $this->waitForElementPresent('link=Add Grant');

    $this->click('link=Add Grant');

    // wait for grant form to load completely
    $this->waitForText('css=span.ui-dialog-title', "New Grant");
    $this->waitForElementPresent('status_id');

    // check contact name on Grant form

    // select grant Status
    $this->select('status_id', 'value=1');

    // select grant type
    $this->select('grant_type_id', 'value=1');

    // total amount
    $this->type('amount_total', '200');

    // amount requested
    $this->type('amount_requested', '200');

    // amount granted
    $this->type('amount_granted', '190');

    // fill in application received Date
    $this->webtestFillDate('application_received_date', 'now');

    // fill in decision Date
    $this->webtestFillDate('decision_date', 'now');

    // fill in money transferred date
    $this->webtestFillDate('money_transfer_date', 'now');

    // fill in grant due Date
    $this->webtestFillDate('grant_due_date', 'now');

    // check  grant report received.
    $this->check('grant_report_received');

    // grant rationale
    $this->type('rationale', 'Grant Rationale for webtest');

    // grant  note
    $this->type('note', "Grant Note for $firstName");

    // Clicking save.
    $this->clickLink('_qf_Grant_upload', "xpath=//div[@class='view-content']//table/tbody/tr[1]/td[8]/span/a[text()='View']", FALSE);

    // click through to the Grant view screen
    $this->clickLink("xpath=//div[@class='view-content']//table/tbody/tr[1]/td[8]/span/a[text()='View']", '_qf_GrantView_cancel-bottom', FALSE);

    $gDate = date('F jS, Y', strtotime('now'));

    // verify tabular data for grant view
    $this->webtestVerifyTabularData(array(
        'Name' => "$firstName $lastName",
        'Grant Status' => 'Submitted',
        'Grant Type' => 'Emergency',
        'Application Received' => $gDate,
        'Grant Decision' => $gDate,
        'Money Transferred' => $gDate,
        'Grant Report Due' => $gDate,
        'Amount Requested' => '$ 200.00',
        'Amount Granted' => '$ 190.00',
        'Grant Report Received?' => 'Yes',
        'Rationale' => 'Grant Rationale for webtest',
        'Notes' => "Grant Note for $firstName",
      )
    );
  }

}
