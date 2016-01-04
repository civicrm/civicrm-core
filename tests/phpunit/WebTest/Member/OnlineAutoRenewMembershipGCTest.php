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
 * Class WebTest_Member_OnlineAutoRenewMembershipGCTest
 */
class WebTest_Member_OnlineAutoRenewMembershipGCTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOnlineAutoRenewMembershipAnonymous() {
    //configure membership signup page.
    $pageId = $this->_configureMembershipPage();

    //now do the test membership signup.
    $this->openCiviPage('contribute/transact', "reset=1&action=preview&id={$pageId}", "_qf_Main_upload-bottom");

    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[2]/span/label/span[1][contains(text(),'Student')]");
    $this->click("auto_renew");

    $firstName = 'John';
    $lastName = 'Smith_' . substr(sha1(rand()), 0, 7);
    $this->type('email-5', "{$lastName}@example.com");

    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next_checkout");

    $text = 'I want this membership to be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);

    $this->click("_qf_Confirm_next_checkout");

    // FIXME: By this time pending records has already been created. Formatting for external page (google checkout in this case)

    // has changed a bit. No point in adding test for external page as we 'll test with fake transactions.
  }

  public function testOnlineAutoRenewMembershipAuthenticated() {
    //configure membership signup page.
    $pageId = $this->_configureMembershipPage();

    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //now do the test membership signup.
    $this->openCiviPage('contribute/transact', "reset=1&action=preview&id={$pageId}", "_qf_Main_upload-bottom");
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]/div[2]/span/label/span[1][contains(text(),'Student')]");

    $this->click("auto_renew");

    $firstName = 'John';
    $lastName = 'Smith_' . substr(sha1(rand()), 0, 7);
    $this->type('email-5', "{$lastName}@example.com");

    $this->clickLink("_qf_Main_upload-bottom", "_qf_Confirm_next_checkout");

    $text = 'I want this membership to be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);

    $this->click("_qf_Confirm_next_checkout");

    // FIXME: By this time pending records has already been created. Formatting for external page (google checkout in this case)

    // has changed a bit. No point in adding test for external page as we 'll test with fake transactions.
  }

  /**
   * @return null
   */
  public function _configureMembershipPage() {
    static $pageId = NULL;

    if (!$pageId) {
      $this->webtestLogin();

      //add payment processor.
      $hash = substr(sha1(rand()), 0, 7);
      $rand = 2 * rand(2, 50);
      $processorName = "Webtest Auto Renew Google Checkout" . $hash;
      $this->webtestAddPaymentProcessor($processorName, 'Google_Checkout');

      // -- start updating membership types
      $this->openCiviPage('admin/member/membershipType/add', 'action=update&id=1&reset=1');

      $this->waitForElementPresent("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");
      $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");

      $this->type("duration_interval", "1");
      $this->select("duration_unit", "label=year");

      $this->click("_qf_MembershipType_upload-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->openCiviPage('admin/member/membershipType/add', 'action=update&id=2&reset=1');

      $this->waitForElementPresent("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");
      $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");

      $this->type("duration_interval", "1");
      $this->select("duration_unit", "label=year");

      $this->click("_qf_MembershipType_upload-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      // create contribution page with randomized title and default params
      $amountSection = FALSE;
      $payLater = TRUE;
      $onBehalf = FALSE;
      $pledges = FALSE;
      $recurring = TRUE;
      $membershipTypes = array(
        array('id' => 1, 'auto_renew' => 1),
        array('id' => 2, 'auto_renew' => 1),
      );
      $memPriceSetId = NULL;
      $friend = TRUE;
      $profilePreId = NULL;
      $profilePostId = NULL;
      $premiums = TRUE;
      $widget = TRUE;
      $pcp = TRUE;

      $contributionTitle = "Title $hash";
      $pageId = $this->webtestAddContributionPage($hash,
        $rand,
        $contributionTitle,
        array($processorName => 'Google_Checkout'),
        $amountSection,
        $payLater,
        $onBehalf,
        $pledges,
        $recurring,
        $membershipTypes,
        $memPriceSetId,
        $friend,
        $profilePreId,
        $profilePostId,
        $premiums,
        $widget,
        $pcp,
        FALSE
      );

      //make sure we do have required permissions.
      $permissions = array("edit-1-make-online-contributions", "edit-1-profile-listings-and-forms");
      $this->changePermissions($permissions);

      // now logout and do membership test that way
      $this->webtestLogout();
    }

    return $pageId;
  }

}
