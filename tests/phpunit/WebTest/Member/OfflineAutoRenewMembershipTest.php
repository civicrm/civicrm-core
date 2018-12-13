<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class WebTest_Member_OfflineAutoRenewMembershipTest
 */
class WebTest_Member_OfflineAutoRenewMembershipTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testOfflineAutoRenewMembership() {
    $this->webtestLogin();

    // We need a payment processor
    $processorName = "Webtest AuthNet" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName, 'AuthNet');

    // Create a membership type to use for this test
    $periodType = 'rolling';
    $duration_interval = 1;
    $duration_unit = 'year';
    $auto_renew = "optional";

    $memTypeParams = $this->webtestAddMembershipType($periodType, $duration_interval, $duration_unit, $auto_renew);

    // create a new contact for whom membership is to be created
    $firstName = 'Apt' . substr(sha1(rand()), 0, 4);
    $lastName = 'Mem' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName, "{$firstName}@example.com");
    $contactName = "$firstName $lastName";

    $this->click('css=li#tab_member a');

    $this->waitForElementPresent('link=Add Membership');

    // since we don't have live credentials we will switch to test mode
    $this->waitForElementPresent("xpath=//div[@class='view-content']//div[@class='action-link']/a[1]");
    $url = $this->getAttribute("xpath=//a[contains(text(), 'Add Membership')]@href");
    $url .= '&mode=test';
    $this->open($url);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // start filling membership form
    $this->waitForElementPresent('payment_processor_id');
    $this->select("payment_processor_id", "label={$processorName}");

    // fill in Membership Organization and Type
    $this->waitForElementPresent('membership_type_id[0]');
    $this->select("membership_type_id[0]", "label={$memTypeParams['member_of_contact']}");
    // Wait for membership type select to reload
    $this->waitForTextPresent($memTypeParams['membership_type']);
    $this->select("membership_type_id[1]", "label={$memTypeParams['membership_type']}");

    $this->click("source");
    $this->type("source", "Online Membership: Admin Interface");

    $this->waitForElementPresent('auto_renew');
    $this->click("auto_renew");
    $this->webtestAddCreditCardDetails();

    $this->webtestAddBillingDetails($firstName, NULL, $lastName);

    $this->clickLink("_qf_Membership_upload-bottom");

    // Use Find Members to make sure membership exists
    $this->openCiviPage("member/search", "reset=1", "member_end_date_high");

    $this->type("sort_name", "$lastName, $firstName");
    $this->click("member_test");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='memberSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickAjaxLink("xpath=//div[@id='memberSearch']/table/tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_MembershipView_cancel-bottom");

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
  }

  /**
   * CRM-18050: create price set with auto-renewing memberships.
   */
  public function testCreatePriceSetWithAutoRenewMembershipType() {
    $this->webtestLogin();

    // Create a membership type to use for this test
    $periodType = 'rolling';
    $duration_interval = 1;
    $duration_unit = 'year';
    $auto_renew = "required";
    $priceSetTitle = 'Membership PriceSet' . substr(sha1(rand()), 0, 7);
    $memTypeParams = $this->webtestAddMembershipType($periodType, $duration_interval, $duration_unit, $auto_renew);

    // Create a price set with membership type.
    $this->openCiviPage("admin/price", "reset=1&action=add", '_qf_Set_cancel-bottom');
    $this->type('title', $priceSetTitle);
    $this->click("xpath=//table[@class='form-layout']/tbody//tr/td[2]/label[text()='Membership']");
    $this->select('financial_type_id', "value=2");
    $this->click("_qf_Set_next-bottom");
    $this->waitForAjaxContent();
    $this->waitForElementPresent('is_active');
    $fieldLablel = 'Field Label' . substr(sha1(rand()), 0, 7);
    $this->type('label', $fieldLablel);
    $this->select('html_type', "value=Radio");
    $this->waitForAjaxContent();
    $this->waitForElementPresent('membership_type_id[1]');
    $this->select('membership_type_id[1]', "label={$memTypeParams['membership_type']}");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button//span[text()='Save']");
    $this->waitForElementPresent('field_page');
    $this->assertElementContainsText("xpath=//div[@id='crm-main-content-wrapper']/div[@id='field_page']/table/tbody/tr/td[1]/div", "{$fieldLablel}");
  }

}
