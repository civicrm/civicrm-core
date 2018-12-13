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
 * Class WebTest_Admin_CustomAddTestSameField
 */
class WebTest_Admin_CustomAddTestSameField extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCustomSameFieldAdd() {
    $this->open($this->sboxPath);
    $this->webtestLogin();

    $this->_testCustomAdd();
    $this->_testCustomAdd();
  }

  public function _testCustomAdd() {
    //CRM-7564 : Different gropus can contain same custom fields
    $this->open($this->sboxPath . "civicrm/admin/custom/group?action=add&reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $customGroupTitle = 'custom_group' . substr(sha1(rand()), 0, 3);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "label=Contacts");
    $this->click("//option[@value='Contact']");
    $this->clickLink("//form[@id='Group']/div[2]/div[3]/span[1]/input");

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '$customGroupTitle' has been added. You can add custom fields now.");

    $gid = $this->urlArg('gid');

    //add custom field - alphanumeric text
    $this->openCiviPage('admin/custom/group/field/add', "reset=1&action=add&gid=$gid");
    $textFieldLabel = 'test_text_field';
    $this->click("header");
    $this->waitForElementPresent('label');
    $this->type("label", $textFieldLabel);
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=0");
    $this->click("//option[@value='0']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "label=CheckBox");
    $this->click("//option[@value='CheckBox']");

    $checkboxFieldLabel = 'test_checkbox';
    $this->waitForElementPresent('label');
    $this->type("label", $checkboxFieldLabel);
    $checkboxOptionLabel1 = 'check1';
    $this->type("option_label_1", $checkboxOptionLabel1);
    $this->type("option_value_1", "1");
    $checkboxOptionLabel2 = 'check2';
    $this->type("option_label_2", $checkboxOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=another choice");
    $checkboxOptionLabel3 = 'check3';
    $this->type("option_label_3", $checkboxOptionLabel3);
    $this->type("option_value_3", "3");
    $this->click("link=another choice");
    $checkboxOptionLabel4 = 'check4';
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
    $this->clickLink("_qf_Field_next_new-bottom");

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$checkboxFieldLabel' has been saved.");

    //add custom field - alphanumeric text
    $textFieldLabel = 'test_text_field';
    $this->click("header");
    $this->waitForElementPresent('label');
    $this->type("label", $textFieldLabel);
    $this->clickLink("_qf_Field_next_new-bottom");

    // Same group will not contain same custome fields so will show error for this field :
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=0");
    $this->click("//option[@value='0']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "label=CheckBox");
    $this->click("//option[@value='CheckBox']");
    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$textFieldLabel' already exists in Database.");

    //create another custom field - Number Radio
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=2");
    $this->click("//option[@value='2']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'test_radio';
    $this->waitForElementPresent('label');
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'radio1';
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'radio2';
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
    $this->clickLink("_qf_Field_done-bottom");

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$radioFieldLabel' has been saved.");

  }

}
