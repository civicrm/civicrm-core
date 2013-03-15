<?php
require_once 'CiviTest/CiviSeleniumTestCase.php';
class WebTest_Event_EventListingTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testEventListing() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Log in using webtestLogin() method
    $this->webtestLogin(TRUE);

    //Closed Event
    $eventTitle1 = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $this->_testCreateEvent($eventTitle1, '-12 months', '-3 months');

    //Closed Event with current date as end date
    $eventTitle2 = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $this->_testCreateEvent($eventTitle2, '-12 months', 'now');

    //Ongoing Event with start date as yesterday
    $eventTitle3 = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $this->_testCreateEvent($eventTitle3, '-1 day', '+12 months');

    //Upcomming Event
    $eventTitle4 = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $this->_testCreateEvent($eventTitle4, '+6 months', '+12 months');

    //Upcomming Event
    $eventTitle5 = 'My Conference - ' . substr(sha1(rand()), 0, 7);
    $this->_testCreateEvent($eventTitle5, '+3 months', '+6 months');

    //go to manage event and check for presence of ongoing and
    //upcomming events
    $this->openCiviPage("event/manage", "reset=1");
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input",$eventTitle1);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertFalse($this->isTextPresent("{$eventTitle1}"));
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input",$eventTitle2);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertFalse($this->isTextPresent("{$eventTitle2}"));
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input",$eventTitle3);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("{$eventTitle3}"));
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input",$eventTitle4);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("{$eventTitle4}"));
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input",$eventTitle5);
    $this->click("_qf_SearchEvent_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("{$eventTitle5}"));
    $this->type("xpath=//div[@class='crm-block crm-form-block crm-event-searchevent-form-block']/table/tbody/tr/td/input","");

    //check if closed Event is present
    $this->waitForElementPresent('CIVICRM_QFID_1_eventsByDates');
    $this->click('CIVICRM_QFID_1_eventsByDates');
    $this->webtestFillDate("end_date", "now");
    $this->waitForElementPresent('_qf_SearchEvent_refresh');
    $this->click('_qf_SearchEvent_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("{$eventTitle1}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle2}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle3}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle4}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle5}"));

    //go to ical and check for presence of ongoing and upcomming events
    $this->openCiviPage("event/ical", "reset=1&page=1&html=1", NULL);
    $this->assertFalse($this->isTextPresent("{$eventTitle1}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle2}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle3}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle4}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle5}"));

    //go to block listing to enable Upcomming Events Block
    // you need to be admin user for below operation
    $this->openCiviPage("logout", "reset=1", NULL);
    $this->webtestLogin(TRUE);

    $this->open($this->sboxPath . 'admin/structure/block/manage/civicrm/6/configure');
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-pages', 'civicrm/dashboard');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($this->sboxPath . 'admin/structure/block');
    $this->select('edit-blocks-civicrm-6-region', 'value=sidebar_second');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("The block settings have been updated.");

    //go to civicrm home and check for presence of upcomming events
    $this->openCiviPage("dashboard", "reset=1");
    $this->assertFalse($this->isTextPresent("{$eventTitle1}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle2}"));
    $this->assertFalse($this->isTextPresent("{$eventTitle3}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle4}"));
    $this->assertTrue($this->isTextPresent("{$eventTitle5}"));

    //go to block listing to disable Upcomming Events Block
    $this->open($this->sboxPath . 'admin/structure/block');
    $this->select('edit-blocks-civicrm-6-region', 'value=-1');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("The block settings have been updated.");
  }

  function _testCreateEvent($eventTitle, $startdate, $enddate) {
    // Go directly to the URL of the screen that you will be testing (New Event).
    $this->openCiviPage("event/add", "reset=1&action=add");

    // $eventTitle = 'My Conference - '.substr(sha1(rand()), 0, 7);
    $eventDescription = "Here is a description for this conference.";

    // As mentioned before, waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_EventInfo_upload-bottom");

    // Let's start filling the form with values.
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
    if ($startdate) {
      $this->webtestFillDateTime("start_date", $startdate);
    }

    if ($enddate == 'now') {
      // to avoid time zone difference problem between selenium-test & drupal
      $this->webtestFillDate("end_date", $enddate);
    }
    elseif ($enddate) {
      $this->webtestFillDateTime("end_date", $enddate);
    }

    $this->type("max_participants", "6");
    $this->click("is_public");
    $this->click("_qf_EventInfo_upload-bottom");

    // Wait for Location tab form to load
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Location_upload_done-bottom");

    $this->click("_qf_Location_upload_done-bottom");

    // Wait for "saved" status msg
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForTextPresent("'Location' information has been saved.");
  }
}


