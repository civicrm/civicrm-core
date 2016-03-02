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
 * Class WebTest_Case_CaseCustomFieldsTest
 */
class WebTest_Case_CaseCustomFieldsTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddCase() {
    $this->webtestLogin('admin');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    $customGrp1 = "CaseCustom_Data1_" . substr(sha1(rand()), 0, 7);

    // create custom group1
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $this->clickLink("newCustomDataGroup");
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Case");
    $this->waitForAjaxContent();
    $this->select("extends_1", "value=2");
    $this->clickLink("_qf_Group_next-bottom");

    // get custom group id
    $customGrpId1 = $this->urlArg('gid');

    $customId = $this->_testGetCustomFieldId($customGrpId1);
    $cusId_1 = 'custom_' . $customId[0] . '_-1';
    $cusId_2 = 'custom_' . $customId[1] . '_-1';
    $cusId_3 = 'custom_' . $customId[2] . '_-1';

    // let's give full CiviCase permissions.
    $permission = array(
      'edit-2-access-all-cases-and-activities',
      'edit-2-access-my-cases-and-activities',
      'edit-2-administer-civicase',
      'edit-2-delete-in-civicase',
    );
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    // Go to reserved New Individual Profile to set value for logged in user's contact name (we'll need that later)
    $this->openCiviPage('profile/edit', 'reset=1&gid=4', '_qf_Edit_next');
    $testUserFirstName = "Testuserfirst";
    $testUserLastName = "Testuserlast";
    $this->type("first_name", $testUserFirstName);
    $this->type("last_name", $testUserLastName);
    $this->clickLink("_qf_Edit_next", "profilewrap4");

    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    $custFname = "Mike" . substr(sha1(rand()), 0, 7);
    $custMname = "Dav" . substr(sha1(rand()), 0, 7);
    $custLname = "Krist" . substr(sha1(rand()), 0, 7);
    // We're using pop-up New Contact dialog
    $client = $this->createDialogContact("client_id");

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel = "Adult Day Care Referral";

