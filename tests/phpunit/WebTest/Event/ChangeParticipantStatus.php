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
 * Class WebTest_Event_ChangeParticipantStatus
 */
class WebTest_Event_ChangeParticipantStatus extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testParticipationAdd() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, 'Anderson', TRUE);
    $sortName1 = "Anderson, $firstName1";
    $this->addParticipant($firstName1);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, 'Anderson', TRUE);
    $sortName2 = "Anderson, $firstName2";
    $this->addParticipant($firstName2);

    // Search the participants
    $this->openCiviPage("event/search", "reset=1", '_qf_Search_refresh');

    $eventName = 'Rain-forest Cup Youth Soccer Tournament';
    $this->select2("event_id", $eventName);
    $this->click('_qf_Search_refresh');

    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName1']");
    $id1 = $this->getAttribute("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName1']/../../td[1]/input@id");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName1']/../../td[1]/");
    $this->click($id1);

    $id2 = $this->getAttribute("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName2']/../../td[1]/input@id");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName2']/../../td[1]/");
    $this->click($id2);

    // Change participant status for selected participants
    $this->select('task', "label=Participant status - change (emails sent)");
    $this->waitForElementPresent('_qf_ParticipantStatus_next');

    $this->select('status_change', "label=Attended");
    $this->clickLink('_qf_ParticipantStatus_next');
    $this->assertTrue($this->isTextPresent('The updates have been saved.'),
      "Status message didn't show up after saving!"
    );

    // Verify the changed status
    $this->openCiviPage("event/search", "reset=1", '_qf_Search_refresh');
    $this->type('sort_name', $firstName1);
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName1']");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName1']/../../td[11]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');
    $this->webtestVerifyTabularData(array('Status' => 'Attended'));

    $this->openCiviPage("event/search", "reset=1", '_qf_Search_refresh');
    $this->type('sort_name', $firstName2);
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName2']");
    $this->click("xpath=//div[@id='participantSearch']/table/tbody//tr/td[3]/a[text()='$sortName2']/../../td[11]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');
    $this->webtestVerifyTabularData(array('Status' => 'Attended'));
  }

  /**
   * @param string $firstName
   */
  public function addParticipant($firstName) {
    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", '_qf_Participant_upload-bottom');

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
    // Select 'Record Payment'
    $this->click('record_contribution');

    // Enter amount to be paid (note: this should default to selected fee level amount, s/b fixed during 3.2 cycle)
    $this->type('total_amount', '800');

    // Select payment method = Check and enter chk number
    $this->select('payment_instrument_id', 'value=4');
    $this->waitForElementPresent('check_number');
    $this->type('check_number', '1044');

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Event registration for $firstName Anderson has been added"),
      "Status message didn't show up after saving!"
    );

    $this->waitForElementPresent("xpath=//*[@id='Search']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->click("xpath=//*[@id='Search']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
      )
    );
    $this->verifyText("xpath=//td[text()='Selections']/following-sibling::td//div", preg_quote('Event Total: $ 800.00'));
  }

}
