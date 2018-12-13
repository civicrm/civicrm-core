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

require_once 'CiviTest/CiviSeleniumTestCase.php';

/**
 * Class WebTest_Case_AddCaseTest
 */
class WebTest_Case_AddCaseTypeTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddEditCaseType() {
    $caseRoles = array(1 => 'Parent of', 2 => 'Spouse of', 3 => 'Partner of');
    $activityTypes = array(1 => 'Meeting', 2 => 'Contribution', 3 => 'Event Registration');
    $timelineActivityTypes = array(1 => 'Meeting', 2 => 'Phone Call', 3 => 'Email');
    // Log in as admin first to verify permissions for CiviCase
    $this->webtestLogin('admin');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    // let's give full CiviCase permissions to demo user (registered user).
    $permission = array(
      'edit-2-access-all-cases-and-activities',
      'edit-2-access-my-cases-and-activities',
      'edit-2-administer-civicase',
      'edit-2-delete-in-civicase',
    );
    $this->changePermissions($permission);

    // Log in as normal user
    $this->webtestLogin();

    $this->openCiviPage('a/#/caseType/new');

    $caseTypeLabel = "Case Type" . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('title');
    $this->type('title', $caseTypeLabel);

    foreach ($caseRoles as $cRoles) {
      $this->select2("xpath=//tr[@class='addRow']/td/span/div/a", $cRoles, FALSE, TRUE);
    }

    foreach ($activityTypes as $aType) {
      $this->select2("xpath=//tr[@class='addRow']/td/span[@placeholder='Add activity type']/div/a", $aType, FALSE, TRUE);
    }

    $this->click("xpath=//a[text()='Standard Timeline']");
    foreach ($timelineActivityTypes as $tActivityType) {
      $this->select2("xpath=//tr[@class='addRow']/td/span[@placeholder='Add activity']/div/a", $tActivityType, FALSE, TRUE);
    }

    $this->click('css=.crm-submit-buttons button:first-child');

    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');
    $client = $this->createDialogContact("client_id");

    $caseStatusLabel = "Ongoing";
    $subject = "Safe daytime setting - senior female";
    $this->select("medium_id", "value=1");
    $location = "Main offices";
    $this->type("activity_location", $location);
    $details = "65 year old female needs safe location during the day for herself and her dog. She is in good health but somewhat disoriented.";
    $this->fireEvent('activity_details', 'focus');
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);
    $this->waitForElementPresent('case_type_id');
    $this->waitForElementPresent('status_id');
    $this->select("case_type_id", "label=$caseTypeLabel");
    $this->select("status_id", "label={$caseStatusLabel}");
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type("duration", "20");
    $this->clickLink("_qf_Case_upload-bottom", "_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Case opened successfully.");

    foreach ($activityTypes as $aType) {
      $this->assertElementPresent("xpath=//div[@class='case-control-panel']/div/p/select", $aType);
    }

    $this->click("xpath=//div[contains(text(), 'Roles')]");
    $this->waitForAjaxContent();

    // check that expected roles are listed in the Case Roles pane
    foreach ($caseRoles as $key => $role) {
      $this->assertElementContainsText("css=div.crm-case-roles-block", $role);
    }

    $id = $this->urlArg('id');
    // check that expected activities are listed in the Case Activities table
    foreach ($timelineActivityTypes as $tActivityType) {
      $this->assertElementContainsText("case_id_$id", $tActivityType);
    }

    // for edit case type
    $this->openCiviPage('a/#/caseType');
    $this->waitForElementPresent("xpath=//*[@id='crm-main-content-wrapper']/div/div/div[2]/a/span[contains(text(),'New Case Type')]");

    $this->click("xpath=//table/tbody//tr/td[1][text()='{$caseTypeLabel}']/../td[5]/span/a[text()='Edit']");
    $this->waitForElementPresent("css=.crm-submit-buttons button:first-child");

    $editCaseTypeLabel = "Case Type Edit" . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('title');
    $this->type('title', $editCaseTypeLabel);

    $this->select2("xpath=//div[@id='crm-main-content-wrapper']/div/div/form/div/div[4]/table/tfoot/tr/td/span/div/a", 'Sibling of', FALSE, TRUE);
    $this->click("xpath=//form[@name='editCaseTypeForm']/div/div[4]/table/tbody/tr[4]/td[2]/input[@type='checkbox']");

    $this->click("xpath=//a[text()='Standard Timeline']");
    $this->select2("xpath=//tr[@class='addRow']/td/span[@placeholder='Add activity']/div/a", 'SMS', FALSE, TRUE);

    $this->click('css=.crm-submit-buttons button:first-child');
    $this->waitForElementPresent("xpath=//*[@id='crm-main-content-wrapper']/div/div/div[2]/a/span[contains(text(),'New Case Type')]");

    $this->verifyText("xpath=//table/tbody//tr/td[contains(text(),'$editCaseTypeLabel')]", $editCaseTypeLabel);
  }

}
