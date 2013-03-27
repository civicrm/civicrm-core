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
class WebTest_Profile_SearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testSearchProfile() {
    $this->webtestLogin();

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-bottom');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    //click on save
    $this->click('_qf_Group_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    // Get profile id (gid) from URL
    $profileId = $this->urlArg('gid');

    // Add Last Name field.
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Individual');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=last_name');
    $this->click("//option[@value='Individual']");
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    //check for field add
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Last Name' has been saved to '$profileTitle'.");
    $this->waitForText('crm-notification-container', 'You can add another profile field.');

    // Add Email field.
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=email');
    $this->click("//option[@value='Contact']");
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save
    $this->click('_qf_Field_next_new-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    //check for field add
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Email' has been saved to '$profileTitle'.");
    $this->waitForText('crm-notification-container', 'You can add another profile field.');

    // Add Sample Custom Field.
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Individual');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=custom_1');
    $this->click("//option[@value='Individual']");
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save
    $this->clickLink('_qf_Field_next-bottom', "xpath=//div[@id='field_page']/div[1]/a[4]/span[text()='Use (create mode)']");
    $this->click("xpath=//div[@id='field_page']/div[1]/a[4]/span[text()='Use (create mode)']");

    $this->waitForElementPresent('_qf_Edit_next');
    $lastName = substr(sha1(rand()), 0, 7);

    // Fill Last Name
    $this->type('last_name', $lastName);
    // Fill Email
    $this->type('email-Primary', "jhon@$lastName.com");
    // Select Custom option
    $this->click('CIVICRM_QFID_Edu_2');
    $this->click('_qf_Edit_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('css=span.msg-text', 'Your information has been saved.');

    // Search Contact via profile.
    $this->waitForElementPresent("xpath=//div[@id='crm-container']//div/a[text()='» Back to Listings']");
    $this->click("xpath=//div[@id='crm-container']//div/a[text()='» Back to Listings']");
    $this->waitForElementPresent("xpath=//div[@class='crm-block crm-form-block']");
    $this->click("xpath=//div[@class='crm-block crm-form-block']");
    // Fill Last Name
    $this->type('last_name', $lastName);
    // Fill Email
    $this->type('email-Primary', "jhon@$lastName.com");
    // Select Custom option
    $this->click('CIVICRM_QFID_Edu_2');
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Verify Data.
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[2][text()='$lastName']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[3][text()='$lastName']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[4][text()='jhon@$lastName.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[5][text()='Education']"));

    // Go back to Profile fields admin
    $this->openCiviPage('admin/uf/group/field', "reset=1&action=browse&gid=$profileId");

    // Edit first profile field
    $this->waitForElementPresent("xpath=//table/tbody/tr[1]/td[9]");
    $this->clickLink("xpath=//table/tbody/tr[1]/td[9]/span[1]/a[1]", '_qf_Field_next-bottom');

    // sleep 5 to make sure jQuery is not hiding field after page load
    // Because it tends to cause problems, all uses of sleep() must be justified in comments
    // Sleep should never be used for wait for anything to load from the server
    // Justification for this instance: FIXME
    sleep(5);
    $this->assertTrue($this->isElementPresent("visibility"), 'Visibility field not present when editing existing profile field.');
    $this->click("xpath=//tr[@id='profile_visibility']/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@id='crm-notification-container']/div/div[2]/p[2]");
    $this->waitForText('crm-notification-container', 'Is this field hidden from other users');
    $this->select('visibility', 'value=Public Pages and Listings');
  }
}
