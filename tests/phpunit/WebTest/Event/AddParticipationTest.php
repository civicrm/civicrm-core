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
 * Class WebTest_Event_AddParticipationTest
 */
class WebTest_Event_AddParticipationTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testEventParticipationAdd() {
    $this->webtestLogin();

    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', TRUE);
    $displayName = "$firstName Anderson";

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", "_qf_Participant_upload-bottom");

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select2('event_id', "Rain-forest Cup Youth Soccer Tournament");

    // Select role
    $this->multiselect2('role_id', array('Volunteer'));

    // Choose Registration Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('register_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event StandaloneAddTest Webtest');

    // Since we're here, let's check of screen help is being displayed properly
    $this->assertTrue($this->isTextPresent('Source for this registration (if applicable).'));

    // Select an event fee
    $this->waitForElementPresent('priceset');

    $this->click("xpath=//input[@class='crm-form-radio']");

    // Enter amount to be paid (note: this should default to selected fee level amount, s/b fixed during 3.2 cycle)
    $this->type('total_amount', '800');

    // Select payment method = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1044');

    // go for the chicken combo (obviously)
    //      $this->click('CIVICRM_QFID_chicken_Chicken');

    $this->waitForElementPresent('send_receipt');
    $this->assertTrue($this->isChecked("send_receipt"), 'Send Confirmation and Receipt checkbox should be checked by default but is not checked.');

    // Clicking save.
    $this->clickLink('_qf_Participant_upload-bottom');

    // Is status message correct?
    $this->checkCRMAlert("Event registration for $displayName has been added");

