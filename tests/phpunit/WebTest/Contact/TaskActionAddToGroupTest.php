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
class WebTest_Contact_TaskActionAddToGroupTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testAddContactsToGroup() {

    $this->webtestLogin();
    $newGroupName = 'Group_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($newGroupName);

    // Create two new contacts with a common random string in email address
    $emailString = substr(sha1(rand()), 0, 7) . '@example.com_';
    $cids = array();
    for ($i = 0; $i < 2; $i++) {
      // create new contact
      $this->webtestAddContact();

      // get cid of new contact
      $queryParams = $this->parseURL();
      $cids[] = $queryParams['queryString']['cid'];

      // update email of new contact
      $this->click("//ul[@id='actions']/li/a/span[text()='Edit']");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->type("email_1_email", $emailString . $i . 'webtest');
      $this->click("_qf_Contact_upload_view");
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }

    // goto advanced search
    $this->openCiviPage("contact/search/advanced", "reset=1", "email");

    $this->type("email", $emailString);
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Verify exactly two contacts found
    $this->assertTrue($this->isTextPresent("2 Contacts"), 'Looking for 2 results with email like ' . $emailString);

    // Click "check all" box and act on "Add to group" action
    $this->click('toggleSelect');
    $this->select("task", "label=Add Contacts to Group");
    sleep(1);
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Select the new group and click to add
    $this->click("group_id");
    $this->select("group_id", "label=" . $newGroupName);
    $this->click("_qf_AddToGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check status messages are as expected
    $this->assertElementContainsText('crm-notification-container', "Added Contacts to {$newGroupName}");
    $this->assertElementContainsText('crm-notification-container', "2 contacts added to group");

    // Search by group membership in newly created group
    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->select("crmasmSelect1", "label=" . $newGroupName);
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Verify those two contacts (and only those two) are in the group
    if (!$this->isTextPresent("2 Contacts")) {
      die("nothing found for group $newGroupName");
    }

    $this->assertTrue($this->isTextPresent("2 Contacts"), 'Looking for 2 results belonging to group: ' . $newGroupName);
    foreach ($cids as $cid) {
      $this->assertTrue($this->isElementPresent('rowid' . $cid));
    }

  }

  function testMultiplePageContactSearchAddContactsToGroup() {
    $this->webtestLogin();
    $newGroupName = 'Group_' . substr(sha1(rand()), 0, 7);
    $this->WebtestAddGroup($newGroupName);

    $this->openCiviPage('contact/search', 'reset=1');
    $this->click("_qf_Basic_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->click("xpath=//div[@class='form-item float-right']/a[text()='25']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("toggleSelect");
    $this->click("xpath=//div[@class='crm-content-block']/div/div[2]/div/span[2]/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->click("toggleSelect");
    $this->select("task", "label=Add Contacts to Group");
    $this->click("Go");
    $this->waitForPageToLoad($this->getTimeoutMsec());

     // Select the new group and click to add
    $this->click("group_id");
    $this->select("group_id", "label=" . $newGroupName);
    $this->click("_qf_AddToGroup_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check status messages are as expected
    $this->assertElementContainsText('crm-notification-container', "Added Contacts to {$newGroupName}");
    $this->assertElementContainsText('crm-notification-container', "50 contacts added to group");

    $this->openCiviPage('contact/search/advanced', 'reset=1');
    $this->select("crmasmSelect1", "label=" . $newGroupName);
    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("50 Contacts"), 'Looking for 50 results belonging to group: ' . $newGroupName);
  }
}


