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
 * Class WebTest_Contact_GroupAddTest
 */
class WebTest_Contact_GroupAddTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testGroupAdd() {
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
    $this->waitForText('crm-notification-container', "The Group '{$params['name']}' has been saved.");

    $this->openCiviPage('group', 'reset=1');
    $this->type('title', $params['name']);
    $this->click('title');
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//table/tbody//tr/td/div[contains(text(), '{$params['name']}')]");
    $createdBy = $this->getText("xpath=//table/tbody//tr/td[3]/a");
    $this->click("xpath=//table/tbody//tr/td[7]//span/a[text()='Settings']");
    $this->waitForElementPresent("xpath=//form[@id='Edit']/div[2]/div/table[1]/tbody/tr[2]/td[contains(text(), '{$createdBy}')]");
    $this->openCiviPage('group', 'reset=1');

    //search groups using created by
    $this->type('created_by', $createdBy);
    $this->click('created_by');

    //show maximum no. of groups on first result set page
    //as many groups can be created by same creator
    //and checking is done on first result set page
    $this->waitForVisible("xpath=//table[@class='crm-group-selector crm-ajax-table dataTable no-footer']");
    $this->select("xpath=//div[@class='dataTables_length']/label/select", '100');
    $this->waitForVisible("xpath=//table[@class='crm-group-selector crm-ajax-table dataTable no-footer']");

    $this->waitForElementPresent("xpath=//table/tbody/tr/td/div[contains(text(), '{$params['name']}')]");
    $this->click("xpath=//table/tbody/tr/td/div[contains(text(), '{$params['name']}')]/../following-sibling::td[2]/a[text()='{$createdBy}']");
    $this->waitForElementPresent("xpath=//table/tbody/tr/td/div[contains(text(), '{$params['name']}')]/../following-sibling::td[2]/a[text()='{$createdBy}']");

