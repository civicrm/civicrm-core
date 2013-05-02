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
class WebTest_Event_MultiprofileEventTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCreateEventRegisterPage() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $customGrp1 = "Custom Data1_" . substr(sha1(rand()), 0, 7);
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $participantfname = 'Dany' . substr(sha1(rand()), 0, 4);
    $participantlname = 'Dan' . substr(sha1(rand()), 0, 4);
    $email1 = $firstName . "@test.com";
    $email2 = $participantfname . "@test.com";

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    //add email to name and address profile
    $cfId = $this->_addEmailField();

    // create custom group1
    $this->openCiviPage("admin/custom/group", "reset=1");
    $this->click("newCustomDataGroup");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Contact");
    $this->click("_qf_Group_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get custom group id
    $customGrpId1 = $this->urlArg('gid');

    $customId = $this->_testGetCustomFieldId($customGrpId1);

    $profileId = $this->_testGetProfileId($customId);

    $this->openCiviPage("event/add", "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    $eventPageId = $this->_testAddMultipleProfile($profileId);

    $this->_testEventRegistration($eventPageId, $customId, $firstName, $lastName,
      $participantfname, $participantlname, $email1, $email2
    );
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Find Main Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $firstName);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $firstName . " " . $lastName;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $participantfname . " " . $participantlname;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // delete all custom data
    if (isset($cfId)) {
      $this->_removeEmailField($cfId);
    }
    foreach ($customId as $cid) {
      $this->openCiviPage("admin/custom/group/field", "action=delete&reset=1&gid={$customGrpId1}&id=$cid");
      $this->click("_qf_DeleteField_next-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    $this->openCiviPage("admin/custom/group", "action=delete&reset=1&id=$customGrpId1");
    $this->click("_qf_DeleteGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function testAnoumyousRegisterPage() {
    // add the required Drupal permission
    $permission = array('edit-1-access-all-custom-data');
    $this->changePermissions($permission);

    $customGrp1 = "Custom Data1_" . substr(sha1(rand()), 0, 7);
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $participantfname = 'Dany' . substr(sha1(rand()), 0, 4);
    $participantlname = 'Dan' . substr(sha1(rand()), 0, 4);
    $email1 = $firstName . "@test.com";
    $email2 = $participantfname . "@test.com";
    $firstName2 = 'Man' . substr(sha1(rand()), 0, 4);
    $lastName2 = 'Ann' . substr(sha1(rand()), 0, 7);
    $participantfname2 = 'Adam' . substr(sha1(rand()), 0, 4);
    $participantlname2 = 'Gil' . substr(sha1(rand()), 0, 4);
    $email3 = $participantfname2 . "@test.com";
    $email4 = $firstName2 . "@test.com";

    // We need a payment processor
    $processorName = "Webtest Dummy" . substr(sha1(rand()), 0, 7);
    $this->webtestAddPaymentProcessor($processorName);

    //add email field to name and address profile
    $cfId = $this->_addEmailField( );

    // create custom group1
    $this->openCiviPage("admin/custom/group", "reset=1");
    $this->click("newCustomDataGroup");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Contact");
    $this->click("_qf_Group_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get custom group id
    $customGrpId1 = $this->urlArg('gid');

    $customId = $this->_testGetCustomFieldId($customGrpId1);

    $profileId = $this->_testGetProfileId($customId);

    $this->openCiviPage('event/add', "reset=1&action=add");

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);

    $eventPageId = $this->_testAddMultipleProfile($profileId);

    // logout
    $this->webtestLogout();

    $this->_testEventRegistration($eventPageId, $customId, $firstName, $lastName, $participantfname, $participantlname, $email1, $email2);
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Find Main Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $firstName);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $firstName . " " . $lastName;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $participantfname . " " . $participantlname;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Edit page and remove some profile
    $this->_testRemoveProfile($eventPageId);

    // logout
    $this->webtestLogout();

    $this->_testEventRegistrationAfterRemoving($eventPageId, $customId, $firstName2, $lastName2, $participantfname2, $participantlname2, $email3, $email4);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Find Main Participant
    $this->openCiviPage('event/search', "reset=1");
    $this->type("sort_name", $firstName2);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $firstName2 . " " . $lastName2;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname2);
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top");

    $name = $participantfname2 . " " . $participantlname2;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // delete all custom data
    if (isset($cfId)) {

      $this->_removeEmailField($cfId);
    }
    foreach ($customId as $cid) {
      $this->openCiviPage("admin/custom/group/field", "action=delete&reset=1&gid={$customGrpId1}&id=$cid");
      $this->click("_qf_DeleteField_next-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    $this->openCiviPage("admin/custom/group", "action=delete&reset=1&id=$customGrpId1");
    $this->click("_qf_DeleteGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testGetCustomFieldId($customGrpId1) {
    $customId = array();

    // Create a custom data to add in profile

    $field1 = "Fname" . substr(sha1(rand()), 0, 7);
    $field2 = "Mname" . substr(sha1(rand()), 0, 7);
    $field3 = "Lname" . substr(sha1(rand()), 0, 7);

    // add custom fields for group 1
    $this->openCiviPage("admin/custom/group/field/add", "reset=1&action=add&gid=$customGrpId1");
    $this->type("label", $field1);
    $this->check("is_searchable");
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->type("label", $field2);
    $this->check("is_searchable");
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->type("label", $field3);
    $this->check("is_searchable");
    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get id of custom fields
    $this->openCiviPage("admin/custom/group/field", "reset=1&action=browse&gid=$customGrpId1");
    $custom1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[1]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom1 = $custom1[1];
    array_push($customId, $custom1);
    $custom2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[2]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom2 = $custom2[1];
    array_push($customId, $custom2);
    $custom3 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[3]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom3 = $custom3[1];
    array_push($customId, $custom3);

    return $customId;
  }

  function _testRemoveProfile($eventPageId) {
    $this->openCiviPage("event/manage/settings", "reset=1&action=update&id=$eventPageId");

    // Go to Online Contribution tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->click("xpath=//select[@id='additional_custom_post_id_multiple_1']/../span/a[text()='remove profile']");
    $this->click("xpath=//select[@id='additional_custom_post_id_multiple_2']/../span/a[text()='remove profile']");
    $this->click("xpath=//select[@id='additional_custom_post_id_multiple_3']/../span/a[text()='remove profile']");
    $this->click("xpath=//select[@id='additional_custom_post_id_multiple_4']/../span/a[text()='remove profile']");
    $this->click("_qf_Registration_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testGetProfileId($customId) {
    // create profiles
    $profileId = array();
    $profilefield = array(
      'street_address' => 'street_address',
      'supplemental_address_1' => 'supplemental_address_1',
      'city' => 'city',
    );
    $location = 1;
    $type = "Contact";
    $profileId1 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId1);

    $profilefield = array(
      'street_address' => 'street_address',
      'city' => 'city',
      'phone' => 'phone',
      'postal_code' => 'postal_code',
    );
    $location = 0;
    $type = "Contact";
    $profileId2 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId2);

    $profilefield = array(
      'nick_name' => 'nick_name',
      'url' => 'url',
    );
    $location = 0;
    $type = "Contact";
    $profileId3 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId3);

    $profilefield = array(
      'current_employer' => 'current_employer',
      'job_title' => 'job_title',
    );
    $location = 0;
    $type = "Individual";
    $profileId4 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId4);

    $profilefield = array(
      'middle_name' => 'middle_name',
      'gender' => 'gender',
    );
    $location = 0;
    $type = "Individual";
    $profileId5 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId5);

    $profilefield = array(
      'custom_' . $customId[0] => 'custom_' . $customId[0],
      'custom_' . $customId[1] => 'custom_' . $customId[1],
      'custom_' . $customId[2] => 'custom_' . $customId[2],
    );
    $location = 0;
    $type = "Contact";
    $profileId6 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId6);

    $profilefield = array(
      'participant_role' => 'participant_role',
    );
    $location = 0;
    $type = "Participant";
    $profileId7 = $this->_testCreateProfile($profilefield, $location, $type);
    array_push($profileId, $profileId7);

    return $profileId;
  }

  function _testCreateProfile($profilefield, $location = 0, $type) {
    $locationfields = array(
      'supplemental_address_1',
      'supplemental_address_2',
      'city',
      'country',
      'email',
      'state',
      'street_address',
      'postal_code',
    );

    // Add new profile.
    $profilename = "Profile_" . substr(sha1(rand()), 0, 7);
    $this->openCiviPage("admin/uf/group", "reset=1");
    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-top');

    //Name of profile
    $this->type('title', $profilename);
    $this->click('_qf_Group_next-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $profileId = $this->urlArg('gid');

    //Add field to profile_testCreateProfile
    foreach ($profilefield as $key => $value) {
      $this->openCiviPage("admin/uf/group/field/add", "reset=1&action=add&gid=$profileId");
      if (in_array($value, $locationfields)) {
        $this->select("field_name[0]", "value={$type}");
        $this->select("field_name[1]", "value={$value}");
        $this->select("field_name[2]", "value={$location}");
        $this->type("label", $value);
      }
      else {
        $this->select("field_name[0]", "value={$type}");
        $this->select("field_name[1]", "value={$value}");
        $this->type("label", $value);
      }
      $this->click('_qf_Field_next-top');
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    return $profileId;
  }

  function _testAddEventInfo($eventTitle, $eventDescription) {
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    $this->select("event_type_id", "value=1");

    // Attendee role s/b selected now.
    $this->select("default_role_id", "value=1");

    // Enter Event Title, Summary and Description
    $this->type("title", $eventTitle);
    $this->type("summary", "This is a great conference. Sign up now!");

    // Type description in ckEditor (fieldname, text to type, editor)
    $this->fillRichTextField("description", $eventDescription, 'CKEditor');

    // Choose Start and End dates.
    // Using helper webtestFillDate function.
    $this->webtestFillDateTime("start_date", "+1 week");
    $this->webtestFillDateTime("end_date", "+1 week 1 day 8 hours ");

    $this->type("max_participants", "50");
    $this->click("is_map");
    $this->click("_qf_EventInfo_upload-bottom");
  }

  function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    //$streetAddress = "100 Main Street";
    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->click("_qf_Location_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Location' information has been saved.");
  }

  function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro") {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");

    // select newly created processor
    $xpath = "xpath=//label[text() = '{$processorName}']/preceding-sibling::input[1]";
    $this->assertElementContainsText('css=.crm-event-manage-fee-form-block-payment_processor', $processorName);
    $this->check($xpath);
    $this->select("financial_type_id", "label=Event Fee");
    if ($priceSet) {
      // get one - TBD
    }
    else {
      $this->type("label_1", "Member");
      $this->type("value_1", "250.00");
      $this->type("label_2", "Non-member");
      $this->type("value_2", "325.00");
      //set default
      $this->click("xpath=//table[@id='map-field-table']/tbody/tr[2]/td[3]/input");
    }

    if ($discount) {
      // enter early bird discounts TBD
    }

    $this->click("_qf_Fee_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Fee' information has been saved.");
  }

  function _testAddMultipleProfile($profileId) {
    // Go to Online Contribution tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->click("is_online_registration");
    $this->check("is_multiple_registrations");
    $this->select("custom_pre_id", "value=1");
    $this->select("custom_post_id", "value=" . $profileId[3]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("custom_post_id_multiple_1");
    $this->select("custom_post_id_multiple_1", "value=" . $profileId[2]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("custom_post_id_multiple_2");
    $this->select("custom_post_id_multiple_2", "value=" . $profileId[1]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("custom_post_id_multiple_3");
    $this->select("custom_post_id_multiple_3", "value=" . $profileId[4]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("custom_post_id_multiple_4");
    $this->select("custom_post_id_multiple_4", "value=" . $profileId[5]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("custom_post_id_multiple_5");
    $this->select("custom_post_id_multiple_5", "value=" . $profileId[6]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("additional_custom_post_id_multiple_1");
    $this->select("additional_custom_post_id_multiple_1", "value=" . $profileId[5]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("additional_custom_post_id_multiple_2");
    $this->select("additional_custom_post_id_multiple_2", "value=" . $profileId[1]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("additional_custom_post_id_multiple_3");
    $this->select("additional_custom_post_id_multiple_3", "value=" . $profileId[2]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a[text()='add profile']");
    $this->waitForElementPresent("additional_custom_post_id_multiple_4");
    $this->select("additional_custom_post_id_multiple_4", "value=" . $profileId[3]);

    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "TestEvent");
    $this->type("confirm_from_email", "testevent@test.com");
    $this->click("_qf_Registration_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "'Registration' information has been saved.");

    return $this->urlArg('id');
  }

  function _testEventRegistration($eventPageId, $customId, $firstName, $lastName,
    $participantfname, $participantlname, $email1, $email2
  ) {
    $this->openCiviPage("event/register", "id={$eventPageId}&reset=1", "_qf_Register_upload-bottom");
    $this->select("additional_participants", "value=1");

    $this->type("email-Primary", $email1);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("street_address-1", "Test street addres");
    $this->type("city-1", "Mumbai");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName . 'billing');
    $this->type("billing_last_name", $lastName . 'billing');
    $this->type("billing_street_address-5", "0121 Mount Highschool.");
    $this->type(" billing_city-5", "Shangai");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");

    $this->type("current_employer", "ABCD");
    $this->type("job_title", "Painter");
    $this->type("nick_name", "Nick");
    $this->type("url-1", "http://www.test.com");

    $this->type("street_address-Primary", "Primary street address");
    $this->type("city-Primary", "primecity");
    $this->type("phone-Primary-1", "98667764");
    $this->type("postal_code-Primary", "6548");

    $this->type("custom_" . $customId[0], "fname_custom1");
    $this->type("custom_" . $customId[1], "mname_custom1");
    $this->type("custom_" . $customId[2], "lname_custom1");

    $this->type("middle_name", "xyz");
    $this->click("name=gender value=2");
    $this->select("participant_role", "value=2");

    $this->click("_qf_Register_upload-bottom");
    $this->waitForElementPresent("_qf_Participant_1_next-Array");

    $this->type("email-Primary", $email2);
    $this->type("first_name", $participantfname);
    $this->type("last_name", $participantlname);
    $this->type("street_address-1", "participant street addres");
    $this->type("city-1", "pune");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    $this->type("current_employer", "ABCD");
    $this->type("job_title", "Potato picker");

    $this->type("custom_" . $customId[0], "participant_custom1");
    $this->type("custom_" . $customId[1], "participant_custom1");
    $this->type("custom_" . $customId[2], "participant_custom1");

    $this->type("street_address-Primary", "Primary street address");
    $this->type("city-Primary", "primecity");
    $this->type("phone-Primary-1", "98667764");
    $this->type("postal_code-Primary", "6548");

    $this->type("nick_name", "Nick1");
    $this->type("url-1", "http://www.part.com");

    $this->clickLink("_qf_Participant_1_next-Array", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testEventRegistrationAfterRemoving($eventPageId, $customId, $firstName2, $lastName2, $participantfname2, $participantlname2, $email3, $email4) {
    $this->openCiviPage("event/register", "id={$eventPageId}&reset=1", "_qf_Register_upload-bottom");
    $this->select("additional_participants", "value=1");

    $this->type("email-Primary", $email4);
    $this->type("first_name", $firstName2);
    $this->type("last_name", $lastName2);
    $this->type("street_address-1", "Test street addres");
    $this->type("city-1", "Mumbai");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    // Credit Card Info
    $this->select("credit_card_type", "value=Visa");
    $this->type("credit_card_number", "4111111111111111");
    $this->type("cvv2", "000");
    $this->select("credit_card_exp_date[M]", "value=1");
    $this->select("credit_card_exp_date[Y]", "value=2020");

    //Billing Info
    $this->type("billing_first_name", $firstName2 . 'billing');
    $this->type("billing_last_name", $lastName2 . 'billing');
    $this->type("billing_street_address-5", "0121 Mount Highschool.");
    $this->type(" billing_city-5", "Shangai");
    $this->select("billing_country_id-5", "value=1228");
    $this->select("billing_state_province_id-5", "value=1004");
    $this->type("billing_postal_code-5", "94129");

    $this->type("current_employer", "ABCD");
    $this->type("job_title", "Painter");

    $this->type("nick_name", "Nickkk");
    $this->type("url-1", "http://www.testweb.com");

    $this->type("street_address-Primary", "Primary street address");
    $this->type("city-Primary", "primecity");
    $this->type("phone-Primary-1", "9866776422");
    $this->type("postal_code-Primary", "6534");

    $this->type("custom_" . $customId[0], "fname_custom1");
    $this->type("custom_" . $customId[1], "mname_custom1");
    $this->type("custom_" . $customId[2], "lname_custom1");

    $this->type("middle_name", "xyz");
    $this->click("name=gender value=2");
    $this->select("participant_role", "value=2");

    $this->click("_qf_Register_upload-bottom");
    $this->waitForElementPresent("_qf_Participant_1_next-Array");

    $this->type("email-Primary", $email3);
    $this->type("first_name", $participantfname2);
    $this->type("last_name", $participantlname2);
    $this->type("street_address-1", "participant street addres");
    $this->type("city-1", "pune");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    $this->type("current_employer", "ABCD");
    $this->type("job_title", "BATCHER");

    $this->clickLink("_qf_Participant_1_next-Array", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
  }

  function _addEmailField( ){
    //add email field in name and address profile
    $this->openCiviPage('admin/uf/group/field/add', 'reset=1&action=add&gid=1', "_qf_Field_next-bottom");
    $this->select("field_name[0]", "value=Contact");
    $this->select("field_name[1]", "value=email");
    $this->select("field_name[2]", "value=0");
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $cfId = "";
    //check wheather webtest has created the field
    if($this->assertElementNotContainsText('crm-notification-container', "The selected field was not added. It already exists in this profile")) {
      $this->waitForElementPresent("xpath=//div[@id='field_page']//table/tbody//tr[8]/td[9]/span/a[text()='Edit']");
      $cfId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[8]/td[9]/span/a[text()='Edit']/@href"));
      $cfId = $cfId[1];
    }
    return $cfId;
  }

  function _removeEmailField($cfId) {

    $this->openCiviPage("admin/uf/group/field", "action=delete&id={$cfId}");
    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}