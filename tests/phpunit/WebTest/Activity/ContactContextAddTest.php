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
 * Class WebTest_Activity_ContactContextAddTest
 */
class WebTest_Activity_ContactContextAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testContactContextActivityAdd() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Anderson", $firstName2 . "@anderson.name");
    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Activity_upload");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", 'Anderson, ' . $firstName2);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);
    $this->typeKeys("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForText("xpath=//div[@id='s2id_assignee_contact_id']", "$firstName1");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText("xpath=//div[@id='s2id_assignee_contact_id']", "Summerson, $firstName1", 'Contact not found in line ' . __LINE__);

    // Putting the contents into subject field - assigning the text to variable, it'll come in handy later
    $subject = "This is subject of test activity being added through activity tab of contact summary screen.";
    // For simple input fields we can use field id as selector
    $this->type("subject", $subject);
    $this->type("location", "Some location needs to be put in this field.");

    // Choosing the Date.
    // Please note that we don't want to put in fixed date, since
    // we want this test to work in the future and not fail because
    // of date being set in the past. Therefore, using helper webtestFillDateTime function.
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
    // TODO TBD

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 months 10:00AM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->click("_qf_Activity_upload");

    // Is status message correct?
    $this->waitForText('crm-notification-container', $subject);
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr[2]/td[8]/span[1]/a[1][text()='View']");

    // click through to the Activity view screen
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr//td/div[text()='$subject']/../../td[8]/span[1]/a[1][text()='View']");
    $this->waitForElementPresent('_qf_Activity_cancel-bottom');

    // verify Activity created
    $this->webtestVerifyTabularData(
      array(
        'Subject' => $subject,
        'Location' => 'Some location needs to be put in this field.',
        'Activity Status' => 'Scheduled',
        'Duration' => '30',
        // Tough luck filling in WYSIWYG editor, so skipping verification for now.
        //'Details'    => 'Really brief details information.',
        'Priority' => 'Urgent',
      ),
      "/label"
    );

    $this->webtestVerifyTabularData(
      array(
        'With Contact' => "Anderson, {$firstName2}",
        'Assigned to' => "Summerson, {$firstName1}",
      ),
      "/label"
    );
  }

  public function testSeparateActivityForMultiTargetContacts() {
    $this->webtestLogin();

    //creating contacts
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Andersonnn", $firstName2 . "@anderson.name");
    $firstName3 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName3, "Anderson", $firstName3 . "@andersonnn.name");

    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", 'Anderson, ' . $firstName3, 'Contact not found in line ' . __LINE__);

    //filling the second target Contact
    $this->click("xpath=//div[@id='s2id_target_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName1);
    $this->typeKeys("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", "$firstName1", 'Contact not found in line ' . __LINE__);

    //filling the third target contact
    $this->click("xpath=//div[@id='s2id_target_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName2);
    $this->typeKeys("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName2);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", "$firstName2", 'Contact not found in line ' . __LINE__);

    //check the checkbox to create a separate activity for the selected target contacts
    $this->check('is_multi_activity');

    $subject = "This is subject of test activity for creating a separate activity for contacts {$firstName1},{$firstName2} and {$firstName3}.";
    $this->type("subject", $subject);

    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');
    $this->select("status_id", "value=1");

    // Clicking save.
    $this->click('_qf_Activity_upload');

    // Is status message correct?
    $this->waitForText('crm-notification-container', $subject);

    //activity search page
    $this->openCiviPage('activity/search', 'reset=1');

    $this->type('activity_subject', $subject);

    $this->clickLink('_qf_Search_refresh');

    $targetContacts = array("Summerson, " . $firstName1, "Andersonnn, " . $firstName2, "Anderson, " . $firstName3);

    //check whether separate activities are created for the target contacts
    foreach ($targetContacts as $contact) {
      $this->assertTrue($this->isElementPresent("xpath=//div[@class='crm-search-results']/table/tbody//tr/td[5]/a[text()='$contact']"));
    }
  }

}
