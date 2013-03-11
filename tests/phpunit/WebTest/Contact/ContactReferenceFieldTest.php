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
class WebTest_Contact_ContactReferenceFieldTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testContactReferenceField() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();


    /* add new group */

    $this->openCiviPage('group/add', 'reset=1', '_qf_Edit_upload');

    $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    $this->type("title", $groupName);

    // fill description
    $this->type("description", "Adding new group.");

    // check Access Control
    $this->click("group_type[1]");

    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    /* add contacts */

    // Individual 1
    $contact1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contact1, "Anderson", "{$contact1}@example.com");

    // Add Individual 1 to group
    $this->click('css=li#tab_group a');
    $this->waitForElementPresent('_qf_GroupContact_next');
    $this->select('group_id', "label={$groupName}");
    $this->click('_qf_GroupContact_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Added to Group");

    // Individual 1
    $contact2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contact2, "Anderson", "{$contact2}@example.com");

    // Organization 1
    $org1 = 'org_' . substr(sha1(rand()), 0, 5);
    $this->webtestAddOrganization($org1, "{$org1}@example.com");

    /* create custom group and fields */

    // Add Custom group //
    // Go directly to the URL of the screen that you will be testing (New Custom Group).
    $this->openCiviPage('admin/custom/group', 'action=add&reset=1');

    //fill custom group title
    $customGroupTitle = 'custom_' . substr(sha1(rand()), 0, 7);
    $this->click("title");
    $this->type("title", $customGroupTitle);

    //custom group extends
    $this->click("extends[0]");
    $this->select("extends[0]", "value=Contact");
    $this->click("//option[@value='Contact']");
    $this->click("_qf_Group_next-bottom");
    $this->waitForElementPresent("_qf_Field_cancel-bottom");

    //Is custom group created?
    $this->assertElementContainsText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    $matches = array();
    preg_match('/gid=([0-9]+)/', $this->getLocation(), $matches);
    $customGroupId = $matches[1];

    // Add contact reference fields
    $contactRefFieldLabel1 = 'contact_ref_' . substr(sha1(rand()), 0, 4);
    $this->click("label");
    $this->type("label", $contactRefFieldLabel1);
    $this->select("data_type[0]", "label=Contact Reference");

    $this->waitForElementPresent("group_id");
    $this->select("group_id", $groupName);

    //clicking save
    $this->click("_qf_Field_next_new-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created?
    $this->assertElementContainsText('crm-notification-container', "Your custom field '$contactRefFieldLabel1' has been saved.");

    //add custom field - alphanumeric checkbox
    $contactRefFieldLabel2 = 'contact_ref_' . substr(sha1(rand()), 0, 4);
    $this->click("label");
    $this->type("label", $contactRefFieldLabel2);
    $this->select("data_type[0]", "label=Contact Reference");

    $this->waitForElementPresent("group_id");
    $this->click("xpath=//form[@id='Field']//a[text()='Advanced Filter']");
    $this->waitForElementPresent("filter");

    $this->type("filter", "action=lookup&contact_type=Organization");

    //clicking save
    $this->click("_qf_Field_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Is custom field created?
    $this->assertElementContainsText('crm-notification-container', "Your custom field '$contactRefFieldLabel2' has been saved.");

    $this->openCiviPage('admin/custom/group/field', "reset=1&action=browse&gid={$customGroupId}");

    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[1]/td[8]/span[1]/a[text()='Edit Field']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $matches = array();
    preg_match('/&id=([0-9]+)/', $this->getLocation(), $matches);
    $contactRefFieldID1 = $matches[1];


    $this->openCiviPage('admin/custom/group/field', "reset=1&action=browse&gid={$customGroupId}");

    $this->click("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/span[1]/a[text()='Edit Field']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $matches = array();
    preg_match('/&id=([0-9]+)/', $this->getLocation(), $matches);
    $contactRefFieldID2 = $matches[1];

    // Visit custom group preview page
    $this->openCiviPage('admin/custom/group', "action=preview&reset=1&id={$customGroupId}");

    $this->type("custom_{$contactRefFieldID1}_-1", "Anderson");
    $this->fireEvent("custom_{$contactRefFieldID1}_-1", "focus");
    $this->click("custom_{$contactRefFieldID1}_-1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->assertElementContainsText("css=div.ac_results-inner li", "{$contact1}@example.com");
    $this->assertElementNotContainsText("css=div.ac_results-inner ul li", "{$contact2}@example.com");


    $this->openCiviPage('admin/custom/group', "action=preview&reset=1&id={$customGroupId}");

    $this->type("custom_{$contactRefFieldID2}_-1", $org1);
    $this->fireEvent("custom_{$contactRefFieldID2}_-1", "focus");
    $this->click("custom_{$contactRefFieldID2}_-1");
    $this->waitForElementPresent("css=div.ac_results-inner li");
    $this->assertElementContainsText("css=div.ac_results-inner li", "{$org1}@example.com");
  }
}

