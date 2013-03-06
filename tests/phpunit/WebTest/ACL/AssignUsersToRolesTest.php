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
class WebTest_ACL_AssignUsersToRolesTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAssignUsersToRoles() {
    $this->open($this->sboxPath);

    $this->webtestLogin();
    // Go directly to the URL of the screen that will create new group.
    $this->openCiviPage("group/add", "reset=1");
    $groupTitle = "testGroup" . substr(sha1(rand()), 0, 4);
    $this->type("title", $groupTitle);
    $this->click("group_type[1]");
    $this->click("_qf_Edit_upload-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('crm-notification-container', "The Group '{$groupTitle}' has been saved.");

    // Go directly to the URL that will create a new ACL role
    $this->openCiviPage("admin/options/acl_role", "group=acl_role&action=add&reset=1", "_qf_Options_cancel-bottom");

    $label = "TestAclRole" . substr(sha1(rand()), 0, 4);
    $this->type("label", $label);
    $this->click("_qf_Options_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "The Acl Role '{$label}' has been saved");


    // Go directly to the URL of the screen that will assign users to role.
    $this->openCiviPage("acl/entityrole", "action=add&reset=1");

    $this->select("acl_role_id", "label=" . $label);
    $this->select("entity_id", "label={$groupTitle}");

    $this->click("_qf_EntityRole_next-botttom");
    $this->waitForPageToLoad($this->getTimeoutMsec());


    // Go directly to the URL of the screen that will manage ACLs
    $this->openCiviPage("acl", "action=add&reset=1");
    $this->click("group_id");
    $this->select("group_id", "label={$groupTitle}");
    $this->select("operation", "label=View");
    $this->select("entity_id", "label={$label}");
    $this->type("name", "describe {$label}");
    $this->click("_qf_ACL_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
}

