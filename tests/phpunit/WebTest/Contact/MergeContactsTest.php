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
class WebTest_Contact_MergeContactsTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testIndividualAdd() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // Go directly to the URL of New Individual.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());

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
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Add Contact to a group
    $group = 'Newsletter Subscribers';
    $this->click('css=li#tab_group a');
    $this->waitForElementPresent('_qf_GroupContact_next');
    $this->select('group_id', "label=$group");
    $this->click('_qf_GroupContact_next');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Added to Group");

    // Add Tags to the contact
    $tag = 'Government Entity';
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent('tagtree');
    $this->click("xpath=//div[@id='tagtree']/ul//li/input/../label[text()='$tag']");
    $this->click("css=#tab_summary a");
    $this->assertElementContainsText('css=.crm-summary-block #tags', $tag);

    // Add an activity
    $subject = "This is subject of test activity being added through activity tab of contact summary screen.";
    $this->addActivity($firstName, $lastName, $subject);

    // contact2: duplicate of contact1.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //fill in email
    $this->type("email_1_email", "{$firstName}.{$lastName}@example.com");

    // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Add second pair of dupes so we can test Merge and Goto Next Pair
    $fname2 = 'Janet';
    $lname2 = 'Rogers' . substr(sha1(rand()), 0, 7);
    $email2 = "{$fname2}.{$lname2}@example.org";
    $this->webtestAddContact($fname2, $lname2, $email2);

    // Can not use helper for 2nd contact since it is a dupe
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->type("first_name", $fname2);
    $this->type("last_name", $lname2);
    $this->type("email_1_email", $email2);
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->open($this->sboxPath . 'civicrm/contact/dedupefind?reset=1&rgid=1&action=renew');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // reload the page
    $this->open($this->sboxPath . 'civicrm/contact/dedupefind?reset=1&rgid=1&action=update');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Select the contacts to be merged
    $this->select("name=option51_length", "value=100");
    $this->waitForTextPresent("$firstName $lastName");

    // sleep seems to work here, not sure why
    sleep(3);
    $this->click("xpath=//a[text()='$firstName $lastName']/../../td[4]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');

    $this->click("css=div.crm-contact-merge-form-block div.action-link a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');

    // Move the activities, groups, etc to the main contact and merge using Merge and Goto Next Pair
    $this->check('move_prefix_id');
    $this->check('move_location_email_2');
    $this->check('move_rel_table_activities');
    $this->check('move_rel_table_groups');
    $this->check('move_rel_table_tags');
    $this->click('_qf_Merge_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->assertTrue($this->isTextPresent('Contacts Merged'), "Contacts Merged text was not found after merge.");

    // Check that we are viewing the next Merge Pair (our 2nd contact, since the merge list is ordered by contact_id)
    $this->assertTrue($this->isTextPresent("{$fname2} {$lname2}"), "Redirect for Goto Next Pair after merge did not work.");

    // Ensure that the duplicate contact has been deleted
    $this->open($this->sboxPath . 'civicrm/contact/search/advanced?reset=1');
    $this->waitForElementPresent('_qf_Advanced_refresh');
    $this->type('sort_name', $firstName);
    $this->check('deleted_contacts');
    $this->click('_qf_Advanced_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent('1 Contact'), "Deletion of duplicate contact during merge was not successful. Dupe contact not found when searching trash.");

    // Search for the main contact
    $this->open($this->sboxPath . 'civicrm/contact/search/advanced?reset=1');
    $this->waitForElementPresent('_qf_Advanced_refresh');
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
    $this->waitForElementPresent("xpath=//table[@id='contact-activity-selector-activity']/tbody/tr");
    $this->verifyText("xpath=//table[@id='contact-activity-selector-activity']/tbody/tr/td[5]/a",
      preg_quote("$lastName, $firstName")
    );

    // Verify group merged
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("xpath=//form[@id='GroupContact']//div[@class='view-content']//div[@class='dataTables_wrapper']/table/tbody/tr");
    $this->verifyText("xpath=//form[@id='GroupContact']//div[@class='view-content']//div[@class='dataTables_wrapper']/table/tbody/tr/td/a",
      preg_quote("$group")
    );

    // Verify tag merged
    $this->click("css=li#tab_tag a");
    $this->waitForElementPresent('check_5');
    $this->assertChecked("check_3");
  }

  function addActivity($firstName, $lastName, $subject) {
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

    // Let's start filling the form with values.

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertTrue($this->isTextPresent("Anderson, " . $withContact), "Contact not found in line " . __LINE__);

    // Now we're filling the "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
    $this->type("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName);
    $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", $firstName);

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");

    // ...and verifying if the page contains properly formatted display name for chosen contact.
    $this->assertTrue($this->isTextPresent("$lastName, " . $firstName), "Contact not found in line " . __LINE__);

    // Since we're here, let's check if screen help is being displayed properly
    $this->assertTrue($this->isTextPresent("Assigned activities will appear in their Activities listing at CiviCRM Home"));

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
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertTrue($this->isTextPresent("Activity '$subject' has been saved."), "Status message didn't show up after saving!");
  }


  function testMergeTest() {
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // Go directly to the URL of New Individual.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());

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
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // contact2: duplicate of contact1.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

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

    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->open($this->sboxPath . 'civicrm/contact/dedupefind?reset=1&rgid=1&action=renew');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Select the contacts to be merged
    $this->select("name=option51_length", "value=100");
    $this->waitForElementPresent("xpath=//a[text()='$firstName $lastName']");
    $this->click("xpath=//a[text()='$firstName $lastName']/../../td[4]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->click("css=div.crm-contact-merge-form-block div.action-link a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[2]/td[4]/span[text()='(overwrite)']");
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[3]/td[4]/span[text()='(add)']");
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
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[2]/a[text() = '{$firstName}.{$lastName}@example.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[2]/a[text() ='{$firstName}.{$lastName}@example.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[4]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[4]/div[2]/a[text() ='$firstName.$lastName@billing.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[2][contains(text(), '9876543211')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[2][contains(text(), '9876543210')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[4]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[4]/div[2][contains(text(), '9876543120')]"));

    //Merge with the feature of (add)
    // Go directly to the URL of New Individual.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());

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
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // contact2: duplicate of contact1.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

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
    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Find and Merge Contacts with Supervised Rule
    $this->open($this->sboxPath . 'civicrm/contact/dedupefind?reset=1&rgid=1&action=renew');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Select the contacts to be merged
    $this->select("name=option51_length", "value=100");
    $this->waitForElementPresent("xpath=//table[@class='pagerDisplay']/tbody//tr/td[1]/a[text()='$firstName1 $lastName1']/../../td[2]/a[text()='$firstName1 $lastName1']");
    $this->click("xpath=//table[@class='pagerDisplay']/tbody//tr/td[1]/a[text()='$firstName1 $lastName1']/../../td[2]/a[text()='$firstName1 $lastName1']/../../td[4]/a[text()='merge']");
    $this->waitForElementPresent('_qf_Merge_cancel-bottom');
    $this->click("css=div.crm-contact-merge-form-block div.action-link a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent("xpath=//form[@id='Merge']/div[2]/table/tbody/tr[2]/td[4]/span[text()='(overwrite)']");
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
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[2]/div[2]/a[text() ='{$firstName1}.{$lastName1}@example.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[1][contains(text(), 'Main')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='email-block']/div/div/div[3]/div[2]/a[text() ='{$firstName1}.{$lastName1}@example.com']"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[1][contains(text(), 'Billing')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[2]/div[2][contains(text(), '9876543120')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[1][contains(text(), 'Home')]"));
    $this->assertTrue($this->isElementPresent("xpath=//div[@id='phone-block']/div/div/div[3]/div[2][contains(text(), '9876543210')]"));
  }

  function testBatchMerge(){
    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin();

    // add contact1 and its duplicate
    //first name
    $firstName = "Kerry".substr(sha1(rand()), 0, 7);
    //last name
    $lastName = "King".substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName,$lastName);

    //add contact2 and its duplicate
    //These are the contacts with conflicts in communication preference.these contacts will be skipped during merge.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //fill in first name
    $firstName1 = "Kurt".substr(sha1(rand()), 0, 7);
    $this->type('first_name', $firstName1);

    //fill in last name
    $lastName1 = "Cobain".substr(sha1(rand()), 0, 7);
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
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    //duplicate of contact2.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

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

    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // add contact3 and its duplicate
    //fill in first name
    $firstName2 = "David".substr(sha1(rand()), 0, 7);
    //fill in last name
    $lastName2 = "Gilmour".substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName2,$lastName2);

    // add contact4 and its duplicate
    //fill in first name
    $firstName3 = "Dave".substr(sha1(rand()), 0, 7);
    //fill in last name
    $lastName3 = "Mustaine".substr(sha1(rand()), 0, 7);
    $this->_createContacts($firstName3,$lastName3);

    // Find and Merge Contacts with Supervised Rule
    $this->open($this->sboxPath . 'civicrm/contact/dedupefind?reset=1&rgid=1&action=renew');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(3);

    $this->select("name=option51_length", "value=100");
    $totalContacts = $this->getXpathCount("//table[@class='pagerDisplay']/tbody/tr");
    $this->click("xpath=//form[@id='DedupeFind']//a/span[text()='Batch Merge Duplicates']");

    // Check confirmation alert.
    $this->assertTrue(
      (bool)preg_match("/^This will run the batch merge process on the listed duplicates. The operation will run in safe mode - only records with no direct data conflicts will be merged. Click OK to proceed if you are sure you wish to run this operation./",
        $this->getConfirmation()
                                       ));
    $this->chooseOkOnNextConfirmation();
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(5);

    $unMergedContacts = $this->getXpathCount("//table[@class='pagerDisplay']/tbody/tr");
    $mergedContacts = $totalContacts - $unMergedContacts;
    $this->assertElementContainsText('crm-notification-container', "safe mode");

    //check the existence of merged contacts
    $contactEmails = array(
      1 => "{$firstName}.{$lastName}@example.com",
      2 => "{$firstName2}.{$lastName2}@example.com",
      3 => "{$firstName3}.{$lastName3}@example.com"
    );

    foreach( $contactEmails as $key => $value ) {
      $this->click('sort_name_navigation');
      $this->type('css=input#sort_name_navigation', $value);
      $this->typeKeys('css=input#sort_name_navigation', $value);
      // Wait for result list.
      $this->waitForElementPresent("css=div.ac_results-inner li");

      // Visit contact summary page.
      $this->click("css=div.ac_results-inner li");
      $this->waitForPageToLoad($this->getTimeoutMsec());
      sleep(2);
    }
  }

  /**
   * Helper FN
   */
  function _createContacts($firstName,$lastName){
    // add contact
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");
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
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    //duplicate of above contact.
    $this->open($this->sboxPath . "civicrm/contact/add?reset=1&ct=Individual");

    //fill in first name
    $this->type("first_name", $firstName);

    //fill in last name
    $this->type("last_name", $lastName);

    //fill in email
    $this->type("email_1_email", "{$firstName}.{$lastName}@example.com");

      // Clicking save.
    $this->click("_qf_Contact_refresh_dedupe");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("One matching contact was found. You can View or Edit the existing contact."));
    $this->click("_qf_Contact_upload_duplicate");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");
  }
}

