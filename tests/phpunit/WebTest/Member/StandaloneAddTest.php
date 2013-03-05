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
class WebTest_Member_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testStandaloneMemberAdd() {

    $this->webtestLogin();

    // create contact
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Memberson", "Memberson{$firstName}@memberson.name");
    $contactName = "Memberson, $firstName";

    // add membership type
    $membershipTypes = $this->webtestAddMembershipType();

    // now add membership
    $this->openCiviPage("member/add", "reset=1&action=add&context=standalone", "_qf_Membership_upload");

    // select contact
    $this->webtestFillAutocomplete($firstName);

    // fill in Membership Organization
    $this->select("membership_type_id[0]", "label={$membershipTypes['member_of_contact']}");

    // select membership type
    $this->select("membership_type_id[1]", "label={$membershipTypes['membership_type']}");

    // fill in Source
    $this->type("source", "Membership StandaloneAddTest Webtest");

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Let End Date be auto computed

    // fill in Status Override?
    // fill in Record Membership Payment?

    $this->click("_qf_Membership_upload");

    //View Membership
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberships']//table/tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $expected = array(
      'Membership Type' => $membershipTypes['membership_type'],
      'Status' => 'New',
      'Source' => 'Membership StandaloneAddTest Webtest',
    );
    $this->webtestVerifyTabularData($expected);
  }

  function testStandaloneMemberOverrideAdd() {

    $this->webtestLogin();

    // add contact
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Memberson", "Memberson{$firstName}@memberson.name");
    $contactName = "Memberson, $firstName";

    // add membership type
    $membershipTypes = $this->webtestAddMembershipType();

    // add membership
    $this->openCiviPage("member/add", "reset=1&action=add&context=standalone", "_qf_Membership_upload");

    // select contact
    $this->webtestFillAutocomplete($firstName);

    // fill in Membership Organization
    $this->select("membership_type_id[0]", "label={$membershipTypes['member_of_contact']}");

    // select membership type
    $this->select("membership_type_id[1]", "label={$membershipTypes['membership_type']}");

    // fill in Source
    $this->type("source", "Membership StandaloneAddTest Webtest");

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Let End Date be auto computed

    // fill in Status Override?
    $this->click("is_override", "value=1");
    $this->waitForElementPresent("status_id");
    $this->select("status_id", "value=3");

    // fill in Record Membership Payment?
    $this->click("record_contribution", "value=1");
    $this->waitForElementPresent("contribution_status_id");
      // let financial type be default

    // let the amount be default

    // select payment instrument type = Check and enter chk number
    $this->select("payment_instrument_id", "value=4");
    $this->waitForElementPresent("check_number");
    $this->type("check_number", "check #12345");
    $this->type("trxn_id", "P5476785" . rand(100, 10000));

    // fill  the payment status be default
    $this->select("contribution_status_id", "value=2");

    //----

    // Clicking save.
    $this->click("_qf_Membership_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // page was loaded
    $this->waitForTextPresent("Membership StandaloneAddTest Webtest");

    // verify if Membership is created
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    $expected = array(
      'Membership Type' => $membershipTypes['membership_type'],
      'Status' => 'Grace',
      'Source' => 'Membership StandaloneAddTest Webtest',
    );
    $this->webtestVerifyTabularData($expected);
  }
}


