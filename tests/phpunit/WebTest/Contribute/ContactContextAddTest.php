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
 * Class WebTest_Contribute_ContactContextAddTest
 */
class WebTest_Contribute_ContactContextAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testContactContextAdd() {

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    // Adding contact with randomized first name (so we can then select that contact when creating contribution.)
    // We're using Quick Add block on the main page for this.
    $firstName = substr(sha1(rand()), 0, 7);
    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Asset';
    $taxDeductible = FALSE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 9.99999999;
    $isDefault = FALSE;

    //Add new organisation
    if ($orgName) {
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

    $this->webtestAddContact($firstName, "Anderson", TRUE);

    // Get the contact id of the new contact
    $contactUrl = $this->parseURL();
    $cid = $contactUrl['queryString']['cid'];
    $this->assertType('numeric', $cid);

    // go to contribution tab and add contribution.
    $this->click("css=li#tab_contribute a");

    // wait for Record Contribution elenment.
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");
    $this->click("link=Record Contribution (Check, Cash, EFT ...)");

    $this->waitForElementPresent("_qf_Contribution_cancel-bottom");
    // fill financial type.
    $this->select("financial_type_id", "Donation");

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
    $this->webtestFillAutocomplete("{$softCreditLname}, {$softCreditFname}", 'soft_credit_contact_id_1');
    $this->type("soft_credit_amount_1", "100");

    //Custom Data
    //$this->waitForElementPresent('CIVICRM_QFID_3_6');
    //$this->click('CIVICRM_QFID_3_6');

    //Additional Detail section
    $this->click("AdditionalDetail");
    $this->waitForElementPresent("thankyou_date");

    $this->type("note", "Test note for {$firstName}.");
    $this->type("fee_amount", "0");
    $this->type("net_amount", "0");
    $this->type("invoice_id", time());
    $this->webtestFillDate('thankyou_date');

    //Premium section
    $this->click("Premium");
    $this->waitForElementPresent("fulfilled_date");
    $this->select("product_name[0]", "label=Coffee Mug ( MUG-101 )");
    $this->select("product_name[1]", "label=Black");
    $this->webtestFillDate('fulfilled_date');

    // Clicking save.
    $this->clickLink("_qf_Contribution_upload-bottom", 'civicrm-footer', FALSE);
    // Is status message correct?
    $this->waitForText('crm-notification-container', "The contribution record has been saved");
    $this->waitForElementPresent("xpath=//form[@class='CRM_Contribute_Form_Search crm-search-form']/div[2]/table[2]/tbody/tr/td[8]/span//a[text()='View']");
    $viewUrl = $this->parseURL($this->getAttribute("xpath=//form[@class='CRM_Contribute_Form_Search crm-search-form']/div[2]/table[2]/tbody/tr/td[8]/span//a[text()='View']@href"));
    $id = $viewUrl['queryString']['id'];
    $this->assertType('numeric', $id);

    // click through to the Contribution view screen
    $this->click("xpath=//div[@class='view-content']/table[2]/tbody/tr/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');

    // verify Contribution created. Non-deductible amount derived from market value of selected 'sample' coffee mug premium (CRM-11956)
    $verifyData = array(
      'From' => $firstName . " Anderson",
      'Financial Type' => 'Donation',
      'Contribution Status' => 'Completed',
      'Payment Method' => 'Check',
      'Total Amount' => '$ 100.00',
      'Non-deductible Amount' => '$ 12.50',
      'Check Number' => 'check #1041',
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='ContributionView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }

    // check values of contribution record in the DB

    $searchParams = array('id' => $id);
    $compareParams = array(
      'contact_id' => $cid,
      'total_amount' => '100.00',
    );
    $this->assertDBCompareValues('CRM_Contribute_DAO_Contribution', $searchParams, $compareParams);

    // go to soft creditor contact view page
    $this->clickLink("css=table.crm-soft-credit-listing tbody tr td a");

    // go to contribution tab
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // verify soft credit details
    $expected = array(
      4 => 'Donation',
      2 => '100.00',
      6 => 'Completed',
      1 => "{$firstName} Anderson",
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody//tr/td[$value]", preg_quote($label));
    }
  }

}
