<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * Class WebTest_Activity_StandaloneAddTest
 */
class WebTest_Activity_StandaloneAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testStandaloneActivityAdd() {
    $this->webtestLogin();

    // Adding Anderson, Anthony and Summerson, Samuel for testStandaloneActivityAdd test
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$firstName1", "Anderson", $firstName1 . "@anderson.com");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$firstName2", "Summerson", $firstName2 . "@summerson.com");

    $this->openCiviPage("activity", "reset=1&action=add&context=standalone", "_qf_Activity_upload");

    // Select one of the options in Activity Type selector. Use option value, not label - since labels can be translated and test would fail
    $this->select("activity_type_id", "value=1");

    // We're filling in ajaxiefied  "With Contact" field:
    // We can not use id as selector for these input widgets. Use css selector, starting with the table row containing this field (which will have a unique class)
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("xpath=//div[@id='s2id_target_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName1);
    $this->typeKeys("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...again, waiting for the box with contact name to show up (span with delete token class indicates that it's present)...
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", "$firstName1");

    //..and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText("xpath=//div[@id='s2id_target_contact_id']", "Anderson, $firstName1", 'Contact not found in line ' . __LINE__);

    // Now we're doing the same for "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName2);
    $this->typeKeys("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName2);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForText("xpath=//div[@id='s2id_assignee_contact_id']", "$firstName2");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText("xpath=//div[@id='s2id_assignee_contact_id']", "Summerson, $firstName2", 'Contact not found in line ' . __LINE__);

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
    //FIX ME: need to fix file uploading
    //$this->waitForElementPresent("attachFile_1");
    //$filePath = $this->webtestAttachFile( "attachFile_1" );

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 months 10:00AM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->clickLink('_qf_Activity_upload');

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Activity '$subject' has been saved.");

    $this->openCiviPage("activity/search", "reset=1", "_qf_Search_refresh");

    $this->type("sort_name", $firstName1);
    $this->click("_qf_Search_refresh");

    $this->waitForElementPresent("xpath=//table[@class='selector row-highlight']/tbody//tr/td[6]/a[text()='Summerson, $firstName2']/../../td[9]/span/a[text()='View']");
    $this->click("xpath=//table[@class='selector row-highlight']/tbody//tr/td[6]/a[text()='Summerson, $firstName2']/../../td[9]/span/a[text()='View']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]");

    $this->VerifyTabularData(
      array(
        'Subject' => $subject,
        'Location' => $location,
        'Activity Status' => 'Scheduled',
        'Duration' => '30',
        // Tough luck filling in WYSIWYG editor, so skipping verification for now.
        //'Details'    => 'Really brief details information.',
        'Priority' => 'Urgent',
        //'Current Attachment(s)' => basename($filePath)
      ),
      "/label"
    );

    $this->VerifyTabularData(
      array(
        'With Contact' => "Anderson, {$firstName1}",
        'Assigned to' => "Summerson, {$firstName2}",
      ),
      "/label"
    );
  }

  public function testAjaxCustomGroupLoad() {
    $this->webtestLogin();
    $triggerElement = array('name' => 'activity_type_id', 'type' => 'select');
    $customSets = array(
      array('entity' => 'Activity', 'subEntity' => 'Interview', 'triggerElement' => $triggerElement),
      array('entity' => 'Activity', 'subEntity' => 'Meeting', 'triggerElement' => $triggerElement),
    );

    $pageUrl = array('url' => 'activity', 'args' => 'reset=1&action=add&context=standalone');
    $this->customFieldSetLoadOnTheFlyCheck($customSets, $pageUrl);
  }

  /**
   * @param $expected
   * @param null $xpathPrefix
   */
  public function VerifyTabularData($expected, $xpathPrefix = NULL) {
    foreach ($expected as $label => $value) {
      $this->waitForElementPresent("xpath=//table/tbody/tr/td{$xpathPrefix}[text()='{$label}']/../following-sibling::td/span");
      $this->verifyText("xpath=//table/tbody/tr/td{$xpathPrefix}[text()='{$label}']/../following-sibling::td/span", preg_quote($value));
    }
  }

}
