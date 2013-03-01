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
class WebTest_Contribute_OnlineContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testOnlineContributionAdd() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $processorType = 'Dummy';
    $pageTitle     = substr(sha1(rand()), 0, 7);
    $rand          = 2 * rand(10, 50);
    $hash          = substr(sha1(rand()), 0, 7);
    $amountSection = TRUE;
    $payLater      = FALSE;
    $onBehalf      = FALSE;
    $pledges       = FALSE;
    $recurring     = FALSE;
    $memberships   = FALSE;
    $friend        = TRUE;
    $profilePreId  = 1;
    $profilePostId = NULL;
    $premiums      = FALSE;
    $widget        = FALSE;
    $pcp           = FALSE;
    $memPriceSetId = NULL;

    // create a new online contribution page
    // create contribution page with randomized title and default params
    $pageId = $this->webtestAddContributionPage($hash,
      $rand,
      $pageTitle,
      array($processorName => $processorType),
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
      $pcp
    );

    //logout
    $this->open($this->sboxPath . "civicrm/logout?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Open Live Contribution Page
    $this->open($this->sboxPath . "civicrm/contribute/transact?reset=1&id=" . $pageId);
    $this->waitForElementPresent("_qf_Main_upload-bottom");


    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $honorFirstName = 'In' . substr(sha1(rand()), 0, 4);
    $honorLastName = 'Hon' . substr(sha1(rand()), 0, 7);
    $honorEmail = $honorFirstName . "@example.com";
    $honorSortName = $honorLastName . ', ' . $honorFirstName;
    $honorDisplayName = 'Ms. ' . $honorFirstName . ' ' . $honorLastName; 

    $this->type("email-5", $firstName . "@example.com");

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->click("xpath=//div[@class='crm-section other_amount-section']//div[2]/input");
    $this->type("xpath=//div[@class='crm-section other_amount-section']//div[2]/input", 100);

    $streetAddress = "100 Main Street";
    $this->type("street_address-1", $streetAddress);
    $this->type("city-1", "San Francisco");
    $this->type("postal_code-1", "94117");
    $this->select("country-1", "value=1228");
    $this->select("state_province-1", "value=1001");

    // Honoree Info
    $this->click("xpath=id('Main')/x:div[2]/x:fieldset/x:div[2]/x:div/x:label[text()='In Honor of']");
    $this->waitForElementPresent("honor_email");

    $this->select("honor_prefix_id", "label=Ms.");
    $this->type("honor_first_name", $honorFirstName);
    $this->type("honor_last_name", $honorLastName);
    $this->type("honor_email", $honorEmail);
    
    //Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . "billing");
    $this->type("billing_last_name", $lastName . "billing");
    $this->type("billing_street_address-5", "15 Main St.");
    $this->type(" billing_city-5", "San Jose");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");
    $this->click("_qf_Main_upload-bottom");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Confirm_next-bottom");

    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login to check contribution
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin();

    //Find Contribution
    $this->open($this->sboxPath . "civicrm/contribute/search?reset=1");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "$firstName $lastName");
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->click("xpath=//div[@id='contributionSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    //View Contribution Record and verify data
    $expected = array(
      'From'                => "{$firstName} {$lastName}",
      'Financial Type'   => 'Donation',
      'Total Amount'        => '100.00',
      'Contribution Status' => 'Completed',
      'In Honor of'         => $honorDisplayName
    );
    $this->webtestVerifyTabularData($expected);

    // Check for Honoree contact created
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", $honorSortName);
    $this->typeKeys("css=input#sort_name_navigation", $honorSortName);

    // wait for result list
    $this->waitForElementPresent("css=div.ac_results-inner li");

    // visit contact summary page
    $this->click("css=div.ac_results-inner li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is contact present?
    $this->assertTrue($this->isTextPresent("$honorDisplayName"), "Honoree contact not found.");
    
    }
  }

