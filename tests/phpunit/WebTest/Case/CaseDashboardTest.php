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
class WebTest_Case_CaseDashboardTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAllOrMyCases() {
    // Log in as admin first to verify permissions for CiviCase
    $this->webtestLogin('true');

    // Enable CiviCase module if necessary
    $this->enableComponents("CiviCase");

    // let's give full CiviCase permissions to demo user (registered user).
    $permission = array('edit-2-access-all-cases-and-activities', 'edit-2-access-my-cases-and-activities', 'edit-2-administer-civicase', 'edit-2-delete-in-civicase');
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
    $this->clickLink("name=find_my_cases", "css=input.form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=2"), 'Find my cases button not properly setting search form value to my cases.');

    // Go back to dashboard
    $this->openCivipage('case', 'reset=1', 'css=a.button');

    // Click on a drilldown cell and check if right radio is checked
    $this->clickLink("css=a.crm-case-summary-drilldown", "css=input.form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=1"), 'Drilldown on dashboard summary cells not properly setting search form value to all cases.');

    // Go back to dashboard and reset to my cases
    $this->openCiviPage('case', 'reset=1', 'css=a.button');
    $this->clickLink("name=allupcoming value=0", "css=a.button");

    // Click on a drilldown cell and check if right radio is checked
    $this->clickLink("css=a.crm-case-summary-drilldown", "css=input.form-submit");
    $this->assertTrue($this->isChecked("name=case_owner value=2"), 'Drilldown on dashboard summary cells not properly setting search form value to my cases.');
  }
}

