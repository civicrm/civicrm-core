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
 * Class WebTest_Contact_InlineFieldsEditTest
 */
class WebTest_Contact_InlineFieldsEditTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  public function testAddAndEditField() {
    $this->webtestLogin();

    // Add a contact
    $firstName = 'WebTest' . substr(sha1(rand()), 0, 7);
    $lastName = 'InlineFieldsEdit' . substr(sha1(rand()), 0, 7);
    $this->webtestAddContact($firstName, $lastName);
    $contactId = $this->urlArg('cid');
    $this->waitForElementPresent('css=.crm-inline-edit-container.crm-edit-ready');

    // Set Communication Prefs
    $this->inlineEdit('crm-communication-pref-content', array(
      'email_greeting_id' => TRUE,
      'privacy_do_not_email' => 1,
      'preferred_communication_method_1' => 1,
      'preferred_communication_method_2' => 1,
    ), 'keep_open');
    $this->waitForElementPresent('css=.icon.privacy-flag.do-not-email');
    $this->inlineEdit('crm-communication-pref-content', array(
      'privacy_do_not_phone' => 1,
      'privacy_do_not_email' => 0,
      'preferred_communication_method_1' => 0,
      'preferred_communication_method_2' => 0,
    ), 'keep_open');
    $this->waitForElementPresent('css=.icon.privacy-flag.do-not-phone');
    $this->inlineEdit('crm-communication-pref-content', array(
      'email_greeting_custom' => 'Hey You!',
    ), 'no_open');
    $this->assertElementNotPresent('css=.icon.privacy-flag.do-not-email');

    // Custom data
    $this->click('css=div.crm-custom-set-block-1 .collapsible-title');
    $this->waitForAjaxContent();
    $this->openInlineForm('custom-set-content-1');
    $dateFieldId = $this->getAttribute("xpath=//table[@class='form-layout-compressed']/tbody/tr[3]/td[@class='html-adjust']/span/input@id");
    $this->inlineEdit('custom-set-content-1', array(
      'CIVICRM_QFID_Edu_2' => 1,
      "//table[@class='form-layout-compressed']/tbody/tr[2]/td[@class='html-adjust']/select" => array('Single'),
      $dateFieldId => 'date: now - 10 years',
    ));

    // Edit contact info
    $params = array(
      'job_title' => 'jobtest123',
      'nick_name' => 'nicktest123',
      'contact_source' => 'sourcetest123',
    );
    $this->inlineEdit('crm-contactinfo-content', $params, 'keep_open');
    // Clear fields and verify they are deleted
    $this->inlineEdit('crm-contactinfo-content', array(
      'job_title' => '',
      'nick_name' => '',
      'contact_source' => '',
    ), 'no_open');
    foreach ($params as $str) {
      $this->assertElementNotContainsText('crm-contactinfo-content', $str);
    }

    // Add a phone
    $this->inlineEdit('crm-phone-content', array(
      'phone_1_phone' => '123-456-7890',
      'phone_1_phone_ext' => '101',
      'phone_1_location_type_id' => array('Work'),
      'phone_1_phone_type_id' => array('Mobile'),
    ));

    // Add im
    $this->inlineEdit('crm-im-content', array(
      'im_1_name' => 'testmeout',
      'im_1_location_type_id' => array('Work'),
      'im_1_provider_id' => array('Jabber'),
    ));

