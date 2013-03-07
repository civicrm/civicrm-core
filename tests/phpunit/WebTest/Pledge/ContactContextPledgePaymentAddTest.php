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
class WebTest_Pledge_ContactContextPledgePaymentAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddPledgePaymentWithAdjustPledgePaymentSchedule() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();
    $this->openCiviPage('admin/setting/localization', 'reset=1');
    $this->select("currencyLimit-f","value=FJD");
    $this->click("add");
    $this->click("_qf_Localization_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    
    // create unique name
    $name      = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName  = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("//a[@id='crm-contact-actions-link']/span/div");

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    // Let's start filling the form with values.
    $this->select("currency","value=FJD");
    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');
        $this->select( "financial_type_id", "label=Donation");

    $this->select("contribution_page_id", "value=3");

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "value=3");

    $honreeFirstName = 'First' . substr(sha1(rand()), 0, 4);
    $honreeLastName = 'last' . substr(sha1(rand()), 0, 7);
    $this->type("honor_first_name", $honreeFirstName);
    $this->type("honor_last_name", $honreeLastName);
    $this->type("honor_email", $honreeFirstName . "@example.com");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Pending',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->click("_qf_PledgeView_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "5");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "10");

    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'In Progress',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->click("_qf_PledgeView_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");

    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "10");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[5]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[5]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Completed',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    $this->openCiviPage('admin/setting/localization', 'reset=1');
    $this->select("currencyLimit-t","value=FJD");
    $this->click("remove");
    $this->click("_qf_Localization_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function testAddPledgePaymentWithAdjustTotalPledgeAmount() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // create unique name
    $name      = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName  = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("//a[@id='crm-contact-actions-link']/span/div");

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    // Let's start filling the form with values.
    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');

    $this->select("contribution_page_id", "value=3");

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "value=3");

    $honreeFirstName = 'First' . substr(sha1(rand()), 0, 4);
    $honreeLastName = 'last' . substr(sha1(rand()), 0, 7);
    $this->type("honor_first_name", $honreeFirstName);
    $this->type("honor_last_name", $honreeLastName);
    $this->type("honor_email", $honreeFirstName . "@example.com");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Pending',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->click("_qf_PledgeView_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->waitForElementPresent("CIVICRM_QFID_2_option_type");
    $this->click("CIVICRM_QFID_2_option_type");
    $this->type("total_amount", "15");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->waitForElementPresent("CIVICRM_QFID_2_option_type");
    $this->click("CIVICRM_QFID_2_option_type");
    $this->type("total_amount", "15");

    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 40.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'In Progress',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->click("_qf_PledgeView_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 40.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Completed',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
  }

  function testAddPledgePayment() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // create unique name
    $name      = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName  = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("//a[@id='crm-contact-actions-link']/span/div");

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    // Let's start filling the form with values.
    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');

    $this->select("contribution_page_id", "value=3");

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "value=3");

    $honreeFirstName = 'First' . substr(sha1(rand()), 0, 4);
    $honreeLastName = 'last' . substr(sha1(rand()), 0, 7);
    $this->type("honor_first_name", $honreeFirstName);
    $this->type("honor_last_name", $honreeLastName);
    $this->type("honor_email", $honreeFirstName . "@example.com");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    //Add payments
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[2]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[3]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[1]/span/a");
    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->click("xpath=//div[@id='Pledges']//table//tbody//tr//td/table/tbody/tr[4]/td[8]/a[text()='Record Payment (Check, Cash, EFT ...)']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@id='Pledges']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Completed',
        'In Honor of' => 'Mr. ' . $honreeFirstName . ' ' . $honreeLastName,
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
  }
}

