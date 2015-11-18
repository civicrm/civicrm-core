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
 * Class WebTest_Contribute_UpdatePendingContributionTest
 */
class WebTest_Contribute_UpdatePendingContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testUpdatePendingContribution() {
    $this->webtestLogin();

    //Offline Pay Later Contribution
    $contact = $this->_testOfflineContribution();

    //Online Pay Later Contribution
    $this->_testOnlineContribution($contact);
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/../a[1]");
    $this->type("sort_name", $contact['sort_name']);
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('radio_ts', 'ts_all');
    $contriIDOff = explode('&', $this->getAttribute("xpath=//div[@id='contributionSearch']/table/tbody/tr[1]/td[11]/span/a@href"));
    $contriIDOn = explode('&', $this->getAttribute("xpath=//div[@id='contributionSearch']/table/tbody/tr[1]/td[11]/span/a@href"));
    if (!empty($contriIDOff)) {
      $contriIDOff = substr($contriIDOff[1], (strrpos($contriIDOff[1], '=') + 1));
    }
    if (!empty($contriIDOn)) {
      $contriIDOn = substr($contriIDOn[1], (strrpos($contriIDOn[1], '=') + 1));
    }
    $this->select('task', "label=Update pending contribution status");
    $this->click("_qf_Search_next_action");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('contribution_status_id', 'label=Completed');
    $this->type("trxn_id_{$contriIDOff}", substr(sha1(rand()), 0, 5));
    $this->type("trxn_id_{$contriIDOn}", substr(sha1(rand()), 0, 5));
    $this->click('_qf_Status_next');
    $this->waitForElementPresent("_qf_Result_done");
    $this->click("_qf_Result_done");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $status = 'Completed';
    $this->verifyText("xpath=id('contributionSearch')/table[1]/tbody/tr[1]/td[9]", preg_quote($status));
    $this->verifyText("xpath=id('contributionSearch')/table[1]/tbody/tr[2]/td[9]", preg_quote($status));
  }

  /**
   * @return array
   *   Array of contact details
   */
  public function _testOfflineContribution() {
    // Create a contact to be used as soft creditor
    $softCreditFname = substr(sha1(rand()), 0, 7);
    $softCreditLname = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($softCreditFname, $softCreditLname, FALSE);

    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $contact = $this->createDialogContact();

    // select financial type
    $this->select("financial_type_id", "value=1");

    // fill in Received Date
    $this->webtestFillDate('receive_date');

    //Contribution status
    $this->select("contribution_status_id", "label=Pending");

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

    // verify if Contribution is created
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[2]//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Contribution view screen
    $this->click("xpath=//div[@class='view-content']//table[2]/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    // View Contribution Record and test for expected values
    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Pending',
      'Payment Method' => 'Check',
      'Check Number' => 'check #1041',
    );
    $this->webtestVerifyTabularData($expected);

    // go to soft creditor contact view page - this also does the soft credit check
    $this->click("xpath=id('ContributionView')/div[2]/div/div[1][contains(text(), 'Soft Credit')]/../div[2]/table[1]/tbody//tr/td[1]/a[contains(text(), '{$softCreditFname} {$softCreditLname}')]");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    // go to contribution tab
    $this->waitForElementPresent("css=li#tab_contribute a");
    $this->click("css=li#tab_contribute a");
    $this->waitForElementPresent("link=Record Contribution (Check, Cash, EFT ...)");

    // verify soft credit details
    $expected = array(
      4 => 'Donation',
      2 => '100.00',
      6 => 'Pending',
      1 => $contact['display_name'],
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('Search')/div[2]/table[2]/tbody/tr[2]/td[$value]", preg_quote($label));
    }
    return $contact;
  }

  /**
   * @param array $contact
   */
  public function _testOnlineContribution($contact) {

    // Use default payment processor
    $processorName = 'Test Processor';
    $processorType = 'Dummy';
    $pageTitle = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $hash = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = FALSE;
    $friend = FALSE;
    $profilePreId = 1;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $memPriceSetId = NULL;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
      $amountSection,
      $payLater,
      $onBehalf,
      $pledges,
      $recurring,
      $memberships,
      $memPriceSetId,
      $friend,
      $profilePreId,
      $profilePostId,
      $premiums,
      $widget,
      $pcp
    );

    //logout
    $this->webtestLogout();
    $this->openCiviPage("contribute/transact", "reset=1&id=$pageId&action=preview", "_qf_Main_upload-bottom");

    $this->type("email-5", $contact['email']);

    $this->type("first_name", $contact['first_name']);
    $this->type("last_name", $contact['last_name']);

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);
    $this->click("xpath=//div[@class='crm-section payment_processor-section']/div[2]//label[text()='Pay later label {$hash}']");
    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Contribution is a Test?')]/../../td[2]/label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->type("sort_name", $contact['sort_name']);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='contributionSearch']//table/tbody/tr[1]/td[11]/span/a[text()='View']", FALSE);
    $this->click("xpath=//div[@id='contributionSearch']//table/tbody/tr[1]/td[11]/span/a[text()='View']");
    // View Contribution Record and test for expected values
    $expected = array(
      'From' => $contact['display_name'],
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
  }

}
