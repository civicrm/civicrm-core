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
class WebTest_Case_ActivityToCaseTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddActivityToCase() {
    $this->open($this->sboxPath);

    // Log in as admin first to verify permissions for CiviCase
    $this->webtestLogin();

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    // let's give full CiviCase permissions to demo user (registered user).
    $permission = array('edit-2-access-all-cases-and-activities', 'edit-2-access-my-cases-and-activities', 'edit-2-administer-civicase', 'edit-2-delete-in-civicase');
    $this->changePermissions($permission);

    // Go directly to the URL of the screen that you will be testing (New Case-standalone).
    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = "Fraser";
    $contactName = "{$lastName}, {$firstName}";
    $displayName = "{$firstName} {$lastName}";
    $email = "{$lastName}.{$firstName}@example.org";
    $this->webtestNewDialogContact($firstName, $lastName, $email, $type = 4);

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel = "Adult Day Care Referral";
    $subject = "Safe daytime setting - senior female";
    $this->select('medium_id', 'value=1');
    $this->type('activity_location', 'Main offices');
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type('activity_subject', $subject);

    $this->select('case_type_id', "label={$caseTypeLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));
    // echo 'Today is ' . $today;
    $this->type('duration', "20");
    $this->click('_qf_Case_upload-bottom');

    // We should be at manage case screen
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_CaseView_cancel-bottom');

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "Case opened successfully.", "Save successful status message didn't show up after saving!");
    $customGroupTitle = 'Custom_' . substr(sha1(rand()), 0, 7);

