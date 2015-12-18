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
 * Class WebTest_Contribute_StandaloneAddTest
 */
class WebTest_Contribute_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStandaloneContributeAdd() {
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Liability';
    $taxDeductible = TRUE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 10.00;
    $isDefault = FALSE;

    //Add new organisation
    $this->webtestAddOrganization($orgName);

    $this->_testAddFinancialAccount($financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $orgName,
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );

    //Add new Financial Type
    $financialType['name'] = 'Taxable FinancialType ' . substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = TRUE;
    $financialType['is_reserved'] = FALSE;
    $this->addeditFinancialType($financialType);

    // Assign the created Financial Account $financialAccountTitle to $financialType
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1]/div[text()='$financialType[name]']/../../td[7]//span//a[text()='Accounts']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']//button//span[contains(text(), 'Assign Account')]");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button//span[contains(text(), 'Assign Account')]");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->select('account_relationship', "label=Sales Tax Account is");
    $this->waitForAjaxContent();
    $this->select('financial_account_id', "label=" . $financialAccountTitle);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']//button//span[contains(text(), 'Assign Account')]");

    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $contact = $this->createDialogContact();

    // select financial type
    $this->select("financial_type_id", "label=" .  $financialType['name']);

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type("source", "Mailer 1");

    // total amount
    $this->type("total_amount", "100");

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #1041");
    $this->click("is_email_receipt");
    $this->assertTrue($this->isChecked("is_email_receipt"), 'Send Receipt checkbox should be checked.');
    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    // soft credit
    $this->webtestFillAutocomplete("{$softCreditLname}, {$softCreditFname}", 's2id_soft_credit_contact_id_1');
    $this->type("soft_credit_amount_1", "100");

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "This is a test note.");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload");
    // Ask for confirmation to send a receipt to the contributor on 'is_email_reciept' check
    $this->assertTrue((bool) preg_match("/^Click OK to save this contribution record AND send a receipt to the contributor now./", $this->getConfirmation()));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->waitForText("crm-notification-container", "The contribution record has been saved.");

    // verify if Membership is created
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='View']");

    $contriID = $this->urlArg('id', $this->getAttribute("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='Edit']@href"));
    $contactID = $this->urlArg('cid', $this->getAttribute("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='Edit']@href"));

    //click through to the Membership view screen
    $this->click("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array(
      'Financial Type' => $financialType['name'],
      'Total Amount' => '$ 110.00',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Check',
      'Check Number' => 'check #1041',
    );

    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='$label']/../td[2]", preg_quote($value));
    }

    // verify if soft credit was created successfully
    $expected = array(
      'Soft Credit To' => "{$softCreditFname} {$softCreditLname}",
      'Amount' => '100.00',
    );

    foreach ($expected as $value) {
      $this->verifyText("css=table.crm-soft-credit-listing", preg_quote($value));
    }

    // go to first soft creditor contact view page
    $this->clickLink("css=table.crm-soft-credit-listing tbody tr td a");

    // go to contribution tab
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody/tr[2]/td[1]/a", preg_quote($contact['display_name']));
    // verify soft credit details
    $expected = array(
      4 => $financialType['name'],
      2 => '100.00',
      6 => 'Completed',
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody/tr[2]/td[$value]", preg_quote($label));
    }

    // CRM-17418 fix: Now cancel the contribution
    $this->openCiviPage("contact/view/contribution", "reset=1&action=update&id=$contriID&cid=$contactID&context=contribution", "_qf_Contribution_upload");
    $this->select('contribution_status_id', 'label=Cancelled');
    $this->waitForElementPresent('cancel_reason');
    $this->click("_qf_Contribution_upload");
    // Is status message correct?
    $this->waitForText("crm-notification-container", "The contribution record has been saved.");

    // 1. On first completed contribution the contribution_amount = 100 and Tax Amount = 10
    // 2. After Cancellation contribution_amount = -100 and Tax Amount = -10
    // So the sum of all the 4 created financial item's amount would be 0
    $query = "SELECT SUM( amount ) FROM `civicrm_financial_item` WHERE entity_id = %1";
    $sum = CRM_Core_DAO::singleValueQuery($query, array(1 => array($contriID, 'Integer')));
    $this->assertEquals($sum, 0.00);
  }

  public function testfinancialTypeSearch() {
    $this->webtestLogin();

    $financialType = array(
      'name' => 'Financial type' . substr(sha1(rand()), 0, 7),
      'is_reserved' => FALSE,
      'is_deductible' => FALSE,
    );

    $this->addeditFinancialType($financialType);
    $this->addStandaloneContribution($financialType);
    $this->addStandaloneContribution($financialType);

    $this->openCiviPage("contribute/search", "reset=1", "_qf_Search_refresh");
    // select group
    $this->select("financial_type_id", "label={$financialType['name']}");
    $this->clickLink("_qf_Search_refresh");
    $this->assertElementContainsText("xpath=//div[@class='crm-content-block']//div[@id='search-status']/table/tbody/tr[1]/td[1]", "2 Result");
    $this->assertElementContainsText("xpath=//div[@class='crm-content-block']//div[@id='search-status']/table/tbody/tr[1]/td[2]", "Financial Type ID In {$financialType['name']}");

    $this->openCiviPage("contact/search/advanced", "reset=1", "_qf_Advanced_refresh-top");
    $this->clickAjaxLink('CiviContribute', "financial_type_id");

    // select group
    $this->select("financial_type_id", "label={$financialType['name']}");
    $this->clickLink("_qf_Advanced_refresh-top");
    $this->assertElementContainsText("xpath=//div[@class='crm-content-block']//div[@id='search-status']//table/tbody/tr[1]/td[1]", "2 Contacts");
    $this->assertElementContainsText("xpath=//div[@class='crm-content-block']//div[@id='search-status']//table/tbody/tr[1]/td[2]", "Financial Type ID In {$financialType['name']}");
  }

  /**
   * @param $financialType
   */
  public function addStandaloneContribution($financialType) {

    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $this->createDialogContact();

    // select financial type
    $this->select("financial_type_id", "label={$financialType['name']}");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    // source
    $this->type("source", "Mailer 1");

    // total amount
    $this->type("total_amount", "100");

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #1041");

    $this->type("trxn_id", "P20901X1" . rand(100, 10000));

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "This is a test note.");
    $this->waitForElementPresent("non_deductible_amount");
    $this->type("non_deductible_amount", "10");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload");

    // Is status message correct?
    $this->checkCRMAlert("The contribution record has been saved.");

    // verify if Membership is created
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//table[@class='selector row-highlight']//tbody/tr[1]/td[8]/span//a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array(
      'Financial Type' => $financialType['name'],
      'Total Amount' => '$ 100.00',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Check',
      'Check Number' => 'check #1041',
    );

    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='$label']/../td[2]", preg_quote($value));
    }
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();
    $triggerElement = array('name' => 'financial_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Contribution', 'subEntity' => 'Donation', 'triggerElement' => $triggerElement),
      array('entity' => 'Contribution', 'subEntity' => 'Member Dues', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'contribute/add', 'args' => 'reset=1&action=add&context=standalone');
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

}
