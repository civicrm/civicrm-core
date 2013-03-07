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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/


require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Pledge_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testStandalonePledgeAdd() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // Go directly to the URL of the screen that you will be testing (New Pledge-standalone).
    $this->openCiviPage('pledge/add', 'reset=1&context=standalone', '_qf_Pledge_upload');

    // create new contact using dialog
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, $lastName, $firstName . '@example.com');

    $this->type('amount', '100');
    $this->type('installments', '10');
    $this->select('frequency_unit', 'value=week');
    $this->type('frequency_day', '2');

    $this->webtestFillDate('acknowledge_date', 'now');

    $this->select('contribution_page_id', 'value=3');

    //Honoree section
    $this->click('Honoree');
    $this->waitForElementPresent('honor_email');

    $this->click('CIVICRM_QFID_1_2');
    $this->select('honor_prefix_id', 'value=3');

    $honorFirstName = 'Fo' . substr(sha1(rand()), 0, 4);
    $honorLastName = 'Ba' . substr(sha1(rand()), 0, 7);
    $this->type('honor_first_name', $honorFirstName);
    $this->type('honor_last_name', $honorLastName);
    $this->type('honor_email', $honorFirstName . '@example.com');

    //PaymentReminders
    $this->click('PaymentReminders');
    $this->waitForElementPresent('additional_reminder_day');
    $this->type('initial_reminder_day', '4');
    $this->type('max_reminders', '2');
    $this->type('additional_reminder_day', '4');

    $this->click('_qf_Pledge_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    // verify if Pledge is created
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_PledgeView_next-bottom');
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 100.00',
        'To be paid in' => '10 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Pending',
        'In Honor of' => 'Mr. ' . $honorFirstName . ' ' . $honorLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    $this->click('_qf_PledgeView_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
  }
}