    $this->waitForElementPresent("xpath=//*[@id='Search']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->clickAjaxLink("xpath=//*[@id='Search']/table/tbody/tr[1]/td[8]/span/a[text()='View']", '_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
        'Fees' => '$ 800.00',
      )
    );
    // check contribution record as well
    //click through to the contribution view screen
    $this->clickAjaxLink("xpath=id('ParticipantView')/div[2]/table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']", '_qf_ContributionView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'From' => $displayName,
        'Financial Type' => 'Event Fee',
        'Total Amount' => '$ 800.00',
        'Contribution Status' => 'Completed',
        'Payment Method' => 'Check',
        'Check Number' => '1044',
      )
    );
  }

  public function testEventParticipationAddWithMultipleRoles() {
    $this->webtestLogin();

    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, 'Anderson', TRUE);
    $displayName = "$firstName Anderson";

    // add custom data for participant role
    $this->openCiviPage("admin/custom/group", "reset=1");

    //add new custom data
    $this->clickLink("//a[@id='newCustomDataGroup']/span");

    //fill custom group title
    $customGroupTitle = 'custom_' . substr(sha1(rand()), 0, 7);
    $this->click('title');
    $this->type('title', $customGroupTitle);

    //custom group extends
    $this->click('extends[0]');
    $this->select('extends[0]', 'value=ParticipantRole');

    $this->click('extends[1][]');
    $this->select('extends[1][]', 'value=2');

    $this->click("//option[@value='Contact']");
    $this->clickLink('_qf_Group_next');

    //Is custom group created?
    $this->checkCRMAlert("Your custom field set '$customGroupTitle' has been added. You can add custom fields now.");

    //add custom field - alphanumeric checkbox
    $this->waitForAjaxContent();
    $checkboxFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->click('label');
    $this->type('label', $checkboxFieldLabel);
    $this->click('data_type[1]');
    $this->select('data_type[1]', 'value=CheckBox');
    $this->click("//option[@value='CheckBox']");
    $checkboxOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $checkboxOptionLabel1);
    $this->type('option_value_1', '1');
    $checkboxOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $checkboxOptionLabel2);
    $this->type('option_value_2', '2');
    $this->click('link=another choice');
    $checkboxOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $checkboxOptionLabel3);
    $this->type('option_value_3', '3');

    //enter options per line
    $this->type('options_per_line', '2');

    //enter pre help message
    $this->type('help_pre', 'this is field pre help');

    //enter post help message
    $this->type('help_post', 'this field post help');

    //Is searchable?
    $this->click('is_searchable');

    //clicking save
    $this->click('_qf_Field_done-bottom');

    //Is custom field created?
    $this->checkCRMAlert("Custom field '$checkboxFieldLabel' has been saved.");
    $this->waitForAjaxContent();

    //create another custom field - Integer Radio
    $this->clickPopupLink('newCustomField', '_qf_Field_cancel');
    $this->click('data_type[0]');
    $this->select('data_type[0]', 'value=1');
    $this->click("//option[@value='1']");
    $this->click('data_type[1]');
    $this->select('data_type[1]', 'value=Radio');
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'custom_field' . substr(sha1(rand()), 0, 4);
    $this->type('label', $radioFieldLabel);
    $radioOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_1', $radioOptionLabel1);
    $this->type('option_value_1', '1');
    $radioOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_2', $radioOptionLabel2);
    $this->type('option_value_2', '2');
    $this->click('link=another choice');
    $radioOptionLabel3 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type('option_label_3', $radioOptionLabel3);
    $this->type('option_value_3', '3');

    //select options per line
    $this->type('options_per_line', '3');

    //enter pre help msg
    $this->type('help_pre', 'this is field pre help');

    //enter post help msg
    $this->type('help_post', 'this is field post help');

    //Is searchable?
    $this->click('is_searchable');

    //clicking save
    $this->clickAjaxLink('_qf_Field_done-bottom');

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", "_qf_Participant_upload-bottom");

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select2('event_id', "Rain-forest Cup Youth Soccer Tournament");

    // Select roles
    $this->multiselect2('role_id', array('Volunteer', 'Host'));

    $this->waitForElementPresent("xpath=//div[@class='custom-group custom-group-$customGroupTitle crm-accordion-wrapper collapsed']");
    $this->click("xpath=//div[@class='custom-group custom-group-$customGroupTitle crm-accordion-wrapper collapsed']//div[1]");
    $this->click("xpath=//div[@class='custom-group custom-group-$customGroupTitle crm-accordion-wrapper']//div[2]//table//tbody//tr[2]//td[2]//table//tbody//tr[1]//td[1]//label");
    $this->click("xpath=//div[@class='custom-group custom-group-$customGroupTitle crm-accordion-wrapper']//div[2]//table//tbody//tr[4]//td[2]//table//tbody//tr[1]//td[1]//label");

    // Choose Registration Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('register_date', 'now');
    $today = date('F jS, Y', strtotime('now'));
    // May 5th, 2010

    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event StandaloneAddTest Webtest');

    // Since we're here, let's check of screen help is being displayed properly
    $this->assertTrue($this->isTextPresent('Source for this registration (if applicable).'));

    // Select an event fee
    $this->waitForElementPresent("xpath=//div[@class='crm-event-form-fee-block']");

    $this->click("xpath=//input[@class='crm-form-radio']");

    // Enter amount to be paid (note: this should default to selected fee level amount, s/b fixed during 3.2 cycle)
    $this->waitForElementPresent('total_amount');
    $this->type('total_amount', '800');

    // Select payment method = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1044');

    // Clicking save.
    $this->clickLink('_qf_Participant_upload-bottom');

    // Is status message correct?
    $this->checkCRMAlert("Event registration for $displayName has been added");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table/tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->clickAjaxLink("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table/tbody/tr[1]/td[8]/span/a[text()='View']", '_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee, Volunteer, Host',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
        'Fees' => '$ 800.00',
      )
    );

    $this->assertTrue($this->isTextPresent("$customGroupTitle"));
    $this->assertTrue($this->isTextPresent("$checkboxOptionLabel1"));
    $this->assertTrue($this->isTextPresent("$radioOptionLabel1"));

    // check contribution record as well
    //click through to the contribution view screen
    $this->clickAjaxLink("xpath=id('ParticipantView')/div[2]/table[@class='selector row-highlight']/tbody/tr[1]/td[8]/span/a[text()='View']", '_qf_ContributionView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'From' => $displayName,
        'Financial Type' => 'Event Fee',
        'Contribution Status' => 'Completed',
        'Payment Method' => 'Check',
        'Check Number' => '1044',
      )
    );
    $this->verifyText("xpath=//table/tbody/tr/td[text()='Total Amount']/following-sibling::td/strong", preg_quote('$ 800.00'));
  }

  public function testEventAddMultipleParticipants() {
    $this->webtestLogin();

    $processorId = $this->webtestAddPaymentProcessor();

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone&mode=test&eid=3");

    $contacts = array();

    $this->assertTrue($this->isTextPresent("New Event Registration"), "Page title 'New Event Registration' missing");
    $this->assertTrue($this->isTextPresent("A TEST transaction will be submitted"), "test mode status 'A TEST transaction will be submitted' missing");
    $contacts[] = $this->_fillParticipantDetails($processorId);
    $this->clickLink('_qf_Participant_upload_new-bottom');

    $this->assertTrue($this->isTextPresent("New Event Registration"), "Page title 'New Event Registration' missing");
    $this->assertTrue($this->isTextPresent("A TEST transaction will be submitted"), "test mode status 'A TEST transaction will be submitted' missing");
    $contacts[] = $this->_fillParticipantDetails($processorId);
    $this->clickLink('_qf_Participant_upload_new-bottom');

    //searching the paricipants
    $this->openCiviPage("event/search", "reset=1");
    $this->type('sort_name', 'Individual');
    $eventName = "Rain-forest Cup Youth Soccer Tournament";
    $this->select2("event_id", $eventName, FALSE, FALSE);
    $this->check('participant_test');
    $this->clickLink("_qf_Search_refresh", "participantSearch");

    //verifying the registered participants
    $status = CRM_Core_TestEntity::appendTestText("Registered");

    foreach ($contacts as $contact) {
      $this->verifyText("xpath=//div[@id='participantSearch']//table//tbody//tr/td[@class='crm-participant-sort_name']/a[text()='{$contact['sort_name']}']/../../td[9]", preg_quote($status));
      $this->verifyText("xpath=//div[@id='participantSearch']//table//tbody//tr/td[@class='crm-participant-sort_name']/a[text()='{$contact['sort_name']}']/../../td[4]/a", preg_quote($eventName));
    }
  }

  public function testAjaxCustomGroupLoad() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    $customSets = array(
      array(
        'entity' => 'ParticipantEventName',
        'subEntity' => 'Fall Fundraiser Dinner',
        'triggerElement' => array(
          'name' => "event_id",
          'type' => "select2",
        ),
      ),
      array(
        'entity' => 'ParticipantRole',
        'subEntity' => 'Attendee',
        'triggerElement' => array(
          'name' => 'role_id',
          'type' => "select",
        ),
      ),
    );
    $pageUrl = array('url' => "participant/add", 'args' => "reset=1&action=add&context=standalone");
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl, TRUE);
  }

  /**
   * Webtest for CRM-10983
   */
  public function testCheckDuplicateCustomDataLoad() {
    $this->webtestLogin();

    $customSets = array(
      array(
        'entity' => 'ParticipantEventType',
        'subEntity' => '- Any -',
        'triggerElement' => array(
          'name' => "event_id",
          'type' => "select",
        ),
      ),
      array(
        'entity' => 'ParticipantEventName',
        'subEntity' => '- Any -',
        'triggerElement' => array(
          'name' => "event_id",
          'type' => "select",
        ),
      ),
      array(
        'entity' => 'ParticipantEventName',
        'subEntity' => 'Rain-forest Cup Youth Soccer Tournament',
        'triggerElement' => array(
          'name' => "event_id",
          'type' => "select",
        ),
      ),
      array(
        'entity' => 'ParticipantRole',
        'subEntity' => '- Any -',
        'triggerElement' => array(
          'type' => "checkbox",
        ),
      ),
      array(
        'entity' => 'ParticipantRole',
        'subEntity' => 'Volunteer',
        'triggerElement' => array(
          'type' => "checkbox",
        ),
      ),
    );

    $return = $this->addCustomGroupField($customSets);

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", "_qf_Participant_upload-bottom");

    // Select event.
    $this->select2('event_id', "Rain-forest Cup Youth Soccer Tournament");

    // Select role.
    $this->multiselect2('role_id', array('Volunteer'));

    foreach ($return as $values) {
      foreach ($values as $entityType => $customData) {
        //checking for duplicate custom data present or not
        $this->assertElementPresent("xpath=//div[@class='custom-group custom-group-{$customData['cgtitle']} crm-accordion-wrapper ']");
        $this->assertEquals(1, $this->getXpathCount("//div[@class='custom-group custom-group-{$customData['cgtitle']} crm-accordion-wrapper ']"));
      }
    }
  }

  /**
   * @param int $processorId
   */
  public function _fillParticipantDetails($processorId) {
    $contact = $this->createDialogContact();

    $event_id = $this->getAttribute("xpath=//*[@id='event_id']@value");
    //check if it is the selected event
    $this->assertEquals($event_id, 3);
    $this->select("role_id", "value=1");
    $this->webtestAddCreditCardDetails();
    $this->webtestAddBillingDetails();
    return $contact;
  }

}