    $this->_testAddNewActivity($firstName, $subject, $customGroupTitle, $contactName);
  }

  function _testAddNewActivity($firstName, $caseSubject, $customGroupTitle, $contactName) {
    $customDataParams = $this->_addCustomData($customGroupTitle);
    //$customDataParams = array( 'optionLabel_47d58', 'custom_8_-1' );

    // Adding Adding contact with randomized first name for test testContactContextActivityAdd
    // We're using Quick Add block on the main page for this.
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName1, "Summerson", $firstName1 . "@summerson.name");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName2, "Anderson", $firstName2 . "@anderson.name");

    // Go directly to the URL of the screen that you will be testing (Activity Tab).
    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    // waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Activity_upload-bottom");

    // Let's start filling the form with values.

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText('css=tr.crm-activity-form-block-target_contact_id td ul li.token-input-token-facebook', 'Anderson, ' . $firstName2, "Contact not found in line " . __LINE__);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
    $this->type("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName1);
    $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertElementContainsText('css=tr.crm-activity-form-block-assignee_contact_id td ul li.token-input-token-facebook', 'Summerson, ' . $firstName1, "Contact not found in line " . __LINE__);

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

    $textField = 'This is test custom data';
    $this->click($customGroupTitle);
    $this->click("xpath=//div[@id='{$customGroupTitle}']/table/tbody/tr[2]/td[2]/table/tbody/tr/td[1]/input");
    $this->type($customDataParams[1], $textField);

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 months 10:00AM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->click("_qf_Activity_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "Activity '$subject' has been saved.",
      "Status message didn't show up after saving!"
    );

    // click through to the Activity view screen
    $this->waitForElementPresent("xpath=//div[@id='Activities']//table/tbody/tr[2]/td[9]");
    $this->click("xpath=//div[@id='Activities']//table/tbody/tr[2]/td[9]/span[2]/ul/li/a[text()='File On Case']");
    $this->waitForElementPresent("css=div#fileOnCaseDialog");
    $this->waitForElementPresent('case_activity_subject');

    // file activity on case
    $this->type('unclosed_cases', $firstName);
    $this->click('unclosed_cases');
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->click("css=div.ac_results-inner li");
    $this->assertContains($firstName, $this->getValue('unclosed_cases'),
      "autocomplete expected $firstName but didn’t find it in " .
      $this->getValue('unclosed_cases')
    );

    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Ok']");
    $this->waitForElementPresent("xpath=//div[@id='Activities']//table/tbody/tr[1]/td[9]/span/a[text()='View']");

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']");

    $this->waitForElementPresent('_qf_CaseView_cancel-bottom');
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='activities-selector']/tbody/tr[1]/td[2]");

    $this->click("xpath=//div[@id='activities']//table[@id='activities-selector']//a[text()='{$subject}']");

    $this->waitForElementPresent('view-activity');
    $this->waitForElementPresent("css=table#crm-activity-view-table tr.crm-case-activityview-form-block-groupTitle");
    $this->assertElementContainsText('crm-activity-view-table', "$customDataParams[0]");
    $this->assertElementContainsText('crm-activity-view-table', "$textField");
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='activities-selector']/tbody/tr[1]/td[2]");

    $this->click("xpath=//div[@id='activities']//table[@id='activities-selector']/tbody//tr/td[2]/a[text()='{$subject}']/../../td[6]/a[text()='Scheduled']");

    $this->waitForElementPresent("css=div#changeStatusDialog");
    $this->waitForElementPresent('activity_change_status');

    // change activity status
    $this->select('activity_change_status', 'value=2');
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Ok']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']");
    $this->waitForElementPresent('_qf_CaseView_cancel-bottom');
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='activities-selector']/tbody/tr[1]/td[2]");
    $this->click("xpath=//div[@id='activities']//table[@id='activities-selector']//a[text()='{$subject}']");
    $this->waitForElementPresent('view-activity');
    $this->waitForElementPresent("css=table#crm-activity-view-table tr.crm-case-activityview-form-block-groupTitle");
  }

  function _addCustomData($customGroupTitle) {
    // Go directly to the URL of the screen that you will be testing (New Custom Group).
    $this->openCiviPage('admin/custom/group', 'reset=1');

    //add new custom data
    $this->click("//a[@id='newCustomDataGroup']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill custom group title
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Activity");
    $this->click("//option[@value='Activity']");
    $this->click('_qf_Group_next-bottom');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->assertElementContainsText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    // create a custom field - Integer Radio
    $this->click("data_type[0]");
    $this->select("data_type[0]", "value=1");
    $this->click("//option[@value='1']");
    $this->click("data_type[1]");
    $this->select("data_type[1]", "value=Radio");
    $this->click("//option[@value='Radio']");

    $radioFieldLabel = 'Custom Field Radio_' . substr(sha1(rand()), 0, 4);
    $this->type("label", $radioFieldLabel);
    $radioOptionLabel1 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_1", $radioOptionLabel1);
    $this->type("option_value_1", "1");
    $radioOptionLabel2 = 'optionLabel_' . substr(sha1(rand()), 0, 5);
    $this->type("option_label_2", $radioOptionLabel2);
    $this->type("option_value_2", "2");

    //select options per line
    $this->type("options_per_line", "3");

    //enter pre help msg
    $this->type("help_pre", "this is field pre help");

    //enter post help msg
    $this->type("help_post", "this is field post help");

    //Is searchable?
    $this->click("is_searchable");

    //clicking save
    $this->click("_qf_Field_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created
    $this->assertElementContainsText('crm-notification-container', "Your custom field '$radioFieldLabel' has been saved.");

    // create another custom field - text field
    $this->click("//a[@id='newCustomField']/span");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $textFieldLabel = 'Custom Field Text_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $textFieldLabel);

    //enter pre help msg
    $this->type('help_pre', "this is field pre help");

    //enter post help msg
    $this->type('help_post', "this is field post help");

    //Is searchable?
    $this->click('is_searchable');

    //clicking save
    $this->click('_qf_Field_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created
    $this->assertElementContainsText('crm-notification-container', "Your custom field '$textFieldLabel' has been saved.");
    $textFieldId = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr/td/span[text()='$textFieldLabel']/../../td[8]/span/a[text()='Edit Field']/@href"));
    $textFieldId = $textFieldId[1];

    return array($radioOptionLabel1, "custom_{$textFieldId}_-1");
  }
}