    // Add an address - should default to home
    $this->inlineEdit('address-block-1', array(
      'address_1_street_address' => '123 St',
      'address_1_city' => 'San Somewhere',
    ), 'keep_open');
    // Try to uncheck is_primary, we should get an error and it should stay checked
    $this->click('address[1][is_primary]');
    $this->waitForElementPresent('css=#crm-notification-container .error.ui-notify-message');
    $this->assertChecked('address[1][is_primary]');
    // Try to open another form while this one is still open - nothing should happen
    $this->waitForElementPresent('address-block-2');
    $this->openInlineForm('address-block-2', FALSE);
    $this->assertElementNotPresent('css#address-block-2.form');
    // Update address
    $this->inlineEdit('address-block-1', array(
      'address_1_street_address' => '321 Other St',
      'address_1_city' => 'Sans Nowhere',
      'address_1_postal_code' => '99999',
      'address_1_postal_code_suffix' => '99',
    ), 'no_open');
    // Another address with same location type as first - should give an error
    $this->inlineEdit('address-block-2', array(
      'address_2_street_address' => '123 Foo',
      'address_2_city' => 'San Anywhere',
      'address_2_location_type_id' => array('Home'),
    ), 'error');
    $this->waitForTextPresent('required');
    // Share address with a new org
    $this->click('address[2][use_shared_address]');
    $orgName = 'Test Org Inline' . substr(sha1(rand()), 0, 7);

    // create new organization with dialog
    $this->clickAt("xpath=//div[@id='s2id_address_2_master_contact_id']/a");
    $this->click("xpath=//li[@class='select2-no-results']//a[contains(text(),' New Organization')]");
    $this->waitForElementPresent("css=div#crm-profile-block");
    $this->waitForElementPresent("_qf_Edit_next");

    $this->type('organization_name', $orgName);
    $this->type('street_address-1', 'Test Org Street');
    $this->type('city-1', 'Test Org City');
    $this->clickLink('_qf_Edit_next', 'selected_shared_address-2', FALSE);
    $this->waitForTextPresent('Test Org Street');
    $this->inlineEdit('address-block-2', array(
      'address_2_location_type_id' => array('Work'),
    ), 'no_open');
    $this->waitForElementPresent('css=.crm-content.crm-contact-current_employer');
    $this->assertElementContainsText('crm-contactinfo-content', $orgName);
    $this->assertElementContainsText('address-block-2', $orgName);
    $this->assertElementContainsText('address-block-2', 'Work Address');

    // Edit demographics
    $this->inlineEdit('crm-demographic-content', array(
      "xpath=//div[@class='crm-clear']/div[1]/div[@class='crm-content']/label[text()='Female']" => TRUE,
      'is_deceased' => 1,
      'birth_date' => 'date: Jan 1 1970',
    ), 'no_open');
    $this->assertElementContainsText('crm-demographic-content', 'Female');
    $this->assertElementContainsText('crm-demographic-content', 'Contact is Deceased');
    $this->inlineEdit('crm-demographic-content', array(
      'is_deceased' => 0,
    ), 'no_open');
    $age = date('Y') - 1970;
    $this->assertElementContainsText('crm-demographic-content', "$age years");

    // Add emails
    $this->inlineEdit('crm-email-content', array(
      'css=#crm-email-content a.add-more-inline' => TRUE,
      'email_1_email' => 'test1@monkey.com',
      'email_2_email' => 'test2@monkey.com',
    ), 'keep_open');

    // Try an invalid email
    $this->inlineEdit('crm-email-content', array(
      'email_2_email' => 'invalid@monkey,com',
    ), 'errorJs');

    // Delete email
    $this->inlineEdit('crm-email-content', array(
      'css=#Email_Block_2 a.crm-delete-inline' => TRUE,
    ));
    $this->assertElementNotContainsText('crm-email-content', 'test2@monkey.com');

    // Add website with invalid url
    $this->inlineEdit('crm-website-content', array(
      'css=#crm-website-content a.add-more-inline' => TRUE,
      'website_1_url' => 'http://example.com',
      'website_2_url' => 'something.wrong',
    ), 'errorJs');

    // Correct invalid url and add a third website
    $this->inlineEdit('crm-website-content', array(
      'css=#crm-website-content a.add-more-inline' => TRUE,
      'website_2_url' => 'http://example.net',
      'website_2_website_type_id' => array('Work'),
      'website_3_url' => 'http://example.biz',
      'website_3_website_type_id' => array('Main'),
    ), 'keep_open');

    // Delete website
    $this->inlineEdit('crm-website-content', array(
      'css=#Website_Block_2 a.crm-delete-inline' => TRUE,
    ));
    $this->assertElementNotContainsText('crm-website-content', 'http://example.net');

