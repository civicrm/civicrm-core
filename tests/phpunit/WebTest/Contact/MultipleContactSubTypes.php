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
class WebTest_Contact_MultipleContactSubTypes extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testIndividualAdd() {
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

    $selection1 = 'Student';
    $selection2 = 'Parent';
    $selection3 = 'Staff';

    //Create custom group for contact sub-types
    list($groupTitleForStudent, $customGroupIdForStudent) = $this->_addCustomData($selection1);
    list($groupTitleForParent, $customGroupIdForParent) = $this->_addCustomData($selection2);
    list($groupTitleForStaff, $customGroupIdForStaff) = $this->_addCustomData($selection3);

    // Go directly to the URL of the screen that you will be testing (New Individual).
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

    //contact details section
    //select prefix
    $this->click("prefix_id");
    $this->select("prefix_id", "value=" . $this->webtestGetFirstValueForOptionGroup('individual_prefix'));

    $this->addSelection('contact_sub_type', "value={$selection1}");
    $this->addSelection('contact_sub_type', "value={$selection2}");

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

    //checking for presence of contact data specific custom data
    if ($this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForStudent}']") && $this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForParent}']") && !$this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForStaff}']")) {
      $assertCheckForCustomGroup = TRUE;
    }
    else {
      $assertCheckForCustomGroup = FALSE;
    }

    $this->assertTrue($assertCheckForCustomGroup, "The Check for contact sub-type specific custom group failed");

    if ($assertCheckForCustomGroup) {

      $this->type("xpath=//div[@id='customData{$customGroupIdForStudent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForStudent}");
      $this->type("xpath=//div[@id='customData{$customGroupIdForParent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForParent}");
    }

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    //fill in address 1
    $this->type("address_1_street_address", "902C El Camino Way SW");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");

    $this->click("address_1_country_id");
    $this->select("address_1_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->isTextPresent("Latitude")) {
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

    if ($this->isTextPresent("Latitude")) {
      $this->type("address_2_geo_code_1", "1234");
      $this->type("address_2_geo_code_2", "5678");
    }

    //Communication Preferences section
    $this->click("//div[@class='crm-accordion-header' and contains(.,'Communication Preferences')]");

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

    //Demographics section
    $this->click("//div[@class='crm-accordion-header' and contains(.,'Demographics')]");
    $this->waitForElementPresent("birth_date");

    $this->webtestFillDate('birth_date', "-1 year");

    //Tags and Groups section
    $this->click("//div[@class='crm-accordion-header' and contains(.,'Tags and Groups')]");

    $this->click("group[{$this->webtestGetValidEntityID('Group')}]");
    $this->click("tag[{$this->webtestGetValidEntityID('Tag')}]");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //checking the contact sub-type of newly created individual
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='contact-summary']/div[@id='contactTopBar']/table/tbody/tr/td[@class='crm-contact_type_label'][text()='{$selection1}, {$selection2}']"));

    //custom data check
    if ($this->isElementPresent("{$groupTitleForStudent}_0")) {
      $groupidSt = "{$groupTitleForStudent}_0";
    }
    else {
      $groupidSt = "{$groupTitleForStudent}_1";
    }

    //custom data check
    if ($this->isElementPresent("{$groupTitleForParent}_0")) {
      $groupidPa = "{$groupTitleForParent}_0";
    }
    else {
      $groupidPa = "{$groupTitleForParent}_1";
    }

    $this->click($groupidSt);
    $this->click($groupidPa);

    $this->assertTrue($this->isTextPresent("dummy text for customData{$customGroupIdForParent}"));
    $this->assertTrue($this->isTextPresent("dummy text for customData{$customGroupIdForStudent}"));

    //editing contact sub-type
    $this->click("xpath=//ul[@id='actions']/li[2]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');
    $selectedValues = $this->getSelectedValues("contact_sub_type");
    if (in_array($selection1, $selectedValues) && in_array($selection2, $selectedValues)) {
      $checkSelection = TRUE;
    }
    else {
      $checkSelection = FALSE;
    }

    $this->assertTrue($checkSelection, 'Assertion failed for multiple selection of contact sub-type');

    //edit contact sub-type
    $this->removeSelection("contact_sub_type", "value={$selection1}");
    $this->addSelection('contact_sub_type', "value={$selection3}");

    //checking for presence of contact data specific custom data
    if (!$this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForStudent}']") && $this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForParent}']") && $this->isElementPresent("xpath=//div[@id='customData{$customGroupIdForStaff}']")) {
      $assertCheckForCustomGroup = TRUE;
    }
    else {
      $assertCheckForCustomGroup = FALSE;
    }

    $this->assertTrue($assertCheckForCustomGroup, "The Check for contact sub-type specific custom group failed after de-selecting Student and selecting staff");

    if ($assertCheckForCustomGroup) {

      $this->type("xpath=//div[@id='customData{$customGroupIdForParent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForParent}");
      $this->type("xpath=//div[@id='customData{$customGroupIdForStaff}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForStaff}");
    }

    $this->click("_qf_Contact_upload_view-bottom");
    sleep(5);

    // Check confirmation alert.
    $this->assertTrue((bool)preg_match("/One or more contact subtypes have been de-selected from the list for this contact. Any custom data associated with de-selected subtype will be removed. Click OK to proceed, or Cancel to review your changes before saving./", $this->getConfirmation()));
    $this->chooseOkOnNextConfirmation();
    sleep(10);

    $this->waitForElementPresent("xpath=//div[@id='contact-summary']/div[@id='contactTopBar']");
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='contact-summary']/div[@id='contactTopBar']/table/tbody/tr/td[@class='crm-contact_type_label'][text()='{$selection2}, {$selection3}']"));

    //custom data check
    if ($this->isElementPresent("{$groupTitleForParent}_0")) {
      $groupidPa = "{$groupTitleForParent}_0";
    }
    else {
      $groupidPa = "{$groupTitleForParent}_1";
    }

    //custom data check
    if ($this->isElementPresent("{$groupTitleForStaff}_0")) {
      $groupidSta = "{$groupTitleForStaff}_0";
    }
    else {
      $groupidSta = "{$groupTitleForStaff}_1";
    }

    $this->click($groupidSta);
    $this->click($groupidPa);

    $this->assertTrue($this->isTextPresent("dummy text for customData{$customGroupIdForParent}"));
    $this->assertTrue($this->isTextPresent("dummy text for customData{$customGroupIdForStaff}"));
  }

  function _addCustomData($contactSubType) {
    // Go directly to the URL of the screen that you will be testing (New Custom Group).
    $this->open($this->sboxPath . "civicrm/admin/custom/group?reset=1");

    //add new custom data
    $customGroupTitle = "Custom group For {$contactSubType}" . substr(sha1(rand()), 0, 4);
    $this->click("//a[@id='newCustomDataGroup']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Individual");
    $this->addSelection("extends[1]", "label={$contactSubType}");
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->assertTrue($this->isTextPresent("Your custom field set '{$customGroupTitle}' has been added."));
    $url = explode('gid=', $this->getLocation());
    $gid = $url[1];

    $fieldLabel = "custom_field_for_{$contactSubType}" . substr(sha1(rand()), 0, 4);
    $this->type('label', $fieldLabel);
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $customGroupTitle = preg_replace('/\s/', '_', trim($customGroupTitle));
    return array($customGroupTitle, $gid);
  }
}



