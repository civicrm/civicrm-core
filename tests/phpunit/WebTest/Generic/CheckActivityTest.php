<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
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
class WebTest_Generic_CheckActivityTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testCheckDashboardElements() {
    // Log in using webtestLogin() method
    $this->webtestLogin();

    // Adding contact with randomized first name
    // We're using Quick Add block on the main page for this.
    $contactFirstName1 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contactFirstName1, "Devis", TRUE);

    // Adding another contact with randomized first name
    // We're using Quick Add block on the main page for this.
    $contactFirstName2 = substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($contactFirstName2, "Anderson", TRUE);
    $this->openCiviPage("activity", "reset=1&action=add&context=standalone", "_qf_Activity_upload");

    $this->select("activity_type_id", "label=Meeting");

    $this->click("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1");
    $this->type("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1", "$contactFirstName1");
    $this->typeKeys("css=tr.crm-activity-form-block-target_contact_id input#token-input-contact_1", "$contactFirstName1");

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    // ...need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up (span with delete token class indicates that it's present)...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-target_contact_id td ul li span.token-input-delete-token-facebook");

    // Now we're doing the same for "Assigned To" field.
    // Typing contact's name into the field (using typeKeys(), not type()!)...
    $this->click("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id");
    $this->type("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", "$contactFirstName2");
    $this->typeKeys("css=tr.crm-activity-form-block-assignee_contact_id input#token-input-assignee_contact_id", "$contactFirstName2");

    // ...waiting for drop down with results to show up...
    $this->waitForElementPresent("css=div.token-input-dropdown-facebook");
    $this->waitForElementPresent("css=li.token-input-dropdown-item2-facebook");

    //..need to use mouseDownAt on first result (which is a li element), click does not work
    $this->mouseDownAt("css=li.token-input-dropdown-item2-facebook");

    // ...again, waiting for the box with contact name to show up...
    $this->waitForElementPresent("css=tr.crm-activity-form-block-assignee_contact_id td ul li span.token-input-delete-token-facebook");
  }
}

