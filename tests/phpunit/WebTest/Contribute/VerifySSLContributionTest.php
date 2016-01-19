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
 * Class WebTest_Contribute_VerifySSLContributionTest
 */
class WebTest_Contribute_VerifySSLContributionTest extends CiviSeleniumTestCase {

  protected $initialized = FALSE;
  protected $names = array();
  protected $pageId = 0;

  protected function setUp() {
    parent::setUp();
  }

  public function testPaymentProcessorsSSL() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->_initialize();
    $this->_tryPaymentProcessor($this->names['AuthNet']);

    // todo: write code to check other payment processors
    /*$this->_tryPaymentProcessor($this->names['Google_Checkout']);
    $this->_tryPaymentProcessor($this->names['PayPal']);
    $this->_tryPaymentProcessor($this->names['PayPal_Standard']);*/
  }

  public function _initialize() {
    if (!$this->initialized) {
      // log in
      $this->webtestLogin();

      // build names
      $hash = substr(sha1(rand()), 0, 7);
      $contributionPageTitle = "Verify SSL ($hash)";
      $this->names['PayPal'] = "PayPal Pro ($hash)";
      $this->names['AuthNet'] = "AuthNet ($hash)";
      //$this->names['PayPal_Standard'] = "PayPal Standard ($hash)";

      $processors = array();
      foreach ($this->names as $key => $val) {
        $processors[$val] = $key;
      }

      // create new contribution page
      $this->pageId = $this->webtestAddContributionPage(
        $hash,
        $rand = NULL,
        $pageTitle = $contributionPageTitle,
        $processor = $processors,
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
        $allowOtherAmount = TRUE
      );

      // enable verify ssl
      $this->openCiviPage("admin/setting/url", "reset=1");
      $this->click("id=CIVICRM_QFID_1_verifySSL");
      $this->click("id=_qf_Url_next-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->initialized = TRUE;
    }
  }

  /**
   * @param string $name
   */
  public function _tryPaymentProcessor($name) {
    // load contribution page
    $this->openCiviPage("contribute/transact", "reset=1&action=preview&id={$this->pageId}", "_qf_Main_upload-bottom");

    // fill out info
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", "30");
    $this->type('email-5', "smith@example.com");

    // choose the payment processor
    $this->click("xpath=//label[text() = '{$name}']/preceding-sibling::input[1]");

    // do we need to add credit card details?
    if (strpos($name, "AuthNet") !== FALSE || strpos($name, "PayPal Pro") !== FALSE) {
      $this->webtestAddCreditCardDetails();
      list($firstName, $middleName, $lastName) = $this->webtestAddBillingDetails();
    }

    // submit contribution
    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next-bottom");

    // confirm contribution
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertStringsPresent("Payment Processor Error message");
  }

}
