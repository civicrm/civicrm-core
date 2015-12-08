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
 * Class WebTest_Contribute_ContributionPageAddTest
 */
class WebTest_Contribute_ContributionPageAddTest extends CiviSeleniumTestCase {
  public function testContributionPageAdd() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array('Test Processor' => 'Dummy'), TRUE, TRUE, 'required');

    $this->openCiviPage("admin/contribute", "reset=1");

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select testdrive mode
    $this->isTextPresent($pageTitle);
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", '_qf_Main_upload-bottom');

    // verify whatever’s possible to verify
    // FIXME: ideally should be expanded
    $texts = array(
      "Title - New Membership $hash",
      "This is introductory message for $pageTitle",
      'Student - $ 50.00',
      "Label $hash - $ $rand.00",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
      $this->waitForAjaxContent();
    }

    // Disable and re-enable Other Amounts (verify fix for CRM-15021)
    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", '_qf_Amount_next-bottom');
    $this->click("is_allow_other_amount");
    $this->clickLink("_qf_Amount_upload_done-bottom");
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", '_qf_Main_upload-bottom');
    $this->assertFalse($this->isTextPresent('Other Amount'), 'Other Amount present but not expected.');
    $this->openCiviPage("admin/contribute/amount", "reset=1&action=update&id=$pageId", '_qf_Amount_next-bottom');
    $this->click("is_allow_other_amount");
    $this->clickLink("_qf_Amount_upload_done-bottom");
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", '_qf_Main_upload-bottom');
    $this->assertTrue($this->isTextPresent('Other Amount'), 'Other Amount not present but expected.');
    $this->isElementPresent("xpath=//div[@class='content other_amount-content']/input");
  }

  /**
   * CRM-12510 Test copy contribution page
   */
  public function testContributionPageCopy() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array('Test Processor' => 'Dummy'), TRUE, TRUE, 'required');

    $this->openCiviPage("admin/contribute", "reset=1");

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->isTextPresent($pageTitle);

    // Call URL to make a copy of the page
    $this->openCiviPage("admin/contribute", "action=copy&gid=$pageId");

    // search for the new copy page and go to its test version
    $this->type('title', 'Copy of ' . $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->isTextPresent('Copy of ' . $pageTitle);
    // get page id of the copy
    // $copyPageId = $this->getText("xpath=//div[@id='configure_contribution_page']/tr[@id='row_4']/td[2]");
    $copyPageId = $this->getText("xpath=//div[@id='option11_wrapper']/table[@id='option11']/tbody/tr[1]/td[2]");
    // select testdrive mode
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$copyPageId", '_qf_Main_upload-bottom');

    // verify whatever’s possible to verify
    // FIXME: ideally should be expanded
    $texts = array(
      "Title - New Membership $hash",
      "This is introductory message for $pageTitle",
      'Student - $ 50.00',
      "Label $hash - $ $rand.00",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
      $this->waitForAjaxContent();
    }
  }

  /**
   * Check CRM-7943
   */
  public function testContributionPageSeparatePayment() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;

    // create contribution page with randomized title, default params and separate payment for Membership and Contribution
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array('Test Processor' => 'Dummy'),
      TRUE, TRUE, 'required', TRUE, FALSE, TRUE, NULL, TRUE,
      1, 7, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE
    );

    $this->openCiviPage("admin/contribute", "reset=1");

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select testdrive mode
    $this->isTextPresent($pageTitle);
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id=$pageId", '_qf_Main_upload-bottom');

    $texts = array(
      "Title - New Membership $hash",
      "This is introductory message for $pageTitle",
      "Label $hash - $ $rand.00",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
      $this->waitForAjaxContent();
    }
  }

  /**
   * Check CRM-7949
   */
  public function testContributionPageSeparatePaymentPayLater() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;

    // create contribution page with randomized title, default params and separate payment for Membership and Contribution
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, NULL,
      TRUE, TRUE, FALSE, FALSE, FALSE, TRUE, NULL, FALSE,
      1, 0, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE, FALSE
    );

    $this->openCiviPage("admin/contribute", "reset=1");

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //logout
    $this->webtestLogout();

    //Open Live Contribution Page
    $this->openCiviPage('contribute/transact', "reset=1&id=$pageId", '_qf_Main_upload-bottom');

    $firstName = 'Ya' . substr(sha1(rand()), 0, 4);
    $lastName = 'Cha' . substr(sha1(rand()), 0, 7);

    $this->type('email-5', $firstName . '@example.com');
    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);

    $this->select('state_province-1', "value=1002");
    $this->clickLink('_qf_Main_upload-bottom', '_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_date_low');

    $this->type('sort_name', "$lastName $firstName");
    $this->select('financial_type_id', "label=Member Dues");
    $this->clickLink('_qf_Search_refresh', "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", '_qf_ContributionView_cancel-bottom', FALSE);
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Member Dues',
      'Total Amount' => '$ 50.00',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_ContributionView_cancel-bottom');

    //View Contribution for separate contribution
    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']");
    // Open search criteria again
    $this->click("xpath=id('Search')/div[2]/div/div[1]");
    $this->waitForElementPresent("financial_type_id");
    $this->type("sort_name", $firstName);
    $this->select('financial_type_id', "label=Donation");
    $this->clickLink('_qf_Search_refresh', "xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", FALSE);
    $this->clickLink("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']", '_qf_ContributionView_cancel-bottom', FALSE);
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_ContributionView_cancel-bottom');

    //Find Member
    $this->openCiviPage("member/search", "reset=1", 'member_source');
    $this->type('sort_name', "$lastName $firstName");
    $this->clickLink('_qf_Search_refresh', "xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", FALSE);
    $this->clickLink("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", '_qf_MembershipView_cancel-bottom', FALSE);

    //View Membership Record
    $expected = array(
      'Member' => "{$firstName} {$lastName}",
      'Membership Type' => 'Student',
      'Status' => 'Pending',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_MembershipView_cancel-bottom');
  }

  /**
   * CRM-12994
   */
  public function testContributionPageAddPremiumRequiredField() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    $processor = array('Test Processor' => 'Dummy');

    // Create a new payment processor
    while (list($processorName, $processorType) = each($processor)) {
      $this->webtestAddPaymentProcessor($processorName, $processorType);
    }

    // go to the New Contribution Page page
    $this->openCiviPage('admin/contribute', 'action=add&reset=1');

    // fill in Title and Settings
    $this->type('title', $pageTitle);

    // to select financial type
    $this->select('financial_type_id', "label=Donation");

    $this->click('is_organization');
    $this->select("xpath=//*[@class='crm-contribution-onbehalf_profile_id']//span[@class='crm-profile-selector-select']//select", 'label=On Behalf Of Organization');
    $this->type('for_organization', "On behalf $hash");
    // make onBehalf optional
    $this->click('CIVICRM_QFID_1_2');

    $this->fillRichTextField('intro_text', 'This is introductory message for ' . $pageTitle, 'CKEditor');
    $this->fillRichTextField('footer_text', 'This is footer message for ' . $pageTitle, 'CKEditor');

    $this->type('goal_amount', 10 * $rand);

    // Submit form
    $this->clickLink('_qf_Settings_next', "_qf_Amount_next-bottom");

    // Get contribution page id
    $pageId = $this->urlArg('id');

    // fill in Processor, Amounts
    if (!empty($processor)) {
      reset($processor);
      while (list($processorName) = each($processor)) {
        // select newly created processor
        $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
        $this->assertTrue($this->isTextPresent($processorName));
        $this->check($xpath);
      }
    }

    // fill in labels & values in Fixed Contribution Options
    $this->type('label_1', 'Fixed Amount 1');
    $this->type('value_1', 1);
    $this->type('label_2', 'Fixed Amount 2');
    $this->type('value_2', 2);
    $this->type('label_3', 'Fixed Amount 3');
    $this->type('value_3', 3);
    $this->click('CIVICRM_QFID_1_4');
    $this->click('_qf_Amount_submit_savenext-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // click through to the membership view screen
    $this->click("css=li#tab_thankyou a");
    $this->waitForElementPresent('_qf_ThankYou_next-bottom');

    // fill in Receipt details
    $this->type('thankyou_title', "Thank-you Page Title $hash");
    $this->fillRichTextField('thankyou_text', 'This is thankyou message for ' . $pageTitle, 'CKEditor', TRUE);
    $this->fillRichTextField('thankyou_footer', 'This is thankyou footer message for ' . $pageTitle, 'CKEditor', TRUE);
    $this->click('is_email_receipt');
    $this->waitForElementPresent('bcc_receipt');
    $this->type('receipt_from_name', "Receipt From Name $hash");
    $this->type('receipt_from_email', "$hash@example.org");
    $this->type('receipt_text', "Receipt Message $hash");
    $this->type('cc_receipt', "$hash@example.net");
    $this->type('bcc_receipt', "$hash@example.com");

    $this->click('_qf_ThankYou_next');
    $this->waitForElementPresent('_qf_ThankYou_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $receiptText = "'ThankYou' information has been saved.";
    $this->assertTrue($this->isTextPresent($receiptText), 'Missing text: ' . $receiptText);

    $this->click('link=Premiums');
    $this->waitForElementPresent('_qf_Premium_submit_savenext-bottom');
    $assertPremiumsCheck = FALSE;
    if (!$this->isChecked('premiums_active')) {
      $assertPremiumsCheck = TRUE;
    }
    $this->assertTrue($assertPremiumsCheck, 'Premiums Section is not unchecked by default.');
    $this->click('_qf_Premium_submit_savenext-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $premiumText = "'Premium' information has been saved.";
    // check if clicking Save & Next button
    // Premium is saved rather than required validation error
    // for No Thank-you Label textfield
    $this->assertTrue($this->isTextPresent($premiumText));

    $this->openCiviPage("admin/contribute", "reset=1");

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->isElementPresent("xpath=//table[@class='display dataTable no-footer']/tbody/tr/td[1]/strong[text()='$pageTitle']");
    $this->waitForElementPresent("xpath=//table[@class='display dataTable no-footer']/tbody/tr/td[4]/div[@class='crm-contribution-page-configure-actions']/span[text()='Configure']");
    $this->click("xpath=//table[@id='option11']/tbody/tr/td[4]/div[@class='crm-contribution-page-configure-actions']/span[text()='Configure']");
    $this->waitForElementPresent("xpath=//table[@id='option11']/tbody/tr/td[4]/div[@class='crm-contribution-page-configure-actions']/span[text()='Configure']/ul[@class='panel']/li[8]/a[@title='Premiums']");
    $this->click("xpath=//table[@id='option11']/tbody/tr/td[4]/div[@class='crm-contribution-page-configure-actions']/span[text()='Configure']/ul[@class='panel']/li[8]/a[@title='Premiums']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('premiums_active');
    $this->waitForElementPresent('_qf_Premium_cancel-bottom');

    // click on Premiums Section Enabled? checkbox
    $this->click('premiums_active');
    $this->waitForElementPresent("xpath=//div[@id='premiumSettings']");
    $this->waitForElementPresent('premiums_nothankyou_position');
    $this->type('premiums_intro_title', 'Premiums Intro Title');
    $this->type('premiums_intro_text', 'Premiums Into Text');
    $this->type('premiums_contact_email', "$hash@example.net");

    // let No Thank-you Label text be blank
    // so that validation error appears
    // $this->type('premiums_nothankyou_label', );
    $this->select('premiums_nothankyou_position', 'value=2');

    // click on save & next button
    $this->click('_qf_Premium_submit_savenext-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $premiumRequiredText = "No Thank-you Label is a required field.";
    // check if clicking Save & Next button
    // required validation error appears
    // for No Thank-you Label textfield
    $this->waitForElementPresent("xpath=//*[@id='premiumSettings']/div/div[2]/table/tbody/tr[6]/td[2]/span[1]");
    $this->assertTrue($this->isTextPresent($premiumRequiredText));

    // fill in value for Premiums No Thank-you Label textfield
    $this->type('premiums_nothankyou_label', 'Premiums No Thank-you Label');
    $this->waitForElementPresent('_qf_Premium_upload_done-bottom');

    // click save & done button
    $this->click('_qf_Premium_upload_done-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $premiumSavedText = "'Premium' information has been saved.";
    // check if clicking Save & Done button
    // contribution page is saved.
    $this->assertTrue($this->isTextPresent($premiumSavedText));
  }

}
