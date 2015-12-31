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
 * Class WebTest_Contribute_UpdateBatchPendingContributionTest
 */
class WebTest_Contribute_UpdateBatchPendingContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testBatchUpdatePendingContribution() {
    $this->webtestLogin();
    $this->_testOfflineContribution();
    $this->_testOfflineContribution();
    $this->_testOfflineContribution();

    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", "Individual");
    $this->multiselect2('contribution_status_id', array("Pending"));
    $this->clickLink("_qf_Search_refresh");

    $this->click('radio_ts', 'ts_all');
    $this->waitForAjaxContent();
    $this->select('task', "label=Update pending contribution status");
    $this->waitForAjaxContent();
    $this->select('contribution_status_id', 'label=Completed');
    $this->waitForAjaxContent();
    $this->click('_qf_Status_next');
    $this->waitForElementPresent("_qf_Result_done");
    $this->click("_qf_Result_done");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Individual");
    $this->multiselect2('contribution_status_id', array("Completed"));
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    $expected = array(
      'Received Into' => "Deposit Bank Account",
      'Contribution Status' => "Completed",
    );

    $this->webtestVerifyTabularData($expected);
  }

  public function testParticipationAdd() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Adding contact with randomized first name (so we can then select that contact when creating event registration)
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, 'Anderson', TRUE);
    $sortName1 = "Anderson, $firstName1";
    $this->_addParticipant($firstName1);

    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, 'Anderson', TRUE);
    $sortName2 = "Anderson, $firstName2";
    $this->_addParticipant($firstName2);

    // Search the participants
    $this->openCiviPage("event/search", "reset=1", '_qf_Search_refresh');

    $eventName = 'Rain';
    $this->select2("event_id", $eventName);
    $this->click('_qf_Search_refresh');

    $this->openCiviPage("contribute/search", "reset=1", "contribution_date_low");

    $this->type("sort_name", "Anderson");
    $this->multiselect2('contribution_status_id', array("Pending"));
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('radio_ts', 'ts_all');

    $this->select('task', "label=Update pending contribution status");
    $this->waitForElementPresent("_qf_Search_next_action");
    $this->click("_qf_Search_next_action");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('contribution_status_id', 'label=Completed');
    $this->click('_qf_Status_next');
    $this->waitForElementPresent("_qf_Result_done");
    $this->click("_qf_Result_done");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Anderson");
    $this->multiselect2('contribution_status_id', array("Completed"));
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//table[@class='selector row-highlight']/tbody/tr[1]/td[10]/span//a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");
    $expected = array(
      'Received Into' => "Deposit Bank Account",
      'Contribution Status' => "Completed",
    );

    $this->webtestVerifyTabularData($expected);
  }

  /**
   * @param string $firstName
   */
  public function _addParticipant($firstName) {

    $this->openCiviPage("participant/add", "reset=1&action=add&context=standalone", '_qf_Participant_upload-bottom');

    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select2('event_id', "Rain-forest Cup Youth Soccer Tournament");

    // Select role
    $this->multiselect2('role_id', array('Volunteer'));

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

    $this->select('contribution_status_id', "label=Pending");

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->waitForText("crm-notification-container", "Event registration for $firstName Anderson has been added");

    $this->waitForElementPresent("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->click("xpath=//form[@class='CRM_Event_Form_Search crm-search-form']/table//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
        'Fees' => '$ 800.00',
      )
    );
  }

  public function _testOfflineContribution() {
    $this->openCiviPage("contribute/add", "reset=1&context=standalone", "_qf_Contribution_upload");

    // create new contact using dialog
    $this->createDialogContact();

    // select financial type
    $this->select("financial_type_id", "value=1");

    //Contribution status
    $this->select("contribution_status_id", "label=Pending");

    // total amount
    $this->type("total_amount", "100");

    // Clicking save.
    $this->click("_qf_Contribution_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The contribution record has been saved."), "Status message didn't show up after saving!");

    // verify if Membership is created
    $this->waitForElementPresent("xpath=//div[@class='view-content']//table[2]//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@class='view-content']//table[2]/tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ContributionView_cancel-bottom");

    $expected = array(
      'Financial Type' => 'Donation',
      'Total Amount' => '100.00',
      'Contribution Status' => 'Pending',
    );
    foreach ($expected as $label => $value) {
      $this->verifyText("xpath=id('ContributionView')/div[2]/table[1]/tbody//tr/td[1][text()='$label']/../td[2]", preg_quote($value));
    }
  }

}
