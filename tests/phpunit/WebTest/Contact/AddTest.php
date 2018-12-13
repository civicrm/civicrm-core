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
 * Class WebTest_Contact_AddTest
 */
class WebTest_Contact_AddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testIndividualAdd() {
    $this->webtestLogin();

    $groupName = $this->WebtestAddGroup();

    // go to display preferences to enable Open ID field
    $this->openCiviPage('admin/setting/preferences/display', "reset=1", "_qf_Display_next-bottom");
    $this->waitForAjaxContent();
    if (!$this->isChecked("xpath=//ul[@id='contactEditBlocks']//li[@id='preference-10-contactedit']/span/input")) {
      $this->click("xpath=//ul[@id='contactEditBlocks']//li/span/label[text()='Open ID']");
    }
    $this->click("_qf_Display_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');

    //contact details section
    //select prefix
    $this->click("prefix_id");
    $this->select("prefix_id", "value=" . $this->webtestGetFirstValueForOptionGroup('individual_prefix'));

    //fill in first name
    $this->type("first_name", substr(sha1(rand()), 0, 7) . "John");

    //fill in middle name
    $this->type("middle_name", "Bruce");

    //fill in last name
    $this->type("last_name", substr(sha1(rand()), 0, 7) . "Smith");

    //select suffix
    $this->select("suffix_id", "value=3");

    //fill in nick name
    $this->type("nick_name", "jsmith");

    //fill in email
    $this->type("email_1_email", substr(sha1(rand()), 0, 7) . "john@gmail.com");

    //fill in phone
    $this->type("phone_1_phone", "2222-4444");

    //fill in IM
    $this->type("im_1_name", "testYahoo");

    //fill in openID
    $this->type("openid_1_openid", "http://" . substr(sha1(rand()), 0, 7) . "openid.com");

    //fill in website
    $this->type("website_1_url", "http://www.john.com");

    //fill in source
    $this->type("contact_source", "johnSource");

    //fill in external identifier
    $indExternalId = substr(sha1(rand()), 0, 4);
    $this->type("external_identifier", $indExternalId);

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
    $this->select("address_1_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->assertElementContainsText('address_table_1', "Latitude")) {
      $this->type("address_1_geo_code_1", "1234");
      $this->type("address_1_geo_code_2", "5678");
    }

    //fill in address 2
    $this->click("//div[@id='addMoreAddress1']/a/span");
    $this->waitForElementPresent("address_2_street_address");
    $this->type("address_2_street_address", "2782Y Dowlen Path W");
    $this->type("address_2_city", "Birmingham");
    $this->type("address_2_postal_code", "3456");

    $this->click("address_2_country_id");
    $this->select("address_2_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->assertElementContainsText('address_table_2', "Latitude")) {
      $this->type("address_2_geo_code_1", "1234");
      $this->type("address_2_geo_code_2", "5678");
    }

    //Communication Preferences section
    $this->click("commPrefs");

    //select greeting/addressee options
    $this->waitForElementPresent("email_greeting_id");
    $this->select("email_greeting_id", "value=2");
    $this->select("postal_greeting_id", "value=3");

    //Select preferred method for Privacy
    $this->click("privacy[do_not_trade]");
    $this->click("privacy[do_not_sms]");

    //Select preferred method(s) of communication
    $this->click("preferred_communication_method[1]");
    $this->click("preferred_communication_method[2]");

    //select preferred language
    $this->waitForElementPresent("preferred_language");
    $this->select("preferred_language", "value=en_US");

    //Notes section
    $this->click("notesBlock");
    $this->waitForElementPresent("subject");
    $this->type("subject", "test note");
    $this->type("note", "this is a test note contact webtest");
    $this->assertElementContainsText('notesBlock', "Subject\n Note");

    //Demographics section
    $this->click("//div[@class='crm-accordion-header' and contains(.,'Demographics')]");
    $this->waitForElementPresent("birth_date");

    $this->webtestFillDate('birth_date', "-1 year");

    //Tags and Groups section
    $this->click("tagGroup");

    // select group
    $this->select("group", "label=$groupName");
    $this->click("tag[{$this->webtestGetValidEntityID('Tag')}]");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");
  }

