<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Class WebTest_Admin_Form_Setting_LocalizationTest
 */
class WebTest_Contribute_AccrualSettingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAccrualSettings() {
    $this->webtestLogin();
    $this->openCiviPage("admin/setting/preferences/contribute", "reset=1");
    $this->waitForElementPresent("_qf_Contribute_next");

    // Check hide/show
    $this->click("deferred_revenue_enabled");
    $this->waitForElementPresent("xpath=//tr[@class='crm-preferences-form-block-default_invoice_page'][@style='display: table-row;']");
    $this->click("deferred_revenue_enabled");
    $this->waitForElementPresent("xpath=//tr[@class='crm-preferences-form-block-default_invoice_page'][@style='display: none;']");

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Test Contribution Page ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array('Test Processor' => 'Dummy'), FALSE, FALSE, FALSE, FALSE,
      FALSE, FALSE, NULL, FALSE, 1, 7, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, FALSE, 'Donation', TRUE, FALSE);

    // Input value
    $this->openCiviPage("admin/setting/preferences/contribute", "reset=1");
    $this->waitForElementPresent("_qf_Contribute_next");
    $this->click("deferred_revenue_enabled");
    $this->waitForElementPresent("xpath=//*[@id='default_invoice_page']");

    $this->select('default_invoice_page', "value={$pageId}");

    // Check hide/show
    $this->click("financial_account_bal_enable");
    $this->waitForElementPresent("xpath=//tr[@class='crm-preferences-form-block-fiscalYearStart'][@style='display: table-row;']");
    $this->click("financial_account_bal_enable");
    $this->waitForElementPresent("xpath=//tr[@class='crm-preferences-form-block-fiscalYearStart'][@style='display: none;']");

    $this->click("financial_account_bal_enable");
    $this->waitForElementPresent("xpath=//*[@id='fiscalYearStart_M']");
    $this->select('fiscalYearStart_M', "value=4");
    $this->select('fiscalYearStart_d', "value=30");

    $this->webtestFillDate('period_closing_date', 'now+2');
    $this->click('_qf_Contribute_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //does data saved.
    $this->assertTrue($this->isTextPresent('Changes saved.'),
      "Status message didn't show up after saving!"
    );
  }

}
