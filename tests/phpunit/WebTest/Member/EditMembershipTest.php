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
 * Class WebTest_Member_EditMembershipTest
 */
class WebTest_Member_EditMembershipTest extends CiviSeleniumTestCase {
  protected function setUp() {
    parent::setUp();
  }

  public function testEditMembershipActivityTypes() {
    // Log in using webtestLogin() method
    $this->webtestLogin();
    // create contact
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Memberson", "Memberson{$firstName}@memberson.name");
    $contactName = "Memberson, {$firstName}";
    $displayName = "{$firstName} Memberson";

    // Add new Financial Account
    $orgName = 'Alberta ' . substr(sha1(rand()), 0, 7);
    $financialAccountTitle = 'Financial Account ' . substr(sha1(rand()), 0, 4);
    $financialAccountDescription = "{$financialAccountTitle} Description";
    $accountingCode = 1033;
    $financialAccountType = 'Liability';
    $taxDeductible = TRUE;
    $isActive = FALSE;
    $isTax = TRUE;
    $taxRate = 10.00;
    $isDefault = FALSE;

    //Add new organisation
    $this->webtestAddOrganization($orgName);

    $this->_testAddFinancialAccount($financialAccountTitle,
      $financialAccountDescription,
      $accountingCode,
      $orgName,
      $financialAccountType,
      $taxDeductible,
      $isActive,
      $isTax,
      $taxRate,
      $isDefault
    );

    //Add new Financial Type
    $financialType['name'] = 'Taxable FinancialType ' . substr(sha1(rand()), 0, 4);
    $financialType['is_deductible'] = TRUE;
    $financialType['is_reserved'] = FALSE;
    $this->addeditFinancialType($financialType);

    // Assign the created Financial Account $financialAccountTitle to $financialType
    $this->click("xpath=id('ltype')/div/table/tbody/tr/td[1]/div[text()='$financialType[name]']/../../td[7]/span/a[text()='Accounts']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']//button/span[contains(text(), 'Assign Account')]");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']//button/span[contains(text(), 'Assign Account')]");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->select('account_relationship', "label=Sales Tax Account is");
    $this->waitForAjaxContent();
    $this->select('financial_account_id', "label=" . $financialAccountTitle);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']//button/span[contains(text(), 'Assign Account')]");

    // add membership type
    $membershipTypes = $this->webtestAddMembershipType('rolling', 1, 'year', 'no', 100, $financialType['name']);

    // now add membership
    $this->openCiviPage("member/add", "reset=1&action=add&context=standalone", "_qf_Membership_upload");

    // select contact
    $this->webtestFillAutocomplete($contactName);

    // fill in Membership Organization
    $this->select("membership_type_id[0]", "label={$membershipTypes['member_of_contact']}");

    // select membership type
    $this->select("membership_type_id[1]", "label={$membershipTypes['membership_type']}");

    // fill in Source
    $this->type("source", "Membership StandaloneAddTest Webtest");

    // Let Join Date and Start Date stay default
    $this->click("_qf_Membership_upload");

    //Open related 'Edit Contribution' form
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberships']//table/tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_MembershipView_cancel-bottom");
    //CRM-17417, Simply open and save edit contribution form to check that tax shouldn't be reapplied
    $this->clickLink("xpath=//a[@title='Edit Contribution']", "_qf_Contribution_upload", FALSE);
    $this->click("_qf_Contribution_upload");
    $this->waitForAjaxContent();
    $this->assertTrue($this->isTextPresent("$ 110.00"), "Contribution Amount got updated as Sale Tax got reapplied which is wrong");

    //View Membership
    $this->click("css=li#tab_member a");
    $this->waitForElementPresent("xpath=//div[@id='memberships']//table//tbody/tr[1]/td[9]/span/a[text()='View']");
    $this->click("xpath=//div[@id='memberships']//table/tbody/tr[1]/td[9]/span/a[text()='View']");
    $expected = array(
      'Membership Type' => $membershipTypes['membership_type'],
      'Status' => 'New',
      'Source' => 'Membership StandaloneAddTest Webtest',
    );
    $this->webtestVerifyTabularData($expected);

    // now edit and update type and status
    $this->click("crm-membership-edit-button-top");
    $this->waitForElementPresent("_qf_Membership_upload-bottom");
    $this->click('is_override');
    $this->waitForElementPresent('status_id');
    $this->select('status_id', 'label=Current');
    $this->select('membership_type_id[0]', 'value=1');
    $this->select('membership_type_id[1]', 'value=1');
    $this->click('_qf_Membership_upload-bottom');

    $this->waitForElementPresent("access");

    // Use activity search to find the expected activities
    $this->openCiviPage('activity/search', 'reset=1', "_qf_Search_refresh");

    $this->type("sort_name", $contactName);
    $this->select('activity_type_id', 'value=35');
    $this->select('activity_type_id', 'value=36');
    $this->clickLink("_qf_Search_refresh");

    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[2][text()='Change Membership Type']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[3][text()='Type changed from {$membershipTypes['membership_type']} to General']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td[5]/a[text()='{$contactName}']"));
  }

}
