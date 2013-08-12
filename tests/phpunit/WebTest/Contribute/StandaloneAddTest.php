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
class WebTest_Contribute_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testStandaloneContributeAdd() {
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    // Add new Financial Account
    $orgName = 'Alberta '.substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account '.substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Asset';
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 9.9999999;
    $isDefault = FALSE;

    //Add new organisation
    if($orgName) {
      $this->webtestAddOrganization($orgName);
    }

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

    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, "Contributor", $firstName . "@example.com");

    // select financial type
    $this->select("financial_type_id", "value=1");

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

    // soft credit
    $this->webtestFillAutocomplete("{$softCreditLname}, {$softCreditFname}", 'soft_credit_contact_1');

    //Custom Data
    //$this->click('CIVICRM_QFID_3_6');

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "This is a test note.");
    $this->type("non_deductible_amount", "10");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Honoree section
    $this->click("Honoree");
    $this->waitForElementPresent("honor_email");

    $this->click("CIVICRM_QFID_1_2");
    $this->select("honor_prefix_id", "label=Ms.");
    $this->type("honor_first_name", "Foo");
    $this->type("honor_last_name", "Bar");
    $this->type("honor_email", "foo@bar.com");

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->click("_qf_Contribution_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been saved."), "Status message didn't show up after saving!");

    // verify if Membership is created
    $this->waitForElementPresent("xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '$ 100.00',
      'Contribution Status' => 'Completed',
      'Paid By' => 'Check',
      'Check Number' => 'check #1041',
      'Soft Credit To' => "{$softCreditFname} {$softCreditLname}",
    );

    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='$label']/../td[2]", preg_quote($value));
    }

    // go to soft creditor contact view page
    $this->click("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='Soft Credit To']/../td[2]/a[text()='{$softCreditFname} {$softCreditLname}']");

    // go to contribution tab
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // verify soft credit details
    $expected = array(
      3 => 'Donation',
      2 => '100.00',
      5 => 'Completed',
      1 => "{$firstName} Contributor",
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody/tr[2]/td[$value]", preg_quote($label));
    }
  }

  function testAjaxCustomGroupLoad() {
    $this->webtestLogin();
    $triggerElement = array('name' => 'financial_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Contribution', 'subEntity' => 'Donation', 'triggerElement' => $triggerElement),
      array('entity' => 'Contribution', 'subEntity' => 'Member Dues', 'triggerElement' => $triggerElement)
    );

    $pageUrl = array('url' => 'contribute/add', 'args' => 'reset=1&action=add&context=standalone');
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }
}