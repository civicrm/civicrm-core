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
class WebTest_Profile_MultiRecordProfileAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }
  function testAdminAddNewProfile() {
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
    list($id, $profileTitle) = $this->_addNewProfile();
    $this->_deleteProfile($id, $profileTitle);
  }

  function testUserAddNewProfile() {
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
    list($id, $profileTitle) = $this->_addNewProfile(TRUE, FALSE, TRUE);
    $this->_deleteProfile($id, $profileTitle);
  }

  function testAddNewNonMultiProfile() {
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
    list($id, $profileTitle) = $this->_addNewProfile(FALSE);
    $this->_deleteProfile($id, $profileTitle);
  }

  function testNonSearchableMultiProfile() {
    $this->open($this->sboxPath);

    $this->webtestLogin();
    list($id, $profileTitle) = $this->_addNewProfile(TRUE, TRUE);
    $this->_deleteProfile($id, $profileTitle);
  }

  function _addNewProfile($checkMultiRecord = TRUE, $checkSearchable = FALSE, $userCheck = FALSE) {
    $params = $this->_testCustomAdd($checkSearchable);
    // Go directly to the URL of the screen that you will be
    // testing (Add new profile ).
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    //profile Used for
    $this->click('uf_group_type_User Account');

    //Profile Advance Settings
    $this->click("//form[@id='Group']/div[2]/div[2]/div[1]");

    //If you want member(s) of your organization to receive a
    //notification email whenever this Profile
    //form is used to enter or update contact information, enter one or more email addresses here.
    $this->type('notify', 'This is notify email');

    //Drupal user account registration option
    $this->click('CIVICRM_QFID_0_8');

    //What to do upon duplicate match
    $this->click('CIVICRM_QFID_1_4');

    //Proximity search options
    $this->click('CIVICRM_QFID_0_14');

    // enable maping for contact
    $this->click('is_map');

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->assertElementContainsText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    //Add field to profile
    $this->waitForElementPresent('field_name[0]');
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'label='.$params['textFieldLabel'].' :: '.$params['customGroupTitle']);
    if ($checkMultiRecord) {
      $this->click('is_multi_summary');
    }
    if (!$checkSearchable) {
      $this->click('is_searchable');
      $this->select('visibility', 'value=Public Pages and Listings');
    }
    else {
      $this->select('visibility', 'value=User and User Admin Only');
    }
    $this->click('in_selector');
    $this->click('is_required');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'label='.$params['selectFieldLabel'].' :: '.$params['customGroupTitle']);
    if ($checkMultiRecord) {
      $this->click('is_multi_summary');
    }
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');

    // Add Contact
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Student');
    $this->click("//option[@value='Student']");

    $this->click('field_name_1');
    $this->select('field_name_1', 'value=first_name');
    $this->click('is_multi_summary');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Student');
    $this->click("//option[@value='Student']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'value=last_name');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next_new-top');

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('field_name_1');
    $this->select('field_name_1', 'value=email');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->type('help_post', 'This is help for profile field');
    $this->click('_qf_Field_next');
    //click on save
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('link=Use (create mode)');
    $recordNew = $this->_addRecords('Create');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $elements = $this->parseURL( );

    $gid = $elements['queryString']['gid'];
    $id = $elements['queryString']['id'];

    if ($userCheck) {
      //add drupal user
      $this->openCiviPage('contact/view/useradd', "reset=1&action=add&cid=$id", 'cms_name');
      $this->type('cms_name', $recordNew['firstname']);
      $this->type('cms_pass', $recordNew['firstname']);
      $this->type('cms_confirm_pass', $recordNew['firstname']);
      $this->click('_qf_Useradd_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->open($this->sboxPath . "user/logout");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->open("{$this->sboxPath}user");
      // Make sure login form is available
      $this->waitForElementPresent('edit-submit');
      $this->type('edit-name', $recordNew['firstname']);
      $this->type('edit-pass', $recordNew['firstname']);
      $this->click('edit-submit');
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    $this->openCiviPage('profile/edit', "reset=1&id=$id&gid=$gid");
    if (!$checkMultiRecord) {
      $this->assertElementContainsText('crm-container', 'No multi-record entries found');
      return array($gid, $profileTitle);
    }
    $this->waitForElementPresent("//div[@id='crm-profile-block']/a");
    $this->click("//div[@id='crm-profile-block']/a");
    $record1 = $this->_addRecords();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->verifyText("//div[@id='browseValues']/div/div/table/thead/tr/th[1]", preg_quote($params['textFieldLabel']));
    $this->verifyText("//div[@id='browseValues']/div/div/table/tbody/tr[2]/td[1]", preg_quote($record1['text']));
    $this->openCiviPage('profile/edit', "reset=1&id=$id&gid=$gid", "//div[@id='crm-profile-block']/a");
    $this->click("//div[@id='crm-profile-block']/a");
    $record2 = $this->_addRecords();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->verifyText("//div[@id='browseValues']/div/div/table/tbody/tr[3]/td[1]", preg_quote($record2['text']));

    // Check Max Record Limit
    $this->verifyElementNotPresent("//div[@id='crm-profile-block']/a/span");

    //Check for edit functionality
    sleep(3);
    $this->click("//div[@id='browseValues']/div/div/table/tbody/tr/td[3]/span/a[text()='Edit']");
    $this->waitForElementPresent("//html/body/div[5]");
    $this->verifyText("//div[@id='browseValues']/div/div/table/thead/tr/th[1]", preg_quote($params['textFieldLabel']));
    $this->type("//div[@id='profile-dialog']/div/form/div[2]/div/div/div[2]/input", $recordNew['text'].'edit');
    $this->click("//div[@id='profile-dialog']/div/form/div[2]/div[2]/span/input[@id='_qf_Edit_next']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->verifyText("//div[@id='browseValues']/div/div/table/tbody/tr[1]/td[1]", preg_quote($recordNew['text'].'edit'));

    // Check the delete functionality
    $this->click("//div[@id='browseValues']/div/div/table/tbody/tr/td[3]/span/a[text()='Delete']");
    $this->waitForElementPresent("//html/body/div[5]");
    sleep(3);
    $this->assertElementContainsText('profile-dialog', 'Are you sure you want to delete this record?');
    $this->click('_qf_Edit_upload_delete');

    // Check the view functionality
    sleep(3);
    $this->click("//div[@id='browseValues']/div/div/table/tbody/tr/td[3]/span/a[text()='View']");
    $this->waitForElementPresent("//html/body/div[5]");
    $this->assertElementContainsText('ui-id-1', 'View '.$params['customGroupTitle']);
    $this->assertElementContainsText('crm-container', $params['textFieldLabel']);
    if ($checkSearchable) {
      $this->verifyElementNotPresent("//div[@id='profile-dialog']/div/div/div/div[1]/div[2]/a");
      return array($gid, $profileTitle);
    }

    // Check Search Functionality
    if (!$userCheck) {
      $this->click("//div[@id='profile-dialog']/div/div/div/div/div[2]/a");
      $this->waitForElementPresent("//form[@id='Search']");
      $this->verifyText("//form[@id='Search']/div[3]/div[2]/table/tbody/tr[2]/td[2]", preg_quote($recordNew['firstname']));
      $this->openCiviPage('profile/view', "reset=1&id=$id&gid=$gid", "//div[@id='row-first_name']/div[2]/a");
      $this->click("//div[@id='row-first_name']/div[2]/a");
      $this->waitForElementPresent("//form[@id='Search']");
      // Check that Email column is not present in selector results
      $this->verifyElementNotPresent("//form[@id='Search']/div[3]/div[2]/table/tbody/tr/th[7]/a[@label='Email (Primary)']");
      $this->verifyText("//form[@id='Search']/div[3]/div[2]/table/tbody/tr/th[3]/a", preg_quote($params['textFieldLabel']));
      $this->verifyText("//form[@id='Search']/div[3]/div[2]/table/tbody/tr[2]/td[3]", preg_quote($record1['text']));
      $this->verifyText("//form[@id='Search']/div[3]/div[2]/table/tbody/tr[3]/td[3]", preg_quote($record2['text']));
    }
    return array($gid, $profileTitle);
  }

  function _deleteProfile($gid, $profileTitle) {
    $this->openCiviPage("admin/uf/group", "action=delete&id={$gid}", '_qf_Group_next-bottom');
    $this->click('_qf_Group_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-container', "Your CiviCRM Profile '{$profileTitle}' has been deleted.");
  }

  function _testCustomAdd($checkSearchable) {
    // Go directly to the URL of the screen that you will be testing (Custom data for contacts).
    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');
    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.

    //fill custom group title
    $params['customGroupTitle'] = 'custom_group' . substr(sha1(rand()), 0, 3);
    $this->click("title");
    $this->type("title", $params['customGroupTitle']);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "label=Contacts");
    $this->click("//option[@value='Contact']");
    $this->waitForElementPresent("//input[@id='is_multiple']");
    $this->click("//input[@id='is_multiple']");
    $this->type("max_multiple", 3);
    $this->click("//form[@id='Group']/div[2]/div[3]/span[1]/input");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom group created?
    $this->assertElementContainsText('crm-container', $params['customGroupTitle']);
    //add custom field - alphanumeric text
    $params['textFieldLabel'] = 'test_text_field' . substr(sha1(rand()), 0, 3);
    $this->click("header");
    $this->type("label", $params['textFieldLabel']);
    //Is searchable?
    if (!$checkSearchable) {
      $this->click("is_searchable");
    }
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=0");
    $this->click("//option[@value='0']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "label=Select");
    $this->click("//option[@value='Select']");

    $params['selectFieldLabel'] = 'test_select' . substr(sha1(rand()), 0, 5);
    $this->type("label", $params['selectFieldLabel']);
    $selectOptionLabel1 = 'option1' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_1", $selectOptionLabel1);
    $this->type("option_value_1", "1");
    $selectOptionLabel2 = 'option2' . substr(sha1(rand()), 0, 3);
    $this->type("option_label_2", $selectOptionLabel2);
    $this->type("option_value_2", "2");
    $this->click("link=another choice");

    //enter pre help message
    $this->type("help_pre", "this is field pre help");

    //enter post help message
    $this->type("help_post", "this field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created?
    $this->assertElementContainsText('crm-container', $params['selectFieldLabel']);
    return $params;
  }

  function _addRecords($context = 'Edit') {
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $params['text'] = 'text' . substr(sha1(rand()), 0, 3);
    $this->type("//div[@id='crm-profile-block']/div/div/div[2]/input[@type='text']", $params['text']);
    if ($context == 'Create') {
      $params['firstname'] = 'John' . substr(sha1(rand()), 0, 3);
      $this->type('first_name', $params['firstname']);
      $params['lastname'] = 'Anderson' . substr(sha1(rand()), 0, 3);
      $this->type('last_name', $params['lastname']);
      $params['email'] =  $params['firstname'].$params['lastname'].'@exa.com';
      $this->type('email-Primary', $params['email']);
    }
    $this->waitForElementPresent("//div[@id='crm-profile-block']/div//div/div[2]/select");
    $this->select("//div[@id='crm-profile-block']/div//div/div[2]/select",'value=1');
    $this->click('_qf_Edit_next');
    return $params;
  }
}