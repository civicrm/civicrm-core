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
class WebTest_Contact_GroupAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testGroupAdd() {
    $this->webtestLogin();

    $this->openCiviPage('group/add', 'reset=1', '_qf_Edit_upload-bottom');

    // Group name
    $params = array('name' => 'group_' . substr(sha1(rand()), 0, 7));

    // fill group name
    $this->type("title", $params['name']);

    // fill description
    $this->type("description", "Adding new group.");

    // check Access Control
    if (isset($params['type1']) && $params['type1'] !== FALSE) {
      $this->click("group_type[1]");
    }

    // check Mailing List
    if (isset($params['type2']) && $params['type2'] !== FALSE) {
      $this->click("group_type[2]");
    }

    // select Visibility as Public Pages
    $params['visibility'] = 'Public Pages';

    $this->select("visibility", "value={$params['visibility']}");

    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "The Group '{$params['name']}' has been saved.");

    $this->openCiviPage('group', 'reset=1');
    $this->type('title', $params['name']);
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//table[@id='crm-group-selector']/tbody/tr/td[3]/a");
    $createdBy = $this->getText("xpath=//table[@id='crm-group-selector']/tbody/tr/td[3]/a");
    $this->click("xpath=//table[@id='crm-group-selector']/tbody/tr/td[7]/span/a[2]");
    $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/table[2]/tbody/tr/td[2]/select");

    //assert created by in the edit page
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Edit']/div[2]/div/table/tbody/tr[2]/td[contains(text(),'Created By')]/following-sibling::td[contains(text(),'{$createdBy}')]"));
    $this->openCiviPage('group', 'reset=1');

    //search groups using created by
    $this->type('created_by', $createdBy);
    $this->click('_qf_Search_refresh');
    $this->waitForElementPresent("xpath=//table[@id='crm-group-selector']/tbody//tr//td[3]/a");
    $this->assertTrue($this->isElementPresent("xpath=//table[@id='crm-group-selector']/tbody//tr/td[1][text()='{$params['name']}']/following-sibling::td[2]/a[text()='{$createdBy}']"));

    //check link of the contact who created the group
    $this->click("xpath=//table[@id='crm-group-selector']/tbody//tr/td[1][text()='{$params['name']}']/following-sibling::td[2]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $name = explode(',', $createdBy);
    $displayName = isset($name[1]) ? "{$name[1]} {$name[0]}" : "{$name[0]}";
    $this->assertElementContainsText("css=div.crm-summary-display_name",$displayName);
  }

  function testGroupReserved() {
    $this->webtestLogin(true);

    $this->openCiviPage('group/add', 'reset=1', '_qf_Edit_upload-bottom');

    // take group name
    $params = array('name' => 'group_' . substr(sha1(rand()), 0, 7));

    // fill group name
    $this->type("title", $params['name']);

    // fill description
    $this->type("description", "Adding new reserved group.");

    // check Access Control
    if (isset($params['type1']) && $params['type1'] !== FALSE) {
      $this->click("group_type[1]");
    }

    // check Mailing List
    if (isset($params['type2']) && $params['type2'] !== FALSE) {
      $this->click("group_type[2]");
    }

    // select Visibility as Public Pages
    if (empty($params['visibility'])) {
      $params['visibility'] = 'Public Pages';
    }

    $this->select("visibility", "value={$params['visibility']}");

    // Check Reserved box
    $this->click("is_reserved");
    
    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("The Group '{$params['name']}' has been saved."));
    
    // Create a new role w/o reserved group permissions
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
    $permissions = array(
      "edit-{$roleId}-view-all-contacts",
      "edit-{$roleId}-access-civicrm",
    );
    $this->changePermissions($permissions);
    
    // Now logout as admin, login as regular user and verify that Group settings, delete and disable links are not available
    $this->openCiviPage('logout', 'reset=1', NULL);
    $this->open($this->sboxPath);
    $this->waitForElementPresent('edit-submit');
    $this->type('edit-name', $user);
    $this->type('edit-pass', 'Test12345');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    
    $this->openCiviPage('group', 'reset=1');
    $this->type('title', $params['name']);
    $this->click('_qf_Search_refresh');
    $this->waitForTextPresent("Adding new reserved group.");
    // Settings link should NOT be included in selector after search returns with only the reserved group.
    $this->assertElementNotContainsText("css=td.crm-group-group_links", "Settings");

    //login as admin and delete the role
    $this->openCiviPage('logout', 'reset=1', NULL);
    $this->open($this->sboxPath);
    $this->webtestLogin(TRUE);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->_roleDelete($role);

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
  
  function _roleDelete($role) {
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->click("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->waitForElementPresent('edit-delete');
    $this->click('edit-delete');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("edit-submit");
    $this->waitForTextPresent("The role has been deleted.");
  }
  
}


