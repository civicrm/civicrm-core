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
class WebTest_Member_OnlineAutoRenewMembershipTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testOnlineAutoRenewMembershipAnonymous() {
    //configure membership signup page.
    $pageId = $this->_configureMembershipPage();

    //now do the test membership signup.
    $this->openCiviPage('contribute/transact', "reset=1&action=preview&id={$pageId}", "_qf_Main_upload-bottom");
   
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    $this->click("auto_renew");

    $this->webtestAddCreditCardDetails();

    list($firstName, $middleName, $lastName) = $this->webtestAddBillingDetails();

    $this->type('email-5', "{$lastName}@example.com");

    $this->click("_qf_Main_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $text = 'I want this membership to be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $text = 'This membership will be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);
  }

  function testOnlineAutoRenewMembershipAuthenticated() {
    //configure membership signup page.
    $pageId = $this->_configureMembershipPage();

    $this->open($this->sboxPath);
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //now do the test membership signup.
    $this->openCiviPage('contribute/transact', "reset=1&action=preview&id={$pageId}", "_qf_Main_upload-bottom");
 
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    $this->click("auto_renew");

    $this->webtestAddCreditCardDetails();

    list($firstName, $middleName, $lastName) = $this->webtestAddBillingDetails();

    $this->type('email-5', "{$lastName}@example.com");

    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $text = 'I want this membership to be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $text = 'This membership will be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);
  }

  function testOnlinePendingAutoRenewMembershipAnonymous() {
    //configure membership signup page.
    $pageId = $this->_configureMembershipPage();

    //now do the test membership signup.
    $this->openCiviPage('contribute/transact', "reset=1&action=preview&id={$pageId}", "_qf_Main_upload-bottom");
   
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'Student')]");

    $this->click("auto_renew");

    $this->webtestAddCreditCardDetails();
    list($firstName, $middleName, $lastName) = $this->webtestAddBillingDetails();
    $this->type('email-5', "{$lastName}@example.com");

    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $text = 'I want this membership to be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $text = 'This membership will be renewed automatically every 1 year(s).';
    $this->assertElementContainsText('css=div.display-block', $text, 'Missing text: ' . $text);
  }

  function _configureMembershipPage() {
    static $pageId = NULL;

    if (!$pageId) {
      $this->open($this->sboxPath);
      $this->webtestLogin();

      //add payment processor.
      $hash          = substr(sha1(rand()), 0, 7);
      $rand          = 2 * rand(2, 50);
      $processorName = "Webtest Auto Renew AuthNet" . $hash;
      $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

      // -- start updating membership types
      $this->openCiviPage('admin/member/membershipType/add', "action=update&id=1&reset=1", "_qf_MembershipType_upload-bottom");

      $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");

      $this->type("duration_interval", "1");
      $this->select("duration_unit", "label=year");
      
      //wait for the auto-complete member_of_contact to populate
      sleep(2);

      $this->click("_qf_MembershipType_upload-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->openCiviPage('admin/member/membershipType/add', "action=update&id=2&reset=1", "_qf_MembershipType_upload-bottom");

      $this->click("xpath=//div[@id='membership_type_form']//table/tbody/tr[6]/td/label[contains(text(), 'Auto-renew Option')]/../../td[2]/label[contains(text(), 'Give option, but not required')]");

      $this->type("duration_interval", "1");
      $this->select("duration_unit", "label=year");

      //wait for the auto-complete member_of_contact to populate
      sleep(2);

      $this->click("_qf_MembershipType_upload-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());


      // create contribution page with randomized title and default params
      $amountSection   = FALSE;
      $payLater        = TRUE;
      $onBehalf        = FALSE;
      $pledges         = FALSE;
      $recurring       = TRUE;
      $membershipTypes = array(array('id' => 1, 'auto_renew' => 1),
        array('id' => 2, 'auto_renew' => 1),
      );
      $memPriceSetId = NULL;
      $friend        = TRUE;
      $profilePreId  = NULL;
      $profilePostId = NULL;
      $premiums      = TRUE;
      $widget        = TRUE;
      $pcp           = TRUE;

      $contributionTitle = "Title $hash";
      $pageId = $this->webtestAddContributionPage($hash,
        $rand,
        $contributionTitle,
        array($processorName => 'AuthNet'),
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
      $permissions = array("edit-1-make-online-contributions");
      $this->changePermissions($permissions);

      // now logout and do membership test that way
      $this->openCiviPage('logout', 'reset=1', NULL);
    }

    return $pageId;
  }
}

