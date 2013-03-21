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
class WebTest_Member_OfflineAutoRenewMembershipTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testOfflineAutoRenewMembership() {
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest AuthNet" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

    // Create a membership type to use for this test
    $periodType        = 'rolling';
    $duration_interval = 1;
    $duration_unit     = 'year';
    $auto_renew        = "optional";

    $memTypeParams = $this->webtestAddMembershipType($periodType, $duration_interval, $duration_unit, $auto_renew);

    // create a new contact for whom membership is to be created
    $firstName = 'Apt' . substr(sha1(rand()), 0, 4);
    $lastName = 'Mem' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName, "{$firstName}@example.com");
    $contactName = "$firstName $lastName";

    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Submit Credit Card Membership');
    $this->click('link=Submit Credit Card Membership');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // since we don't have live credentials we will switch to test mode
    $url = $this->getLocation();
    $url = str_replace('mode=live', 'mode=test', $url);
    $this->open($url);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // start filling membership form
    $this->waitForElementPresent('payment_processor_id');
    $this->select("payment_processor_id", "label={$processorName}");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$memTypeParams['member_of_contact']}");
    // Wait for membership type select to reload
    $this->waitForTextPresent($memTypeParams['membership_type']);
    $this->select("membership_type_id[1]", "label={$memTypeParams['membership_type']}");

    $this->click("source");
    $this->type("source", "Online Membership: Admin Interface");

    $this->waitForElementPresent('auto_renew');
    $this->click("auto_renew");

    $this->webtestAddCreditCardDetails();

    // since country is not pre-selected for offline mode
    $this->select("billing_country_id-5", "label=United States");
    //wait for states to populate the select box
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(2);
    $this->click('billing_state_province_id-5');
    $this->webtestAddBillingDetails($firstName, NULL, $lastName);

    $this->click("_qf_Membership_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Use Find Members to make sure membership exists
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$firstName $lastName");
    $this->click("member_test");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='memberSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");

    // View Membership Record
    $verifyData = array(
      'Member' => "$firstName $lastName",
      'Membership Type' => $memTypeParams['membership_type'],
      'Source' => 'Online Membership: Admin Interface',
      'Status' => 'Pending',
      'Auto-renew' => 'Yes',
    );
    foreach ($verifyData as $label => $value) {
      $this->verifyText("xpath=//form[@id='MembershipView']//table/tbody/tr/td[text()='{$label}']/following-sibling::td",
        preg_quote($value)
      );
    }
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");
  }
}

