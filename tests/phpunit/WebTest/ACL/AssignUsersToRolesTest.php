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
    $this->type("value", "Acl value" . $label);
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

  /**
   * Check ACL for Smart Groups and Profiles.
   */
  public function testACLforSmartGroupsAndProfiles() {
    $this->webtestLogin();

    //Create role
    $role = 'role' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForAjaxContent();
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
    $this->assertTrue($this->isElementPresent("xpath=//table[@class='selector row-highlight']/tbody/tr/td[3]/a[text()='{$lastName}, {$firstName}']"));
    $this->click("xpath=//table[@class='selector row-highlight']/tbody//tr/td[1]/input[@type='checkbox']");
    $this->click('_qf_Basic_next_action');
    $this->waitForElementPresent("_qf_AddToGroup_back-bottom");
    $this->click('_qf_AddToGroup_next-bottom');
    $this->waitForText('crm-notification-container', "1 contact added to group");

    //create Smart Group
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("location");
    $this->waitForElementPresent("country");
    $this->select("country", "UNITED STATES");
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForElementPresent("task");
    $this->click('radio_ts', 'ts_all');
    $this->click('task');
    $this->select('task', 'label=Group - create smart group');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $smartGroupTitle = "SmartGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $smartGroupTitle);
    $this->clickLink("_qf_SaveSearch_next-bottom");
    $this->waitForText('crm-notification-container', "Your smart group has been saved as \'$smartGroupTitle\'");

    //Create ACL role
    $this->openCiviPage("admin/options/acl_role", "reset=1", "xpath=//a[@class='button new-option']");
    $this->click("xpath=//a[@class='button new-option']");
    $label = "TestAclRole" . substr(sha1(rand()), 0, 4);
    $this->waitForElementPresent("label");
    $this->type("label", $label);
    $this->click("_qf_Options_next-bottom");
    $this->waitForText('crm-notification-container', "The ACL Role '{$label}' has been saved.");

    // Assign group to ACL role created
    $this->openCiviPage("acl/entityrole", "reset=1", 'newACL');
    $this->click('newACL');
    $this->waitForElementPresent("acl_role_id");
    $this->select("acl_role_id", "label=" . $label);
    $this->waitForAjaxContent();
    $this->select("entity_id", "label={$groupTitle}");
    $this->clickLink("_qf_EntityRole_next-botttom", 'newACL', FALSE);

    //Create ACL granting 'Edit' access on smart group to the role
    $this->waitForAjaxContent();
    $this->openCiviPage("acl", "reset=1");
    $this->click('newACL');
    $this->waitForElementPresent("group_id");
    $this->select("group_id", "label={$smartGroupTitle}");
    $this->select("operation", "label=Edit");
    $this->waitForAjaxContent();
    $this->select("entity_id", "label={$label}");
    $this->type("name", "describe {$label}");
    $this->clickLink("_qf_ACL_next-bottom", 'newACL', FALSE);

    //ACL granting edit permission on events.
    $this->waitForAjaxContent();
    $this->click('newACL');
    $this->waitForElementPresent('name');
    $this->type("name", "Edit All Events $label");
    $this->select("entity_id", "label={$label}");
    $this->waitForAjaxContent();
    $this->select("operation", "label=Edit");
    $this->click("xpath=//label[contains(text(), 'Events')]");
    $this->select("event_id", "value=0");
    $this->clickLink("_qf_ACL_next-bottom", 'newACL', FALSE);

    $this->webtestLogin($user, 'Test12345');
    $this->openCiviPage('event/manage/registration', 'reset=1&action=update&id=3');
    //ensure all the three buttons are not displayed
    $this->waitForElementPresent('registration_screen');
    $this->verifyElementNotPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div/button[contains(text(), 'Edit')]");
    $this->verifyElementNotPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div//button[contains(text(), 'Copy')]");
    $this->verifyElementNotPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div//button[contains(text(), 'Create')]");
    $this->webtestLogout();

    $this->webtestLogin();

    //Create ACL granting Edit permission on Profiles
    $this->openCiviPage("acl", "reset=1", 'newACL');
    $this->click('newACL');
    $this->waitForElementPresent('name');
    $this->type("name", "Edit All Profiles $label");
    $this->select("entity_id", "label={$label}");
    $this->select("operation", "label=Edit");
    $this->click("xpath=//label[contains(text(), 'A profile')]");
    $this->select("uf_group_id", "value=0");
    $this->clickLink("_qf_ACL_next-bottom", 'newACL', FALSE);

    //Login as your role user and do Find Contacts
    $this->webtestLogin($user, 'Test12345');
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("location");
    $this->waitForElementPresent("country");
    $this->select("country", "UNITED STATES");
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForElementPresent("xpath=//div[@class='crm-search-results']");
    $this->assertElementNotContainsText("xpath=//form[@id='Advanced']/div[3]/div/div", "No matches found for");
    $this->verifyText("xpath=//div[@class='crm-search-results']//table/tbody/tr[1]/td[8]", 'UNITED STATES');

    $this->checkEditOnEventProfile();
  }

  /**
   * CRM-16776 - Check Profile Edit on Events with 'manage event profile' permission.
   */
  public function testEventProfilePermission() {
    $this->webtestLogin();

    //create new role
    $role = 'role' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");

    $this->waitForAjaxContent();
    $this->type("edit-name", $role);
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']");
    $roleId = explode('/', $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$role}']/../td[4]/a[text()='edit permissions']/@href"));
    $roleId = end($roleId);

    $this->open($this->sboxPath . "admin/people/create");
    $this->waitForElementPresent("edit-submit");
    $name = "TestUser" . substr(sha1(rand()), 0, 4);
    $this->type("edit-name", $name);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type("edit-mail", $emailId);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");
    $role = "edit-roles-" . $roleId;
    $this->check("name=roles[$roleId] value={$roleId}");

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $permissions = array("edit-{$roleId}-access-civicrm", "edit-{$roleId}-edit-all-events", "edit-{$roleId}-manage-event-profiles");
    $this->changePermissions($permissions);
    $this->webtestLogout();
    $this->webtestLogin($name, 'Test12345');
    $this->checkEditOnEventProfile();
  }

  /**
   * Check Profile Edit on OnlineRegistration Tab
   */
  public function checkEditOnEventProfile() {
    $this->openCiviPage('event/manage/registration', 'reset=1&action=update&id=3');
    //ensure all the three buttons are displayed
    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div/button[contains(text(), 'Edit')]");
    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div//button[contains(text(), 'Copy')]");
    $this->waitForElementPresent("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div//button[contains(text(), 'Create')]");

    $this->click("xpath=//div[@id='registration_screen']/table[2]/tbody/tr/td[2]/div/div/button[contains(text(), 'Edit')]");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("//div[@class='crm-designer-fields-region']");
    if ($this->isElementPresent("xpath=//span[@class='crm-designer-label'][contains(text(), 'City')]")) {
      $this->click("xpath=//span[@class='crm-designer-label'][contains(text(), 'City')]/../../span//a[@title='Remove']");
      $this->waitForElementNotPresent("xpath=//span[@class='crm-designer-label'][contains(text(), 'City')]");
    }
    else {
      $this->click("xpath=//li[@class='crm-designer-palette-section jstree-closed']/a[contains(text(), 'Individual')]");
      $this->waitForAjaxContent();
      $this->doubleClick("xpath=//a[contains(text(), 'Individual')]/../ul//li/a[contains(text(), 'City')]");
      $this->waitForAjaxContent();
    }
    $this->click("xpath=//button/span[contains(text(), 'Save')]");
    $this->waitForElementPresent("crm-notification-container");
    $this->assertElementNotContainsText("crm-notification-container", 'API permission check failed for UFGroup/create call; insufficient permission: require administer CiviCRM');
    $this->click("_qf_Registration_upload-top");
    $this->waitForTextPresent("'Online Registration' information has been saved.");
  }

  /**
   * CRM-16777: Allow to add schedule reminder for event through ACLs 'edit' permission
   */
  public function testACLforReminders() {
    $this->webtestLogin('admin');

    //Details for ACLUser1
    $ACLrole1 = 'ACLrole1' . substr(sha1(rand()), 0, 7);
    $ACLUser1 = "ACLUser1" . substr(sha1(rand()), 0, 4);
    $emailId1 = substr(sha1(rand()), 0, 7) . '@web.com';

    //create ACLrole1 (with 'Access CiviCRM' and 'Access CiviEvent' permissions only).
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->type("edit-name", $ACLrole1);
    $this->waitForElementPresent("edit-add");
    $this->click("edit-add");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$ACLrole1}']");
    $roleId = explode("people/permissions/", $this->getAttribute("xpath=//table[@id='user-roles']/tbody//tr/td[1][text()='{$ACLrole1}']/../td[4]/a[text()='edit permissions']/@href"));
    $permissions = array(
      "edit-{$roleId[1]}-access-civicrm",
      "edit-{$roleId[1]}-access-civievent",
    );
    $this->changePermissions($permissions);

    //Create ACLUser1
    $this->open($this->sboxPath . "admin/people/create");
    $this->waitForElementPresent("edit-submit");
    $this->type("edit-name", $ACLUser1);
    $this->type("edit-mail", $emailId1);
    $this->type("edit-pass-pass1", "Test12345");
    $this->type("edit-pass-pass2", "Test12345");
    $this->click("xpath=//div[@class='form-item form-type-checkboxes form-item-roles']/div//div/label[contains(text(), '{$ACLrole1}')]");
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);
    $this->type("street_address-1", "902C El Camino Way SW");
    $this->type("city-1", "Dumfries");
    $this->type("postal_code-1", "1234");
    $this->select("state_province-1", "value=1019");
    $this->click("edit-submit");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Create group and add contact.
    $this->openCiviPage('group/add', 'reset=1', '_qf_Edit_upload-bottom');
    $groupTitle = 'ACLGroup' . substr(sha1(rand()), 0, 7);
    $this->type("title", $groupTitle);
    $this->click("group_type_1");
    $this->click("_qf_Edit_upload-bottom");
    $this->waitForElementPresent('_qf_Basic_refresh');
    $this->type("sort_name", $firstName);
    $this->click('_qf_Basic_refresh');
    $this->waitForElementPresent('toggleSelect');
    $this->click('_qf_Basic_next_action');
    $this->waitForElementPresent('_qf_AddToGroup_back-bottom');
    $this->click('_qf_AddToGroup_next-bottom');
    $this->waitForTextPresent("1 contact added to group");

    //Add the ACLs
    $this->openCiviPage("admin/options/acl_role", "action=add&reset=1", "_qf_Options_cancel-bottom");
    $label = "TestAclRole" . substr(sha1(rand()), 0, 4);
    $this->type("label", $label);
    $this->type("value", "Acl value" . $label);
    $this->click("_qf_Options_next-bottom");
    $this->waitForText('crm-notification-container', "The ACL Role '{$label}' has been saved.");
    $this->waitForAjaxContent();
    $this->openCiviPage("acl/entityrole", "action=add&reset=1");
    $this->waitForAjaxContent();
    $this->select("acl_role_id", "label=" . $label);
    $this->waitForAjaxContent();
    $this->select("entity_id", "label={$groupTitle}");
    $this->clickLink("_qf_EntityRole_next-botttom");
    $this->openCiviPage("acl", "action=add&reset=1");
    $this->type("name", "Edit Events{$label}");
    $this->select("operation", "label=Edit");
    $this->select("entity_id", "label={$label}");
    $this->waitForElementPresent("xpath=//tr[@class='crm-acl-form-block-object_type']/td[2]/label[contains(text(), 'Events')]");
    $this->click("xpath=//tr[@class='crm-acl-form-block-object_type']/td[2]/label[contains(text(), 'Events')]");
    $this->select("event_id", "label=All Events");
    $this->clickLink("_qf_ACL_next-bottom");
    $this->webtestLogout();
    $this->webtestLogin($ACLUser1, 'Test12345');

    //Add scheduled reminder
    $this->openCiviPage("event/manage/reminder", "reset=1&action=browse&setTab=1&id=1");
    $reminderTitle = "Fall Fundraiser Dinner" . substr(sha1(rand()), 0, 4);
    $this->waitForElementPresent('newScheduleReminder');
    $this->click("newScheduleReminder");
    $this->waitForElementPresent("_qf_ScheduleReminders_next-bottom");
    $this->type("title", $reminderTitle);
    $this->select('entity', 'label=Registered');
    $this->select('start_action_offset', 'label=1');
    $this->select('start_action_condition', 'label=after');
    $this->click('is_repeat');
    $this->select('repetition_frequency_interval', 'label=2');
    $this->select('end_date', 'label=Event End Date');
    $this->click('recipient');
    $this->select('recipient', 'label=Participant Role');
    $subject = 'subject' . substr(sha1(rand()), 0, 4);
    $this->type('subject', $subject);
    $this->fillRichTextField("html_message", "This is the test HTML version here!!!", 'CKEditor');
    $this->type("text_message", "This is the test text version here!!!");
    $this->click('_qf_ScheduleReminders_next-bottom');
    $this->webtestLogout();

    //Disable the ACLs
    $this->webtestLogin('admin');
    $this->openCiviPage("acl", "reset=1");
    $this->waitForAjaxContent();
    $this->click("xpath=//div[contains(text(), 'Edit Events{$label}')]/../../td[7]/span/a[2][contains(text(), 'Disable')]");
    $this->waitForTextPresent("Are you sure you want to disable this ACL?");
    $this->click("xpath=//button//span[contains(text(), 'Yes')]");

    //Login with same test-user created above
    $this->webtestLogin($ACLUser1, 'Test12345');
    $this->openCiviPage("event/manage", "reset=1");
    $this->waitForElementPresent("xpath=//div[@id='event_status_id']/div[@class='dataTables_wrapper no-footer']");
    $this->verifyText("xpath=//div[@id='event_status_id']/div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td", "None found.");
  }

}
