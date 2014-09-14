<?php
/*
   +--------------------------------------------------------------------+
   | CiviCRM version 4.5                                                |
   +--------------------------------------------------------------------+
   | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Class WebTest_Case_ActivityToCaseTest
 */
class WebTest_Case_ActivityToCaseTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddActivityToCase() {
    // Log in as admin first to verify permissions for CiviCase
    $this->webtestLogin('true');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    // let's give full CiviCase permissions to demo user (registered user).
    $permission = array('edit-2-access-all-cases-and-activities', 'edit-2-access-my-cases-and-activities', 'edit-2-administer-civicase', 'edit-2-delete-in-civicase');
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = "Fraser";
    $contactName = "{$lastName}, {$firstName}";
    $displayName = "{$firstName} {$lastName}";
    $email = "{$lastName}.{$firstName}@example.org";
    $this->webtestNewDialogContact($firstName, $lastName, $email, $type = 4, "s2id_client_id");

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

    $this->type('duration', "20");
    $this->clickLink('_qf_Case_upload-bottom', '_qf_CaseView_cancel-bottom', FALSE);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Case opened successfully.");
    $customGroupTitle = 'Custom_' . substr(sha1(rand()), 0, 7);

    $this->_testAddNewActivity($firstName, $subject, $customGroupTitle, $contactName);
  }

  function testLinkCases() {
    $this->webtestLogin();

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    //Add Case 1
    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = "Fraser";
    $contactName = "{$lastName}, {$firstName}";
    $displayName = "{$firstName} {$lastName}";
    $email = "{$lastName}.{$firstName}@example.org";
    $this->webtestNewDialogContact($firstName, $lastName, $email, $type = 4, "s2id_client_id");

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

    $this->type('duration', "20");
    $this->clickLink('_qf_Case_upload-bottom', '_qf_CaseView_cancel-bottom', FALSE);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Case opened successfully.");

    //Add Case 2
    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName2 = substr(sha1(rand()), 0, 7);
    $lastName2 = "Fraser";
    $contactName2 = "{$lastName}, {$firstName}";
    $displayName2 = "{$firstName} {$lastName}";
    $email2 = "{$lastName2}.{$firstName2}@example.org";
    $this->webtestNewDialogContact($firstName2, $lastName2, $email2, $type = 4, "s2id_client_id");

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel2 = "Adult Day Care Referral";
    $subject2 = "Subject For Case 2";
    $this->select('medium_id', 'value=1');
    $this->type('activity_location', 'Main offices');
    $details2 = "Details For Case 2";
    $this->fillRichTextField("activity_details", $details2, 'CKEditor');
    $this->type('activity_subject', $subject2);

    $this->select('case_type_id', "label={$caseTypeLabel2}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type('duration', "20");
    $this->clickLink('_qf_Case_upload-bottom', '_qf_CaseView_cancel-bottom', FALSE);

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Case opened successfully.");

    //Add Link Case Activity
    $this->select('add_activity_type_id', 'Link Cases');
    $this->waitForElementPresent("_qf_Activity_cancel-bottom");
    $this->select2('link_to_case_id', $firstName);
    $activitydetails = 'Details of Link Case Activity';
    $this->fillRichTextField("details", $activitydetails, 'CKEditor');
    $this->click('activity-details');
    $this->waitForElementPresent('subject');
    $activitySubject = 'Link Case Activity between case 1 and case 2';
    $activitylocation = 'Main Office Building';
    $this->select2('source_contact_id', $firstName2);
    $this->type('subject', $activitySubject);
    $this->type('location', $activitylocation);
    $this->click('_qf_Activity_upload-bottom');
    $id = $this->urlArg('id');
    $this->waitForText("case_id_$id", $activitySubject);
    $this->click("xpath=//a[contains(text(),'$activitySubject')]");

    $LinkCaseActivityData = array(
      "Client" => $firstName2,
      "Activity Type" => "Link Cases",
      "Subject" => $activitySubject,
      "Reported By" => "{$firstName2} {$lastName2}",
      "Medium" => "Phone",
      "Location" => $activitylocation,
      "Date and Time" => $today,
      "Details" => $activitydetails,
      "Status" => "Scheduled",
      "Priority" => "Normal",
    );
    $this->webtestVerifyTabularData($LinkCaseActivityData);
  }
  /**
   * @param $firstName
   * @param $caseSubject
   * @param $customGroupTitle
   * @param $contactName
   */
  function _testAddNewActivity($firstName, $caseSubject, $customGroupTitle, $contactName) {
    $customDataParams = $this->_addCustomData($customGroupTitle);
    //$customDataParams = array( 'optionLabel_47d58', 'custom_8_-1' );

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

    // waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Activity_upload-bottom");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->waitForText("xpath=//div[@id='s2id_target_contact_id']", 'Anderson, ' . $firstName2, "Contact not found in line " . __LINE__);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input");
    $this->keyDown("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", " ");
    $this->type("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);
    $this->typeKeys("xpath=//div[@id='s2id_assignee_contact_id']/ul/li/input", $firstName1);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("xpath=//div[@class='select2-result-label']");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->clickAt("xpath=//div[@class='select2-result-label']");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForText("xpath=//div[@id='s2id_assignee_contact_id']","$firstName1");

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

    $textField = 'This is test custom data';
    $this->click("xpath=//div[@id='customData']//div[@class='custom-group custom-group-$customGroupTitle crm-accordion-wrapper collapsed']");
    $this->waitForElementPresent("xpath=//div[@class='crm-accordion-body']//table/tbody/tr[2]/td[2]/table/tbody/tr/td[1]/input");
    $this->click("xpath=//div[@class='crm-accordion-body']//table/tbody/tr[2]/td[2]/table/tbody/tr/td[1]/input");
    $this->type($customDataParams[1], $textField);

    // Scheduling follow-up.
    $this->click("css=.crm-activity-form-block-schedule_followup div.crm-accordion-header");
    $this->select("followup_activity_type_id", "value=1");
    $this->webtestFillDateTime('followup_date', '+2 months 10:00AM');
    $this->type("followup_activity_subject", "This is subject of schedule follow-up activity");

    // Clicking save.
    $this->click("_qf_Activity_upload-bottom");

    // Is status message correct?
    $this->waitForText('crm-notification-container', $subject);

    // click through to the Activity view screen
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[5]/a[text()='Summerson, $firstName1']/../../td[8]/span/a[1][text()='View']");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[5]/a[text()='Summerson, $firstName1']/../../td[8]/span/a[1][text()='View']");
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[5]/a[text()='Summerson, $firstName1']/../../td[8]/span[2][text()='more']/ul[1]/li[1]/a");
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[5]/a[text()='Summerson, $firstName1']/../../td[8]/span[2][text()='more']/ul[1]/li[1]/a");

    // file activity on case
    $this->waitForElementPresent('file_on_case_unclosed_case_id');
    $this->select2('file_on_case_unclosed_case_id', $firstName);
    $this->assertElementContainsText("xpath=//div[@id='s2id_file_on_case_unclosed_case_id']", "$firstName", 'Contact not found in line ' . __LINE__);
    $this->type('file_on_case_activity_subject', $subject);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Save']");
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr/td[5]/a[text()='Summerson, $firstName1']/../../td[8]/span/a[1][text()='View']");

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']");

    $this->waitForElementPresent('_qf_CaseView_cancel-bottom');
    $id = $this->urlArg('id');
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='case_id_".$id."']/tbody/tr[1]/td[2]");

    $this->click("xpath=//div[@id='activities']//table[@id='case_id_".$id."']/tbody/tr[1]/td[2]//a[text()='{$subject}']");

    $this->waitForElementPresent('ActivityView');
    $this->waitForElementPresent("css=table#crm-activity-view-table tr.crm-case-activityview-form-block-groupTitle");
    $this->assertElementContainsText('crm-activity-view-table', "$textField");
    $this->click("xpath=//span[@class='ui-button-icon-primary ui-icon ui-icon-closethick']");
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='case_id_".$id."']/tbody/tr[1]/td[2]");

    $this->click("xpath=//div[@id='activities']//table[@id='case_id_".$id."']/tbody//tr/td[2]/a[text()='{$subject}']/../../td[6]/a[text()='Scheduled']");

    $this->waitForElementPresent("xpath=//html/body/div[7]");
    $this->waitForElementPresent('activity_change_status');

    // change activity status
    $this->select('activity_change_status', 'value=2');
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[text()='Continue']");
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']");
    $this->waitForElementPresent('_qf_CaseView_cancel-bottom');
    $id2 = $this->urlArg('id');
    $this->waitForElementPresent("xpath=//div[@id='activities']//table[@id='case_id_".$id2."']/tbody/tr[1]/td[2]");
    $this->click("xpath=//div[@id='activities']//table[@id='case_id_".$id2."']//a[text()='{$subject}']");
    $this->waitForElementPresent('ActivityView');
    $this->waitForElementPresent("css=table#crm-activity-view-table tr.crm-case-activityview-form-block-groupTitle");
  }

  /**
   * @param $customGroupTitle
   *
   * @return array
   */
  function _addCustomData($customGroupTitle) {

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
    $this->waitForElementPresent('newCustomField');
    $this->click('newCustomField');
    $this->waitForElementPresent('_qf_Field_cancel-bottom');

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

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
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[1]/span[2]");

    //Is custom field created
    $this->waitForElementPresent("newCustomField");
    $this->waitForText('crm-notification-container', "Custom field '$radioFieldLabel' has been saved.");

    // create another custom field - text field
    $this->click("newCustomField");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[1]/span[2]");

    $textFieldLabel = 'Custom Field Text_' . substr(sha1(rand()), 0, 4);
    $this->type('label', $textFieldLabel);

    //enter pre help msg
    $this->type('help_pre', "this is field pre help");

    //enter post help msg
    $this->type('help_post', "this is field post help");

    //Is searchable?
    $this->click('is_searchable');

    //clicking save
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[1]/span[2]");
    $this->waitForElementPresent("//a[@id='newCustomField']/span");

    //Is custom field created
    $this->waitForText('crm-notification-container', "Custom field '$textFieldLabel' has been saved.");
    $textFieldId = explode('&id=', $this->getAttribute("xpath=//table[@id='options']/tbody//tr/td[1]/span[text()='$textFieldLabel']/../../td[8]/span/a[1][text()='Edit Field']/@href"));
    $textFieldId = $textFieldId[1];

    return array($radioOptionLabel1, "custom_{$textFieldId}_-1");
  }
}

