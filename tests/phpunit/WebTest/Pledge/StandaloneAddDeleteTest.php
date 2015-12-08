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
 * Class WebTest_Pledge_StandaloneAddDeleteTest
 */
class WebTest_Pledge_StandaloneAddDeleteTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStandalonePledgeAddDelete() {
    $this->webtestLogin();

    $this->openCiviPage('pledge/add', 'reset=1&context=standalone', '_qf_Pledge_upload');

    // create new contact using dialog
    $contact = $this->createDialogContact();

    $this->type('amount', '2400');
    $this->type('installments', '10');
    $this->select('frequency_unit', 'value=month');
    $this->type('frequency_day', '2');
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
        'Total Pledge Amount' => '$ 2,400.00',
        'To be paid in' => '10 installments of $ 240.00 every 1 month(s)',
        'Payments are due on the' => '2 day of the period',
        'Pledge Made' => $pledgeDate,
        'Financial Type' => 'Donation',
        'Pledge Status' => 'Pending',
      )
    );
    $this->click('_qf_PledgeView_next-bottom');
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//div[@class='view-content']//table//tbody/tr[2]/td/div/table/tbody/tr[2]/td[8]/a");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->click("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->type('total_amount', '300.00');
    $this->click('_qf_Contribution_upload-bottom');

    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[8]/a");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->click("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->type('total_amount', '250.00');
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    $this->waitForElementPresent("xpath=//div[@class='view-content']//table//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a[text()='Record Payment']");
    $this->click("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[8]/a");
    $this->waitForElementPresent("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");
    $this->click("xpath=//form[@id='Contribution']//div[2]/table/tbody/tr[3]/td[2]/a");

    $this->type('total_amount', '170.00');
    $this->click('_qf_Contribution_upload-bottom');
    $this->waitForText('crm-notification-container', "The contribution record has been saved.");

    // delete the contribution associated with the 2nd payment
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[2]/td/div/table[@class='nestedSelector']/tbody/tr[3]/td[8]/a[text()='View Payment']");
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']/tbody/tr[2]/td/div/table[@class='nestedSelector']/tbody/tr[3]/td[8]/a");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[2]");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[2]");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']//button/span[text()='Delete']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button/span[text()='Delete']");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//li[@id='tab_pledge']/a");
    $this->click("xpath=//li[@id='tab_pledge']/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[10]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='view-content']//table[@class='selector row-highlight']//tbody/tr[1]/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[7]");

    $this->waitForAjaxContent();
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td[7]", "Pending");
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td");

    // verify that payment owed amount is correct (250.00)
    $this->waitForAjaxContent();
    $this->verifyText("xpath=//div[@class='view-content']//table/tbody/tr[2]/td/div/table/tbody/tr[3]/td", "250.00");
    // verify that Total Paid and Balance sums are correct
    $this->waitForAjaxContent();
    $this->verifyText("xpath=//div[@class='view-content']/table[@class='selector row-highlight']//tbody/tr[1]/td[3]", "470.00");
    $this->verifyText("xpath=//div[@class='view-content' and contains(., 'view pledge payments')]/table[@class='selector row-highlight']//tbody/tr[1]/td[4]", "1,930.00");

  }

}
