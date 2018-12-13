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
 * Tests for the ability to add a CMS user from a contact's record
 * See http://issues.civicrm.org/jira/browse/CRM-8723
 * Class WebTest_Contact_CreateCmsUserFromContactTest
 */
class WebTest_Contact_CreateCmsUserFromContactTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test that option to create a cms user is present on a contact who.
   * does not have a cms account already (in this case, a new
   * contact).
   */
  public function testCreateContactLinkPresent() {

    //login
    $this->webtestLogin('admin');

    //create a New Contact
    $firstName = substr(sha1(rand()), 0, 7) . "John";
    $lastName = substr(sha1(rand()), 0, 7) . "Smith";
    $email = $this->webtestAddContact($firstName, $lastName, TRUE);

    //Assert that the user actually does have a CMS Id displayed
    $this->assertTrue(!$this->isTextPresent("User ID"));

    //Assert that the contact user record link says create user record
    $this->assertElementContainsText("css=#actions li.crm-contact-user-record", "Create User Record", "Create User Record link not in action menu of new contact");
  }

  /**
   * Test that the action link is missing for users who already have a
   * contact record. The contact record for drupal user 1 is used.
   */
  public function testCreateContactLinkMissing() {

    //login
    $this->webtestLogin('admin');

    // go to My Account page
    $this->open($this->sboxPath . "user");

    // click "View contact record" link
    $this->waitForElementPresent("xpath=//div[@class='profile']/span/a[text()='View contact record']");
    $this->click("xpath=//div[@class='profile']/span/a[text()='View contact record']");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //Assert that the user actually does have a CMS Id displayed
    $this->assertTrue($this->isTextPresent("User ID"));

    //Assert that the text of the user record link does not say Create User Record
    $this->assertElementNotContainsText("css=#actions li.crm-contact-user-record", "Create User Record", "Create User Record link not in action menu of new contact");
  }

  /**
   * Test the ajax "check username availibity" link when adding cms user.
   */
  public function testCheckUsernameAvailability() {
    $this->webtestLogin('admin');

    $email = $this->_createUserAndGotoForm();
    $password = "abc123";

    //use the username of the admin user to test if the username is taken
    $username = $this->settings->adminUsername;

    $this->_fillCMSUserForm($username, $password, $password);
    $this->click("checkavailability");
    $this->waitForCondition("selenium.browserbot.getCurrentWindow().jQuery('#msgbox').text() != 'Checking...'");
    $this->assertElementContainsText("msgbox", "This username is taken", "Taken username is indicated as being available");

    //fill the form with a good username
    $username = sha1(rand());
    $this->_fillCMSUserForm($username, $password, $password);
    $this->click("checkavailability");
    $this->waitForCondition("selenium.browserbot.getCurrentWindow().jQuery('#msgbox').text() != 'Checking...'");
    $this->assertElementContainsText("msgbox", "This username is currently available", "Available username is indicated as being taken");
  }

  /**
   * Test form submission when the username is taken.
   */
  public function testTakenUsernameSubmission() {

    //login
    $this->webtestLogin('admin');

    //create a New Contact
    list($cid, $firstName, $lastName, $email) = $this->_createUserAndGotoForm();
    $password = 'abc123';

    //submit the form with the bad username
    $username = $this->settings->adminUsername;
    $this->_fillCMSUserForm($username, $password, $password);
    $this->click("_qf_Useradd_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //the civicrm messages should indicate the username is taken

    $this->assertElementContainsText("xpath=//span[@class = 'crm-error']", "already taken", "CiviCRM Message does not indicate the username is in user");
    //check the uf match table that no contact has been created
    $results = $this->webtest_civicrm_api("UFMatch", "get", array('contact_id' => $cid));
    $this->assertTrue($results['count'] == 0);
  }

  /**
   * Test form sumbission when user passwords dont match.
   */
  public function testMismatchPasswordSubmission() {

    //login
    $this->webtestLogin('admin');

    //create a New Contact
    list($cid, $firstName, $lastName, $email) = $this->_createUserAndGotoForm();
    $password = 'abc123';

    //submit with mismatch passwords
    $username = $this->settings->adminUsername;
    $this->_fillCMSUserForm($username, $password, $password . "mismatch");
    $this->click("_qf_Useradd_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //check that that there is a password mismatch text
    $this->assertElementContainsText("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td/span[@class='crm-error']", "Password mismatch", "No form error given on password missmatch");

    //check that no user was created;
    $results = $this->webtest_civicrm_api("UFMatch", "get", array('contact_id' => $cid));
    $this->assertTrue($results['count'] == 0);
  }

  public function testMissingDataSubmission() {

    //login
    $this->webtestLogin('admin');

    //create a New Contact
    list($cid, $firstName, $lastName, $email) = $this->_createUserAndGotoForm();
    $password = 'abc123';

    //submit with mismatch passwords
    $username = $this->settings->adminUsername;
    $this->click("_qf_Useradd_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //the civicrm messages section should not indicate that a user has been created
    $this->assertElementNotContainsText("xpath=//span[@class='crm-error']", "User has been added", "CiviCRM messages say that a user was created when username left blank");

    //the civicrm message should say username is required
    $this->assertElementContainsText("xpath=//span[@class='crm-error']", "Username is required", "The CiviCRM messae does not indicate that the username is required");

    //the civicrm message should say password is required
    $this->assertElementContainsText("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td/span[@class='crm-error']", "Password is required", "The CiviCRM messae does not indicate that the password is required");

    //check that no user was created;
    $results = $this->webtest_civicrm_api("UFMatch", "get", array('contact_id' => $cid));
    $this->assertTrue($results['count'] == 0);
  }

  /**
   * Test a valid (username unique and passwords match) submission.
   */
  public function testValidSubmission() {

    //login
    $this->webtestLogin('admin');

    //create a New Contact
    list($cid, $firstName, $lastName, $email) = $this->_createUserAndGotoForm();
    $password = 'abc123';

    //submit with matching passwords
    $this->_fillCMSUserForm($firstName, $password, $password);
    $this->waitForAjaxContent();
    $this->click("_qf_Useradd_next-bottom");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    //drupal messages should say user created
    $this->assertTrue($this->isTextPresent("Created a new user account"), "Drupal does not report success creating user in the message");

    //The new user id should be on the page
    $this->assertTrue($this->isTextPresent("User ID"));

    //Assert that a user was actually created AND that they are tied to the record
    $results = $this->webtest_civicrm_api("UFMatch", "get", array('contact_id' => $cid));
    $this->assertTrue($results['count'] == 1);
  }

  /**
   * @param string $username
   * @param $password
   * @param $confirm_password
   */
  public function _fillCMSUserForm($username, $password, $confirm_password) {
    $this->type("cms_name", $username);
    $this->type("cms_pass", $password);
    $this->type("cms_confirm_pass", $confirm_password);
  }

  /**
   * @return array
   */
  public function _createUserAndGoToForm() {
    $firstName = substr(sha1(rand()), 0, 7) . "John";
    $lastName = substr(sha1(rand()), 0, 7) . "Smith";
    $email = $this->webtestAddContact($firstName, $lastName, TRUE);

    // Get the contact id of the new contact
    $cid = $this->urlArg('cid');

    //got to the new cms user form
    $this->openCiviPage('contact/view/useradd', "reset=1&action=add&cid={$cid}");

    return array($cid, $firstName, $lastName, $email);
  }

}