    $caseStatusLabel = "Ongoing";
    $subject = "Safe daytime setting - senior female";
    $this->select("medium_id", "value=1");
    $location = "Main offices";
    $this->type("activity_location", $location);
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);

    $this->select("case_type_id", "label={$caseTypeLabel}");
    $this->waitForAjaxContent();
    $this->select("status_id", "label={$caseStatusLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type("duration", "20");
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custMname);
    $this->type("{$cusId_3}", $custLname);
    $this->clickLink("_qf_Case_upload-bottom", "_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->checkCRMAlert("Case opened successfully.");
    $this->clickLink("_qf_CaseView_cancel-bottom");
    $this->openCiviPage('case', 'reset=1', "xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[8]/a[text()='Open Case']");
    $this->clickPopupLink("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[8]/a[text()='Open Case']");

    $openCaseData = array(
      "Client" => $client['display_name'],
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
    $this->click("xpath=//span[@class='ui-button-icon-primary ui-icon fa-times']");

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->waitForElementPresent("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[9]/span/a[1][text()='Manage']");
    $this->clickLink("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[9]/span/a[1][text()='Manage']");

    $this->clickAjaxLink("css=#{$customGrp1} .crm-accordion-header", "css=#{$customGrp1} a.button");
    $cusId_1 = 'custom_' . $customId[0] . '_1';
    $cusId_2 = 'custom_' . $customId[1] . '_1';
    $cusId_3 = 'custom_' . $customId[2] . '_1';
    $this->clickAjaxLink("css=#{$customGrp1} a.button", $cusId_1);

    $custFname = "Miky" . substr(sha1(rand()), 0, 7);
    $custMname = "Davy" . substr(sha1(rand()), 0, 7);
    $custLname = "Kristy" . substr(sha1(rand()), 0, 7);
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custMname);
    $this->type("{$cusId_3}", $custLname);
    $this->clickAjaxLink("_qf_CustomData_upload");

    $this->openCiviPage('case', 'reset=1');
    $this->clickAjaxLink("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[8]/a[text()='Change Custom Data']");

    $openCaseChangeData = array(
      "Client" => $client['display_name'],
      "Activity Type" => "Change Custom Data",
      "Subject" => $customGrp1 . " : change data",
      "Created By" => "{$testUserFirstName} {$testUserLastName}",
      "Reported By" => "{$testUserFirstName} {$testUserLastName}",
      "Date and Time" => $today,
      "Status" => "Completed",
      "Priority" => "Normal",
    );
    $this->webtestVerifyTabularData($openCaseChangeData);
    $this->_testAdvansearchCaseData($customId, $custFname, $custMname, $custLname);
    $this->_testDeleteCustomData($customGrpId1, $customId);
  }

  /**
   * @param $customGrpId1
   * @param bool $noteRichEditor
   *
   * @return array
   */
  public function _testGetCustomFieldId($customGrpId1, $noteRichEditor = FALSE) {
    $customId = array();
    $this->openCiviPage('admin/custom/group/field/add', array('reset' => 1, 'action' => 'add', 'gid' => $customGrpId1));

    if ($noteRichEditor) {
      // Create a custom data to add in profile
      $field1 = "Note_Textarea" . substr(sha1(rand()), 0, 7);
      $field2 = "Note_Richtexteditor" . substr(sha1(rand()), 0, 7);

      // add custom fields for group 1
      $this->type("label", $field1);
      $this->select("data_type_0", "value=4");
      $this->select("data_type_1", "value=TextArea");
      $this->check("is_searchable");
      $this->clickLink("_qf_Field_next_new-bottom");

      $this->type("label", $field2);
      $this->select("data_type_0", "value=4");
      //$this->select("data_type_1", "value=TextArea");
      $this->select("data_type_1", "value=RichTextEditor");
      $this->check("is_searchable");
      $this->clickLink("_qf_Field_next_new-bottom");

      // get id of custom fields
      $this->openCiviPage("admin/custom/group/field", array(
          'reset' => 1,
          'action' => 'browse',
          'gid' => $customGrpId1,
        ));
      $custom1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[1]/td[8]/span/a[text()='Edit Field']/@href"));
      $custom1 = $custom1[1];
      array_push($customId, $custom1);
      $custom2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[2]/td[8]/span/a[text()='Edit Field']/@href"));
      $custom2 = $custom2[1];
      array_push($customId, $custom2);
    }
    else {
      // Create a custom data to add in profile
      $field1 = "Fname" . substr(sha1(rand()), 0, 7);
      $field2 = "Mname" . substr(sha1(rand()), 0, 7);
      $field3 = "Lname" . substr(sha1(rand()), 0, 7);

      // add custom fields for group 1
      $this->type("label", $field1);
      $this->check("is_searchable");
      $this->clickLink("_qf_Field_next_new-bottom");

      $this->type("label", $field2);
      $this->check("is_searchable");
      $this->clickLink("_qf_Field_next_new-bottom");

      $this->type("label", $field3);
      $this->check("is_searchable");
      $this->clickLink("_qf_Field_done-bottom");

      // get id of custom fields
      $this->openCiviPage("admin/custom/group/field", array(
          'reset' => 1,
          'action' => 'browse',
          'gid' => $customGrpId1,
        ));
      $custom1 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[1]/td[8]/span/a[text()='Edit Field']/@href"));
      $custom1 = $custom1[1];
      array_push($customId, $custom1);
      $custom2 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[2]/td[8]/span/a[text()='Edit Field']/@href"));
      $custom2 = $custom2[1];
      array_push($customId, $custom2);
      $custom3 = explode('&id=', $this->getAttribute("xpath=//div[@id='field_page']//table/tbody//tr[3]/td[8]/span/a[text()='Edit Field']/@href"));
      $custom3 = $custom3[1];
      array_push($customId, $custom3);
    }

    return $customId;
  }

  /**
   * @param $customGrpId1
   * @param array $customId
   */
  public function _testDeleteCustomData($customGrpId1, $customId) {
    // delete all custom data
    foreach ($customId as $cKey => $cValue) {
      $this->openCiviPage("admin/custom/group/field", array(
          'action' => 'delete',
          'reset' => '1',
          'gid' => $customGrpId1,
          'id' => $cValue,
        ));
      $this->clickLink("_qf_DeleteField_next-bottom");
    }

    // delete custom group
    $this->openCiviPage("admin/custom/group", "action=delete&reset=1&id=" . $customGrpId1);
    $this->clickLink("_qf_DeleteGroup_next-bottom");
  }

  /**
   * CRM-12812
   */
  public function testCaseCustomNoteRichEditor() {
    $this->webtestLogin('admin');

    //setting ckeditor as WYSIWYG
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1', '_qf_Display_next-bottom');
    $this->select('editor_id', 'CKEditor');
    $this->clickLink('_qf_Display_next-bottom');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    $customGrp1 = "CaseCustom_Data1_" . substr(sha1(rand()), 0, 7);

    // create custom group1
    $this->openCiviPage('admin/custom/group', 'reset=1');
    $this->clickLink("newCustomDataGroup");
    $this->type("title", $customGrp1);
    $this->select("extends[0]", "value=Case");
    $this->select("extends_1", "value=2");
    $this->clickLink("_qf_Group_next-bottom");

    // get custom group id
    $customGrpId1 = $this->urlArg('gid');

    $customId = $this->_testGetCustomFieldId($customGrpId1, TRUE);
    $cusId_1 = 'custom_' . $customId[0] . '_-1';
    $cusId_2 = 'custom_' . $customId[1] . '_-1';

    // let's give full CiviCase permissions.
    $permission = array(
      'edit-2-access-all-cases-and-activities',
      'edit-2-access-my-cases-and-activities',
      'edit-2-administer-civicase',
      'edit-2-delete-in-civicase',
    );
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    // Go to reserved New Individual Profile to set value for logged in user's contact name (we'll need that later)
    $this->openCiviPage('profile/edit', 'reset=1&gid=4', '_qf_Edit_next');
    $testUserFirstName = "Testuserfirst";
    $testUserLastName = "Testuserlast";
    $this->type("first_name", $testUserFirstName);
    $this->type("last_name", $testUserLastName);
    $this->clickLink("_qf_Edit_next", "profilewrap4");

    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // Adding contact with randomized first name (so we can then select that contact when creating case)
    $custFname = "Mike" . substr(sha1(rand()), 0, 7);
    $custLname = "Krist" . substr(sha1(rand()), 0, 7);
    // We're using pop-up New Contact dialog
    $client = $this->createDialogContact("client_id");

    // Fill in other form values. We'll use a case type which is included in CiviCase sample data / xml files.
    $caseTypeLabel = "Adult Day Care Referral";

    $caseStatusLabel = "Ongoing";
    $subject = "Safe daytime setting - senior female";
    $this->select("medium_id", "value=1");
    $location = "Main offices";
    $this->type("activity_location", $location);
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);

    $this->select("case_type_id", "label={$caseTypeLabel}");
    $this->select("status_id", "label={$caseStatusLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type("duration", "20");
    $this->type("{$cusId_1}", $custFname);
    $this->type("{$cusId_2}", $custLname);
    $this->clickLink("_qf_Case_upload-bottom", "_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->checkCRMAlert("Case opened successfully.");
    $this->clickLink("_qf_CaseView_cancel-bottom");

    $this->openCiviPage('case', 'reset=1');
    $this->waitForElementPresent("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[8]/a[text()='Open Case']");

    $this->click("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[8]/a[text()='Open Case']");

    $openCaseData = array(
      "Client" => $client['display_name'],
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
    // wait for elements to load
    foreach ($openCaseData as $label => $value) {
      $this->waitForElementPresent("xpath=//table/tbody/tr/td[text()='{$label}']/following-sibling::td");
    }
    $this->webtestVerifyTabularData($openCaseData);

    // verify if custom data is present
    $this->openCiviPage('case', 'reset=1');
    $this->clickLink("xpath=//table[@class='caseSelector']/tbody//tr/td[2]/a[text()='{$client['sort_name']}']/../../td[9]/span/a[text()='Manage']", "css=#{$customGrp1} .crm-accordion-header");

    $this->click("css=#{$customGrp1} .crm-accordion-header");

    $cusId_1 = 'custom_' . $customId[0] . '_1';
    $cusId_2 = 'custom_' . $customId[1] . '_1';
    $this->clickLink("css=#{$customGrp1} a.button", '_qf_CustomData_cancel-bottom', FALSE);
    $this->waitForElementPresent("xpath=//span[@class='ui-dialog-title']");
    $this->assertElementContainsText("xpath=//span[@class='ui-dialog-title']", "Edit $customGrp1");

    $custFname = "Miky" . substr(sha1(rand()), 0, 7);
    $custLname = "Kristy" . substr(sha1(rand()), 0, 7);
    $this->type("{$cusId_1}", $custFname);

    // Wait for rich text editor element
    $this->waitForElementPresent("css=div#cke_{$cusId_2}");

    $this->fillRichTextField("{$cusId_2}", $custLname, 'CKEditor');
    $this->click("_qf_CustomData_upload");
    // delete custom data
    $this->_testDeleteCustomData($customGrpId1, $customId);
  }

  /**
   * @param int $customId
   * @param string $custFname
   * @param string $custMname
   * @param $custLname
   */
  public function _testAdvansearchCaseData($customId, $custFname, $custMname, $custLname) {
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
