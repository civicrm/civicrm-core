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
 * Class WebTest_Contact_AdvancedSearchedRelatedContactTest
 */
class WebTest_Contact_AdvancedSearchedRelatedContactTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testSearchRelatedContact() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Use default payment processor
    $processorName = 'Test Processor';
    $paymentProcessorId = $this->webtestAddPaymentProcessor($processorName);

    $this->openCiviPage('event/add', 'reset=1&action=add');

    $eventTitle = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";
    $this->_testAddEventInfo($eventTitle, $eventDescription);

    $streetAddress = "100 Main Street";
    $this->_testAddLocation($streetAddress);

    $this->_testAddFees(FALSE, FALSE, $processorName);
    $this->openCiviPage('event/manage', 'reset=1');
    $this->type('title', $eventTitle);
    $this->click('_qf_SearchEvent_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $Id = explode('-', $this->getAttribute("xpath=//div[@id='event_status_id']/div[2]/table/tbody/tr@id"));
    $eventId = $Id[1];

    $params = array(
      'label_a_b' => 'Owner of ' . rand(),
      'label_b_a' => 'Belongs to ' . rand(),
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Individual',
      'description' => 'The company belongs to this individual',
    );

    $this->webtestAddRelationshipType($params);
    $relType = $params['label_b_a'];

    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");
    $sortName = "Anderson, $firstName";
    $displayName = "$firstName Anderson";

    //create a New Individual
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Andy", "$firstName1@andy.name");
    $sortName1 = "Andy, $firstName1";
    $displayName1 = "$firstName1 Andy";
    $this->_testAddRelationship($sortName1, $sortName, $relType);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "David", "$firstName2@andy.name");
    $sortName2 = "David, $firstName2";
    $displayName2 = "$firstName2 David";
    $this->_testAddRelationship($sortName2, $sortName, $relType);

    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type("sort_name", $sortName);
    $this->select("contact_type", "value=Individual");
    $this->clickLink("_qf_Basic_refresh", "//table[@class='selector row-highlight']/tbody//tr/td[11]/span/a[text()='View']", FALSE);

    // click through to the Relationship view screen
    $this->click("xpath=//table[@class='selector row-highlight']/tbody//tr/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=li#tab_participant a");

    // wait for add Event link
    $this->waitForElementPresent("link=Add Event Registration");
    $this->click("link=Add Event Registration");
    $this->waitForText("s2id_event_id", "- select event -");
    $this->select2("event_id", $eventTitle);
    $this->click("_qf_Participant_upload-bottom");
    $this->waitForElementPresent("link=Add Event Registration");

    $this->openCiviPage('contact/search/advanced', 'reset=1');

    $this->waitForElementPresent("sort_name");
    $this->type("sort_name", $sortName);
    $this->click('_qf_Advanced_refresh');
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    $this->waitForElementPresent('search-status');
    $this->assertElementContainsText('search-status', '1 Contact');

    $this->click('css=div.crm-advanced_search_form-accordion div.crm-accordion-header');
    $this->waitForElementPresent("component_mode");
    $this->select("component_mode", "label=Related Contacts");
    $this->waitForElementPresent("display_relationship_type");
    $this->select("display_relationship_type", $relType);
    $this->click('_qf_Advanced_refresh');
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    $this->waitForElementPresent('search-status');
    $this->assertElementContainsText('search-status', '2 Contact');

    $this->select("task", "label=Group - add contacts");

    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click('CIVICRM_QFID_1_group_option');

    $groupName = "Group " . substr(sha1(rand()), 0, 7);
    $this->type('title', $groupName);

    $this->click("_qf_AddToGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "Added Contacts to " . $groupName);
    $this->waitForText('crm-notification-container', '2 contacts added to group');
    $this->_testSearchResult($relType);
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
    $streetAddress = "100 Main Street";
    $this->type("address_1_street_address", $streetAddress);
    $this->type("address_1_city", "San Francisco");
    $this->type("address_1_postal_code", "94117");
    $this->select('address_1_country_id', 'UNITED STATES');
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
   * @param int|array $processorIDs
   */
  public function _testAddFees($discount = FALSE, $priceSet = FALSE, $processorIDs) {
    // Go to Fees tab
    $this->click("link=Fees");
    $this->waitForElementPresent("_qf_Fee_upload-bottom");
    $this->click("CIVICRM_QFID_1_is_monetary");
    $this->select2('payment_processor', $processorIDs, TRUE);
    $this->select("financial_type_id", "value=4");
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
   * @param string $ContactName
   * @param string $relatedName
   * @param $relType
   */
  public function _testAddRelationship($ContactName, $relatedName, $relType) {

    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type("sort_name", $ContactName);
    $this->select("contact_type", "value=Individual");
    $this->clickLink("_qf_Basic_refresh", "//div[@class='crm-search-results']/table[@class='selector row-highlight']/tbody/tr/", FALSE);

    // click through to the Contribution view screen
    $this->click("xpath=//div[@class='crm-search-results']/table[@class='selector row-highlight']/tbody/tr/td[11]//span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "label={$relType}");

    //fill in the individual
    $this->waitForElementPresent("related_contact_id");
    $this->select2('related_contact_id', $relatedName, TRUE);

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    //$this->click("_qf_Relationship_upload");
    $this->click('_qf_Relationship_upload-bottom');
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']");

    //check the status message
    $this->waitForText('crm-notification-container', "Relationship created.");
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']//table/tbody//tr/td[9]/span/a[text()='View']");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']//table/tbody//tr/td[9]/span/a[text()='View']");

    $this->waitForElementPresent("xpath=//table[@class='crm-info-panel']");
    $this->webtestVerifyTabularData(
      array(
        'Description' => $description,
        'Status' => 'Enabled',
      )
    );
    $this->assertElementContainsText("xpath=//table[@class='crm-info-panel']", $relType);
  }

  /**
   * @param $relType
   */
  public function _testSearchResult($relType) {

    //search related contact using Advanced Search
    $this->openCiviPage('contact/search/advanced', 'reset=1', '_qf_Advanced_refresh');
    $this->select("component_mode", "label=Related Contacts");
    $this->select("display_relationship_type", "label={$relType}");
    $this->click("CiviEvent");
    $this->waitForElementPresent("event_type_id");
    $this->select2("event_type_id", "Conference");
    $this->click("_qf_Advanced_refresh");
    $this->waitForElementPresent("xpath=id('search-status')");
    $this->assertElementContainsText('search-status', '2 Contacts');
  }

  public function testAdvanceSearchForLog() {
    $this->webtestLogin();

    $Pdate = date('F jS, Y h:i:s A', mktime(0, 0, 0, date('m'), date('d') - 1, date('Y')));
    $Ndate = date('F jS, Y h:i:s A', mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')));

    //create a contact and return the contact id
    $firstNameSoft = "John_" . substr(sha1(rand()), 0, 5);
    $lastNameSoft = "Doe_" . substr(sha1(rand()), 0, 5);
    $this->webtestAddContact($firstNameSoft, $lastNameSoft);
    $cid = $this->urlArg('cid');

    //advance search for created contacts
    $this->openCiviPage('contact/search/advanced', 'reset=1', '_qf_Advanced_refresh');
    $this->type('sort_name', $lastNameSoft . ', ' . $firstNameSoft);
    $this->click('changeLog');
    $this->waitForElementPresent("log_date_low");
    $this->select("log_date_relative", "value=0");
    $this->webtestFillDate('log_date_low', "-1 day");
    $this->webtestFillDate('log_date_high', "+1 day");
    $this->click('_qf_Advanced_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue(TRUE, 'greater than or equal to "{$Pdate}" AND less than or equal to "{$Ndate}"');
    $value = "$lastNameSoft, $firstNameSoft";
    $this->waitForElementPresent("xpath= id('rowid{$cid}')/td[3]/a");
    $this->verifyText("xpath= id('rowid{$cid}')/td[3]/a", preg_quote($value));

  }

}
