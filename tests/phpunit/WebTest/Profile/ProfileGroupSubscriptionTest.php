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
 * Class WebTest_Profile_ProfileGroupSubscriptionTest
 */
class WebTest_Profile_ProfileGroupSubscriptionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testProfileGroupSubscription() {
    $this->webtestLogin();

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-top');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);
    $this->click('uf_group_type_Profile');

    //Drupal user account registration option
    $this->click('CIVICRM_QFID_0_8');

    //What to do upon duplicate match
    $this->click('CIVICRM_QFID_0_2');

    //Proximity search options
    $this->click('CIVICRM_QFID_0_14');

    // enable mapping for contact
    $this->click('is_map');

    // include a link in the listings to Edit profile fields
    $this->click('is_edit_link');

    //to view contacts' Drupal user account information
    $this->click('is_uf_link');

    //click on save
    $this->click('_qf_Group_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    //Add email field to profile
    $this->waitForElementPresent("field_name[0]");
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");

    $this->select('field_name[1]', 'value=email');
    $this->click("//option[@value='email']");

    //click on save
    $this->click('_qf_Field_next_new-top');
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Email' has been saved to '$profileTitle'.");

    //Add email field to profile
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click("//option[@value='Contact']");

    $this->waitForElementPresent("field_name[1]");
    $this->select('field_name[1]', 'value=group');
    $this->click("//option[@value='group']");

    //click on save
    $this->click('_qf_Field_next');

    $this->waitForElementPresent("xpath=//div[@id='field_page']/div/a[4]/span");

    //now use profile create mode for group subscription
    $this->click("xpath=//div[@id='field_page']/div/a[4]/span");

    $this->waitForElementPresent('email-Primary');

    //check for group field
    $this->assertElementContainsText('crm-profile-block', 'Group(s)', "Groups field was not found.");

    //fill the subscription form
    $randomEmail = substr(sha1(rand()), 0, 7) . "@example.com";

    $this->type("email-Primary", $randomEmail);

    // check advisory group ( may be we should create a separate group to test this)
    $this->click("group_3");

    $this->click('_qf_Edit_next');

    // assert for subscription message

    $this->isTextPresent("Your subscription request has been submitted for");
    //check if profile is saved
    $this->isTextPresent("Your information has been saved.");

    // delete the profile
    $this->openCiviPage('admin/uf/group', 'reset=1');
    $this->_testdeleteProfile($profileTitle);
  }

  /**
   * @param $profileTitle
   */
  public function _testdeleteProfile($profileTitle) {
    $this->waitForElementPresent("xpath=//div[@id='user-profiles']/div/div[1]/table/tbody//tr/td[1]/div[text() = '$profileTitle']/../../td[7]/span[2][text()='more']/ul//li/a[text()='Delete']");
    $this->click("xpath=//div[@id='user-profiles']/div/div[1]/table/tbody//tr/td[1]/div[text() = '$profileTitle']/../../td[7]/span[2][text()='more']/ul//li/a[text()='Delete']");
    $this->waitForElementPresent('_qf_Group_next-bottom');
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('newCiviCRMProfile-bottom');
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been deleted.");
  }

}
