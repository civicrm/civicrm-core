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
class WebTest_Contact_UpdateProfileTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testUpdateProfile() {
    // Create new via profile
    include_once ('WebTest/Contact/AddViaProfileTest.php');
    WebTest_Contact_AddViaProfileTest::testAddViaCreateProfile();

    // Open profile for editing
    $locationUrl = $this->getLocation();
    $editUrl = str_replace('/view?', '/edit?', $locationUrl);
    $this->open($editUrl);
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Modify profile field values
    // contact details section
    // name fields
    $firstName = 'Ma' . substr(sha1(rand()), 0, 4);
    $lastName = 'An' . substr(sha1(rand()), 0, 7);

    $this->type("first_name", $firstName);
    $this->type("last_name", $lastName);

    //Address Details
    $street = substr(sha1(rand()), 0, 4) . ' Main St.';
    $this->type("street_address-1", $street);
    $city = 'Ci ' . substr(sha1(rand()), 0, 4);
    $this->type("city-1", $city);
    $postalCode = substr(sha1(rand()), 0, 4);
    $this->type("postal_code-1", $postalCode);
    // Hard-coding to Arkansas, not  sure best way to get random state.
    $this->select("state_province-1", "value=1003");

    // Clicking save.
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Confirm save was done.
    $this->assertTrue($this->isTextPresent("Thank you. Your information has been saved."), 'In line ' . __LINE__);
    $this->assertTrue($this->isTextPresent($firstName), 'In line ' . __LINE__);
    $this->assertTrue($this->isTextPresent($lastName), 'In line ' . __LINE__);
    $this->assertTrue($this->isTextPresent($street), 'In line ' . __LINE__);
    $this->assertTrue($this->isTextPresent($city), 'In line ' . __LINE__);
    $this->assertTrue($this->isTextPresent($postalCode), 'In line ' . __LINE__);
    $this->assertTrue($this->isElementPresent("//div[@id='profilewrap1']/div[@id='crm-container']/div[7]/div[2][contains(text(), 'AR')]"));
  }
}

