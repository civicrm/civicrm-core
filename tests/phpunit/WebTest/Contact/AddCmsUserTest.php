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
 * Class WebTest_Contact_AddCmsUserTest
 */
class WebTest_Contact_AddCmsUserTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAuthenticAddUser() {

    $this->webtestLogin('admin');

    $this->open($this->sboxPath . 'admin/people/create');

    $this->waitForElementPresent('edit-submit');

    $name = 'TestUserAuthenticated' . substr(sha1(rand()), 0, 4);
    $this->type('edit-name', $name);

    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type('edit-mail', $emailId);
    $this->type('edit-pass-pass1', 'Test12345');
    $this->type('edit-pass-pass2', 'Test12345');

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('first_name');
    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);

    //Address Details
    $this->type('street_address-1', '902C El Camino Way SW');
    $this->type('city-1', 'Dumfries');
    $this->type('postal_code-1', '1234');
    $this->select('state_province-1', 'value=1019');

    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

  public function testAnonymousAddUser() {
    // Make sure Drupal account settings allow visitors to register for account w/o admin approval
    // login as admin
    $this->webtestLogin('admin');
    $this->open($this->sboxPath . 'admin/config/people/accounts');
    $this->waitForElementPresent('edit-submit');

    $this->click('edit-user-register-1');
    $this->check('edit-user-email-verification');
    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    // logout
    $this->webtestLogout();

    $this->open($this->sboxPath . 'user/register');

    $this->waitForElementPresent('edit-submit');
    $name = 'TestUserAnonymous' . substr(sha1(rand()), 0, 7);
    $this->type('edit-name', $name);
    $emailId = substr(sha1(rand()), 0, 7) . '@web.com';
    $this->type('edit-mail', $emailId);

    //Add profile Details
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);
    $this->waitForElementPresent('first_name');
    $this->type('first_name', $firstName);
    $this->type('last_name', $lastName);

    //Address Details
    $this->type('street_address-1', '902C El Camino Way SW');
    $this->type('city-1', 'Dumfries');
    $this->type('postal_code-1', '1234');
    $this->assertTrue($this->isTextPresent('UNITED STATES'));
    $this->select('state_province-1', 'value=1019');

    $this->click('edit-submit');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // In case the site is set up to login immediately upon registration
    $this->webtestLogout();

    $this->webtestLogin();

    $this->openCiviPage('contact/search', 'reset=1', '_qf_Basic_refresh');
    $this->type('sort_name', $emailId);
    $this->click('_qf_Basic_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    $this->assertElementContainsText('css=.crm-search-results', $emailId);
    $this->assertElementContainsText('css=.crm-search-results', $lastName . ', ' . $firstName);
    $this->assertElementContainsText('css=.crm-search-results', '902C El Camino Way SW');
    $this->assertElementContainsText('css=.crm-search-results', 'Dumfries');
    $this->assertElementContainsText('css=.crm-search-results', '1234');
  }

}
