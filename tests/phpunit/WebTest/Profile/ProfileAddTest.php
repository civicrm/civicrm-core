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
class WebTest_Profile_ProfileAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddNewProfile() {
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

    // Go directly to the URL of the screen that you will be
    // testing (Add new profile ).
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
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->assertElementContainsText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    //Add field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");
    $this->click('is_required');
    $this->type('help_post', 'This is help for profile field');

    //click on save
    $this->click('_qf_Field_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // delete the profile
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->_testdeleteProfile($profileTitle);
  }

  function _testdeleteProfile($profileTitle) {
    //$this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("//div[@id='user-profiles']/div/div/table/tbody//tr/td[1]/span[text() = '$profileTitle']/../following-sibling::td[4]/span[2][text()='more']/ul/li[4]/a[text()='Delete']");
    $this->click("//div[@id='user-profiles']/div/div/table/tbody//tr/td[1]/span[text() = '$profileTitle']/../following-sibling::td[4]/span[2][text()='more']/ul/li[4]/a[text()='Delete']");

    $this->waitForElementPresent('_qf_Group_next-bottom');
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('newCiviCRMProfile-bottom');
    $this->assertElementContainsText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been deleted.");
  }
}