  public function testHouseholdAdd() {
    $this->webtestLogin();

    $groupName = $this->WebtestAddGroup();

    // go to display preferences to enable Open ID field
    $this->openCiviPage('admin/setting/preferences/display', "reset=1", "_qf_Display_next-bottom");
    $this->waitForAjaxContent();
    if (!$this->isChecked("xpath=//ul[@id='contactEditBlocks']//li[@id='preference-10-contactedit']/span/input")) {
      $this->click("xpath=//ul[@id='contactEditBlocks']//li/span/label[text()='Open ID']");
    }
    $this->click("_qf_Display_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('contact/add', "reset=1&ct=Household");

    //contact details section
    //fill in Household name
    $this->click("household_name");
    $name = substr(sha1(rand()), 0, 7) . "Fraddie Grant's home ";
    $this->type("household_name", $name);

    //fill in nick name
    $this->type("nick_name", substr(sha1(rand()), 0, 7) . "Grant's home");

    //fill in email
    $email = substr(sha1(rand()), 0, 7) . "fraddiegrantshome@web.com ";
    $this->type("email_1_email", $email);
    $this->click("Email_1_IsBulkmail");

    //fill in phone
    $this->type("phone_1_phone", "444-4444");
    $this->select("phone_1_phone_type_id", "value=" . $this->webtestGetFirstValueForOptionGroup('phone_type'));

    //fill in IM
    foreach (array('Yahoo', 'MSN', 'AIM', 'GTalk', 'Jabber', 'Skype') as $option) {
      $this->assertSelectHasOption('im_1_provider_id', $option);
    }
    $this->type("im_1_name", "testSkype");
    $this->select("im_1_location_type_id", "value=3");
    $this->select("im_1_provider_id", "value=6");

    //fill in openID
    $this->type("openid_1_openid", "http://" . substr(sha1(rand()), 0, 7) . "shomeopenid.com");

    //fill in website url
    $this->type("website_1_url", "http://www.fraddiegrantshome.com");

    //fill in contact source
    $this->type("contact_source", "Grant's home source");

    //fill in external identifier
    $houExternalId = substr(sha1(rand()), 0, 4);
    $this->type("external_identifier", $houExternalId);

    //check for duplicate contact
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    $this->type("address_1_street_address", "938U Bay Rd E");
    $this->type("address_1_city", "Birmingham");
    $this->type("address_1_postal_code", "35278");

    $this->click("address_1_country_id");
    $this->select("address_1_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->assertElementContainsText('address_table_1', "Latitude")) {
      $this->type("address_1_geo_code_1", "1234");
      $this->type("address_1_geo_code_2", "5678");
    }

    //Communication Preferences section
    $this->click("commPrefs");

    //select greeting/addressee options
    $this->waitForElementPresent("addressee_id");
    $this->select("addressee_id", "value=4");
    $this->type("addressee_custom", "Grant's home");

    //Select preferred method(s) of communication
    $this->click("preferred_communication_method[1]");
    $this->click("preferred_communication_method[2]");
    $this->click("preferred_communication_method[5]");

    //Select preferred method for Privacy
    $this->click("privacy[do_not_sms]");

    //select preferred language
    $this->waitForElementPresent("preferred_language");
    $this->select("preferred_language", "value=fr_FR");

    //Notes section
    $this->clickAt("//*[@id='Contact']/div[2]/div[6]/div[1]");
    $this->waitForElementPresent("subject");
    $this->type("subject", "Grant's note");
    $this->type("note", "This is a household contact webtest note.");

    // select group
    $this->clickAt("xpath=//div[text()='Tags and Groups']");
    $this->select("group", "label=$groupName");

    //tags section
    $this->click("tag[{$this->webtestGetValidEntityID('Tag')}]");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Contact Saved");
  }

  public function testOrganizationAdd() {
    $this->webtestLogin();

    $groupName = $this->WebtestAddGroup();

    // go to display preferences to enable Open ID field
    $this->openCiviPage('admin/setting/preferences/display', "reset=1", "_qf_Display_next-bottom");
    $this->waitForAjaxContent();
    if (!$this->isChecked("xpath=//ul[@id='contactEditBlocks']//li[@id='preference-10-contactedit']/span/input")) {
      $this->click("xpath=//ul[@id='contactEditBlocks']//li/span/label[text()='Open ID']");
    }
    $this->click("_qf_Display_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('contact/add', 'reset=1&ct=Organization');

    //contact details section
    //fill in Organization name
    $this->click("organization_name");
    $this->type("organization_name", substr(sha1(rand()), 0, 7) . "syntel tech");

    //fill in legal name
    $this->type("legal_name", "syntel tech Ltd");

    //fill in nick name
    $this->type("nick_name", "syntel");

    //fill in email
    $this->type("email_1_email", substr(sha1(rand()), 0, 7) . "info@syntel.com");

    //fill in phone
    $this->type("phone_1_phone", "222-7777");
    $this->select("phone_1_phone_type_id", "value=2");

    //fill in IM
    $this->type("im_1_name", "testGtalk");
    $this->select("im_1_location_type_id", "value=4");
    $this->select("im_1_provider_id", "value=4");

    //fill in openID
    $this->select("openid_1_location_type_id", "value=5");
    $this->type("openid_1_openid", "http://" . substr(sha1(rand()), 0, 7) . "Openid.com");

    //fill in website url
    $this->type("website_1_url", "http://syntelglobal.com");

    //fill in contact source
    $this->type("contact_source", "syntel's source");

    //fill in external identifier
    $orgExternalId = substr(sha1(rand()), 0, 4);
    $this->type("external_identifier", $orgExternalId);

    //check for duplicate contact
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    $this->type("address_1_street_address", "928A Lincoln Way W");
    $this->type("address_1_city", "Madison");
    $this->type("address_1_postal_code", "68748");

    $this->click("address_1_country_id");
    $this->select("address_1_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->assertElementContainsText('address_table_1', "Latitude")) {
      $this->type("address_1_geo_code_1", "1234");
      $this->type("address_1_geo_code_2", "5678");
    }

    //Communication Preferences section
    $this->click("commPrefs");

    //Select preferred method(s) of communication
    $this->click("preferred_communication_method[2]");
    $this->click("preferred_communication_method[5]");

    //Select preferred method for Privacy
    $this->click("privacy[do_not_sms]");
    $this->click("privacy[do_not_mail]");
    //select preferred language
    $this->waitForElementPresent("preferred_language");
    $this->select("preferred_language", "value=de_DE");

    //Notes section
    $this->click("notesBlock");
    $this->waitForElementPresent("subject");
    $this->type("subject", "syntel global note");
    $this->type("note", "This is a note for syntel global's contact webtest.");

    //Tags and Groups section
    $this->click("tagGroup");

    // select group
    $this->select("group", "label=$groupName");

    $this->click("tag[{$this->webtestGetValidEntityID('Tag')}]");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Contact Saved");
  }

  public function testIndividualAddWithSharedAddress() {
    $this->webtestLogin();

    $this->openCiviPage('contact/add', "reset=1&ct=Individual");

    //contact details section
    //select prefix
    $this->click("prefix_id");
    $this->select("prefix_id", "value=" . $this->webtestGetFirstValueForOptionGroup('individual_prefix'));

    //fill in first name
    $this->type("first_name", substr(sha1(rand()), 0, 7) . "John");

    //fill in middle name
    $this->type("middle_name", "Bruce");

    $lastName = substr(sha1(rand()), 0, 7) . "Smith";
    //fill in last name
    $this->type("last_name", $lastName);

    //create new current employer
    $currentEmployer = substr(sha1(rand()), 0, 7) . "Web Access";

    //fill in email
    $this->type("email_1_email", substr(sha1(rand()), 0, 7) . "john@gmail.com");

    //fill in phone
    $this->type("phone_1_phone", "2222-4444");

    //fill in source
    $this->type("contact_source", "johnSource");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");

    $this->select('address_1_location_type_id', 'value=2');

    $this->click('address[1][use_shared_address]');

    // create new organization with dialog
    $this->clickAt("xpath=//div[@id='s2id_address_1_master_contact_id']/a");
    $this->click("xpath=//li[@class='select2-no-results']//a[contains(text(),' New Organization')]");

    // create new contact using dialog
    $this->waitForElementPresent("css=div#crm-profile-block");
    $this->waitForElementPresent("_qf_Edit_next");

    $this->type('organization_name', $currentEmployer);
    $this->type('street_address-1', '902C El Camino Way SW');
    $this->type("email-Primary", "john@gmail.com" . substr(sha1(rand()), 0, 7));
    $this->type('city-1', 'Dumfries');
    $this->type('postal_code-1', '1234');
    $this->select('state_province-1', 'value=1001');

    $this->click("_qf_Edit_next");

    $this->select2('employer_id', $currentEmployer);

    //make sure shared address is selected
    $this->waitForElementPresent('selected_shared_address-1');

    //fill in address 2
    $this->click("//div[@id='addMoreAddress1']/a/span");
    $this->waitForElementPresent("address_2_street_address");

    $this->select('address_2_location_type_id', 'value=1');

    $this->click('address[2][use_shared_address]');

    // create new household with dialog
    $this->clickAt("xpath=//div[@id='s2id_address_2_master_contact_id']/a");
    $this->click("xpath=//li[@class='select2-no-results']//a[contains(text(),' New Household')]");

    // create new contact using dialog
    $this->waitForElementPresent("css=div#crm-profile-block");
    $this->waitForElementPresent("_qf_Edit_next");

    $sharedHousehold = substr(sha1(rand()), 0, 7) . 'Smith Household';
    $this->type('household_name', $sharedHousehold);
    $this->type('street_address-1', '2782Y Dowlen Path W');
    $this->type("email-Primary", substr(sha1(rand()), 0, 7) . "john@gmail.com");
    $this->type('city-1', 'Birmingham');
    $this->type('postal_code-1', '3456');
    $this->select('state_province-1', 'value=1001');

    $this->click("_qf_Edit_next");

    //make sure shared address is selected
    $this->waitForElementPresent('selected_shared_address-2');

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $name = $this->getText("xpath=//div[@class='crm-summary-display_name']");
    $this->waitForText('crm-notification-container', "$name has been created.");

    //make sure current employer is set
    $this->verifyText("xpath=id('contactinfo-block')/div/div/div[2]/div", 'Employer');
    $this->verifyText("xpath=id('contactinfo-block')/div/div/div[2]/div[2]/a[text()]", $currentEmployer);

    //make sure both shared address are set.
    $this->assertElementContainsText('address-block-1', "Address belongs to $currentEmployer");
    $this->assertElementContainsText('address-block-2', "Address belongs to $sharedHousehold");

    // make sure relationships are created
    $this->click("xpath=id('tab_rel')/a");
    $this->waitForElementPresent('permission-legend');
    $this->assertElementContainsText('DataTables_Table_0', 'Employee of');
    $this->assertElementContainsText('DataTables_Table_0', 'Household Member of');
  }

  public function testContactDeceased() {
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('contact/add', 'reset=1&ct=Individual');
    //contact details section
    //fill in first name
    $fname = substr(sha1(rand()), 0, 7) . "John";
    $lname = substr(sha1(rand()), 0, 7) . "Smith";
    $this->type("first_name", $fname);
    //fill in last name
    $this->type("last_name", $lname);
    //fill in email
    $this->type("email_1_email", substr(sha1(rand()), 0, 7) . "john@gmail.com");
    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");
    //Edit Contact
    $cid = $this->urlArg('cid');
    $dname = $fname . ' ' . $lname . ' (deceased)';
    foreach (array('', 'deceased') as $val) {
      $this->openCiviPage("contact/add", "reset=1&action=update&cid={$cid}");
      if ($val) {
        $this->assertElementContainsText('page-title', 'Edit ' . $dname);
      }
      // Click on the Demographics tab
      $this->click('demographics');
      $this->waitForElementPresent('is_deceased');
      $this->click('is_deceased');
      // Click on Save
      $this->click('_qf_Contact_upload_view-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
      if (!$val) {
        $this->assertElementContainsText('css=div.crm-summary-display_name', $dname);
      }
      else {
        $this->assertTrue(($this->getText('css=div.crm-summary-display_name') != $dname));
      }
    }
    foreach (array('', 'deceased') as $val) {
      $this->mouseDown('crm-demographic-content');
      $this->mouseUp('crm-demographic-content');
      $this->waitForElementPresent("css=#crm-demographic-content .crm-container-snippet form");
      $this->click('is_deceased');
      $this->click("css=#crm-demographic-content input.crm-form-submit");
      $this->waitForElementPresent("css=#crm-demographic-content > .crm-inline-block-content");
      if (!$val) {
        $this->assertElementContainsText('css=div.crm-summary-display_name', $dname);
      }
      else {
        $this->assertTrue(($this->getText('css=div.crm-summary-display_name') != $dname));
      }
    }
    $this->openCiviPage("contact/add", "reset=1&action=update&cid={$cid}");
    $this->assertTrue(($this->getText('page-title') != $dname));
  }

}
