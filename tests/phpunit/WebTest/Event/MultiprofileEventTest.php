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
 * Class WebTest_Event_MultiprofileEventTest
 */
class WebTest_Event_MultiprofileEventTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testCreateEventRegisterPage() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    $customGrp1 = "Custom Data1_" . substr(sha1(rand()), 0, 7);
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $participantfname = 'Dany' . substr(sha1(rand()), 0, 4);
    $participantlname = 'Dan' . substr(sha1(rand()), 0, 4);
    $email1 = $firstName . "@test.com";
    $email2 = $participantfname . "@test.com";

    // Use default payment processor
    $processorName = 'Test Processor';
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
    $this->waitForAjaxContent();

    // Find Main Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $firstName);
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $firstName . " " . $lastName;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table[1]/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table[1]/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname);
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $participantfname . " " . $participantlname;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table[1]/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table[1]/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table[1]/tbody/tr[6]/td[2]", preg_quote($status));

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

  public function testAnoumyousRegisterPage() {
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

    // Use default payment processor
    $processorName = 'Test Processor';
    $this->webtestAddPaymentProcessor($processorName);

    //add email field to name and address profile
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
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $firstName . " " . $lastName;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname);
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $participantfname . " " . $participantlname;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
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
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $firstName2 . " " . $lastName2;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[3]/td[2]/a", preg_quote($eventTitle));
    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[6]/td[2]", preg_quote($status));

    // Find additional  Participant
    $this->openCiviPage("event/search", "reset=1");
    $this->type("sort_name", $participantfname2);
    $this->click("xpath=//tr/td[1]/label[contains(text(), 'Participant is a Test?')]/../label[contains(text(), 'Yes')]/preceding-sibling::input[1]");
    $this->clickLink("_qf_Search_refresh", "xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->clickLink("xpath=//div[@id='participantSearch']//table//tbody/tr[1]/td[11]/span/a[text()='View']", "_qf_ParticipantView_cancel-top", FALSE);

    $name = $participantfname2 . " " . $participantlname2;
    $status = 'Registered';

    $this->verifyText("xpath=//form[@id='ParticipantView']/div[2]/table/tbody/tr[1]/td[2]/strong/a", preg_quote($name));
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

  /**
   * Get custom field ID.
   *
   * @param int $customGrpId1
   *
   * @return array
   */
  public function _testGetCustomFieldId($customGrpId1) {
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
    $this->click("_qf_Field_done-bottom");
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

  /**
   * @param int $eventPageId
   */
  public function _testRemoveProfile($eventPageId) {
    $this->openCiviPage("event/manage/settings", "reset=1&action=update&id=$eventPageId");

    // Go to Online Contribution tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->click("xpath=//*[@id='additional_custom_post_id_multiple_1']/parent::td/span[1]/a");
    $this->click("xpath=//*[@id='additional_custom_post_id_multiple_2']/parent::td/span[1]/a");
    $this->click("xpath=//*[@id='additional_custom_post_id_multiple_3']/parent::td/span[1]/a");
    $this->click("xpath=//*[@id='additional_custom_post_id_multiple_4']/parent::td/span[1]/a");
    $this->click("_qf_Registration_upload-bottom");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
  }

  /**
   * @param int $customId
   *
   * @return array
   */
  public function _testGetProfileId($customId) {
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
      'gender_id' => 'gender_id',
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

  /**
   * Test profile creation.
   *
   * @param array $profileField
   * @param int $location
   * @param $type
   *
   * @return null
   */
  public function _testCreateProfile($profileField, $location = 0, $type) {
    $locationFields = array(
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
      'city',
      'country',
      'email',
      'state',
      'street_address',
      'postal_code',
    );

    // Add new profile.
    $profileName = "Profile_" . substr(sha1(rand()), 0, 7);
    $this->openCiviPage("admin/uf/group", "reset=1");
    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-top');

    //Name of profile
    $this->type('title', $profileName);
    $this->click('uf_group_type_Profile');
    $this->click('_qf_Group_next-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $profileId = $this->urlArg('gid');

    //Add field to profile_testCreateProfile
    foreach ($profileField as $key => $value) {
      $this->openCiviPage("admin/uf/group/field/add", "reset=1&action=add&gid=$profileId");
      if (in_array($value, $locationFields)) {
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

  /**
   * @param $eventTitle
   * @param $eventDescription
   */
  public function _testAddEventInfo($eventTitle, $eventDescription) {
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

  /**
   * @param $streetAddress
   */
  public function _testAddLocation($streetAddress) {
    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload-bottom");

    // Fill in address fields
    //$streetAddress = "100 Main Street";
    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->waitForElementPresent('address_1_country_id');
    $this->select("address_1_country_id", "value=1228");
    $this->type("address_1_postal_code", "94117");
    $this->select("address_1_state_province_id", "value=1004");
    $this->type("email_1_email", "info@civicrm.org");

    $this->click("_qf_Location_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Location_upload-bottom");
    $this->waitForText('crm-notification-container', "'Event Location' information has been saved.");
  }

  /**
   * @param bool $discount
   * @param bool $priceSet
   * @param string $processorName
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorName = "PP Pro") {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");

    // select newly created processor
    $this->select2('payment_processor', $processorName, TRUE);
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
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->waitForTextPresent("'Fees' information has been saved.");
  }

  /**
   * Test adding multiple profiles.
   *
   * @param int $profileId
   *
   * @return null
   */
  public function _testAddMultipleProfile($profileId) {
    // Go to Online Contribution tab
    $this->click("link=Online Registration");
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->click("is_online_registration");
    $this->check("is_multiple_registrations");
    $this->select("xpath=//*[@id='custom_pre_id']/parent::td/div[1]/div/span/select", "value=1");
    $this->select("xpath=//*[@id='custom_post_id']/parent::td/div[1]/div/span/select", "value=" . $profileId[3]);
    $this->select("xpath=//*[@id='additional_custom_post_id']/parent::td/div[1]/div/span/select", "- same as for main contact -");
    //Click 'add another profile (bottom of page)'
    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("custom_post_id_multiple_1");
    $this->select("xpath=//*[@id='custom_post_id_multiple_1']/parent::td/div[1]/div/span/select", "value=" . $profileId[2]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("custom_post_id_multiple_2");
    $this->select("xpath=//*[@id='custom_post_id_multiple_2']/parent::td/div[1]/div/span/select", "value=" . $profileId[1]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("custom_post_id_multiple_3");
    $this->select("xpath=//*[@id='custom_post_id_multiple_3']/parent::td/div[1]/div/span/select", "value=" . $profileId[4]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("custom_post_id_multiple_4");
    $this->select("xpath=//*[@id='custom_post_id_multiple_4']/parent::td/div[1]/div/span/select", "value=" . $profileId[5]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[2]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("custom_post_id_multiple_5");
    $this->select("xpath=//*[@id='custom_post_id_multiple_5']/parent::td/div[1]/div/span/select", "value=" . $profileId[6]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("additional_custom_post_id_multiple_1");
    $this->select("xpath=//*[@id='additional_custom_post_id_multiple_1']/parent::td/div[1]/div/span/select", "value=" . $profileId[5]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("additional_custom_post_id_multiple_2");
    $this->select("xpath=//*[@id='additional_custom_post_id_multiple_2']/parent::td/div[1]/div/span/select", "value=" . $profileId[1]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("additional_custom_post_id_multiple_3");
    $this->select("xpath=//*[@id='additional_custom_post_id_multiple_3']/parent::td/div[1]/div/span/select", "value=" . $profileId[2]);

    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->click("xpath=//div[@id='registration_screen']/table[3]//tbody/tr[2]/td[2]/span/a");
    $this->waitForElementPresent("additional_custom_post_id_multiple_4");
    $this->select("xpath=//*[@id='additional_custom_post_id_multiple_4']/parent::td/div[1]/div/span/select", "value=" . $profileId[3]);

    $this->click("CIVICRM_QFID_1_is_email_confirm");
    $this->type("confirm_from_name", "TestEvent");
    $this->type("confirm_from_email", "testevent@test.com");
    $this->click("_qf_Registration_upload-bottom");

    // Wait for "saved" status msg
    $this->waitForElementPresent("_qf_Registration_upload-bottom");
    $this->waitForText('crm-notification-container', "'Online Registration' information has been saved.");

    return $this->urlArg('id');
  }

  /**
   * @param int $eventPageId
   * @param int $customId
   * @param string $firstName
   * @param string $lastName
   * @param string $participantfname
   * @param string $participantlname
   * @param $email1
   * @param $email2
   */
  public function _testEventRegistration(
    $eventPageId, $customId, $firstName, $lastName,
    $participantfname, $participantlname, $email1, $email2
  ) {
    $this->openCiviPage("event/register", "id={$eventPageId}&reset=1&action=preview", "_qf_Register_upload-bottom");
    $this->waitForElementPresent("_qf_Register_upload-bottom");
    $this->select("additional_participants", "value=1");

    $this->type("email-Primary", $email1);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("street_address-1", "Test street address");
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

    $this->waitForElementPresent("current_employer");
    $this->type("current_employer", "ABCD");
    $this->type("job_title", "Painter");
    $this->waitForElementPresent('nick_name');
    $this->type("nick_name", "Nick");
    $this->type("url-1", "http://www.test.com");

    $this->waitForElementPresent('street_address-Primary');
    $this->type("street_address-Primary", "Primary street address");
    $this->type("city-Primary", "primecity");
    $this->type("phone-Primary-1", "98667764");
    $this->type("postal_code-Primary", "6548");

    $this->type("custom_" . $customId[0], "fname_custom1");
    $this->type("custom_" . $customId[1], "mname_custom1");
    $this->type("custom_" . $customId[2], "lname_custom1");

    $this->type("middle_name", "xyz");
    $this->click("name=gender_id value=2");
    $this->waitForElementPresent('participant_role');
    $this->select("participant_role", "value=2");

    $this->click("_qf_Register_upload-bottom");
    $this->waitForElementPresent("_qf_Participant_1_next-Array");
    $this->type("email-Primary", $email2);
    $this->type("first_name", $participantfname);
    $this->type("last_name", $participantlname);
    $this->type("street_address-1", "participant street address");
    $this->type("city-1", "pune");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    $this->waitForElementPresent("current_employer");
    $this->type("current_employer", "ABCD");
    $this->type("job_title", "Potato picker");

    $this->type("custom_" . $customId[0], "participant_custom1");
    $this->type("custom_" . $customId[1], "participant_custom1");
    $this->type("custom_" . $customId[2], "participant_custom1");

    $this->waitForElementPresent('street_address-Primary');
    $this->type("street_address-Primary", "Primary street address");
    $this->type("city-Primary", "primecity");
    $this->type("phone-Primary-1", "98667764");
    $this->type("postal_code-Primary", "6548");
    $this->waitForElementPresent('nick_name');
    $this->type("nick_name", "Nick1");
    $this->type("url-1", "http://www.part.com");

    $this->clickLink("_qf_Participant_1_next-Array", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  /**
   * @param int $eventPageId
   * @param int $customId
   * @param string $firstName2
   * @param string $lastName2
   * @param string $participantfname2
   * @param $participantlname2
   * @param $email3
   * @param $email4
   */
  public function _testEventRegistrationAfterRemoving($eventPageId, $customId, $firstName2, $lastName2, $participantfname2, $participantlname2, $email3, $email4) {
    $this->openCiviPage("event/register", "id={$eventPageId}&reset=1&action=preview", "_qf_Register_upload-bottom");
    $this->select("additional_participants", "value=1");

    $this->type("email-Primary", $email4);
    $this->type("first_name", $firstName2);
    $this->type("last_name", $lastName2);
    $this->type("street_address-1", "Test street address");
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
    $this->click("name=gender_id value=2");
    $this->select("participant_role", "value=2");

    $this->click("_qf_Register_upload-bottom");
    $this->waitForElementPresent("_qf_Participant_1_next-Array");

    $this->type("email-Primary", $email3);
    $this->type("first_name", $participantfname2);
    $this->type("last_name", $participantlname2);
    $this->type("street_address-1", "participant street address");
    $this->type("city-1", "pune");
    $this->type("postal_code-1", "2354");
    $this->select("state_province-1", "value=1001");

    $this->type("current_employer", "ABCD");
    $this->type("job_title", "BATCHER");

    $this->clickLink("_qf_Participant_1_next-Array", "_qf_Confirm_next-bottom");
    $this->click("_qf_Confirm_next-bottom");
  }

  /**
   * @return array|string
   */
  public function _addEmailField() {
    //add email field in name and address profile
    $this->openCiviPage('admin/uf/group/field/add', 'reset=1&action=add&gid=1', "_qf_Field_next-bottom");
    $this->select("field_name[0]", "value=Contact");
    $this->select("field_name[1]", "value=email");
    $this->select("field_name[2]", "value=0");
    $this->click('_qf_Field_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $cfId = "";
    //check wheather webtest has created the field
    if ($this->assertElementNotContainsText('crm-notification-container', "The selected field was not added. It already exists in this profile")) {
      $this->waitForElementPresent("xpath=//div[@id='field_page']//table/tbody//tr[8]/td[9]/span/a[text()='Edit']");
      $cfId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[8]/td[9]/span/a[text()='Edit']/@href"));
      $cfId = $cfId[1];
    }
    return $cfId;
  }

  /**
   * @param int $cfId
   */
  public function _removeEmailField($cfId) {
    $this->openCiviPage("admin/uf/group/field", "action=delete&id={$cfId}");
    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

}
