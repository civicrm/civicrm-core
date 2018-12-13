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
 * Class WebTest_Contact_CustomDataAddTest
 */
class WebTest_Contact_CustomDataAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCustomDataAdd() {
    $this->webtestLogin();

    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');

    //fill custom group title
    $customGroupTitle = 'custom_' . substr(sha1(rand()), 0, 7);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Contact");
    $this->click("//option[@value='Contact']");
    $this->clickLink("_qf_Group_next-bottom");

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");
    $this->waitForElementPresent("_qf_Field_cancel-bottom");

    //add custom field - alphanumeric checkbox
    $checkboxFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->click("label");
    $this->type("label", $checkboxFieldLabel);
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=CheckBox");
    $this->click("//option[@value='CheckBox']");
    $checkboxOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $checkboxOptionLabel1);
    $this->type("option_value_1", "1");
    $checkboxOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $checkboxOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=add another choice");
    $checkboxOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_3", $checkboxOptionLabel3);
    $this->type("option_value_3", "3");

    //enter options per line
    $this->type("options_per_line", "2");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_done");

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$checkboxFieldLabel' has been saved.");
    $this->waitForElementPresent('newCustomField');

    //create another custom field - Integer Radio
    $this->clickLink("newCustomField", '_qf_Field_cancel', FALSE);
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=1");
    $this->click("//option[@value='1']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $radioOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=add another choice");
    $radioOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_3", $radioOptionLabel3);
    $this->type("option_value_3", "3");

    //select options per line
    $this->type("options_per_line", "3");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_done");

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$radioFieldLabel' has been saved.");

    // Go to the URL to create an Individual contact.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //expand all tabs
    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //fill first name, last name, email id
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->click("first_name");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email_1_email", $emailId);

    //fill custom values for the contact
    $this->click("xpath=//table//tr/td/label[text()=\"$checkboxOptionLabel2\"]");
    $this->click("xpath=//table//tr/td/label[text()=\"$radioOptionLabel3\"]");
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  public function testCustomDataMoneyAdd() {
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');

    //fill custom group title
    $customGroupTitle = 'custom_' . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent("title");
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Contact");
    $this->click("//option[@value='Contact']");
    $this->clickLink("_qf_Group_next-bottom");

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");
    $this->waitForElementPresent("_qf_Field_cancel-bottom");

    //add custom field - money text
    $moneyTextFieldLabel = 'money' . substr(sha1(rand()), 0, 4);
    $this->click("label");
    $this->type("label", $moneyTextFieldLabel);
    $this->waitForElementPresent("data_type[0]");
    $this->click("data_type[0]");
    $this->select("data_type[0]", "label=Money");

    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Text");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_done");

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$moneyTextFieldLabel' has been saved.");

    //Get the customFieldsetID
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $customFieldsetId = explode('&gid=', $this->getAttribute("xpath=//div[@id='custom_group']/table/tbody//tr/td/div[text()='$customGroupTitle']/../../td[7]/span/a@href"));
    $customFieldsetId = $customFieldsetId[1];

    //create Individual contact
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //expand all tabs
    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //fill first name, last name, email id
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->click("first_name");
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("email_1_email", $emailId);

    //fill custom values for the contact
    $this->click("xpath=//table//tr/td/label[text()=\"$moneyTextFieldLabel\"]");
    $this->type("xpath=//table//tr/td/label[text()=\"$moneyTextFieldLabel\"]/../following-sibling::td/input", "12345678.98");
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //verify the money custom field value in the proper format
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='custom-set-content-{$customFieldsetId}']/div/div[2]/div[2]"));
    $this->assertElementContainsText("xpath=//div[@id='custom-set-content-{$customFieldsetId}']/div/div[2]/div[2]", '12,345,678.98');
  }

  public function testCustomDataChangeLog() {
    $this->webtestLogin();

    //enable logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->click("CIVICRM_QFID_1_logging");
    $this->click("_qf_Miscellaneous_next-top");

    // Increase timeout by quadruple since enabling logging takes a long time
    $this->waitForPageToLoad($this->getTimeoutMsec() * 4);
    $this->waitForTextPresent("Changes Saved");

    // Create new Custom Field Set
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $this->click("css=#newCustomDataGroup > span");
    $this->waitForElementPresent('_qf_Group_next-bottom');
    $customFieldSet = 'Fieldset' . rand();
    $this->type("id=title", $customFieldSet);
    $this->select("id=extends_0", "label=Individual");
    $this->click("id=collapse_display");
    $this->clickLink("id=_qf_Group_next-bottom");
    $this->waitForText('crm-notification-container', "Your custom field set '$customFieldSet' has been added.");
    $this->waitForElementPresent('_qf_Field_done-bottom');

    // Add field to fieldset
    $customField = 'CustomField' . rand();
    $this->type("id=label", $customField);
    $this->select("id=data_type_0", "value=0");
    $this->click("id=_qf_Field_done-bottom");
    $this->waitForText('crm-notification-container', "Custom field '$customField' has been saved.");

    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    //contact details section
    //fill in first name
    $firstName = 'Jimmy' . substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = 'Page' . substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName);

    //fill in email id
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

    //fill in phone
    $this->type("phone_1_phone", "2222-4444");

    $this->click("xpath=//table//tr/td/label[text()=\"$customField\"]");
    $value = "custom" . rand();
    $this->type("xpath=//table//tr/td/label[text()=\"$customField\"]/../following-sibling::td/input", $value);

    //check for matching contact
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    //fill in address 1
    $this->type("address_1_street_address", "902C El Camino Way SW");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");

    $this->click("address_1_country_id");
    $this->select("address_1_country_id", "value=1228");

    if ($this->assertElementContainsText("address_table_1", "Latitude")) {
      $this->type("address_1_geo_code_1", "1234");
      $this->type("address_1_geo_code_2", "5678");
    }

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "{$firstName} {$lastName} has been created.");

    //Update the custom field
    $this->waitForElementPresent("xpath=//ul[@id='actions']/li[2]/a/span");
    $this->clickLink("xpath=//ul[@id='actions']/li[2]/a/span");
    $this->click("xpath=//table//tr/td/label[text()=\"$customField\"]");
    $value1 = "custom_1" . rand();
    $this->type("xpath=//table//tr/td/label[text()=\"$customField\"]/../following-sibling::td/input", $value1);
    $this->click("_qf_Contact_upload_view-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=#tab_log a");

    //check the changed log
    $this->waitForElementPresent("xpath=//div[@id='changeLog']/div[2]/form/div[2]/table/tbody/tr[1]/td[4]/a[contains(text(), '$firstName $lastName')]");
    $this->waitForElementPresent("xpath=//div[@id='changeLog']/div[2]/form/div[2]/table/tbody/tr[1]/td/a[2]");
    $this->click("xpath=//div[@id='changeLog']/div[2]/form/div[2]/table/tbody/tr[1]/td/a[2]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='LoggingDetail']/div[2]/table/tbody/tr/td[2][contains(text(), '$value')]"));
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='LoggingDetail']/div[2]/table/tbody/tr/td[3][contains(text(), '$value1')]"));

    //disable logging
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->click("CIVICRM_QFID_0_logging");
    $this->click("_qf_Miscellaneous_next-top");

    // Increase timeout by triple since disabling logging takes a long time
    $this->waitForPageToLoad($this->getTimeoutMsec() * 3);
    $this->waitForTextPresent("Changes Saved");
  }

}
