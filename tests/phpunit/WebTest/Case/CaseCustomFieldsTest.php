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
class WebTest_Case_CaseCustomFieldsTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddCase() {
    $this->webtestLogin();

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    $customGrp1 = "CaseCustom_Data1_" . substr(sha1(rand()), 0, 7);

    // create custom group1
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $this->click("newCustomDataGroup");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Case");
    sleep(1);
    $this->select("extends_1", "value=2");
    $this->click("_qf_Group_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get custom group id
    $elements = $this->parseURL();
    $customGrpId1 = $elements['queryString']['gid'];

    $customId = $this->_testGetCustomFieldId($customGrpId1);
    $cusId_1 = 'custom_' . $customId[0] . '_-1';
    $cusId_2 = 'custom_' . $customId[1] . '_-1';
    $cusId_3 = 'custom_' . $customId[2] . '_-1';

    // let's give full CiviCase permissions.
    $permission = array('edit-2-access-all-cases-and-activities', 'edit-2-access-my-cases-and-activities', 'edit-2-administer-civicase', 'edit-2-delete-in-civicase');
    $this->changePermissions($permission);

    // Go to reserved New Individual Profile to set value for logged in user's contact name (we'll need that later)
    $this->openCiviPage('profile/edit', 'reset=1&gid=4', '_qf_Edit_next');
    $testUserFirstName = "Testuserfirst";
    $testUserLastName = "Testuserlast";
    $this->type("first_name", $testUserFirstName);
    $this->type("last_name", $testUserLastName);
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("profilewrap4");

    // Go directly to the URL of the screen that you will be testing (New Case-standalone).
    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Try submitting the form without creating or selecting a contact (test for CRM-7971)
    $this->click("_qf_Case_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("css=span.crm-error");

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    // We're using pop-up New Contact dialog
    $firstName = substr(sha1(rand()), 0, 7);
    $lastName = "Fraser";
    $contactName = "{$lastName}, {$firstName}";
    $displayName = "{$firstName} {$lastName}";
    $email = "{$lastName}.{$firstName}@example.org";
    $custFname = "Mike" . substr(sha1(rand()), 0, 7);
    $custMname = "Dav" . substr(sha1(rand()), 0, 7);
    $custLname = "Krist" . substr(sha1(rand()), 0, 7);
    $this->webtestNewDialogContact($firstName, $lastName, $email, $type = 4);

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel = "Adult Day Care Referral";

    // activity types we expect for this case type
    $activityTypes = array("ADC referral", "Follow up", "Medical evaluation", "Mental health evaluation");
    $caseRoles = array("Senior Services Coordinator", "Health Services Coordinator", "Benefits Specialist", "Client");
    $caseStatusLabel = "Ongoing";
    $subject = "Safe daytime setting - senior female";
    $this->select("medium_id", "value=1");
    $location = "Main offices";
    $this->type("activity_location", $location);
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);

    $this->select("case_type_id", "label={$caseTypeLabel}");
    sleep(3);
    $this->select("status_id", "label={$caseStatusLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    // echo 'Today is ' . $today;
    $this->type("duration", "20");
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custMname);
    $this->type("{$cusId_3}", $custLname);
    $this->click("_qf_Case_upload-bottom");

    // We should be at manage case screen
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->assertTextPresent("Case opened successfully.", "Save successful status message didn't show up after saving!");
    $this->click("_qf_CaseView_cancel-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('case', 'reset=1', "xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[8]/a[text()='Open Case']");
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[8]/a[text()='Open Case']");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");
    sleep(3);

    $openCaseData = array(
      "Client" => $displayName,
      "Activity Type" => "Open Case",
      "Subject" => $subject,
      "Created By" => "{$testUserFirstName} {$testUserLastName}",
      "Reported By" => "{$testUserFirstName} {$testUserLastName}",
      "Medium" => "In Person",
      "Location" => $location,
      "Date and Time" => $today,
      "Status" => "Completed",
      "Priority" => "Normal",
    );
    $this->webtestVerifyTabularData($openCaseData);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[9]/span/a[text()='Manage']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("css=#{$customGrp1} .crm-accordion-header");
    $this->waitForElementPresent("css=#{$customGrp1} a.button");
    $cusId_1 = 'custom_' . $customId[0] . '_1';
    $cusId_2 = 'custom_' . $customId[1] . '_1';
    $cusId_3 = 'custom_' . $customId[2] . '_1';
    $this->click("css=#{$customGrp1} a.button");
    sleep(2);
    $this->waitForElementPresent("{$cusId_1}");
    $custFname = "Miky" . substr(sha1(rand()), 0, 7);
    $custMname = "Davy" . substr(sha1(rand()), 0, 7);
    $custLname = "Kristy" . substr(sha1(rand()), 0, 7);
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custMname);
    $this->type("{$cusId_3}", $custLname);
    $this->click("_qf_CustomData_upload");
    sleep(2);
    $this->openCiviPage('case', 'reset=1');
    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$contactName}']/../../td[8]/a[text()='Change Custom Data']");
    sleep(3);
    $openCaseChangeData = array(
      "Client" => $displayName,
      "Activity Type" => "Change Custom Data",
      "Subject" => $customGrp1 . " : change data",
      "Created By" => "{$testUserFirstName} {$testUserLastName}",
      "Reported By" => "{$testUserFirstName} {$testUserLastName}",
      "Date and Time" => $today,
      "Status" => "Completed",
      "Priority" => "Normal",
    );
    $this->webtestVerifyTabularData($openCaseChangeData);
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button/span[text()='Done']");
    sleep(2);
    $this->_testAdvansearchCaseData($customId, $custFname, $custMname, $custLname);
    $this->_testDeleteCustomData($customGrpId1, $customId);
  }

  function _testVerifyCaseSummary($validateStrings, $activityTypes) {
    $this->assertStringsPresent($validateStrings);
    foreach ($activityTypes as $aType) {
      $this->assertText("activity_type_id", $aType);
    }
    $this->assertElementPresent("link=Assign to Another Client", "Assign to Another Client link is missing.");
    $this->assertElementPresent("name=case_report_all", "Print Case Summary button is missing.");
  }

  function _testGetCustomFieldId($customGrpId1) {
    $customId = array();

    // Create a custom data to add in profile
    $field1 = "Fname" . substr(sha1(rand()), 0, 7);
    $field2 = "Mname" . substr(sha1(rand()), 0, 7);
    $field3 = "Lname" . substr(sha1(rand()), 0, 7);

    // add custom fields for group 1
    $this->openCiviPage('admin/custom/group/field/add', array('reset' => 1, 'action' => 'add', 'gid' => $customGrpId1));
    $this->type("label", $field1);
    $this->check("is_searchable");
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->type("label", $field2);
    $this->check("is_searchable");
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->type("label", $field3);
    $this->check("is_searchable");
    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // get id of custom fields
    $this->openCiviPage("admin/custom/group/field", array('reset' => 1, 'action' => 'browse', 'gid' => $customGrpId1));
    $custom1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[1]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom1 = $custom1[1];
    array_push($customId, $custom1);
    $custom2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[2]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom2 = $custom2[1];
    array_push($customId, $custom2);
    $custom3 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[3]/td[8]/span/a[text()='Edit Field']/@href"));
    $custom3 = $custom3[1];
    array_push($customId, $custom3);

    return $customId;
  }

  function _testDeleteCustomData($customGrpId1, $customId) {
    // delete all custom data
    $this->openCiviPage("admin/custom/group/field", array('action' => 'delete', 'reset' => '1', 'gid' => $customGrpId1, 'id' => $customId[0]));
    $this->click("_qf_DeleteField_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage("admin/custom/group/field", array('action' => 'delete', 'reset' => '1', 'gid' => $customGrpId1, 'id' => $customId[1]));
    $this->click("_qf_DeleteField_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage("admin/custom/group/field", array('action' => 'delete', 'reset' => '1', 'gid' => $customGrpId1, 'id' => $customId[2]));
    $this->click("_qf_DeleteField_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage("admin/custom/group", "action=delete&reset=1&id=" . $customGrpId1);
    $this->click("_qf_DeleteGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  function _testAdvansearchCaseData($customId, $custFname, $custMname, $custLname) {
    // search casecontact
    $this->openCiviPage('contact/search/advanced', 'reset=1', '_qf_Advanced_refresh');
    $this->click("CiviCase");
    $this->waitForElementPresent('case_from_relative');
    $cusId_1 = 'custom_' . $customId[0];
    $cusId_2 = 'custom_' . $customId[1];
    $cusId_3 = 'custom_' . $customId[2];
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custMname);
    $this->type("{$cusId_3}", $custLname);
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-container', '1 Contact');
  }
}