    //check link of the contact who created the group
    $this->clickLink("xpath=//table/tbody//tr/td[1]/div[contains(text(),'{$params['name']}')]/../following-sibling::td[2]/a", "css=div.crm-summary-display_name", FALSE);
    $name = explode(',', $createdBy);
    $name1 = isset($name[1]) ? trim($name[1]) : NULL;
    $name0 = trim($name[0]);
    $displayName = isset($name1) ? "{$name1} {$name0}" : "{$name0}";
    $this->assertElementContainsText("css=div.crm-summary-display_name", $displayName);
  }

  public function testGroupReserved() {
    $this->webtestLogin('admin');

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
    $this->clickLink("_qf_Edit_upload");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "The Group '{$params['name']}' has been saved.");

    // Create a new role w/o reserved group permissions
    $role = 'role' . substr(sha1(rand()), 0, 7);
    $this->open($this->sboxPath . "admin/people/permissions/roles");

    $this->waitForElementPresent("edit-add");
    $this->type("edit-name", $role);
    $this->clickLink("edit-add", NULL);

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

    // Now logout as admin, login as regular user and verify that Group settings,
    // delete and disable links are not available
    $this->webtestLogin($user, 'Test12345');

    $this->openCiviPage('group', 'reset=1');
    $this->type('title', $params['name']);
    $this->click('title');
    $this->waitForTextPresent("Adding new reserved group.");
    // Settings link should NOT be included in selector
    // after search returns with only the reserved group.
    $this->assertElementNotContainsText("css=td.crm-group-group_links", "Settings");

    //login as admin and delete the role
    $this->webtestLogin('admin');
    $this->open($this->sboxPath . "admin/people/permissions/roles");
    $this->_roleDelete($role);
  }

  /**
   * @param int $roleid
   *
   * @return string
   */
  public function _testCreateUser($roleid) {
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

  /**
   * @param $role
   */
  public function _roleDelete($role) {
    $this->waitForElementPresent("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->click("xpath=//table[@id='user-roles']/tbody//tr/td[text()='{$role}']/..//td/a[text()='edit role']");
    $this->waitForElementPresent('edit-delete');
    $this->click('edit-delete');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("edit-submit");
    $this->waitForTextPresent("The role has been deleted.");
  }

  /**
   * Webtest for add contact to group (CRM-15108)
   */
  public function testAddContactToGroup() {
    $this->webtestLogin();
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $this->waitForElementPresent('_qf_Contact_upload_view-bottom');

    //Create contact.
    $group = "Advisory Board";
    $firstName = "Adams" . substr(sha1(rand()), 0, 4);
    $lastName = substr(sha1(rand()), 0, 4);
    $email = "{$lastName}.{$firstName}@example.org";
    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");
    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForText('crm-notification-container', "Contact Saved");

    $this->openCiviPage('group', 'reset=1');
    $this->waitForElementPresent("xpath=//div[@id='crm-main-content-wrapper']/div[@class='crm-submit-buttons']/a/span[text()=' Add Group']");
    $this->waitForElementPresent("xpath=//table[@id='DataTables_Table_0']/tbody//tr/td[1]/div[contains(text(), '{$group}')]");
    $this->click("xpath=//table[@id='DataTables_Table_0']/tbody//tr/td[1]/div[text()='{$group}']/../../td[7]/span[1]/a[1]");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->clickLink("xpath=//form[@id='Basic']/div[2]/a/span");
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

    $this->openCiviPage('contact/search', 'reset=1');
    $this->waitForElementPresent("_qf_Basic_refresh");
    $this->type('sort_name', $firstName);
    $this->select('group', "Advisory Board");
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isElementPresent("xpath=//table/tbody//tr/td[3]/a[text()='{$lastName}, {$firstName}']"));
  }

  /**
   * CRM-18585 - test to check OR operator on Smart Groups
   */
  public function testAddSmartGroup() {
    $this->webtestLogin();
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->click("xpath=//input[@value='OR']");
    $this->select('group', 'Advisory Board');
    $this->select('contact_tags', 'Major Donor');
    $this->clickLink("_qf_Advanced_refresh");
    $this->waitForElementPresent("task");
    $count = trim($this->getText("//div[@id='search-status']/table/tbody/tr/td"));

    //create smart group for contacts resulted from OR operator search.
    $this->click('radio_ts', 'ts_all');
    $this->click('task');
    $this->select('task', 'label=Group - create smart group');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $smartGroupTitle = "SmartGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $smartGroupTitle);
    $this->clickLink("_qf_SaveSearch_next-bottom");
    $this->waitForText('crm-notification-container', "Your smart group has been saved as '$smartGroupTitle'");
    $this->clickLink("_qf_Result_done");
    $expectedCount = explode('-', $this->getText("//div[@id='search-status']/table/tbody/tr/td"));
    $this->assertEquals($count, trim($expectedCount[1]));

    //Assert the count from Contacts link in Manage Group Page.
    $this->openCiviPage('group', 'reset=1');
    $this->waitForElementPresent("xpath=//table/tbody//tr//td/div[contains(text(), \"{$smartGroupTitle} (Smart Group)\")]");
    $this->clickLink("xpath=//table/tbody//tr//td/div[contains(text(), \"{$smartGroupTitle} (Smart Group)\")]/../../td[@class='crm-group-group_links']/span/a[contains(text(), 'Contacts')]");
    $this->waitForElementPresent("xpath=//span[contains(text(), \"Edit Smart Group Search Criteria for {$smartGroupTitle}\")]");
    $this->clickLink("xpath=//a/span[contains(text(), \"Edit Smart Group Search Criteria for {$smartGroupTitle}\")]/");
    $this->waitForElementPresent('search-status');
    $expectedCount = explode('-', $this->getText("//div[@id='search-status']/table/tbody/tr/td"));
    $this->assertEquals($count, trim($expectedCount[1]));
  }

}
