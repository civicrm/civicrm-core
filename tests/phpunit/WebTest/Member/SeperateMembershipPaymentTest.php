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
class WebTest_Member_SeperateMembershipPaymentTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testSeperateMembershipCreate() {
    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    $firstName1 = 'Ma_' . substr(sha1(rand()), 0, 7);
    $lastName1 = 'An_' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, $lastName1, TRUE);
    $this->assertElementContainsText('crm-notification-container', "$firstName1 $lastName1 has been created.");
    $url = explode('&cid=', $this->getLocation());
    $cid = $url[1];

    // create contribution page with randomized title and default params
    $amountSection = TRUE;
    $payLater = TRUE;
    $onBehalf = FALSE;
    $pledges = FALSE;
    $recurring = FALSE;
    $memberships = TRUE;
    $memPriceSetId = NULL;
    $friend = TRUE;
    $profilePreId = NULL;
    $profilePostId = NULL;
    $premiums = FALSE;
    $widget = FALSE;
    $pcp = FALSE;
    $isAddPaymentProcessor = FALSE;
    $isSeparatePayment = TRUE;

    $contributionTitle = "Title $hash";
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $contributionTitle,
      NULL,
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
      $pcp,
      $isAddPaymentProcessor,
      FALSE,
      $isSeparatePayment,
      FALSE
    );


    // create new membership types
    $memTypeParams1 = $this->webtestAddMembershipType();
    $memTypeTitle1  = $memTypeParams1['membership_type'];
    $memTypeId1     = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/div[2]/table/tbody//tr/td[text()='{$memTypeTitle1}']/../td[12]/span/a[3]@href"));
    $memTypeId1     = $memTypeId1[1];

    $memTypeParams2 = $this->webtestAddMembershipType();
    $memTypeTitle2  = $memTypeParams2['membership_type'];
    $memTypeId2     = explode('&id=', $this->getAttribute("xpath=//div[@id='membership_type']/div[2]/table/tbody//tr/td[text()='{$memTypeTitle2}']/../td[12]/span/a[3]@href"));
    $memTypeId2     = $memTypeId2[1];

    // edit contribution page memberships tab to add two new membership types
    $this->openCiviPage('admin/contribute/membership', "reset=1&action=update&id={$pageId}", "_qf_MembershipBlock_next-bottom");
    $this->click("membership_type_$memTypeId1");
    $this->click("membership_type_$memTypeId2");
    $this->click('_qf_MembershipBlock_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_MembershipBlock_next-bottom');
    $text = "'MembershipBlock' information has been saved.";
    $this->assertElementContainsText('crm-notification-container', $text, 'Missing text: ' . $text);
    $this->_testOnlineMembershipSignup($pageId, $memTypeTitle1, $cid);

    //Find Member
    $this->openCiviPage('member/search', 'reset=1', 'member_end_date_high');
    $this->type("sort_name", "$firstName1 $lastName1");
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='memberSearch']/table/tbody/tr");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    //View Membership Record
    $verifyData = array(
      'Member' => $firstName1 . ' ' . $lastName1,
      'Membership Type' => $memTypeTitle1,
      'Status' => 'Pending',
      'Source' => 'Online Contribution:' . ' ' . $contributionTitle,
    );

    $this->webtestVerifyTabularData($verifyData);

    // Click View action link on associated contribution record
    $this->waitForElementPresent("xpath=//form[@id='MembershipView']/div[2]/div/table[@class='selector']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->click("xpath=//form[@id='MembershipView']/div[2]/div/table[@class='selector']/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    //View Contribution Record
    $verifyData = array(
      'From' => $firstName1 . ' ' . $lastName1,
      'Total Amount' => '$ 100.00',
    );
    $this->webtestVerifyTabularData($verifyData);

    $this->click("_qf_ContributionView_cancel-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testOnlineMembershipSignup($pageId, $memTypeId, $cid = NULL) {
    //Open Live Contribution Page
    if ($cid) {
      $contribUrl = array('url' => "contribute/transact", 'args' => "reset=1&id=$pageId&cid=$cid");
    }
    else {
      $contribUrl = array('url' => "contribute/transact", 'args' => "reset=1&id=$pageId");
    }
    $this->openCiviPage($contribUrl['url'], $contribUrl['args'], '_qf_Main_upload-bottom');

    // Select membership type 1
    $this->click("xpath=//div[@class='crm-section membership_amount-section']/div[2]//span/label/span[2][contains(text(),'$memTypeId')]");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 60);
    $this->click("_qf_Main_upload-bottom");
    $this->waitForElementPresent("_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}