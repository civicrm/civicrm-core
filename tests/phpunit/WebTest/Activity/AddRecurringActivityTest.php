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

/**
 * Description of AddRecurringActivityTest
 *
 * @author Priyanka
 */

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Activity_AddRecurringActivityTest
 */
class WebTest_Activity_AddRecurringActivityTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testRecurringActivity() {
    $this->webtestLogin();

    //Adding new contact
    $contact1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$contact1", "Karan", $contact1 . "@exampleone.com");
    $contact2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact("$contact2", "Jane", $contact2 . "@exampletwo.com");

    //Lets create an activity and add repeat configuration
    $this->openCiviPage("activity", "?reset=1&action=add&context=standalone", '_qf_Activity_cancel-bottom');
    $this->select("activity_type_id", "value=1");

    //Add a new contact
    $this->click("xpath=//div[@id='s2id_target_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $contact1);
    $this->typeKeys("xpath=//div[@id='s2id_target_contact_id']/ul/li/input", $contact1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", "$contact1");
    $this->assertElementContainsText("xpath=//div[@id='s2id_target_contact_id']", "Karan, $contact1", 'Contact not found in line ' . __LINE__);

    //Assigned To field
    $this->click("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $contact2);
    $this->typeKeys("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $contact2);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForText("xpath=//div[@id='s2id_assignee_contact_id']", "$contact2");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText("xpath=//div[@id='s2id_assignee_contact_id']", "Jane, $contact2", 'Contact not found in line ' . __LINE__);

    if ($this->isTextPresent("A copy of this activity will be emailed to each Assignee.")) {
      $isAssigneeNotificationEnabled = TRUE;
    }

    $subject = "Test activity recursion " . substr(sha1(rand()), 0, 7);
    $this->type("subject", $subject);
    $this->type("duration", "30");

    //Lets configure recursion for activity
    $this->click("css=.crm-activity-form-block-recurring_activity div.crm-accordion-header");
    $this->click('repetition_frequency_unit');
    $this->select('repetition_frequency_unit', 'label=month');
    $this->click('repetition_frequency_interval');
    $this->select('repetition_frequency_interval', 'label=1');
    $this->click('CIVICRM_QFID_1_repeats_by');
    $this->click('limit_to');
    $this->select('limit_to', 'label=26');
    $this->click('CIVICRM_QFID_1_ends');

    $occurrences = rand(3, 5);
    if (!$occurrences) {
      $occurrences = 3;
    }
    $this->type('start_action_offset', $occurrences);
    $this->click('_qf_Activity_upload-bottom');
    $this->waitForTextPresent('A repeating set will be created with the following dates.');

    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Continue']");
    $this->waitForPageToLoad();

    //Lets go to search screen and see if the records new activities are created()
    $this->openCiviPage("activity/search", "?reset=1", '_qf_Search_refresh');
    $this->type('activity_subject', $subject);
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad();

    //Minus tr having th and parent activity
    $countOfActivities = $this->getXpathCount("//div[@class='crm-search-results']/table/tbody/tr");
    $countOfActivities = $countOfActivities - 2;

    if (!empty($isAssigneeNotificationEnabled)) {
      $countOfActivities--;
    }

    $this->assertEquals($occurrences, $countOfActivities);
    $this->assertTrue($this->isTextPresent("Repeating"));

    //Cascade changes
    $this->click("xpath=//div[@class='crm-search-results']/table/tbody/tr[2]/td/span/a[text()='Edit']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Cancel']");
    $this->type('subject', "{$subject} modified");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Cancel']");
    $this->click("xpath=//input[@id='recur-this-and-all-following-entity']");
    $this->waitForAjaxContent();
    $this->type('activity_subject', "{$subject} modified");
    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad();
    $countOfActivities = $this->getXpathCount("xpath=//div[@class='crm-search-results']/table/tbody/tr");
    if ($countOfActivities) {
      for ($i = 0; $i <= $countOfActivities; $i++) {
        $this->verifyText("xpath=//div[@class='crm-search-results']/table/tbody/tr/td[3]", 'Test activity recursion modified');
      }
    }
  }

}
