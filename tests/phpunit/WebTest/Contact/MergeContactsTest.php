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
 * Class WebTest_Contact_MergeContactsTest
 */
class WebTest_Contact_MergeContactsTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testIndividualAdd() {
    $this->markTestSkipped('Skipping for now as it works fine locally.');
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    // add contact1
    //select prefix
    $prefix = 'Mr.';
    $this->click("prefix_id");
    $this->select("prefix_id", "label=$prefix");

    //fill in first name
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName);

    //fill in email id
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

    //fill in billing email id
    $this->click('addEmail');
    $this->waitForElementPresent('email_2_email');
    $this->type('email_2_email', "$firstName.$lastName@billing.com");
    $this->select('email_2_location_type_id', 'value=5');

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // Add Contact to a group
    $group = 'Newsletter Subscribers';
    $this->click('css=li#tab_group a');
    $this->waitForElementPresent('_qf_GroupContact_next');
    $this->select('group_id', "label=$group");
    $this->click('_qf_GroupContact_next');
    $this->waitForElementPresent('link=Delete');
    $this->waitForText('crm-notification-container', "Added to Group");

    // Add Tags to the contact
    $tag = 'Government Entity';
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent('tagtree');
    $this->click("xpath=//div[@id='tagtree']/ul//li/input/../span/label[text()='$tag']");
    $this->click("css=#tab_summary a");
    $this->assertElementContainsText('css=.crm-summary-block #tags', $tag);

    // Add an activity
    $subject = "This is subject of test activity being added through activity tab of contact summary screen.";
    $this->addActivity($firstName, $lastName, $subject);

    // contact2: duplicate of contact1.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //fill in email
    $this->type("email_1_email", "{$firstName}.{$lastName}@example.com");

    // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // Add second pair of dupes so we can test Merge and Goto Next Pair
    $fname2 = 'Janet';
    $lname2 = 'Rogers' . substr(sha1(rand()), 0, 7);
    $email2 = "{$fname2}.{$lname2}@example.org";
    $this->webtestAddContact($fname2, $lname2, $email2);

    // Can not use helper for 2nd contact since it is a dupe
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $this->type("first_name", $fname2);
    $this->type("last_name", $lname2);
    $this->type("email_1_email", $email2);
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->openCiviPage("contact/dedupefind", "reset=1&rgid=1&action=renew");

    // reload the page
    $this->openCiviPage("contact/dedupefind", "reset=1&rgid=1&action=update");

    // Select the contacts to be merged
    $this->waitForElementPresent('dupePairs_length');
    $this->select("name=dupePairs_length", "value=100");
    $this->waitForElementPresent("xpath=//table[@id='dupePairs']/tbody//tr/td[5]/a[text()='$firstName $lastName']/../../td[8]/a[text()='merge']");
    $this->click("xpath=//table[@id='dupePairs']/tbody//tr/td[5]/a[text()='$firstName $lastName']/../../td[8]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');

    $this->clickLink("css=div.crm-contact-merge-form-block div.action-link a", '_qf_Merge_cancel-bottom');

    // Move the activities, groups, etc to the main contact and merge using Merge and Goto Next Pair
    $this->check('move_prefix_id');
    $this->check('move_location_email_2');
    $this->check('move_rel_table_activities');
    $this->check('move_rel_table_groups');
    $this->check('move_rel_table_tags');
    $this->clickLink('_qf_Merge_next-bottom', '_qf_Merge_cancel-bottom');
    $this->assertTrue($this->isTextPresent('Contacts Merged'), "Contacts Merged text was not found after merge.");

    // Check that we are viewing the next Merge Pair (our 2nd contact, since the merge list is ordered by contact_id)
    $this->assertTrue($this->isTextPresent("{$fname2} {$lname2}"), "Redirect for Goto Next Pair after merge did not work.");

    // Ensure that the duplicate contact has been deleted
    $this->openCiviPage("contact/search/advanced", "reset=1", '_qf_Advanced_refresh');
    $this->type('sort_name', $firstName);
    $this->click('deleted_contacts');
    $this->click('_qf_Advanced_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('1 Contact'), "Deletion of duplicate contact during merge was not successful. Dupe contact not found when searching trash.");

    // Search for the main contact
    $this->openCiviPage("contact/search/advanced", "reset=1", '_qf_Advanced_refresh');
    $this->type('sort_name', $firstName);
    $this->click('_qf_Advanced_refresh');
    $this->waitForElementPresent("xpath=//form[@id='Advanced']/div[3]/div/div[2]/table/tbody/tr");

    $this->click("//form[@id='Advanced']/div[3]/div/div[2]/table/tbody/tr/td[11]/span[1]/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Verify prefix merged
    // $this->verifyText( "xpath=//div[@class='left-corner']/h2", preg_quote( "$prefix $firstName $lastName" ) );

    // Verify billing email merged
    $this->isElementPresent("xpath=//div[@class='contact_details']/div[1][@class='contact_panel']/div[1][@class='contactCardLeft']/table/tbody/tr[4]/td[2]/span/a[text()='$firstName.$lastName@billing.com']");

    // Verify activity merged
    $this->click("css=li#tab_activity a");
    $this->waitForAjaxContent();
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr");
    $this->verifyText("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[5]/a", preg_quote("$lastName, $firstName"));

    // Verify group merged
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("GroupContact");
    $this->waitForElementPresent("xpath=//form[@id='GroupContact']//div[@class='view-content view-contact-groups']//div/table/tbody/tr/td/a");
    $this->verifyText("xpath=//form[@id='GroupContact']//div[@class='view-content view-contact-groups']//div/table/tbody/tr/td/a",
      preg_quote("$group")
    );

    // Verify tag merged
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent('check_5');
    $this->assertChecked("check_3");
  }

  public function testMergeContactSubType() {
    $this->webtestLogin();
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $this->waitForElementPresent('_qf_Contact_cancel-bottom');
    //fill in first name
    $firstName = "Anderson" . substr(sha1(rand()), 0, 4);
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = substr(sha1(rand()), 0, 4);
    $this->type('last_name', $lastName);

    //fill in email id
    $this->waitForElementPresent('email_1_email');
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");
    $this->waitForElementPresent('contact_sub_type');
    $this->select('contact_sub_type', "Parent");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // contact2: contact with same email id as contact 1.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $this->waitForElementPresent('_qf_Contact_cancel-bottom');

    $fName = "John" . substr(sha1(rand()), 0, 4);
    $this->type('first_name', $fName);
    $lName = substr(sha1(rand()), 0, 4);
    $this->type('last_name', $lName);
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");
    $this->waitForElementPresent('contact_sub_type');
    $this->multiselect2('contact_sub_type', array("Student", "Staff"));
    $this->click("_qf_Contact_upload_view");
    $this->waitForText('crm-notification-container', "Contact Saved");
    $this->openCiviPage("contact/deduperules", "reset=1");
    $this->click("xpath=//*[@id='option12']/tbody/tr[3]/td[3]/span/a[1][contains(text(),'Use Rule')]");
    $this->waitForElementPresent('_qf_DedupeFind_submit-bottom');
    $this->click("_qf_DedupeFind_next-bottom");

    $this->waitForElementPresent("xpath=//table[@id='dupePairs']/tbody//tr/td[3]/a[text()='{$firstName} {$lastName}']/../../td[8]/a[2][text()='merge']");
    $this->waitForElementPresent("xpath=//form[@id='DedupeFind']//a/span[contains(text(),'Done')]");
    $this->isElementPresent("xpath=//table[@id='dupePairs']/tbody//tr//td/a[text()='$firstName $lastName']/../../td[5]/a[text()='{$fName} {$lName}']/../../td[8]/a[text()='merge']");
    $this->click("xpath=//table[@id='dupePairs']/tbody//tr/td[3]/a[text()='{$firstName} {$lastName}']/../../td[8]/a[2][text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->click('toggleSelect');
    $this->click('_qf_Merge_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("Staff, Student"));
  }

  /**
   * @param string $firstName
   * @param string $lastName
   * @param $subject
   */
  public function addActivity($firstName, $lastName, $subject) {
    $withContact = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($withContact, "Anderson", $withContact . "@anderson.name");

    $this->click("css=li#tab_activity a");

    // waiting for the activity dropdown to show up
    $this->waitForElementPresent("other_activity");

    // Select the activity type from the activity dropdown
    $this->select("other_activity", "label=Meeting");

    // waitForPageToLoad is not always reliable. Below, we're waiting for the submit
    // button at the end of this page to show up, to make sure it's fully loaded.
    $this->waitForElementPresent("_qf_Activity_upload");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertTrue($this->isTextPresent("Anderson, " . $withContact), "Contact not found in line " . __LINE__);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->select2("assignee_contact_id", $firstName, TRUE, FALSE);

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertTrue($this->isTextPresent("$lastName, " . $firstName), "Contact not found in line " . __LINE__);

    // Since we're here, let's check if screen help is being displayed properly
    $this->assertTrue($this->isTextPresent("A copy of this activity will be emailed to each Assignee."));

    // Putting the contents into subject field - assigning the text to variable, it'll come in handy later
    // For simple input fields we can use field id as selector
    $this->type("subject", $subject);
    $this->type("location", "Some location needs to be put in this field.");

    // Choosing the Date.
    // Please note that we don't want to put in fixed date, since
    // we want this test to work in the future and not fail because
    // of date being set in the past. Therefore, using helper webtestFillDateTime function.
    $this->webtestFillDateTime('activity_date_time', '+1 month 11:10PM');

    // Setting duration.
    $this->type("duration", "30");

    // Putting in details.
    $this->type("details", "Really brief details information.");

    // Making sure that status is set to Scheduled (using value, not label).
    $this->select("status_id", "value=1");

    // Setting priority.
    $this->select("priority_id", "value=1");

    // Clicking save.
    $this->click("_qf_Activity_upload");
    $this->waitForElementPresent("crm-notification-container");

    // Is status message correct?
    $this->waitForText('crm-notification-container', "Activity '$subject' has been saved.", "Status message didn't show up after saving!");
  }

  public function testMergeTest() {
    $this->webtestLogin();

    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    // add contact1
    //fill in first name
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName);

    //fill in email id
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

    //fill in billing email id
    $this->click('addEmail');
    $this->waitForElementPresent('email_2_email');
    $this->type('email_2_email', "$firstName.$lastName@billing.com");
    $this->select('email_2_location_type_id', 'value=5');

    //fill in home phone no
    $this->type('phone_1_phone', "9876543210");

    //fill in billing phone id
    $this->click('addPhone');
    $this->waitForElementPresent('phone_2_phone');
    $this->type('phone_2_phone', "9876543120");
    $this->select('phone_2_location_type_id', 'value=5');

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // contact2: duplicate of contact1.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //fill in email
    $this->type("email_1_email", "{$firstName}.{$lastName}@example.com");

    //fill in home phone no
    $this->type('phone_1_phone', "9876543211");

    // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->openCiviPage("contact/dedupefind", "reset=1&rgid=1&action=renew");

    // Select the contacts to be merged
    $this->waitForElementPresent('dupePairs_length');
    $this->waitForElementPresent("xpath=//a[text()='$firstName $lastName']");
    $this->click("xpath=//a[text()='$firstName $lastName']/../../td[8]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->clickLink("css=div.crm-contact-merge-form-block div.action-link a", "xpath=//form[@id='Merge']/div[2]/table/tbody/tr[3]/td[4]/span[text()='(overwrite)']", FALSE);
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[5]/td[4]/span[text()='(add)']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');

    $this->check("move_location_email_1");
    $this->check("location[email][1][operation]");
    $this->check("move_location_email_2");
    $this->check("move_location_phone_1");
    $this->check("location[phone][1][operation]");
    $this->check("move_location_phone_2");
    $this->click("_qf_Merge_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent('Contacts Merged'));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[2]/a[contains(text(), '{$firstName}.{$lastName}@example.com')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[2]/a[contains(text(), '{$firstName}.{$lastName}@example.com')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[4]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[4]/div[2]/a[contains(text(), '$firstName.$lastName@billing.com')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[2][contains(text(), '9876543211')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[2][contains(text(), '9876543210')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[4]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[4]/div[2][contains(text(), '9876543120')]"));

    //Merge with the feature of (add)
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    // add contact1
    //fill in first name
    $firstName1 = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName1);

    //fill in last name
    $lastName1 = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName1);

    //fill in billing email id
    $this->waitForElementPresent('email_1_email');
    $this->type('email_1_email', "$firstName1.$lastName1@example.com");
    $this->select('email_1_location_type_id', 'value=5');

    $this->click('addEmail');
    $this->waitForElementPresent('email_2_email');
    $this->type('email_2_email', "$firstName.$lastName@home.com");
    $this->select('email_2_location_type_id', 'value=1');

    //fill in home phone no
    $this->type('phone_1_phone', "9876543210");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // contact2: duplicate of contact1.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName1);

    //fill in last name
    $this->type("last_name", $lastName1);

    //fill in email
    $this->type("email_1_email", "{$firstName1}.{$lastName1}@example.com");

    //fill in billing phone no
    $this->type('phone_1_phone', "9876543120");
    $this->select('phone_1_location_type_id', 'value=5');

    // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->openCiviPage("contact/dedupefind", "reset=1&rgid=1&action=renew");

    // Select the contacts to be merged
    $this->waitForElementPresent('dupePairs_length');
    $this->waitForElementPresent("xpath=//a[text()='$firstName1 $lastName1']");
    $this->click("xpath=//a[text()='$firstName1 $lastName1']/../../td[8]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->clickLink("css=div.crm-contact-merge-form-block div.action-link a", "xpath=//form[@id='Merge']/div[2]/table/tbody/tr[4]/td[4]/span[text()='(overwrite)']");
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[3]/td[4]/span[text()='(add)']");
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[4]/td[4]/span[text()='(overwrite)']");
    $this->select('location_email_1_locTypeId', 'value=3');
    $this->select('location_phone_1_locTypeId', 'value=1');
    $this->assertFalse($this->isElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[2]/td[4]/span[text()='(overwrite)']"));
    $this->assertFalse($this->isElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[4]/td[4]/span[text()='(overwrite)']"));
    $this->assertTrue($this->isElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[3]/td[4]/span[text()='(add)']"));
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');

    $this->check("move_location_email_1");
    $this->check("move_location_phone_1");
    $this->click("_qf_Merge_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent('Contacts Merged'));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[2]/a[contains(text(), '{$firstName1}.{$lastName1}@example.com')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[1][contains(text(), 'Main')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[2]/a[contains(text(), '{$firstName1}.{$lastName1}@example.com')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[2][contains(text(), '9876543120')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[2][contains(text(), '9876543210')]"));
  }

  public function testBatchMerge() {
    $this->webtestLogin();

    // add contact1 and its duplicate
    //first name
    $firstName = "Kerry" . substr(sha1(rand()), 0, 7);
    //last name
    $lastName = "King" . substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName, $lastName);

    //add contact2 and its duplicate
    //These are the contacts with conflicts in communication preference.these contacts will be skipped during merge.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //fill in first name
    $firstName1 = "Kurt" . substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName1);

    //fill in last name
    $lastName1 = "Cobain" . substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName1);

    //fill in email id
    $this->type('email_1_email', "{$firstName1}.{$lastName1}@example.com");

    //fill in billing email id
    $this->click('addEmail');
    $this->waitForElementPresent('email_2_email');
    $this->type('email_2_email', "$firstName1.$lastName1@billing.com");
    $this->select('email_2_location_type_id', 'value=5');

    //fill in home phone no
    $this->type('phone_1_phone', "9876543210");

    //fill in billing phone id
    $this->click('addPhone');
    $this->waitForElementPresent('phone_2_phone');
    $this->type('phone_2_phone', "9876543120");
    $this->select('phone_2_location_type_id', 'value=5');

    //select communication preference
    $this->check("privacy[do_not_phone]");

    //Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    //duplicate of contact2.
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName1);

    //fill in last name
    $this->type("last_name", $lastName1);

    //fill in email
    $this->type("email_1_email", "{$firstName1}.{$lastName1}@example.com");

    //fill in home phone no
    $this->type('phone_1_phone', "9876543211");

    //select communication preference
    $this->check("preferred_communication_method[1]");

    // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    // add contact3 and its duplicate
    //fill in first name
    $firstName2 = "David" . substr(sha1(rand()), 0, 7);
    //fill in last name
    $lastName2 = "Gilmour" . substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName2, $lastName2);

    // add contact4 and its duplicate
    //fill in first name
    $firstName3 = "Dave" . substr(sha1(rand()), 0, 7);
    //fill in last name
    $lastName3 = "Mustaine" . substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName3, $lastName3);

    // Find and Merge Contacts with Supervised Rule
    $this->openCiviPage("contact/dedupefind", "reset=1&rgid=1&action=renew", "dupePairs");

    $this->waitForElementPresent('dupePairs_length');
    $this->select("name=dupePairs_length", "value=100");
    $totalContacts = $this->getXpathCount("//table[@id='dupePairs']/tbody/tr");
    $this->click("//form[@id='DedupeFind']//a/span[contains(text(),' Batch Merge All Duplicates')]");

    // Check confirmation alert.
    $this->assertTrue(
      (bool) preg_match("/^This will run the batch merge process on the listed duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation./",
        $this->getConfirmation()
      )
    );
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('civicrm-footer');
    $this->waitForElementPresent("crm-main-content-wrapper");

    // If we are still on the dedupe table page, count unmerged contacts
    if ($this->isElementPresent("//table[@class='pagerDisplay']")) {
      // Wait for datatable to load
      $this->waitForElementPresent("//table[@class='pagerDisplay']/tbody/tr");
      $unMergedContacts = $this->getXpathCount("//table[@class='pagerDisplay']/tbody/tr");
    }
    else {
      $unMergedContacts = 0;
    }

    $mergedContacts = $totalContacts - $unMergedContacts;

    //check the existence of merged contacts
    $contactEmails = array(
      1 => "{$firstName}.{$lastName}@example.com",
      2 => "{$firstName2}.{$lastName2}@example.com",
      3 => "{$firstName3}.{$lastName3}@example.com",
    );

    foreach ($contactEmails as $key => $value) {
      $this->click('sort_name_navigation');
      $this->type('css=input#sort_name_navigation', $value);
      $this->typeKeys('css=input#sort_name_navigation', $value);
      // Wait for result list.
      $this->waitForElementPresent("css=ul.ui-autocomplete li.ui-menu-item");

      // Visit contact summary page.
      $this->clickLink("css=ul.ui-autocomplete li.ui-menu-item", 'civicrm-footer');
    }
  }

  /**
   * Helper FN.
   * @param null $firstName
   * @param null $lastName
   * @param null $organizationName
   * @param string $contactType
   * @return array
   */
  public function _createContacts($firstName = NULL, $lastName = NULL, $organizationName = NULL, $contactType = 'Individual') {
    if ($contactType == 'Individual') {
      // add contact
      $this->openCiviPage("contact/add", "reset=1&ct=Individual");
      //fill in first name
      $this->type('first_name', $firstName);

      //fill in last name
      $this->type('last_name', $lastName);

      //fill in email id
      $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

      //fill in billing email id
      $this->click('addEmail');
      $this->waitForElementPresent('email_2_email');
      $this->type('email_2_email', "$firstName.$lastName@billing.com");
      $this->select('email_2_location_type_id', 'value=5');

      // Clicking save.
      $this->click("_qf_Contact_upload_view");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForText('crm-notification-container', "Contact Saved");

      //duplicate of above contact.
      $this->openCiviPage("contact/add", "reset=1&ct=Individual");

      //fill in first name
      $this->type("first_name", $firstName);

      //fill in last name
      $this->type("last_name", $lastName);

      //fill in email
      $this->type("email_1_email", "{$firstName}.{$lastName}@example.com");

      // Clicking save.
      $this->click("_qf_Contact_refresh_dedupe");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
      $this->click("_qf_Contact_upload_duplicate");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForText('crm-notification-container', "Contact Saved");
    }
    elseif ($contactType == 'Organization') {
      // add contact
      $this->openCiviPage("contact/add", "reset=1&ct=Organization");
      //fill in Organization name
      $this->type('organization_name', $organizationName);

      //fill in email id
      $this->type('email_1_email', "{$organizationName}@org.com");
      // Clicking save.
      $this->click("_qf_Contact_upload_view-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForText('crm-notification-container', "Contact Saved");
      $mainId = explode("Contact ID:", trim($this->getText("xpath=//div[@id='crm-record-log']/span[@class='col1']")));
      $mainId = trim($mainId[1]);

      //Duplicate of above contact.
      $this->openCiviPage("contact/add", "reset=1&ct=Organization");

      //fill in Organization name
      $this->type('organization_name', $organizationName);

      //fill in email id
      $this->type('email_1_email', "{$organizationName}@org.com");

      // Clicking save.
      $this->click("_qf_Contact_upload_view-bottom");
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->waitForText('crm-notification-container', "One matching contact was found. You can View or Edit the existing contact.");
      $this->click("_qf_Contact_upload_duplicate");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      $this->waitForText('crm-notification-container', "Contact Saved");
      $duplicateId = explode("Contact ID:", trim($this->getText("xpath=//div[@id='crm-record-log']/span[@class='col1']")));
      $duplicateId = trim($duplicateId[1]);

      return array(
        'mainId' => $mainId,
        'duplicateId' => $duplicateId,
      );
    }
  }

  /**
   * Helper FN.
   * to create new membership type
   * @param $membershipOrganization
   */
  public function addMembershipType($membershipOrganization) {
    $this->openCiviPage("admin/member/membershipType", "reset=1&action=browse");
    $this->click("link=Add Membership Type");
    $this->waitForElementPresent('_qf_MembershipType_cancel-bottom');

    $this->type('name', "Membership Type $membershipOrganization");
    $this->select2('member_of_contact_id', $membershipOrganization);

    $this->type('minimum_fee', '1');
    $this->select('financial_type_id', 'value=2');
    $this->type('duration_interval', 1);
    $this->select('duration_unit', "label=year");

    $this->select('period_type', "label=Fixed");
    $this->waitForElementPresent('fixed_period_rollover_day[d]');

    // fixed period start set to April 1
    $this->select('fixed_period_start_day[M]', 'value=4');
    // rollover date set to Jan 31
    $this->select('fixed_period_rollover_day[M]', 'value=1');

    // Employer of relationship
    $this->select('relationship_type_id', 'value=5_b_a');
    $this->click('_qf_MembershipType_upload-bottom');
    $this->waitForElementPresent('link=Add Membership Type');
    $this->waitForText("crm-notification-container", "The membership type 'Membership Type $membershipOrganization' has been saved.");
  }

  /**
   * Test for CRM-12695 fix
   */
  public function testMergeOrganizations() {
    $this->webtestLogin();

    // build organisation name
    $orgnaizationName = 'org_' . substr(sha1(rand()), 0, 7);

    $contactIds = array();
    // create organization and its duplicate
    $contactIds = $this->_createContacts(NULL, NULL, $orgnaizationName, 'Organization');

    /*** Add Membership Type - start ***/
    $this->addMembershipType($orgnaizationName);
    /*** Add Membership Type - end ***/

    //create a New Individual to be related to main organization
    $firstName = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, "Anderson", "$firstName@anderson.name");
    $sortName = "Anderson, $firstName";

    // go to main organization contact to add membership
    $this->openCiviPage("contact/view", "reset=1&cid={$contactIds['mainId']}");
    // click through to the membership view screen
    $this->click("css=li#tab_member a");

    $this->waitForElementPresent("link=Add Membership");
    $this->click("link=Add Membership");

    $this->waitForElementPresent("_qf_Membership_cancel-bottom");

    // fill in Membership Organization and Type
    $this->select("membership_type_id[0]", "label={$orgnaizationName}");
    $this->waitForElementPresent("membership_type_id[1]");
    // Wait for membership type select to reload
    $this->waitForTextPresent("Membership Type $orgnaizationName");
    $this->select("membership_type_id[1]", "label=Membership Type $orgnaizationName");

    $sourceText = "Membership-Organization Duplicate Merge Webtest";
    // fill in Source
    $this->type("source", $sourceText);

    // Let Join Date stay default

    // fill in Start Date
    $this->webtestFillDate('start_date');

    // Clicking save.
    $this->click("_qf_Membership_upload");
    $this->waitForElementPresent('crm-notification-container');
    // page was loaded
    $this->waitForTextPresent($sourceText);
    // Is status message correct?
    $this->waitForText('crm-notification-container', "membership for $orgnaizationName has been added.");

    // add relationship "Employer of"
    // click through to the relationship view screen
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "value=5_b_a");

    //fill in the individual
    $this->waitForElementPresent('related_contact_id');
    $this->select2('related_contact_id', $sortName, TRUE, FALSE);

    $this->waitForElementPresent("_qf_Relationship_upload");

    //fill in the relationship start date
    //$this->webtestFillDate('start_date', '-2 year');
    //$this->webtestFillDate('end_date', '+1 year');

    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    $this->click("_qf_Relationship_upload");
    $this->isTextPresent("Current Relationships");

    //check the status message
    $this->waitForText('crm-notification-container', "Relationship created.");
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[9]/span/a[text()='View']");
    $this->waitForAjaxContent();
    $this->click("xpath=//a[text()='$sortName']");
    $this->waitForAjaxContent();

    // Check if Membership for the individual is created
    $this->waitForElementPresent("xpath=//li[@id='tab_member']/a/em");
    $this->verifyText("xpath=//li[@id='tab_member']/a/em", 1);

    //create a New Individual to be related to duplicate organization
    $firstNameOther = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstNameOther, "Harmison", "$firstNameOther@harmison.name");
    $sortNameOther = "Harmison, $firstNameOther";

    // go to main organization contact to add membership
    $this->openCiviPage("contact/view", "reset=1&cid={$contactIds['duplicateId']}");

    // add relationship "Employer of"
    // click through to the relationship view screen
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent('link=Add Relationship');
    $this->click('link=Add Relationship');

    //choose the created relationship type
    $this->waitForElementPresent("relationship_type_id");
    $this->select('relationship_type_id', "value=5_b_a");

    //fill in the individual
    $this->waitForElementPresent('related_contact_id');
    $this->select2('related_contact_id', $sortNameOther, TRUE, FALSE);

    $this->waitForElementPresent("_qf_Relationship_upload");

    //fill in the relationship start date
    $this->webtestFillDate('start_date', '-2 year');
    $this->webtestFillDate('end_date', '+1 year');

    $description = "Well here is some description !!!!";
    $this->type("description", $description);

    //save the relationship
    //$this->click("_qf_Relationship_upload");
    $this->click("_qf_Relationship_upload");
    $this->isTextPresent("Current Relationships");

    //check the status message
    $this->isTextPresent("Relationship created.");

    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody/tr/td[9]/span/a[text()='View']");

    // go directly to contact merge page.
    $this->openCiviPage("contact/merge", "reset=1&cid={$contactIds['mainId']}&oid={$contactIds['duplicateId']}&action=update&rgid=2");

    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->click('_qf_Merge_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // click through to the relationship view screen
    $this->click("css=li#tab_rel a");

    // wait for add Relationship link
    $this->waitForElementPresent("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr//td//a[text()='$sortName']");
    // go to duplicate organization's related contact
    // to check if membership is added to that contact
    $this->click("xpath=//div[@class='dataTables_wrapper no-footer']/table/tbody//tr//td//a[text()='$sortName']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Check if Membership for the individual is created
    $this->waitForElementPresent("xpath=//li[@id='tab_member']/a/em");
    $this->verifyText("xpath=//li[@id='tab_member']/a/em", 0);
  }

  /**
   * Test for CRM-15658 fix
   */
  public function testMergeEmailAndAddress() {
    $this->webtestLogin();
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $firstName = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName);

    //fill in last name
    $lastName = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName);

    //fill in email id
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    $this->type("address_1_street_address", "902C El Camino Way SW");
    $this->type("address_1_city", "Dumfries");
    $this->type("address_1_postal_code", "1234");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    //duplicate contact with same email id
    $this->openCiviPage("contact/add", "reset=1&ct=Individual");
    $firstName2 = substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName2);

    //fill in last name
    $lastName2 = substr(sha1(rand()), 0, 7);
    $this->type('last_name', $lastName2);

    //fill in email id
    $this->type('email_1_email', "{$firstName}.{$lastName}@example.com");

    //address section
    $this->click("addressBlock");
    $this->waitForElementPresent("address_1_street_address");
    $this->type("address_1_street_address", "2782Y Dowlen Path W");
    $this->type("address_1_city", "Birmingham");
    $this->type("address_1_postal_code", "3456");

    // Clicking save.
    $this->click("_qf_Contact_upload_view");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForText('crm-notification-container', "Contact Saved");

    $this->openCiviPage("contact/dedupefind", "reset=1&action=update&rgid=4");
    $this->click("//a/span[contains(text(),'Refresh Duplicates')]");
    $this->assertTrue((bool) preg_match("/This will refresh the duplicates list. Click OK to proceed./", $this->getConfirmation()));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//table[@id='dupePairs']/tbody//tr/td[3]/a[text()='$firstName $lastName']/../../td[8]//a[text()='merge']");
    $this->click("xpath=//table[@id='dupePairs']/tbody//tr/td[3]/a[text()='$firstName $lastName']/../../td[8]//a[text()='merge']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //merge without specifying any criteria
    $this->click("_qf_Merge_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent('Contacts Merged'));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[1][contains(text(), 'Home')]"));
    $this->verifyElementNotPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[1][contains(text(), 'Home')]");
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[2]/a[contains(text(), '{$firstName}.{$lastName}@example.com')]"));
    $this->verifyElementNotPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[2]/a[contains(text(), '{$firstName}.{$lastName}@example.com')]");

    $this->assertElementContainsText('address-block-1', "902C El Camino Way SW");
    $this->assertElementContainsText('address-block-1', "Dumfries");
    $this->assertElementContainsText('address-block-1', "1234");

    $this->assertElementNotContainsText("address-block-2", "2782Y Dowlen Path W");
    $this->assertElementNotContainsText("address-block-2", "Birmingham");
    $this->assertElementNotContainsText("address-block-2", "3456");
  }

}
