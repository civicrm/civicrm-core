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
 * Class WebTest_Contact_ContactReferenceFieldTest
 */
class WebTest_Contact_ContactReferenceFieldTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testContactReferenceField() {
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
    $this->waitForText('crm-notification-container', "Added to Group");

    // Individual 1
    $contact2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contact2, "Anderson", "{$contact2}@example.com");

    // Organization 1
    $org1 = 'org_' . substr(sha1(rand()), 0, 5);
    $this->webtestAddOrganization($org1, "{$org1}@example.com");

    /* create custom group and fields */

    // Add Custom group //

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
    $this->waitForElementPresent("newCustomField");

    //Is custom group created?
    $this->waitForText('crm-notification-container', "Your custom field set '{$customGroupTitle}' has been added. You can add custom fields now.");

    $customGroupId = $this->urlArg('gid');

    // Add contact reference fields
    $contactRefFieldLabel1 = 'contact_ref_' . substr(sha1(rand()), 0, 4);
    $this->waitForElementPresent("label");
    $this->click("label");
    $this->waitForElementPresent("label");
    $this->type("label", $contactRefFieldLabel1);
    $this->waitForElementPresent("data_type[0]");
    $this->select("data_type[0]", "label=Contact Reference");

    $this->waitForElementPresent("group_id");
    $this->select("group_id", $groupName);

    //clicking save
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[2]/span[2]/");

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$contactRefFieldLabel1' has been saved.");
    $this->waitForElementPresent("xpath=//div[@class='ui-dialog-buttonset']/button[3]/span[2]/");
    //add custom field - alphanumeric checkbox
    $contactRefFieldLabel2 = 'contact_ref_' . substr(sha1(rand()), 0, 4);

    $this->click("label");
    $this->waitForElementPresent("label");
    $this->type("label", $contactRefFieldLabel2);
    $this->waitForElementPresent("data_type[0]");
    $this->select("data_type[0]", "label=Contact Reference");

    $this->waitForElementPresent("group_id");
    $this->click("xpath=//form[@id='Field']//a[text()='Advanced Filter']");
    $this->waitForElementPresent("filter");

    $this->type("filter", "action=get&contact_type=Organization");

    //clicking save
    $this->click("xpath=//div[@class='ui-dialog-buttonset']/button[1]/span[2]/");

    //Is custom field created?
    $this->waitForText('crm-notification-container', "Custom field '$contactRefFieldLabel2' has been saved.");

    $this->openCiviPage('admin/custom/group/field', "reset=1&action=browse&gid={$customGroupId}");

    $fieldid1 = explode("&id=", $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[1]/td[8]/span[1]/a[text()='Edit Field']@href"));
    $fieldid1 = $fieldid1[1];

    $contactRefFieldID1 = $fieldid1;

    $this->openCiviPage('admin/custom/group/field', "reset=1&action=browse&gid={$customGroupId}");

    $fieldid2 = explode("&id=", $this->getAttribute("xpath=//div[@id='field_page']//table/tbody/tr[2]/td[8]/span[1]/a[text()='Edit Field']@href"));
    $fieldid2 = $fieldid2[1];

    $contactRefFieldID2 = $fieldid2;

    // Visit custom group preview page
    $this->openCiviPage('admin/custom/group', "action=preview&reset=1&id={$customGroupId}");

    $this->clickAt("//*[@id='custom_{$contactRefFieldID1}_-1']/../div/a");
    $this->keyDown("//*[@id='select2-drop']/div/input", " ");
    $this->type("//*[@id='select2-drop']/div/input", "Anderson");
    $this->typeKeys("//*[@id='select2-drop']/div/input", "Anderson");
    $this->waitForElementPresent("css=div.select2-result-label span");
    $this->assertElementContainsText("css=div.select2-result-label", "{$contact1}@example.com");
    $this->assertElementNotContainsText("css=div.select2-result-label", "{$contact2}@example.com");

    $this->openCiviPage('admin/custom/group', "action=preview&reset=1&id={$customGroupId}");

    $this->clickAt("//*[@id='custom_{$contactRefFieldID2}_-1']/../div/a");
    $this->keyDown("//*[@id='select2-drop']/div/input", " ");
    $this->type("//*[@id='select2-drop']/div/input", $org1);
    $this->typeKeys("//*[@id='select2-drop']/div/input", $org1);
    $this->waitForElementPresent("css=div.select2-result-label");
    $this->assertElementContainsText("css=div.select2-result-label", "{$org1}@example.com");
  }

}
