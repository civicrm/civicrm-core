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
 * Class WebTest_Contact_DupeContactTest
 */
class WebTest_Contact_DupeContactTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testDuplicateContactAdd() {
    $this->webtestLogin();

    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    $firstName = substr(sha1(rand()), 0, 7);
    $lastName1 = substr(sha1(rand()), 0, 7);
    $email = "{$firstName}@example.com";
    $lastName2 = substr(sha1(rand()), 0, 7);

    //contact details section
    //select prefix
    $this->click("prefix_id");
    $this->select("prefix_id", "value=3");

    //fill in first name
    $this->type("first_name", "$firstName");

    //fill in last name
    $this->type("last_name", "$lastName1");

    //fill in email
    $this->type("email_1_email", "$email");

    //check for matching contact
    //$this->click("_qf_Contact_refresh_dedupe");
    //$this->waitForPageToLoad($this->getTimeoutMsec());

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    //contact details section

    //fill in first name
    $this->type("first_name", "$firstName");

    //fill in last name
    $this->type("last_name", "$lastName1");

    //fill in email
    $this->type("email_1_email", "$email");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText("css=.notify-content", "Please correct the following errors in the form fields below: One matching contact was found. You can View or Edit the existing contact.");
  }

  /**
   * Edit Dedupe rule for individual
   */
  public function testEditRule() {
    $this->webtestLogin();
    $this->openCiviPage('contact/deduperules', 'action=add&contact_type=Individual&reset=1');
    $ruleName = 'Rule_' . substr(sha1(rand()), 0, 7);

    //Add Rule for individual
    $this->type("title", "$ruleName");
    $this->click("xpath=//table[@class='form-layout']/tbody/tr[@class='crm-dedupe-rules-form-block-used']/td[2]/label[text()='General']");
    $lengthValueFname = $lengthValueLname = 7;
    $weighthValueFname = 5;
    $weightValueLname = 8;
    $lengthValueEmail = 20;
    $weightValueEmail = 15;

    // Add first name
    $this->select("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[1]/select", 'label=First Name');
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[2]/input", 10);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[3]/input", 10);
    // Add last name
    $this->select("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[1]/select", 'label=Last Name');
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[2]/input", 10);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[3]/input", 10);
    // Add email
    $this->select("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[1]/select", 'label=Email');
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[2]/input", 10);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[3]/input", 10);
    $this->click("_qf_DedupeRules_next-bottom");
    $this->waitForText("crm-notification-container", "The rule '$ruleName' has been saved.");

    // Edit the rule for individual.
    $this->click("xpath=//div[@id='browseValues_Individual']/div[1]/div/table/tbody//tr/td[1][text()='$ruleName']/../td[3]/span//a[text()='Edit Rule']");
    $this->waitForElementPresent("_qf_DedupeRules_cancel-bottom");

    //edit length and weight for First Name
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[2]/input", $lengthValueFname);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[3]/input", $weighthValueFname);

    //edit length and weight for Last Name
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[2]/input", $lengthValueLname);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[3]/input", $weightValueLname);

    //edit length and weight for Email
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[2]/input", $lengthValueEmail);
    $this->type("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[3]/input", $weightValueEmail);

    $this->click("_qf_DedupeRules_next-bottom");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[@id='browseValues_Individual']/div[1]/div/table/tbody//tr/td[1][text()='$ruleName']/../td[3]/span//a[text()='Edit Rule']");
    $this->waitForAjaxContent();

    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[2]/input[@value=$lengthValueFname]"));
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[2]/td[3]/input[@value=$weighthValueFname]"));
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[2]/input[@value=$lengthValueLname]"));
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[3]/input[@value=$weightValueLname]"));
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[2]/input[@value=$lengthValueEmail]"));
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='form-layout-compressed']/tbody/tr[4]/td[3]/input[@value=$weightValueEmail]"));
  }

}
