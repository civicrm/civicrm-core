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
 * Class WebTest_Profile_ProfileAddTest
 */
class WebTest_Profile_ProfileAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddNewProfile() {
    $this->webtestLogin();

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    //profile Used for
    $this->click('uf_group_type_User Registration');
    $this->click('uf_group_type_User Account');

    //Profile Advance Settings
    $this->click("//form[@id='Group']/div[2]/div[2]/div[1]");

    //Select a group if you are using this profile for search and listings.
    $this->select('group', 'value=1');

    //Select a group if you are using this profile for adding new contacts.
    $this->select('add_contact_to_group', 'value=1');

    //If you want member(s) of your organization to receive a
    //notification email whenever this Profile
    //form is used to enter or update contact information, enter one or more email addresses here.
    $this->type('notify', 'This is notify email');

    //If you are using this profile as a contact signup or edit
    //form, and want to redirec the user to a static URL after
    //they've submitted the form - enter the complete URL here.
    $this->type('post_URL', 'This is Post Url');

    // If you are using this profile as a contact signup or edit
    // form, and want to redirect the user to a
    //static URL if they click the Cancel button - enter the complete URL here.
    $this->type('cancel_URL', 'This is cancle Url');

    //reCaptcha settings
    $this->click('add_captcha');

    //Drupal user account registration option
    $this->click('CIVICRM_QFID_0_8');

    //What to do upon duplicate match
    $this->click('CIVICRM_QFID_0_2');

    //Proximity search options
    $this->click('CIVICRM_QFID_0_14');

    // enable maping for contact
    $this->click('is_map');

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->clickLink('_qf_Group_next');

    $gid = $this->urlArg('gid');

    //Add field to profile
    $this->waitForElementPresent("field_name[0]");
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('is_required');
    $this->type('help_post', 'This is help for profile field');

    //click on save
    $this->click('_qf_Field_next');
    sleep(1);

