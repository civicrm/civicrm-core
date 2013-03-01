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
class WebTest_Contribute_UpdateBatchPendingContributionTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testBatchUpdatePendingContribution() {
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
    $this->_testOfflineContribution();
    $this->_testOfflineContribution();
    $this->_testOfflineContribution();

    $this->open($this->sboxPath . "civicrm/contribute/search?reset=1");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Contributor");
    $this->click('contribution_status_id_2');
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('radio_ts', 'ts_all');

    $this->select('task', "label=Update Pending Contribution Status");
    $this->click("_qf_Search_next_action");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('contribution_status_id', 'label=Completed');
    $this->click('_qf_Status_next');
    $this->waitForElementPresent("_qf_Result_done");
    $this->click("_qf_Result_done");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Contributor");
    $this->click('contribution_status_id_1');
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@id='contributionSearch']/table[@class='selector']/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $expected = array(
      'Received Into' => "Deposit Bank Account",
      'Contribution Status' => "Completed",
    );   
    $this->webtestVerifyTabularData($expected);
  }

  function testParticipationAdd() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

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
    $this->open($this->sboxPath . 'civicrm/event/search?reset=1');
    $this->waitForElementPresent('_qf_Search_refresh');

    $eventName = 'Rain';
    $this->click("event_name");
    $this->type("event_name", $eventName);
    $this->typeKeys("event_name", $eventName);
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($eventName, $this->getValue("event_name"), "autocomplete expected $eventName but didn’t find it in " . $this->getValue("event_name"));
    $this->click('_qf_Search_refresh');

    $this->open($this->sboxPath . "civicrm/contribute/search?reset=1");
    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Anderson");
    $this->click('contribution_status_id_2');
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click('radio_ts', 'ts_all');

    $this->select('task', "label=Update Pending Contribution Status");
    $this->click("_qf_Search_next_action");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->select('contribution_status_id', 'label=Completed');
    $this->click('_qf_Status_next');
    $this->waitForElementPresent("_qf_Result_done");
    $this->click("_qf_Result_done");

    $this->waitForElementPresent("contribution_date_low");

    $this->type("sort_name", "Anderson");
    $this->click('contribution_status_id_1');
    $this->click("_qf_Search_refresh");

    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("xpath=//div[@id='contributionSearch']/table[@class='selector']/tbody/tr[1]/td[11]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $expected = array(
      'Received Into'        => "Deposit Bank Account",
      'Contribution Status' => "Completed",
    );   
    $this->webtestVerifyTabularData($expected);
  }

  function _addParticipant($firstName) {
    // Go directly to the URL of the screen that you will be testing (Register Participant for Event-standalone).
    $this->open($this->sboxPath . 'civicrm/participant/add?reset=1&action=add&context=standalone');

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent('_qf_Participant_upload-bottom');

    // Let's start filling the form with values.
    // Type contact last name in contact auto-complete, wait for dropdown and click first result
    $this->webtestFillAutocomplete($firstName);

    // Select event. Based on label for now.
    $this->select('event_id', "label=regexp:Rain-forest Cup Youth Soccer Tournament.");

    // Select role
    $this->click('role_id[2]');


    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event StandaloneAddTest Webtest');

    // Since we're here, let's check of screen help is being displayed properly
    $this->assertTrue($this->isTextPresent('Source for this registration (if applicable).'));

    // Select an event fee
    $this->waitForElementPresent('priceset');

    $this->click("xpath=//input[@class='form-radio']");

    // Enter amount to be paid (note: this should default to selected fee level amount, s/b fixed during 3.2 cycle)
    $this->type('total_amount', '800');

    $this->select('contribution_status_id', "label=Pending");

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Event registration for $firstName Anderson has been added"),
      "Status message didn't show up after saving!"
    );

    $this->waitForElementPresent("xpath=//div[@id='Events']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    //click through to the participant view screen
    $this->click("xpath=//div[@id='Events']//table//tbody/tr[1]/td[8]/span/a[text()='View']");
    $this->waitForElementPresent('_qf_ParticipantView_cancel-bottom');

    $this->webtestVerifyTabularData(
      array(
        'Event' => 'Rain-forest Cup Youth Soccer Tournament',
        'Participant Role' => 'Attendee',
        'Status' => 'Registered',
        'Event Source' => 'Event StandaloneAddTest Webtest',
        'Event Fees' => '$ 800.00',
      )
    );
  }

  function _testOfflineContribution() {
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName  = 'Contributor';
    $email     = $firstName . "@example.com";

    // Go directly to the URL of the screen that you will be testing (New Contribution-standalone).
    $this->open($this->sboxPath . "civicrm/contribute/add?reset=1&context=standalone");

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Contribution_upload");

    // Let's start filling the form with values.

    // create new contact using dialog
    $this->webtestNewDialogContact($firstName, "Contributor", $email);

    // select financial type
    $this->select( "financial_type_id", "value=1" );

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
    $this->waitForElementPresent("xpath=//div[@id='Contributions']//table//tbody/tr[1]/td[8]/span/a[text()='View']");

    //click through to the Membership view screen
    $this->click("xpath=//div[@id='Contributions']//table/tbody/tr[1]/td[8]/span/a[text()='View']");
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

