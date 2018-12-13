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
 * Class WebTest_Profile_SearchTest
 */
class WebTest_Profile_SearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testSearchProfile() {
    $this->webtestLogin();

    // enable county field
    $this->openCiviPage('admin/setting/preferences/address', 'reset=1');
    $this->check('address_options[7]');
    $this->clickLink('_qf_Address_next-bottom');

    // Add new profile.
    $this->openCiviPage('admin/uf/group', 'reset=1');

    $this->click('newCiviCRMProfile-bottom');

    $this->waitForElementPresent('_qf_Group_next-bottom');

    //Name of profile
    $profileTitle = 'profile_' . substr(sha1(rand()), 0, 7);
    $this->type('title', $profileTitle);

    $this->click('uf_group_type_Profile');
    //click on save
    $this->click('_qf_Group_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Field_next-bottom");

    //check for  profile create
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile '{$profileTitle}' has been added. You can add fields to this profile now.");

    // Get profile id (gid) from URL
    $profileId = $this->urlArg('gid');

    // Add Last Name field.
    $this->waitForElementPresent("field_name[0]");
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
    //check for field add
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Last Name' has been saved to '$profileTitle'.");

    // Add Email field.
    $this->waitForElementPresent("field_name[0]");
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->waitForElementPresent('field_name[1]');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=email');
    $this->click("//option[@value='Contact']");
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save
    $this->click('_qf_Field_next_new-bottom');
    //check for field add
    $this->waitForText('crm-notification-container', "Your CiviCRM Profile Field 'Email' has been saved to '$profileTitle'.");

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
    $this->clickLink('_qf_Field_next_new-bottom', 'field_name[0]', FALSE);
    $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");

    // Add state, country and county field
    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=country');
    $this->select('field_name[2]', 'Primary');
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save and new
    $this->clickLink('_qf_Field_next_new-bottom', 'field_name[0]', FALSE);
    $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");

    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=state_province');
    $this->select('field_name[2]', 'Primary');
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');
    // click on save and new
    $this->clickLink('_qf_Field_next_new-bottom', 'field_name[0]', FALSE);
    $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");

    $this->click('field_name[0]');
    $this->select('field_name[0]', 'value=Contact');
    $this->click('field_name[1]');
    $this->select('field_name[1]', 'value=county');
    $this->select('field_name[2]', 'Primary');
    $this->click('visibility');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');

    // click on save and new
    $this->clickLink('_qf_Field_next_new-bottom', 'field_name[0]', FALSE);
    $this->waitForElementPresent("xpath=//select[@id='field_name_1'][@style='display: none;']");

    $this->select('field_name[0]', 'value=Individual');
    $this->select('field_name[1]', 'value=current_employer');
    $this->select('visibility', 'value=Public Pages and Listings');
    $this->click('is_searchable');
    $this->click('in_selector');

    // click on save
    $this->clickLink('_qf_Field_next-bottom', "xpath=//div[@id='field_page']/div[1]/a[4]/span", FALSE);

    $uselink = explode('?', $this->getAttribute("xpath=//*[@id='field_page']/div[1]/a[4]@href"));
    $this->openCiviPage('profile/create', "$uselink[1]", '_qf_Edit_next');
    $lastName = substr(sha1(rand()), 0, 7);
    $orgName = 'Organisation' . substr(sha1(rand()), 0, 7);

    // Fill Last Name
    $this->type('last_name', $lastName);
    // Fill Email
    $this->type('email-Primary', "jhon@$lastName.com");
    // Select Custom option
    $this->click('CIVICRM_QFID_Edu_2');

    // fill country, state, county
    $this->select('country-Primary', "UNITED STATES");

    // wait for state data to be populated
    $this->waitForElementPresent("xpath=//select[@id='state_province-Primary']/option[text()='California']");
    $this->select('state_province-Primary', "California");

    // wait for county data to be populated
    $this->waitForElementPresent("xpath=//select[@id='county-Primary']/option[text()='Alameda']");
    $this->select('county-Primary', "Alameda");

    $this->type('current_employer', $orgName);

    $this->clickLink('_qf_Edit_next', NULL);

    $this->assertElementContainsText("css=span.msg-text", 'Your information has been saved.');

    // Search Contact via profile.
    $this->waitForElementPresent("xpath=//div[@id='crm-container']//div/a[text()='» Back to Listings']");
    $this->click("xpath=//div[@id='crm-container']//div/a[text()='» Back to Listings']");
    $this->waitForElementPresent("xpath=//div[@class='crm-block crm-form-block']");
    $this->click("xpath=//div[@class='crm-block crm-form-block']");
    // Fill Last Name
    $this->type('last_name', $lastName);
    // Fill Email
    $this->type('email-Primary', "jhon@$lastName.com");

    // Fill state, county, country
    $this->select('country-Primary', "UNITED STATES");

    // wait for state data to be populated
    $this->waitForElementPresent("xpath=//select[@id='state_province-Primary']/option[text()='California']");
    $this->select('state_province-Primary', "California");

    // wait for county data to be populated
    $this->waitForElementPresent("xpath=//select[@id='county-Primary']/option[text()='Alameda']");
    $this->select('county-Primary', "Alameda");

    // Select Custom option
    $this->select('custom_1', 'Education');
    $this->clickLink('_qf_Search_refresh', NULL);

    // Verify Data.
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[2][text()='$lastName']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[3][text()='$lastName']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[4][text()='jhon@$lastName.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[5][text()='Education']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[6][text()='UNITED STATES']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[7][text()='CA']"));
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody/tr[2]/td[8][text()='Alameda']"));

    // verify if the organization has been created -- CRM-15368
    $this->click("css=input#sort_name_navigation");
    $this->type("css=input#sort_name_navigation", "$orgName");
    $this->typeKeys("css=input#sort_name_navigation", "$orgName");
    $this->waitForElementPresent("css=ul.ui-autocomplete li");

    // visit contact summary page
    $this->click("css=ul.ui-autocomplete li");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go back to Profile fields admin
    $this->openCiviPage('admin/uf/group/field', "reset=1&action=browse&gid=$profileId", "xpath=//table/tbody/tr[1]/td[9]");

    // Edit first profile field
    $this->clickLink("xpath=//table/tbody/tr[1]/td[9]/span[1]/a[1]", '_qf_Field_next-bottom', FALSE);

    $this->waitForElementPresent("visibility");
    $this->click("xpath=//tr[@id='profile_visibility']/td[1]/a");
    $this->waitForElementPresent("xpath=//div[@id='crm-notification-container']/div/div[2]/p[2]");
    $this->waitForText('crm-notification-container', 'Is this field hidden from public search');
    $this->select('visibility', 'value=Public Pages and Listings');
  }

}