    // delete the profile
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->_testdeleteProfile($profileTitle);
  }

  public function testProfileAddContactstoGroup() {
    $this->webtestLogin();

    $permissions = array("edit-1-profile-listings-and-forms");
    $this->changePermissions($permissions);
    // take group name and create group
    $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($groupName);

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    $this->click('uf_group_type_Profile');
    //Profile Advance Settings
    $this->click("//form[@id='Group']/div[2]/div[2]/div[1]");

    //Select the newly created group for adding new contacts into it.
    $this->select('add_contact_to_group', "label=$groupName");

    //click on save
    $this->clickLink('_qf_Group_next');

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    //Add fields to profile
    $fields = array(
      'first_name' => 'Individual',
      'last_name' => 'Individual',
      'email' => 'Contact',
    );
    $this->waitForElementPresent("field_name_0");
    foreach ($fields as $field => $type) {
      $this->click('field_name_0');
      $this->select('field_name_0', "value=$type");
      $this->click("//option[@value='$type']");
      $this->click('field_name_1');
      $this->select('field_name_1', "value=$field");
      $this->clickLink('_qf_Field_next_new-top', 'field_name_0', FALSE);
      $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");
    }

    // create mode
    $gid = $this->urlArg('gid');
    $this->openCiviPage('profile/create', "gid=$gid&reset=1", NULL);
    $firstName1 = "John_" . substr(sha1(rand()), 0, 7);
    $lastName1 = "Smiths_x" . substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName1);
    $this->type('last_name', $lastName1);
    $this->type('email-Primary', "$firstName1.$lastName1@example.com");
    $this->clickLink('_qf_Edit_next', NULL);

    //anonymous contact
    $this->webtestLogout();
    $this->openCiviPage('profile/create', "gid=$gid&reset=1", NULL);
    $firstName2 = "John12_" . substr(sha1(rand()), 0, 7);
    $lastName2 = "Smiths34_x" . substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName2);
    $this->type('last_name', $lastName2);
    $this->type('email-Primary', "$firstName2.$lastName2@example.com");
    $this->clickLink('_qf_Edit_next', NULL);

    $this->webtestLogin();
    //check the existence of the two contacts in the group
    $this->openCiviPage('group', 'reset=1');
    $this->type('title', $groupName);
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//table[@class='crm-group-selector no-footer dataTable']/tbody/tr/td/span[text() = '$groupName']/parent::td/following-sibling::td[@class=' crm-group-group_links']/span/a");
    $this->clickLink("xpath=//table[@class='crm-group-selector no-footer dataTable']/tbody/tr/td[1]/span[text() = '$groupName']/parent::td/following-sibling::td[@class=' crm-group-group_links']/span/a");
    $contactEmails = array(
      1 => "$lastName1, $firstName1",
      2 => "$lastName2, $firstName2",
    );
    foreach ($contactEmails as $row => $name) {
      $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-search-results']/table/tbody/tr[$row]/td[4]/a[contains(text(), '$name')]"));
    }

    //add the api keys in the recaptcha settings
    $this->openCiviPage('admin/setting/misc', 'reset=1');
    $this->type('recaptchaPublicKey', '6Lcexd8SAAAAAOwcoLCRALkyRrmPX7jY7b4V5iju');
    $this->type('recaptchaPrivateKey', '6Lcexd8SAAAAANZXtyU5SVrnl9-_ckwFxUAZgxQp');
    $this->clickLink('_qf_Miscellaneous_next-bottom');

    //enable recaptcha in the profile
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->clickLink("xpath=//div[@id='user-profiles']/div/div/table/tbody//tr/td[1]/div[text()= '$profileTitle']/../following-sibling::td[6]/span/a[2]");
    $this->click("//form[@id='Group']/div[2]/div[2]/div[1]");
    //reCaptcha settings
    $this->click('add_captcha');
    $this->clickLink('_qf_Group_next-bottom');

    //check if recaptcha loads for anonymous profile
    $this->webtestLogout();
    $this->openCiviPage('profile/create', "gid=$gid&reset=1", NULL);
    $this->waitForElementPresent('recaptcha_widget_div');
    $this->assertTrue($this->isElementPresent('recaptcha_area'));

    // delete the profile
    $this->webtestLogin();
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->_testdeleteProfile($profileTitle);
  }

  /**
   * @param $profileTitle
   */
  public function _testdeleteProfile($profileTitle) {
    //$this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//div[@id='user-profiles']/div/div/table/tbody//tr/td/div[text() = '$profileTitle']/../../td[7]/span[2][text()='more']/ul//li/a[text()='Delete']");
    $this->click("xpath=//div[@id='user-profiles']/div/div/table/tbody//tr/td/div[text() = '$profileTitle']/../../td[7]/span[2][text()='more']/ul//li/a[text()='Delete']");

    $this->waitForElementPresent('_qf_Group_next-bottom');
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('newCiviCRMProfile-bottom');
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been deleted.");
  }

  /**
   * CRM-12439
   * Test to check profile description field
   * which has a rich text editor (CKEditor)
   */
  public function testCheckDescAndCreatedIdFields() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // open Add Profile page
    $this->openCiviPage("admin/uf/group/add", "action=add&reset=1");

    $this->waitForElementPresent('_qf_Group_next-bottom');
    $profileTitle = 'Test Profile' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    // check if description field is present
    $this->waitForElementPresent('description');

    $profileDescription = "Test Profile description" . substr(sha1(rand()), 0, 7);
    $this->type('description', $profileDescription);
    $this->check('uf_group_type_Search Profile');

    // click save button
    $this->clickLink('_qf_Group_next-bottom');

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', 'Profile Added');

    $this->waitForElementPresent("field_name_0");

    // select field(s) to be added in profile
    $this->select("field_name_0", "value=Contact");
    $this->select("field_name_1", "value=email");
    $this->select("field_name_2", "value=2");

    // click on Save buttonProfile Field Saved
    $this->clickLink("xpath=//button/span[text()='Save']", "xpath=//a/span/i[@class='crm-i fa-plus-circle']", FALSE);

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', "Profile Field Saved");

    $this->waitForElementPresent("xpath=//div[@id='field_page']/table/tbody/tr[1]/td[9]/span/a[text()='Edit']");
    // extract profile Id
    $id = explode("gid=", $this->getAttribute("xpath=//div[@id='field_page']/table/tbody/tr/td[9]/span/a[text()='Edit']/@href"));
    $id = $id[1];

    // click on Edit Settings
    $this->clickLink("xpath=//a/span/i[@class='crm-i fa-wrench']", '_qf_Group_next-bottom', FALSE);

    // check for description field
    $this->waitForElementPresent('description');
    // check value of description field is retrieved correctly
    $this->assertEquals($this->getValue('description'), $profileDescription);

    // click on save button
    $this->clickLink('_qf_Group_next-bottom', "xpath=//a/span/i[@class='crm-i fa-wrench']", FALSE);

    // Wait for "saved" status msg
    $this->waitForText('crm-notification-container', 'Profile Saved');

    $this->openCiviPage("admin/uf/group", "reset=1");
    $this->waitForElementPresent("xpath=//div[@class='crm-submit-buttons']/a[@id='newCiviCRMProfile-bottom']");
    $this->waitForElementPresent("xpath=//div[@id='user-profiles']/div/div/table/tbody/tr[@id='UFGroup-$id']/td[2]/a");
    $this->waitForElementPresent("xpath=//div[@id='user-profiles']/div/div/table/tbody/tr[@id='UFGroup-$id']/td[3]");

    // check description is displayed on profile listing page
    $this->assertEquals(
      $this->getText("xpath=//div[@id='user-profiles']/div/div/table/tbody/tr[@id='UFGroup-$id']/td[3]"),
      $profileDescription);

    // fetch created by
    $createdBy = $this->getText("xpath=//div[@id='user-profiles']/div/div/table/tbody/tr[@id='UFGroup-$id']/td[2]/a");

    // click on created by
    $this->click("xpath=id('UFGroup-$id')/td[2]/a");

    // Is contact present?
    $this->assertTrue($this->isTextPresent("$createdBy"), "Contact did not find!");

    $this->openCiviPage('admin/uf/group', 'reset=1');
    // delete created profile
    $this->_testdeleteProfile($profileTitle);
  }

}
