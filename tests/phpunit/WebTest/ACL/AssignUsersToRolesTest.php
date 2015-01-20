<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 * Class WebTest_ACL_AssignUsersToRolesTest
 */
class WebTest_ACL_AssignUsersToRolesTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAssignUsersToRoles() {

    $this->webtestLogin();

    $this->openCiviPage("group/add", "reset=1");
    $groupTitle = "testGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $groupTitle);
    $this->click("group_type[1]");
    $this->click("_qf_Edit_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "The Group '{$groupTitle}' has been saved.");

    $this->openCiviPage("admin/options/acl_role", "action=add&reset=1", "_qf_Options_cancel-bottom");

    $label = "TestAclRole" . substr(sha1(rand()), 0, 4);
    $this->type("label", $label);
    $this->click("_qf_Options_next-bottom");

    $this->waitForText('crm-notification-container', "The ACL Role '{$label}' has been saved.");

    $this->openCiviPage("acl/entityrole", "action=add&reset=1");

    $this->select("acl_role_id", "label=" . $label);
    $this->select("entity_id", "label={$groupTitle}");

    $this->clickLink("_qf_EntityRole_next-botttom");

    $this->openCiviPage("acl", "action=add&reset=1");
    $this->click("group_id");
    $this->select("group_id", "label={$groupTitle}");
    $this->select("operation", "label=View");
    $this->select("entity_id", "label={$label}");
    $this->type("name", "describe {$label}");
    $this->clickLink("_qf_ACL_next-bottom");
  }

  public function testACLforSmartGroups() {
    $this->webtestLogin();

    //Create role
    $role = 'role' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("edit-submit");
    $this->type("edit-name", $role);
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']");
    $roleURL = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']/../td[4]/a[text()='edit permissions']/@href"));
    $roleId = end($roleURL);

    //create user with roleId
    $this->open($this->sboxPath . "admin/people/create");
    $this->waitForElementPresent("edit-submit");
    $user = "TestUser" . substr(sha1(rand()), 0, 4);
    $this->type("edit-name", $user);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");
    $role = "edit-roles-" . $roleId;
    $this->check("name=roles[$roleId] value={$roleId}");
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $permissions = array("edit-{$roleId}-access-civicrm");
    $this->changePermissions($permissions);

    //Create group and add your user's contact to that group
    $this->openCiviPage("group/add", "reset=1");
    $groupTitle = "testGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $groupTitle);
    $this->click("group_type[1]");
    $this->click("_qf_Edit_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "The Group '{$groupTitle}' has been saved.");
    $this->waitForElementPresent("_qf_Basic_refresh");
    $this->type('sort_name', $firstName);
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("_qf_Basic_next_action");
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[3]/a[text()='{$lastName}, {$firstName}']"));
    $this->click("xpath=//table/tbody//tr/td[1]/input[@type='checkbox']");
    $this->click('_qf_Basic_next_action');
    $this->waitForElementPresent("_qf_AddToGroup_back-bottom");
    $this->click('_qf_AddToGroup_next-bottom');
    $this->waitForText('crm-notification-container', "1 contact added to group");

    //create Smart Group
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("location");
    $this->waitForElementPresent("country");
    $this->select("country", "United States");
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForElementPresent("task");
    $this->click('radio_ts', 'ts_all');
    $this->click('task');
    $this->select('task', 'label=New Smart Group');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $smartGroupTitle = "SmartGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $smartGroupTitle);
    $this->clickLink("_qf_SaveSearch_next-bottom");
    $this->waitForText('crm-notification-container', "Your smart group has been saved as \'$smartGroupTitle\'");

    //Create ACL role
    $this->openCiviPage("admin/options/acl_role", "action=add&reset=1", "_qf_Options_cancel-bottom");
    $label = "TestAclRole" . substr(sha1(rand()), 0, 4);
    $this->type("label", $label);
    $this->click("_qf_Options_next-bottom");
    $this->waitForText('crm-notification-container', "The ACL Role '{$label}' has been saved.");

    // Assign group to ACL role created
    $this->openCiviPage("acl/entityrole", "action=add&reset=1");
    $this->select("acl_role_id", "label=" . $label);
    $this->select("entity_id", "label={$groupTitle}");
    $this->clickLink("_qf_EntityRole_next-botttom");

    //Create ACL granting 'Edit' access on smart group to the role
    $this->openCiviPage("acl", "action=add&reset=1");
    $this->click("group_id");
    $this->select("group_id", "label={$smartGroupTitle}");
    $this->select("operation", "label=Edit");
    $this->select("entity_id", "label={$label}");
    $this->type("name", "describe {$label}");
    $this->clickLink("_qf_ACL_next-bottom");

    //Login as your role user and do Find Contacts
    $this->webtestLogin($user, 'Test12345');
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("location");
    $this->waitForElementPresent("country");
    $this->select("country", "United States");
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']");
    $this->assertElementNotContainsText("xpath=//form[@id='Advanced']/div[3]/div/div", "No matches found for");
    $this->verifyText("xpath=//div[@class='crm-search-results']//table/tbody/tr[1]/td[8]", 'United States');
  }

}
