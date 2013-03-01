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
class WebTest_Event_ParticipantSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function _checkStrings(&$strings) {
    // search for elements
    foreach ($strings as $string) {
      $this->assertTrue($this->isTextPresent($string), "Could not find $string on page");
    }
  }

  function testParticipantSearchForm() {
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

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

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

  function testParticipantSearchForce() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1&force=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");
  }

  function testParticipantSearchEmpty() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $crypticName = "foobardoogoo_" . md5(time());
    $this->type("sort_name", $crypticName);

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array(
      'No matches found for',
      'Name or Email LIKE',
      $crypticName,
    );

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchEventName() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $eventName = "Fall Fundraiser Dinner";
    $this->type("event_name", $eventName);
    $this->type("event_id", 1);

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array(
      "Event = $eventName",
      'Select Records:',
      'Edit Search Criteria',
    );

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchEventDate() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->select('event_relative', "label=Choose Date Range");
    $this->webtestFillDate('event_start_date_low', '-2 year');
    $this->webtestFillDate('event_end_date_high', '+1 year');

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array(
      "Start Date - greater than or equal to",
      '...AND...',
      "End Date - less than or equal to",
      'Select Records:',
      'Edit Search Criteria',
    );

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchEventDateAndType() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->select('event_relative', "label=Choose Date Range");
    $this->webtestFillDate('event_start_date_low', '-2 year');
    $this->webtestFillDate('event_end_date_high', '+1 year');

    $eventTypeName = 'Fundraiser';
    $this->type("event_type", $eventTypeName);
    $this->type("event_type_id", 3);


    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array(
      "Start Date - greater than or equal to",
      '...AND...',
      "End Date - less than or equal to",
      "Event Type - $eventTypeName",
      'Select Records:',
      'Edit Search Criteria',
    );

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchCustomField() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("xpath=//div[@id='Food_Preference']/div[2]/table/tbody/tr/td[2]//label[contains(text(),'Chicken Combo')]");

    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // note since this is generated data
    // we are not sure if someone has this selection, so
    // we are not testing for an empty record set
    $stringsToCheck = array("Soup Selection = Chicken Combo");

    $this->_checkStrings($stringsToCheck);

    $this->click("xpath=//div[@id='Food_Preference']/div[2]/table/tbody/tr/td[2]//label[contains(text(),'Salmon Stew')]");
    $this->click("_qf_Search_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $stringsToCheck = array("Soup Selection = Salmon Stew");

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchForceAndView() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1&force=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");

    $this->waitForElementPresent("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_ParticipantView_cancel-bottom");

    // ensure we get to particpant view
    $stringsToCheck = array(
      "View Participant",
      "Event Registration",
      "Name",
      "Event",
      "Participant Role",
    );

    $this->_checkStrings($stringsToCheck);
  }

  function testParticipantSearchForceAndEdit() {
    $this->open($this->sboxPath);

    $this->webtestLogin();

    // visit event search page
    $this->open($this->sboxPath . "civicrm/event/search?reset=1&force=1");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // assume generated DB
    // there are participants
    $this->assertTrue($this->isTextPresent("Select Records"), "A forced event search did not return any results");

    $this->waitForElementPresent("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='Edit']");
    $this->click("xpath=id('participantSearch')/table/tbody/tr/td[11]/span/a[text()='Edit']");
    $this->waitForElementPresent("_qf_Participant_cancel-bottom");

    // ensure we get to particpant view
    $stringsToCheck = array(
      "Edit Event Registration",
      "Participant",
      "Event",
      "Participant Role",
    );

    $this->_checkStrings($stringsToCheck);
  }
}

