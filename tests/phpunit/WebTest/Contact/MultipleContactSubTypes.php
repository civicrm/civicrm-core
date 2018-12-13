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
 * Class WebTest_Contact_MultipleContactSubTypes
 */
class WebTest_Contact_MultipleContactSubTypes extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testIndividualAdd() {
    $this->webtestLogin();

    $selection1 = 'Student';
    $selection2 = 'Parent';
    $selection3 = 'Staff';

    //Create custom group for contact sub-types
    list($groupTitleForStudent, $customGroupIdForStudent) = $this->_addCustomData($selection1);
    list($groupTitleForParent, $customGroupIdForParent) = $this->_addCustomData($selection2);
    list($groupTitleForStaff, $customGroupIdForStaff) = $this->_addCustomData($selection3);

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

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

    //fill in website
    $this->type("website_1_url", "http://www.john.com");

    //fill in source
    $this->type("contact_source", "johnSource");

    //fill in external identifier
    $indExternalId = substr(sha1(rand()), 0, 4);
    $this->type("external_identifier", $indExternalId);

    $this->waitForElementPresent("customData$customGroupIdForStudent");
    $this->waitForElementPresent("customData$customGroupIdForParent");

    // Make sure our staff custom set does NOT show up
    $this->assertFalse($this->isElementPresent("customData$customGroupIdForStaff"), "A custom field showed up for the wrong subtype!");

    $this->type("xpath=//div[@id='customData{$customGroupIdForStudent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForStudent}");
    $this->type("xpath=//div[@id='customData{$customGroupIdForParent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForParent}");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    //fill in address 1
    $this->type("address_1_street_address", "902C El Camino Way SW");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");

    $this->click("address_1_country_id");
    $this->select("address_1_country_id", "value=" . $this->webtestGetValidCountryID());

    if ($this->isElementPresent("address_1_geo_code_1")) {
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

    if ($this->isElementPresent("address_2_geo_code_1")) {
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
    $this->waitForText('crm-notification-container', "Contact Saved");
    $this->assertElementContainsText('css=.crm-contact_type_label', "Student");
    $this->assertElementContainsText('css=.crm-contact_type_label', "Parent");

    //custom data check
    $this->waitForText("custom-set-content-{$customGroupIdForParent}", "dummy text for customData{$customGroupIdForParent}");
    $this->waitForText("custom-set-content-{$customGroupIdForStudent}", "dummy text for customData{$customGroupIdForStudent}");

    // Get contact id
    $cid = $this->urlArg('cid');

    //editing contact sub-type
    $this->openCiviPage('contact/add', "reset=1&action=update&cid=$cid");

    //edit contact sub-type
    $this->removeSelection("contact_sub_type", "value={$selection1}");
    $this->addSelection('contact_sub_type', "value={$selection3}");

    $this->waitForElementPresent("customData$customGroupIdForStaff");

    // Make sure our staff custom set does NOT show up
    $this->assertFalse($this->isElementPresent("customData$customGroupIdForStudent"), "A custom field showed up for the wrong subtype!");

    $this->type("xpath=//div[@id='customData{$customGroupIdForParent}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForParent}");
    $this->type("xpath=//div[@id='customData{$customGroupIdForStaff}']/table/tbody/tr//td/input", "dummy text for customData{$customGroupIdForStaff}");

    $this->click("_qf_Contact_upload_view-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check confirmation alert.
    $this->assertTrue((bool) preg_match("/One or more contact subtypes have been de-selected from the list for this contact. Any custom data associated with de-selected subtype will be removed. Click OK to proceed, or Cancel to review your changes before saving./", $this->getConfirmation()));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Verify contact types
    $this->waitForText('crm-notification-container', "Contact Saved");
    $this->assertElementNotContainsText('css=.crm-contact_type_label', "Student");
    $this->assertElementContainsText('css=.crm-contact_type_label', "Staff");
    $this->assertElementContainsText('css=.crm-contact_type_label', "Parent");

    //custom data check
    $this->waitForText("custom-set-content-{$customGroupIdForParent}", "dummy text for customData{$customGroupIdForParent}");
    $this->waitForText("custom-set-content-{$customGroupIdForStaff}", "dummy text for customData{$customGroupIdForStaff}");
  }

  /**
   * Add custom fields for a contact sub-type
   * @param $contactSubType
   * @return array
   */
  public function _addCustomData($contactSubType) {
    $this->openCiviPage("admin/custom/group", "action=add&reset=1");

    //fill custom group title
    $customGroupTitle = "Custom group For {$contactSubType}" . substr(sha1(rand()), 0, 4);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends_0");
    $this->select("extends_0", "value=Individual");
    $this->addSelection("extends_1", "label={$contactSubType}");

    // Don't collapse
    $this->uncheck('collapse_display');

    // Save
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added.");
    $gid = $this->urlArg('gid');

    // Add field
    $this->openCiviPage('admin/custom/group/field/add', "reset=1&action=add&gid=$gid");
    $fieldLabel = "custom_field_for_{$contactSubType}" . substr(sha1(rand()), 0, 4);
    $this->type('label', $fieldLabel);
    $this->click('_qf_Field_done-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $customGroupTitle = preg_replace('/\s/', '_', trim($customGroupTitle));
    return array($customGroupTitle, $gid);
  }

}
