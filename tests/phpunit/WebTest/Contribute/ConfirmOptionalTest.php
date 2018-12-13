<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class WebTest_Contribute_ConfirmOptionalTest
 */
class WebTest_Contribute_ConfirmOptionalTest extends CiviSeleniumTestCase {
  protected $pageId = 0;

  protected function setUp() {
    parent::setUp();
  }

  public function testWithConfirm() {
    $this->_addContributionPage(TRUE);
    $this->_fillOutContributionPage();

    // confirm contribution
    $this->assertFalse($this->isTextPresent("Your transaction has been processed successfully"), "Loaded thank you page");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->assertTrue($this->isTextPresent("Please verify the information below carefully"), "Should load confirmation page");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // thank you page
    $this->assertTrue($this->isTextPresent("Your transaction has been processed successfully"), "Should load thank you page");
  }

  public function testWithoutConfirm() {
    $this->_addContributionPage(FALSE);
    $this->_fillOutContributionPage();

    // thank you page
    $this->assertTrue($this->isTextPresent("Your transaction has been processed successfully"), "Didn't load thank you page after main page");
    $this->assertFalse($this->isTextPresent("Your contribution will not be completed until"), "Loaded confirmation page");
  }

  /**
   * @param $isConfirmEnabled
   */
  protected function _addContributionPage($isConfirmEnabled) {
    // log in
    $this->webtestLogin();

    // create new contribution page
    $hash = substr(sha1(rand()), 0, 7);
    $this->pageId = $this->webtestAddContributionPage(
      $hash,
      $rand = NULL,
      $pageTitle = "Test Confirm ($hash)",
      $processor = array("Dummy ($hash)" => 'Dummy'),
      $amountSection = TRUE,
      $payLater = FALSE,
      $onBehalf = FALSE,
      $pledges = FALSE,
      $recurring = FALSE,
      $membershipTypes = FALSE,
      $memPriceSetId = NULL,
      $friend = FALSE,
      $profilePreId = NULL,
      $profilePostId = NULL,
      $premiums = FALSE,
      $widget = FALSE,
      $pcp = FALSE,
      $isAddPaymentProcessor = TRUE,
      $isPcpApprovalNeeded = FALSE,
      $isSeparatePayment = FALSE,
      $honoreeSection = FALSE,
      $allowOtherAmount = TRUE,
      $isConfirmEnabled = $isConfirmEnabled
    );
  }

  protected function _fillOutContributionPage() {
    // load contribution page
    $this->openCiviPage("contribute/transact", "reset=1&id={$this->pageId}&action=preview", "_qf_Main_upload-bottom");

    // fill out info
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", "30");
    $this->webtestAddCreditCardDetails();
    list($firstName, $middleName, $lastName) = $this->webtestAddBillingDetails();
    $this->type('email-5', "$lastName@example.com");

    // submit contribution
    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

}
