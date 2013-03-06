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
class WebTest_Activity_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testStandaloneActivityAdd() {

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

    // Adding Anderson, Anthony and Summerson, Samuel for testStandaloneActivityAdd test
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$firstName1", "Anderson", $firstName1 . "@anderson.com");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$firstName2", "Summerson", $firstName2 . "@summerson.com");

    // Go directly to the URL of the screen that you will be testing (New Activity-standalone).
    $this->openCiviPage("activity", "reset=1&action=add&context=standalone", "_qf_Activity_upload");

    // Let's start filling the form with values.

    // Select one of the options in Activity Type selector. Use option value, not label - since labels can be translated and test would fail
    $this->select("activity_type_id", "value=1");

    // We're filling in ajaxiefied  "With Contact" field:
    // We can not use id as selector for these input widgets. Use css selector, starting with the table row containing this field (which will have a unique class)
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1");
    $this->type("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1", "$firstName1");
    $this->typeKeys("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1", "$firstName1");

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up (span with delete token class indicates that it's present)...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-target_contact_id td ul li span.token-input-delete-token-facebook");

    //..and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText('css=tr.crm-activity-form-block-target_contact_id td ul li.token-input-token-facebook', "Anderson, $firstName1", 'Contact not found in line ' . __LINE__);

    // Now we're doing the same for "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
    $this->type("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", "$firstName2");
    $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", "$firstName2");

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText('css=tr.crm-activity-form-block-assignee_contact_id td ul li.token-input-token-facebook', "Summerson, $firstName2", 'Contact not found in line ' . __LINE__);

    // Since we're here, let's check of screen help is being displayed properly
    $this->assertElementContainsText('css=tr.crm-activity-form-block-assignee_contact_id td span.description', 'You can optionally assign this activity to someone', 'Help text is missing.');

    // Putting the contents into subject field - assigning the text to variable, it'll come in handy later
    $subject = "This is subject of test activity being added through standalone screen.";
    // For simple input fields we can use field id as selector
    $this->type("subject", $subject);

    $location = 'Some location needs to be put in this field.';
    $this->type("location", $location);

    // Choosing the Date.
    // Please note that we don't want to put in fixed date, since
    // we want this test to work in the future and not fail because
    // of date being set in the past. Therefore, using helper webtestFillDate function.
    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');

    // Setting duration.
    $this->type("duration", "30");

    // Putting in details.
    $this->type("details", "Really brief details information.");

    // Making sure that status is set to Scheduled (using value, not label).
    $this->select("status_id", "value=1");

    // Setting priority.
    $this->select("priority_id", "value=1");

    // Adding attachment
    $this->click("css=.crm-activity-form-block-attachment div.crm-accordion-header");
    //FIX ME: need to fix file uploading
    //$this->waitForElementPresent("attachFile_1");
    //$filePath = $this->webtestAttachFile( "attachFile_1" );

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 months 10:00AM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->click("_qf_Activity_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Activity '$subject' has been saved."), "Status message didn't show up after saving!");

    $this->openCiviPage("activity/search", "reset=1", "_qf_Search_refresh");

    $this->type("sort_name", $firstName1);
    $this->click("_qf_Search_refresh");
    $this->waitForElementPresent("_qf_Search_next_print");

    $this->click("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[3]/td[9]/span/a[text()='View']");
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");

    $this->webtestVerifyTabularData(
      array(
        'Subject' => $subject,
        'Location' => $location,
        'Status' => 'Scheduled',
        'Duration' => '30',
        // Tough luck filling in WYSIWYG editor, so skipping verification for now.
        //'Details'    => 'Really brief details information.',
        'Priority' => 'Urgent',
        //'Current Attachment(s)' => basename($filePath)
      ),
      "/label"
    );

    $this->webtestVerifyTabularData(
      array(
        'With Contact' => "Anderson, {$firstName1}",
        'Assigned To' => "Summerson, {$firstName2}",
      )
    );
  }
}

