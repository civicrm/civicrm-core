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
class WebTest_Contribute_ContributionPageAddTest extends CiviSeleniumTestCase {
  function testContributionPageAdd() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array("Webtest Dummy" . substr(sha1(rand()), 0, 7) => 'Dummy'), TRUE, TRUE, 'required');

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
      '$ 50.00 Student',
      "$ $rand.00 Label $hash",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      "Honoree Section Title $hash",
      "Honoree Introductory Message $hash",
      'In Honor of',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }
  }

  // CRM-12510 Test copy contribution page
  function testContributionPageCopy() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array("Webtest Dummy" . substr(sha1(rand()), 0, 7) => 'Dummy'), TRUE, TRUE, 'required');

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
      '$ 50.00 Student',
      "$ $rand.00 Label $hash",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      "Honoree Section Title $hash",
      "Honoree Introductory Message $hash",
      'In Honor of',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }
  }

  /**
   * check CRM-7943
   */
  function testContributionPageSeparatePayment() {
    // open browser, login
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash = substr(sha1(rand()), 0, 7);
    $rand = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;

    // create contribution page with randomized title, default params and separate payment for Membership and Contribution
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array("Webtest Dummy" . substr(sha1(rand()), 0, 7) => 'Dummy'),
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
      "$ $rand.00 Label $hash",
      "Pay later label $hash",
      'Organization Details',
      'Other Amount',
      'I pledge to contribute this amount every',
      "Honoree Section Title $hash",
      "Honoree Introductory Message $hash",
      'In Honor of',
      'Name and Address',
      'Summary Overlay',
    );
    foreach ($texts as $text) {
      $this->assertTrue($this->isTextPresent($text), 'Missing text: ' . $text);
    }
  }

  /**
   * check CRM-7949
   */
  function testContributionPageSeparatePaymentPayLater() {
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

    $this->clickLink('_qf_Main_upload-bottom', '_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->webtestLogin();

    //Find Contribution
    $this->openCiviPage("contribute/search", "reset=1", 'contribution_date_low');

    $this->type('sort_name', "$firstName $lastName");
    $this->select('financial_type_id',"label=Member Dues");
    $this->clickLink('_qf_Search_refresh', "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", '_qf_ContributionView_cancel-bottom');
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Member Dues',
      'Total Amount' => '$ 50.00',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_ContributionView_cancel-bottom');

    //View Contribution for separate contribution
    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    // Open search criteria again
    $this->click("xpath=id('Search')/div[2]/div/div[1]");
    $this->waitForElementPresent("financial_type_id");
    $this->select('financial_type_id',"label=Donation");
    $this->clickLink('_qf_Search_refresh', "xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");

    $this->clickLink("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", '_qf_ContributionView_cancel-bottom');
    $expected = array(
      'From' => "{$firstName} {$lastName}",
      'Financial Type' => 'Donation',
      'Contribution Status' => 'Pending : Pay Later',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_ContributionView_cancel-bottom');

    //Find Member
    $this->openCiviPage("member/search", "reset=1", 'member_source');
    $this->type('sort_name', "$firstName $lastName");
    $this->clickLink('_qf_Search_refresh', "xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", '_qf_MembershipView_cancel-bottom');

    //View Membership Record
    $expected = array(
      'Member' => "{$firstName} {$lastName}",
      'Membership Type' => 'Student',
      'Status' => 'Pending',
    );
    $this->webtestVerifyTabularData($expected);
    $this->click('_qf_MembershipView_cancel-bottom');
  }
}

