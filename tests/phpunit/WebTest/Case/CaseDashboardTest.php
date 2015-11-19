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
 * Class WebTest_Case_CaseDashboardTest
 */
class WebTest_Case_CaseDashboardTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAllOrMyCases() {
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

    $this->openCiviPage('case', 'reset=1');

    // Should default to My Cases
    $this->assertTrue($this->isChecked("name=allupcoming value=0"), 'Case dashboard should default to My Cases.');
    // The header text of the table changes too
    $this->assertElementContainsText('crm-container', "Summary of Involvement");

    $this->clickLink("name=allupcoming value=1", "css=a.button");

    $this->assertTrue($this->isChecked("name=allupcoming value=1"), 'Selection of All Cases failed.');
    $this->assertElementContainsText('crm-container', "Summary of All Cases");

    // Go back to dashboard
    $this->openCiviPage('case', 'reset=1', 'css=a.button');

    // Click on find my cases and check if right radio is checked
    $this->clickLink("name=find_my_cases", "css=input.crm-form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=2"), 'Find my cases button not properly setting search form value to my cases.');

    //Add case to get drilldown cell on Case dashboard
    $this->openCiviPage('case/add', 'reset=1&action=add&atype=13&context=standalone', '_qf_Case_upload-bottom');

    // We're using pop-up New Contact dialog
    $params = $this->createDialogContact('client_id');

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
    $this->fireEvent('activity_details', 'focus');
    $this->fillRichTextField("activity_details", $details, 'CKEditor');
    $this->type("activity_subject", $subject);

    $this->select("case_type_id", "label={$caseTypeLabel}");
    $this->select("status_id", "label={$caseStatusLabel}");

    // Choose Case Start Date.
    // Using helper webtestFillDate function.
    $this->webtestFillDate('start_date', 'now');
    $today = date('F jS, Y', strtotime('now'));

    $this->type("duration", "20");
    $this->clickLink("_qf_Case_upload-bottom", "_qf_CaseView_cancel-bottom");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Case opened successfully.");

    // Go back to dashboard
    $this->openCiviPage('case', 'reset=1');
    //Check whether case status link opens in search correctly
    $this->clickLink("xpath=//table[@class='report']/tbody/tr[3]/td/a");
    $this->assertElementContainsText('Search', "{$params['last_name']}, {$params['first_name']}");

    // Go back to dashboard
    $this->openCiviPage('case', 'reset=1');

    // Click on a drilldown cell and check if right radio is checked
    $this->clickLink("css=a.crm-case-summary-drilldown", "css=input.crm-form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=1"), 'Drilldown on dashboard summary cells not properly setting search form value to all cases.');

    // Go back to dashboard and reset to my cases
    $this->openCiviPage('case', 'reset=1', 'css=a.button');
    $this->clickLink("name=allupcoming value=0", "css=a.button");

    // Click on a drilldown cell and check if right radio is checked
    $this->clickLink("css=a.crm-case-summary-drilldown", "css=input.crm-form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=2"), 'Drilldown on dashboard summary cells not properly setting search form value to my cases.');
  }

}
