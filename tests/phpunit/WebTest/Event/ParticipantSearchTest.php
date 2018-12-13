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
 * Class WebTest_Event_ParticipantSearchTest
 */
class WebTest_Event_ParticipantSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * @param $strings
   */
  public function _checkStrings(&$strings) {
    // search for elements
    foreach ($strings as $string) {
      $this->assertTrue($this->isTextPresent($string), "Could not find $string on page");
    }
  }

  public function testParticipantSearchForm() {
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");

    $stringsToCheck = array(
      'Participant Name',
      'Event Name',
      'Event Dates',
      'Participant Status',
      'Participant Role',
      'Participant is a Test?',
      'Participant is Pay Later?',
      'Fee Level',
      'Fee Amount',
      // check that the custom data is also there
      'Food Preference',
      'Soup Selection',
    );
    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchForce() {
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1&force=1");

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");
  }

  public function testParticipantSearchEmpty() {
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");

    $crypticName = "foobardoogoo_" . md5(time());
    $this->type("sort_name", $crypticName);

    $this->clickLink("_qf_Search_refresh");

    $stringsToCheck = array(
      'No matches found for',
      'Name or Email LIKE',
      $crypticName,
    );

    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchEventName() {
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");
    $this->waitForElementPresent('_qf_Search_refresh');

    $eventName = "Rain-forest Cup Youth Soccer Tournament";
    $this->waitForElementPresent("event_id");
    $this->select2("event_id", $eventName);

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array(
      "Event = $eventName",
      'Select Records:',
      'Edit Search Criteria',
    );
    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchEventDate() {

    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");

    $this->select('event_relative', "label=Choose Date Range");
    $this->webtestFillDate('event_start_date_low', '-2 year');
    $this->webtestFillDate('event_end_date_high', '+1 year');

    $this->clickLink("_qf_Search_refresh");

    $stringsToCheck = array(
      "Start Date - greater than or equal to",
      '...AND...',
      "End Date - less than or equal to",
      'Select Records:',
      'Edit Search Criteria',
    );

    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchEventDateAndType() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");

    $eventTypeName = 'Fundraiser';
    $this->waitForElementPresent('event_type_id');
    $this->select2("event_type_id", $eventTypeName);
    $this->waitForElementPresent('event_relative');
    $this->select('event_relative', "label=Choose Date Range");
    $this->webtestFillDate('event_start_date_low', '-2 year');
    $this->webtestFillDate('event_end_date_high', '+1 year');

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//form[@id='Search']/div[3]/div/div[1]/div");

    $stringsToCheck = array(
      "Start Date - greater than or equal to",
      '...AND...',
      "End Date - less than or equal to",
      "Event Type = $eventTypeName",
      'Select Records:',
      'Edit Search Criteria',
    );

    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchCustomField() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1");

    $this->select("css=select[data-crm-custom='Food_Preference:Soup_Selection']", 'Chicken Combo');

    $this->clickLink("_qf_Search_refresh");

    // note since this is generated data
    // we are not sure if someone has this selection, so
    // we are not testing for an empty record set
    $stringsToCheck = array("Soup Selection In Chicken Combo");

    $this->_checkStrings($stringsToCheck);

    $this->select("css=select[data-crm-custom='Food_Preference:Soup_Selection']", 'Salmon Stew');
    $this->clickLink("_qf_Search_refresh");

    $stringsToCheck = array("Soup Selection In Salmon Stew");

    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchForceAndView() {

    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1&force=1");

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");

    $this->waitForElementPresent("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForTextPresent("View Event Registration");

    // ensure we get to particpant view
    $stringsToCheck = array(
      "Name",
      "Event",
      "Participant Role",
    );

    $this->_checkStrings($stringsToCheck);
  }

  public function testParticipantSearchForceAndEdit() {

    $this->webtestLogin();

    // visit event search page
    $this->openCiviPage("event/search", "reset=1&force=1");

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");

    $this->waitForElementPresent("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='Edit']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='Edit']");
    $this->waitForElementPresent("xpath=//button//span[contains(text(),'Save')]");

    // ensure we get to particpant view
    $stringsToCheck = array(
      "Participant",
      "Event",
      "Participant Role",
    );

    $this->_checkStrings($stringsToCheck);
  }

}
