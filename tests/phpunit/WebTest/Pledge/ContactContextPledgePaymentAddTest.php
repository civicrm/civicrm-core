<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Pledge_ContactContextPledgePaymentAddTest
 */
class WebTest_Pledge_ContactContextPledgePaymentAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddPledgePaymentWithAdjustPledgePaymentSchedule() {
    $this->webtestLogin();
    $this->openCiviPage('admin/setting/localization', 'reset=1');
    $this->select("currencyLimit", "value=FJD");
    $this->click("add");
    $this->click("_qf_Localization_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // create unique name
    $name = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("xpath=//div[@class='crm-actions-ribbon']/ul[@id='actions']/li[@class='crm-contact-activity crm-summary-block']/div/a[@id='crm-contact-actions-link']");
    $this->waitForElementPresent('crm-contact-actions-list');

    // wait for add pledge link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    $this->select("currency", "value=FJD");
    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');
    $this->select("financial_type_id", "label=Donation");

    $this->select("contribution_page_id", "value=3");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");
    $this->waitForElementPresent('link=Add Pledge');

    $this->waitForText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
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
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->clickLink("_qf_PledgeView_next-bottom", "xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']", FALSE);
    $this->waitForAjaxContent();
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "5");
    $this->click("_qf_Contribution_upload");
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[1]/a");

    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "10");

    $this->click("_qf_Contribution_upload");
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
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
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->clickLink("_qf_PledgeView_next-bottom", "xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']", FALSE);
    $this->waitForAjaxContent();
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");

    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->type("total_amount", "10");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[5]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[5]/td[8]/a[text()='Record Payment']");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[10]/span/a[text()='View']");

    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 30.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Completed',
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    $this->openCiviPage('admin/setting/localization', 'reset=1');
    $this->waitForElementPresent("currencyLimit");
    $this->select("currencyLimit", "value=FJD");
    $this->click("remove");
    $this->click("_qf_Localization_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  public function testAddPledgePaymentWithAdjustTotalPledgeAmount() {
    $this->webtestLogin();
    // Disable pop-ups for this test. Running test w/ pop-ups causes a spurious failure. dgg
    $this->enableDisablePopups(FALSE);

    // create unique name
    $name = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("xpath=//div[@class='crm-actions-ribbon']/ul[@id='actions']/li[@class='crm-contact-activity crm-summary-block']/div/a[@id='crm-contact-actions-link']");
    $this->waitForElementPresent('crm-contact-actions-list');

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');

    $this->select("contribution_page_id", "value=3");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");

    $this->waitForText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
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
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    $this->clickLink("_qf_PledgeView_next-bottom", "xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']", FALSE);
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->waitForElementPresent("CIVICRM_QFID_2_option_type");
    $this->click("CIVICRM_QFID_2_option_type");
    $this->type("total_amount", "15");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->click("xpath=//form[@id='Contribution']//table//tbody/tr[3]/td[2]/a[text()='adjust payment amount']");
    $this->waitForElementPresent("adjust-option-type");
    $this->waitForElementPresent("CIVICRM_QFID_2_option_type");
    $this->click("CIVICRM_QFID_2_option_type");
    $this->type("total_amount", "15");

    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
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
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );

    $this->clickLink("_qf_PledgeView_next-bottom", "xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']", FALSE);
    $this->waitForAjaxContent();
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");

    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    $this->waitForElementPresent("_qf_PledgeView_next-bottom");
    $this->webtestVerifyTabularData(array(
        'Pledge By' => $firstName . ' ' . $lastName,
        'Total Pledge Amount' => '$ 40.00',
        'To be paid in' => '3 installments of $ 10.00 every 1 week(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Completed',
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    // Re-enable pop-ups to leave things in the same state
    $this->enableDisablePopups(TRUE);
  }

  public function testAddPledgePayment() {
    $this->webtestLogin();

    // create unique name
    $name = substr(sha1(rand()), 0, 7);
    $firstName = 'Adam' . $name;
    $lastName = 'Jones' . $name;

    // create new contact
    $this->webtestAddContact($firstName, $lastName, $firstName . "@example.com");

    // wait for action element
    $this->waitForElementPresent('crm-contact-actions-link');

    // now add pledge from contact summary
    $this->click("xpath=//div[@class='crm-actions-ribbon']/ul[@id='actions']/li[@class='crm-contact-activity crm-summary-block']/div/a[@id='crm-contact-actions-link']");
    $this->waitForElementPresent('crm-contact-actions-list');

    // wait for add plegde link
    $this->waitForElementPresent('link=Add Pledge');

    $this->click('link=Add Pledge');

    // wait for pledge form to load completely
    $this->waitForElementPresent('_qf_Pledge_upload-bottom');

    // check contact name on pledge form
    $this->assertElementContainsText('css=tr.crm-pledge-form-block-displayName', "$firstName $lastName");

    $this->type("amount", "30");
    $this->type("installments", "3");
    $this->select("frequency_unit", "value=week");
    $this->type("frequency_day", "2");

    $this->webtestFillDate('acknowledge_date', 'now');

    $this->select("contribution_page_id", "value=3");

    //PaymentReminders
    $this->click("PaymentReminders");
    $this->waitForElementPresent("additional_reminder_day");
    $this->type("initial_reminder_day", "4");
    $this->type("max_reminders", "2");
    $this->type("additional_reminder_day", "4");

    $this->click("_qf_Pledge_upload-bottom");

    $this->waitForText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    //Add payments
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForText('crm-notification-container', "The contribution record has been saved.");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->waitForElementPresent("_qf_Contribution_upload");
    $this->click("_qf_Contribution_upload");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[10]/span/a[text()='View']");
    //click through to the Pledge view screen
    $this->click("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']/div[2]/table/tbody/tr[1]/td[10]/span/a[text()='View']");
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
        'Initial Reminder Day' => '4 days prior to schedule date',
        'Maximum Reminders Send' => 2,
        'Send additional reminders' => '4 days after the last one sent',
      )
    );
    // Re-enable pop-ups to leave things in the same state
    $this->enableDisablePopups(TRUE);
  }

}
