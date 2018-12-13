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
 * Class WebTest_Contact_PrivacyOptionSearchTest
 */
class WebTest_Contact_PrivacyOptionSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testPrivacyOptionSearch() {
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Add new group.
    $this->openCiviPage('group/add', 'reset=1', "_qf_Edit_upload");

    $groupName = 'group_' . substr(sha1(rand()), 0, 7);
    $this->type("title", $groupName);

    // Fill description.
    $this->type("description", "Adding new group.");

    // Check Access Control.
    $this->click("group_type[1]");

    // Clicking save.
    $this->click("_qf_Edit_upload");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Add Contact1.
    $fname1 = substr(sha1(rand()), 0, 7);
    $lname1 = substr(sha1(rand()), 0, 7);
    $this->openCiviPage("contact/add", "reset=1&ct=Individual", '_qf_Contact_upload_view-bottom');
    $this->type('first_name', $fname1);
    $this->type('last_name', $lname1);
    $email1 = $fname1 . '@example.org';
    $this->type('email_1_email', $email1);

    //click 'Communication Preferences' Panel
    $this->click("commPrefs");
    $this->waitForElementPresent("preferred_mail_format");
    $this->click("privacy_do_not_phone");
    $this->click("privacy_do_not_email");
    $this->click("privacy_do_not_mail");
    $this->click("privacy_do_not_sms");

    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Add contact to the group.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");

    // Add Contact2.
    $fname2 = substr(sha1(rand()), 0, 7);
    $lname2 = substr(sha1(rand()), 0, 7);
    $this->openCiviPage("contact/add", "reset=1&ct=Individual", '_qf_Contact_upload_view-bottom');
    $this->type('first_name', $fname2);
    $this->type('last_name', $lname2);
    $email2 = $fname2 . '@example.org';
    $this->type('email_1_email', $email2);

    //click 'Communication Preferences' Panel
    $this->click("commPrefs");
    $this->waitForElementPresent("preferred_mail_format");
    $this->click("privacy_do_not_phone");
    $this->click("privacy_do_not_email");
    $this->click("privacy_do_not_trade");

    $this->click('_qf_Contact_upload_view-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Add contact to the group.
    $this->click("css=li#tab_group a");
    $this->waitForElementPresent("group_id");
    $this->select("group_id", "label=$groupName");
    $this->click("_qf_GroupContact_next");

    // Go to advance search, check for 'Exclude' option.
    $this->openCiviPage("contact/search/advanced", "reset=1");

    $this->select("group", "label={$groupName}");
    $this->waitForTextPresent($groupName);

    $this->multiselect2('privacy_options', array('Do not phone', 'Do not email'));

    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    $this->assertTrue($this->isTextPresent("None found."));

    // Go to advance search, check for 'Include' + 'OR' options.
    $this->openCiviPage("contact/search/advanced", "reset=1");

    $this->select("group", "label={$groupName}");
    $this->waitForTextPresent($groupName);

    $this->click("CIVICRM_QFID_2_privacy_toggle");

    $this->multiselect2('privacy_options', array('Do not phone', 'Do not email'));

    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("2 Contacts"));
    $this->assertTrue($this->isTextPresent("$lname1, $fname1"));
    $this->assertTrue($this->isTextPresent("$lname2, $fname2"));

    // Go to advance search, check for 'Include' + 'AND' options.
    $this->openCiviPage("contact/search/advanced", "reset=1");

    $this->select("group", "label={$groupName}");
    $this->waitForTextPresent($groupName);

    $this->click("CIVICRM_QFID_2_privacy_toggle");

    $this->multiselect2('privacy_options', array('Do not phone', 'Do not trade'));

    $this->select('privacy_operator', 'value=AND');

    $this->click("_qf_Advanced_refresh");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    $this->assertTrue($this->isTextPresent("1 Contact"));
    $this->assertTrue($this->isTextPresent("$lname2, $fname2"));
  }

}
