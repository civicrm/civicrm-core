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
 * Class WebTest_Admin_CustomAddTest
 */
class WebTest_Admin_CustomAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCustomAdd() {
    $this->webtestLogin();

    $this->openCiviPage("admin/custom/group", "action=add&reset=1");

    //fill custom group title
    $customGroupTitle = 'custom_group' . substr(sha1(rand()), 0, 3);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "label=Contacts");
    $this->click("//option[@value='Contact']");
    $this->click("//form[@id='Group']/div[2]/div[3]/span[1]/input");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('newCustomField');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '$customGroupTitle' has been added. You can add custom fields now.");

    //add custom field - alphanumeric text
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $textFieldLabel = 'test_text_field' . substr(sha1(rand()), 0, 3);
    $this->click("header");
    $this->type("label", $textFieldLabel);
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForElementPresent('_qf_Field_cancel-bottom');
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=0");
    $this->click("//option[@value='0']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "label=CheckBox");
    $this->click("//option[@value='CheckBox']");

    $checkboxFieldLabel = 'test_checkbox' . substr(sha1(rand()), 0, 5);
    $this->type("label", $checkboxFieldLabel);
    $checkboxOptionLabel1 = 'check1' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_1", $checkboxOptionLabel1);
    $this->type("option_value_1", "1");
    $checkboxOptionLabel2 = 'check2' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_2", $checkboxOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=add another choice");
    $checkboxOptionLabel3 = 'check3' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_3", $checkboxOptionLabel3);
    $this->type("option_value_3", "3");
    $this->click("link=add another choice");
    $checkboxOptionLabel4 = 'check4' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_4", $checkboxOptionLabel4);
    $this->type("option_value_4", "4");

    //enter options per line
    $this->type("options_per_line", "2");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //create another custom field - Number Radio
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=2");
    $this->click("//option[@value='2']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'test_radio' . substr(sha1(rand()), 0, 5);
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'radio1' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'radio2' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_2", $radioOptionLabel2);
    $this->type("option_value_2", "2");

    //select options per line
    $this->type("options_per_line", "3");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_done-bottom");
    $this->waitForElementPresent('newCustomField');

    //On New Individual contact form
    $this->openCiviPage("contact/add", "ct=Individual&reset=1");
    $this->assertElementContainsText('page-title', "New Individual");

    //expand all tabs
    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //verify custom group fields are present on new Individual Contact Form
    $this->assertElementContainsText('customData', $textFieldLabel);
    $this->assertElementContainsText('customData', $checkboxFieldLabel);
    $this->assertElementContainsText('customData', $checkboxOptionLabel1);
    $this->assertElementContainsText('customData', $checkboxOptionLabel2);
    $this->assertElementContainsText('customData', $checkboxOptionLabel3);
    $this->assertElementContainsText('customData', $checkboxOptionLabel4);
    $this->assertElementContainsText('customData', $radioFieldLabel);
    $this->assertElementContainsText('customData', $radioOptionLabel1);
    $this->assertElementContainsText('customData', $radioOptionLabel2);

    //On New Household contact form
    $this->openCiviPage("contact/add", "ct=Household&reset=1");
    $this->assertElementContainsText('page-title', "New Household");

    //expand all tabs
    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //verify custom group fields are present on new household Contact Form
    $this->assertElementContainsText('customData', $textFieldLabel);
    $this->assertElementContainsText('customData', $checkboxFieldLabel);
    $this->assertElementContainsText('customData', $checkboxOptionLabel1);
    $this->assertElementContainsText('customData', $checkboxOptionLabel2);
    $this->assertElementContainsText('customData', $checkboxOptionLabel3);
    $this->assertElementContainsText('customData', $checkboxOptionLabel4);
    $this->assertElementContainsText('customData', $radioFieldLabel);
    $this->assertElementContainsText('customData', $radioOptionLabel1);
    $this->assertElementContainsText('customData', $radioOptionLabel2);

    //On New Organization contact form
    $this->openCiviPage("contact/add", "ct=Organization&reset=1");
    $this->assertElementContainsText('page-title', "New Organization");

    //expand all tabs
    $this->click("expand");
    $this->waitForElementPresent("address_1_street_address");

    //verify custom group fields are present on new Organization Contact Form
    $this->assertElementContainsText('customData', $textFieldLabel);
    $this->assertElementContainsText('customData', $checkboxFieldLabel);
    $this->assertElementContainsText('customData', $checkboxOptionLabel1);
    $this->assertElementContainsText('customData', $checkboxOptionLabel2);
    $this->assertElementContainsText('customData', $checkboxOptionLabel3);
    $this->assertElementContainsText('customData', $checkboxOptionLabel4);
    $this->assertElementContainsText('customData', $radioFieldLabel);
    $this->assertElementContainsText('customData', $radioOptionLabel1);
    $this->assertElementContainsText('customData', $radioOptionLabel2);
  }

}
