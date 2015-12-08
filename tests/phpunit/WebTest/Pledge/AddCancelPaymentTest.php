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
 * Class WebTest_Pledge_AddCancelPaymentTest
 */
class WebTest_Pledge_AddCancelPaymentTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddCancelPayment() {
    $this->webtestLogin();

    $this->openCiviPage('pledge/add', 'reset=1&context=standalone', '_qf_Pledge_upload');

    // create new contact using dialog
    $contact = $this->createDialogContact();

    $this->type('amount', '1200');
    $this->type('installments', '12');
    $this->select('frequency_unit', 'value=month');
    $this->type('frequency_day', '1');
    $this->webtestFillDate('acknowledge_date', 'now');
    $this->select('contribution_page_id', 'value=3');
    $this->click('_qf_Pledge_upload-bottom');
    $this->waitForPageToLoad("30000");

    $this->waitForText('crm-notification-container', "Pledge has been recorded and the payment schedule has been created.");

    // verify if Pledge is created
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    //click through to the Pledge view screen
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_PledgeView_next-bottom');
    $pledgeDate = date('F jS, Y', strtotime('now'));

    $this->webtestVerifyTabularData(array(
        'Pledge By' => $contact['display_name'],
        'Total Pledge Amount' => '$ 1,200.00',
        'To be paid in' => '12 installments of $ 100.00 every 1 month(s)',
        'Payments are due on the' => '1 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Pending',
      )
    );
    //Edit and add the first payment for 300.00
    $this->click('_qf_PledgeView_next-bottom');
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody//tr//td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//div[@class='view-content']//table//tbody//tr//td/div/table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->click("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->type('total_amount', '300.00');
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");

    //Check whether the next two payments are done or not
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']//div/table/tbody//tr//td/div/table/tbody/tr[2]/td[8]/a[text()='View Payment']");
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[3]/td[7]", "Completed");
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[4]/td[7]", "Completed");

    //Cancel the contribution made for amount of 300.00
    $this->waitForElementPresent("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']//div/table/tbody//tr//td/div/table/tbody/tr[2]/td[8]/a[text()='View Payment']");
    $this->click("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//form[@id='ContributionView']//div[2]/div/div/a");
    $this->click("xpath=//form[@id='ContributionView']//div[2]/div/div/a");
    $this->waitForElementPresent("_qf_Contribution_upload-bottom");
    $this->select('contribution_status_id', 'value=3');
    $this->click("_qf_Contribution_upload-bottom");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    $this->click("_qf_ContributionView_cancel-bottom");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[3]/td[7]");

    // verify that first paayment is cancelled and the rest two payments are revert back to the pending status
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[2]/td[7]", "Cancelled");
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[3]/td[7]", "Pending");
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[4]/td[7]", "Pending");

    // Check whether a new payment with pending status is added at the last or not
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr/td/div/table/tbody/tr[14]/td[7]", "Pending");

    // verify that Balance sum is correct
    $this->verifyText("xpath=//form[@class='CRM_Pledge_Form_Search crm-search-form']//div[@class='view-content']//table//tbody/tr[1]/td[4]", "1,200.00");

  }

}
