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
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash      = substr(sha1(rand()), 0, 7);
    $rand      = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array("Webtest Dummy" . substr(sha1(rand()), 0, 7) => 'Dummy'), TRUE, TRUE, 'required');

    $this->open($this->sboxPath . 'civicrm/admin/contribute?reset=1');
    $this->waitForPageToLoad();

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select testdrive mode
    $this->isTextPresent($pageTitle);
    $this->open($this->sboxPath . 'civicrm/contribute/transact?reset=1&action=preview&id=' . $pageId);
    $this->waitForPageToLoad($this->getTimeoutMsec());
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

  /*
     * check CRM-7943
     */
  function testContributionPageSeparatePayment() {
    // open browser, login
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash      = substr(sha1(rand()), 0, 7);
    $rand      = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;

    // create contribution page with randomized title, default params and separate payment for Membership and Contribution
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, array("Webtest Dummy" . substr(sha1(rand()), 0, 7) => 'Dummy'),
      TRUE, TRUE, 'required', TRUE, FALSE, TRUE, NULL, TRUE,
      1, 7, TRUE, TRUE, TRUE, TRUE, FALSE, TRUE
    );

    $this->open($this->sboxPath . 'civicrm/admin/contribute?reset=1');
    $this->waitForPageToLoad();

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // select testdrive mode
    $this->isTextPresent($pageTitle);
    $this->open($this->sboxPath . 'civicrm/contribute/transact?reset=1&action=preview&id=' . $pageId);

    $this->waitForPageToLoad($this->getTimeoutMsec());
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

  /*
     * check CRM-7949
     */
  function testContributionPageSeparatePaymentPayLater() {
    // open browser, login
    $this->open($this->sboxPath);
    $this->webtestLogin();

    // a random 7-char string and an even number to make this pass unique
    $hash      = substr(sha1(rand()), 0, 7);
    $rand      = 2 * rand(2, 50);
    $pageTitle = 'Donate Online ' . $hash;

    // create contribution page with randomized title, default params and separate payment for Membership and Contribution
    $pageId = $this->webtestAddContributionPage($hash, $rand, $pageTitle, NULL,
      TRUE, TRUE, FALSE, FALSE, FALSE, TRUE, NULL, FALSE,
      1, 0, FALSE, FALSE, FALSE, FALSE, FALSE, TRUE, FALSE
    );

    $this->open($this->sboxPath . 'civicrm/admin/contribute?reset=1');
    $this->waitForPageToLoad();

    // search for the new contrib page and go to its test version
    $this->type('title', $pageTitle);
    $this->click('_qf_SearchContribution_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //get Url for Live Contribution Page
    $registerUrl = "civicrm/contribute/transact?reset=1&id=$pageId";
    //logout
    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Open Live Contribution Page
    $this->open($this->sboxPath . $registerUrl);
    $this->waitForElementPresent('_qf_Main_upload-bottom');

    $firstName = 'Ya' . substr(sha1(rand()), 0, 4);
    $lastName = 'Cha' . substr(sha1(rand()), 0, 7);

    $this->type('email-5', $firstName . '@example.com');
    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);
    //$this->click( "xpath=id('Main')/x:div[2]/x:div[3]/x:div[2]/x:label[2]" );
    $this->waitForElementPresent('_qf_Main_upload-bottom');
    $this->click('_qf_Main_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Confirm_next-bottom');

    $this->click('_qf_Confirm_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->open($this->sboxPath . 'civicrm/contribute/search?reset=1');

    $this->waitForElementPresent('contribution_date_low');

    $this->type('sort_name', "$firstName $lastName");
    $this->click('_qf_Search_refresh');

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_ContributionView_cancel-bottom');
    //View Contribution Record
    $expected = array(
      2 => 'Donation',
      7 => 'Pending : Pay Later',
      1 => "{$firstName} {$lastName}",
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody/tr[$value]/td[2]", preg_quote($label));
    }
    $this->click('_qf_ContributionView_cancel-bottom');


    //Find Member
    $this->open($this->sboxPath . 'civicrm/member/search?reset=1');
    $this->waitForElementPresent('member_source');
    $this->type('sort_name', "$firstName $lastName");
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //visit the Member View link
    $this->waitForElementPresent("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_MembershipView_cancel-bottom');

    //View Membership Record
    $expected = array(
      3 => 'Pending',
      1 => "{$firstName} {$lastName}",
    );
    foreach ($expected as $value => $label) {
      $this->verifyText("xpath=id('MembershipView')/div[2]/div/table/tbody/tr[$value]/td[2]", preg_quote($label));
    }
    $this->click('_qf_MembershipView_cancel-bottom');
  }
}

