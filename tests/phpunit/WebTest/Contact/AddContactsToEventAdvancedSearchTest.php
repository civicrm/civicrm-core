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
 * Class WebTest_Contact_AddContactsToEventAdvancedSearchTest
 */
class WebTest_Contact_AddContactsToEventAdvancedSearchTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddContactsToEventAdvanceSearch() {
    $this->webtestLogin();
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Advanced Search
    $this->openCiviPage('contact/search/advanced', 'reset=1', '_qf_Advanced_refresh');
    $this->click('_qf_Advanced_refresh');

    $this->waitForElementPresent("xpath=//div[@id='search-status']/table/tbody/tr[2]/td[2]/input[1]");
    $this->click("xpath=//div[@id='search-status']/table/tbody/tr[2]/td[2]/input[1]");

    $this->select('task', "label=Register participants for event");

    // Select event. Based on label for now.
    $this->waitForElementPresent('event_id');
    $this->select2('event_id', "Rain-forest Cup Youth Soccer Tournament");

    // Select role
    $this->multiselect2('role_id', array('Volunteer'));

    // Select participant status
    $this->select('status_id', 'value=1');

    // Setting registration source
    $this->type('source', 'Event StandaloneAddTest Webtest');

    $this->assertElementContainsText('css=tr.crm-participant-form-block-source span.description', 'Source for this registration (if applicable).');

    // Clicking save.
    $this->click('_qf_Participant_upload-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }

}
