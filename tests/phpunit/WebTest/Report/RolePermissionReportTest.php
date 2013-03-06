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
class WebTest_Report_RolePermissionReportTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testRolePermissionReport() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin(TRUE);

    //create new roles
    $role1 = 'role1' . substr(sha1(rand()), 0, 7);
    $role2 = 'role2' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("edit-add");
    $this->type("edit-name", $role1);
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("edit-add");
    $this->type("edit-name", $role2);
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "admin/people/permissions/roles");

    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role1}']");
    $roleid = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role1}']/../td[4]/a[text()='edit permissions']/@href"));
    $roleId1 = end($roleid);
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role2}']");
    $roleid = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role2}']/../td[4]/a[text()='edit permissions']/@href"));
    $roleId2 = end($roleid);

    $user1 = $this->_testCreateUser($roleId1);
    $user2 = $this->_testCreateUser($roleId2);
    $this->open($this->sboxPath . "user/logout");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // let's give full CiviReport permissions.
    $permissions = array(
      "edit-2-access-civireport",
      "edit-2-view-all-contacts",
      "edit-2-administer-civicrm",
      "edit-2-access-civicrm",
    );
    $this->changePermissions($permissions);

    // change report setting to for a particular role
    $this->openCiviPage('report/instance/1', 'reset=1');
    $this->click("css=div.crm-report_setting-accordion div.crm-accordion-header");
    $this->waitForElementPresent("_qf_Summary_submit_save");
    $this->select("permission", "value=access CiviCRM");
    $this->select("grouprole-f", "value=$role1");
    $this->click("add");
    $this->click("_qf_Summary_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('logout','reset=1');
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user2);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('report/instance/1', 'reset=1');
    $this->assertElementContainsText('crm-container', 'You do not have permission to access this report.');
    $this->openCiviPage('report/list', 'reset=1');
    $this->openCiviPage('logout', 'reset=1');

    //delete roles
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->_roleDelete($role1);
    $this->_roleDelete($role2);
  }

  /*
   *check for CRM-10148
   */
  function testReservedReportPermission() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin(TRUE);

    //create new role
    $role = 'role' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");

    $this->waitForElementPresent("edit-add");
    $this->type("edit-name", $role);
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']");
    $roleId = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']/../td[4]/a[text()='edit permissions']/@href"));
    $roleId = end($roleId);
    $user = $this->_testCreateUser($roleId);
    $this->openCiviPage('report/instance/1', 'reset=1');
    if ($this->isChecked("is_reserved")) {
      $this->click("is_reserved");
      $this->click("_qf_Summary_submit_save");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    $permissions = array(
      "edit-{$roleId}-access-civireport",
      "edit-{$roleId}-view-all-contacts",
      "edit-{$roleId}-administer-reports",
      "edit-{$roleId}-access-civicrm"
    );
    $this->changePermissions($permissions);

    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('report/instance/1', 'reset=1');

    //check if the reserved report field is frozen
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='instanceForm']//table[3]/tbody//tr/td[2]/tt[text()='[ ]']"));

    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->webtestLogin(TRUE);
    // let's give full CiviReport permissions.
    $permissions = array(
      "edit-{$roleId}-access-civireport",
      "edit-{$roleId}-view-all-contacts",
      "edit-{$roleId}-administer-reports",
      "edit-{$roleId}-access-civicrm",
      "edit-{$roleId}-administer-reserved-reports"
    );
    $this->changePermissions($permissions);

    $this->openCiviPage('report/instance/1', 'reset=1');

    //make the report reserved
    $this->click("is_reserved");
    $this->click("_qf_Summary_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('report/instance/1', 'reset=1');

    //check if the report criteria and settings is accessible
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Summary']//div[@id='id_default']//input[@id='fields_email']"));
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Summary']//div[@id='instanceForm']/table//input[@id='title']"));

    //login as admin and remove reserved permission
    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . "admin/people/permissions");
    $this->waitForElementPresent("edit-submit");

    if ($this->isChecked("edit-2-administer-reserved-reports")) {
      $this->click("edit-2-administer-reserved-reports");
    } else {
      $this->click("edit-{$roleId}-administer-reserved-reports");
    }
    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //login as user and check for absence of report criteria and settings
    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('report/instance/1', 'reset=1');

    if ($this->isElementPresent("xpath=//form[@id='Summary']/div[2]/div/div/div")) {
      $this->verifyNotText("xpath=//form[@id='Summary']/div[2]/div/div/div", "Report Criteria");
    }
    if ($this->isElementPresent("xpath=//form[@id='Summary']/div[2]/div[2]/div")) {
      $this->verifyNotText("xpath=//form[@id='Summary']/div[2]/div[2]/div", "Report Settings");
    }

    $this->assertFalse($this->isElementPresent("xpath=//form[@id='Summary']//div[@id='instanceForm']//input[@id='title']"));

    //login as admin and turn the is_reserved flag off for the instance
    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->webtestLogin(TRUE);
    $this->openCiviPage('report/instance/1', 'reset=1');
    $this->click("is_reserved");
    $this->click("_qf_Summary_submit_save");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->openCiviPage('report/instance/1', 'reset=1');

    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Summary']//div[@id='id_default']//input[@id='fields_email']"));
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Summary']//div[@id='instanceForm']//input[@id='title']"));

    //login as admin and delete the role
    $this->openCiviPage('logout', 'reset=1');
    $this->open($this->sboxPath);
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->_roleDelete($role);
  }

  function _roleDelete($role) {
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->click("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->waitForElementPresent('edit-delete');
    $this->click('edit-delete');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("edit-submit");
    $this->waitForTextPresent("The role has been deleted.");
  }

  function _testCreateUser($roleid) {

    // Go directly to the URL of the screen that will Create User Authentically.
    $this->open($this->sboxPath . "admin/people/create");

    $this->waitForElementPresent("edit-submit");

    $name = "TestUser" . substr(sha1(rand()), 0, 4);
    $this->type("edit-name", $name);

    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");
    $role = "edit-roles-" . $roleid;
    $this->check("name=roles[$roleid] value={$roleid}");

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //Address Details
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");

    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    return $name;
  }
}