    // Change contact name
    $this->inlineEdit('crm-contactname-content', array(
      'first_name' => 'NewName',
      'prefix_id' => array('Mr.'),
    ));
    $this->assertElementContainsText('css=div.crm-summary-display_name', "Mr. NewName $lastName");
    // Page title should be updated with new name on reload
    $this->openCiviPage('contact/view', "reset=1&cid=$contactId", "crm-record-log");
    $this->assertElementContainsText('css=title', "Mr. NewName $lastName");
  }

  /**
   * Click on an inline-edit block and wait for it to open
   *
   * @param string $block
   *   selector.
   * @param bool $wait
   */
  private function openInlineForm($block, $wait = TRUE) {
    $this->mouseDown($block);
    $this->mouseUp($block);
    if ($wait) {
      $this->waitForElementPresent("css=#$block .crm-container-snippet form");
    }
  }

  /**
   * Enter values in an inline edit block and save.
   *
   * @param string $block
   *   selector.
   * @param array $params
   * @param \str|string $valid str: submit behavior
   *   'error' if we are expecting a form validation error,
   *   're_open' (default) after saving, opens the form and validate inputs
   *   'keep_open' same as 're_open' but doesn't automatically cancel at the end
   *   'no_open' do not re-open to validate
   */
  private function inlineEdit($block, $params, $valid = 're_open') {
    $this->openInlineForm($block);
    foreach ($params as $item => $val) {
      switch (gettype($val)) {
        case 'boolean':
          $this->click($item);
          break;

        case 'string':
          if (substr($val, 0, 5) == 'date:') {
            $this->webtestFillDate($item, trim(substr($val, 5)));
          }
          else {
            $this->type($item, $val);
          }
          break;

        case 'integer':
          $method = $val ? 'check' : 'uncheck';
          $this->$method($item);
          break;

        case 'array':
          foreach ($val as $option) {
            $selector = is_int($option) ? 'value' : 'label';
            $this->select($item, "$selector=$option");
          }
          break;
      }
    }
    $this->click("css=#$block input.crm-form-submit");
    if ($valid !== 'error' && $valid != 'errorJs') {
      // Verify the form saved
      $this->waitForElementPresent("css=#$block > .crm-inline-block-content");
      $validate = FALSE;
      foreach ($params as $val) {
        if (is_string($val) && $val && substr($val, 0, 5) != 'date:') {
          $this->assertElementContainsText($block, $val);
          $validate = TRUE;
        }
        elseif (!is_bool($val)) {
          $validate = TRUE;
        }
        if (is_array($val)) {
          foreach ($val as $option) {
            if (!is_int($option)) {
              $this->assertElementContainsText($block, $option);
            }
          }
        }
      }
      // Open the form back up and check everything
      if ($validate && $valid !== 'no_open') {
        $this->openInlineForm($block);
        foreach ($params as $item => $val) {
          switch (gettype($val)) {
            case 'string':
              if ($val && substr($val, 0, 5) == 'date:') {
                $val = date('m/d/Y', strtotime(trim(substr($val, 5))));
                $item = "xpath=//input[@id='{$item}']/following-sibling::input";
              }
              if ($val) {
                $this->assertElementValueEquals($item, $val);
              }
              break;

            case 'integer':
              $method = $val ? 'assertChecked' : 'assertNotChecked';
              $this->$method($item);
              break;

            case 'array':
              foreach ($val as $option) {
                $method = is_int($option) ? 'assertIsSelected' : 'assertSelected';
                $this->$method($item, $option);
              }
              break;
          }
        }
        if ($valid !== 'keep_open') {
          $this->click("css=#$block input.cancel");
        }
      }
    }
    // Verify there was a form error
    else {
      switch ($valid) {
        case 'errorJs':
          $this->waitForElementPresent('css=label.error');
          break;

        default:
          $this->waitForElementPresent('css=#crm-notification-container .error.ui-notify-message');
          $this->click('css=#crm-notification-container .error .ui-notify-cross');
      }
    }
  }

}
