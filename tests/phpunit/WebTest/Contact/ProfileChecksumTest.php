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
class WebTest_Contact_ProfileChecksumTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  function testProfileChecksum() {
    // This is the path where our testing install resides.
    // The rest of URL is defined in CiviSeleniumTestCase base class, in
    // class attributes.
    $this->open($this->sboxPath);

    // Logging in. Remember to wait for page to load. In most cases,
    // you can rely on 30000 as the value that allows your test to pass, however,
    // sometimes your test might fail because of this. In such cases, it's better to pick one element
    // somewhere at the end of page and use waitForElementPresent on it - this assures you, that whole
    // page contents loaded and you can continue your test execution.
    $this->webtestLogin(TRUE);

    // Profile fields.
    $fields = array(
      'first_name' => array(
        'type' => 'Individual',
        'label' => 'First Name',
        'default_value' => substr(sha1(rand()), 0, 7),
        'update_value' => substr(sha1(rand()), 0, 7),
        'element_name' => 'first_name',
      ),
      'last_name' => array(
        'type' => 'Individual',
        'label' => 'Last Name',
        'default_value' => substr(sha1(rand()), 0, 7),
        'update_value' => substr(sha1(rand()), 0, 7),
        'element_name' => 'last_name',
      ),
      'email' => array(
        'type' => 'Contact',
        'label' => 'Email',
        'location' => 0,
        'default_value' => substr(sha1(rand()), 0, 5) . '@example.com',
        'update_value' => substr(sha1(rand()), 0, 7) . '@example.com',
        'element_name' => 'email-Primary',
      ),
      'city' => array(
        'type' => 'Contact',
        'label' => 'City',
        'location' => 0,
        'default_value' => substr(sha1(rand()), 0, 7),
        'update_value' => substr(sha1(rand()), 0, 7),
        'element_name' => 'city-Primary',
      ),
      'state_province' => array(
        'type' => 'Contact',
        'label' => 'State',
        'location' => 0,
        'default_value' => '1004',
        'update_value' => '1031',
        'update_value_label' => 'NY',
        'element_name' => 'state_province-Primary',
        'html_type' => 'select',
      ),
      'country' => array(
        'type' => 'Contact',
        'label' => 'Country',
        'location' => 0,
        'default_value' => '1228',
        'update_value' => '1228',
        'update_value_label' => 'United States',
        'element_name' => 'country-Primary',
        'html_type' => 'select',
      ),
    );

    // Create a contact.
    $this->webtestAddContact($fields['first_name']['default_value'], $fields['last_name']['default_value'], $fields['email']['default_value']);

    // Get contact id from url.
    $matches = array();
    preg_match('/cid=([0-9]+)/', $this->getLocation(), $matches);
    $contactId = $matches[1];

    // Create profile for contact
    $profileName = "Profile_" . substr(sha1(rand()), 0, 7);
    $profileId = $this->_testCreateContactProfile($fields, $profileName);

    // Check for profile create/edit permissions.
    $permission = array('edit-1-profile-edit', 'edit-1-profile-create', 'edit-1-access-all-custom-data');
    $this->changePermissions($permission);

    // Get checksum of the newly created contact.
    require_once 'CRM/Contact/BAO/Contact/Utils.php';
    $cs = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId);

    // Logout.
    $this->open($this->sboxPath . 'civicrm/logout?reset=1');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go to edit profile page of the created contact.
    $this->open($this->sboxPath . "civicrm/profile/edit?id={$contactId}&gid={$profileId}&reset=1&cs={$cs}");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    $this->assertStringsPresent(array($profileName));

    // Check all profile fields, update their values.
    foreach ($fields as $field) {
      $this->assertTrue($this->isElementPresent($field['element_name']), "Missing Field: {$field['label']}.");
      if (isset($field['html_type']) && $field['html_type'] == 'select') {
        $this->select($field['element_name'], "value={$field['update_value']}");
      }
      else {
        $this->type($field['element_name'], $field['update_value']);
      }
    }
    // Save profile.
    $this->click("_qf_Edit_next");
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());

    // Check profile view page.
    $this->assertStringsPresent(array($profileName));

    // Check updated values of all fields.
    $checkFieldValues = array();
    foreach ($fields as $field) {
      $checkFieldValues[] = isset($field['update_value_label']) ? $field['update_value_label'] : $field['update_value'];
    }
    $this->assertStringsPresent($checkFieldValues);
  }

  function _testCreateContactProfile($fields, $profileName) {
    // Go directly to the URL of the screen that you will be
    // testing (Add new profile ).
    $this->open($this->sboxPath . 'civicrm/admin/uf/group?reset=1');
    $this->waitForPageToLoad(2 * $this->getTimeoutMsec());
    $this->click('newCiviCRMProfile-top');
    $this->waitForElementPresent('_qf_Group_next-top');

    // Name of the profile.
    $this->type('title', $profileName);
    $this->click('_qf_Group_next-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $elements = $this->parseURL();
    $profileId = $elements['queryString']['gid'];

    // Add field to the profile.
    foreach ($fields as $key => $values) {
      $this->open($this->sboxPath . 'civicrm/admin/uf/group/field/add?reset=1&action=add&gid=' . $profileId);
      $this->waitForPageToLoad($this->getTimeoutMsec());

      $this->select("field_name[0]", "value={$values['type']}");
      sleep(1);
      $this->select("field_name[1]", "value={$key}");
      if (isset($values['location'])) {
        sleep(1);
        $this->select("field_name[2]", "value={$values['location']}");
      }
      $this->type("label", $values['label']);
      $this->click('_qf_Field_next-top');
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
    return $profileId;
  }
}

