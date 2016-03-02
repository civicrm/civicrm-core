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

  }

}
