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
class WebTest_Contact_SignatureTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /*
   *  Test Signature in TinyMC.
   */
  function testTinyMCE() {
    $this->webtestLogin();

    $this->openCiviPage('dashboard', 'reset=1', 'crm-recently-viewed');
    $this->click("//div[@id='crm-recently-viewed']/ul/li/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $name = $this->getText("xpath=//div[@class='crm-summary-display_name']");

    // Get contact id from url.
    $matches = array();
    preg_match('/cid=([0-9]+)/', $this->getLocation(), $matches);
    $contactId = $matches[1];

    // Select Your Editor
    $this->_selectEditor('TinyMCE');

    $this->openCiviPage("contact/add", "reset=1&action=update&cid={$contactId}");

    $this->click("//tr[@id='Email_Block_1']/td[1]/div[2]/div[1]");
    // HTML format message
    $signature = 'Contact Signature in html';

    $this->fireEvent('email_1_signature_html', 'focus');
    $this->fillRichTextField('email_1_signature_html', $signature, 'TinyMCE');

    // TEXT Format Message
    $this->type('email_1_signature_text', 'Contact Signature in text');
    $this->click('_qf_Contact_upload_view-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText('crm-notification-container', "Contact Saved");

    // Go for Ckeck Your Editor, Click on Send Mail
    $this->click("//a[@id='crm-contact-actions-link']/span");
    $this->click('link=Send an Email');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(10);

    $this->click('subject');
    $subject = 'Subject_' . substr(sha1(rand()), 0, 7);
    $this->type('subject', $subject);

    // Is signature correct? in Editor
    $this->_checkSignature('html_message', $signature, 'TinyMCE');

    $this->click('_qf_Email_upload-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go for Activity Search
    $this->_checkActivity($subject, $signature);
  }

  /*
   *  Test Signature in CKEditor.
   */
  function testCKEditor() {
    $this->webtestLogin();

    $this->openCiviPage('dashboard', 'reset=1', 'crm-recently-viewed');
    $this->click("//div[@id='crm-recently-viewed']/ul/li/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $name = $this->getText("xpath=//div[@class='crm-summary-display_name']");

    // Get contact id from url.
    $matches = array();
    preg_match('/cid=([0-9]+)/', $this->getLocation(), $matches);
    $contactId = $matches[1];

    // Select Your Editor
    $this->_selectEditor('CKEditor');

    $this->openCiviPage("contact/add", "reset=1&action=update&cid={$contactId}");
    $this->click("//tr[@id='Email_Block_1']/td[1]/div[2]/div[1]");
    
    // HTML format message
    $signature = 'Contact Signature in html';
    $this->fireEvent('email_1_signature_html', 'focus');
    $this->fillRichTextField('email_1_signature_html', $signature, 'CKEditor');

    // TEXT Format Message
    $this->type('email_1_signature_text', 'Contact Signature in text');
    $this->click('_qf_Contact_upload_view-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->assertElementContainsText("crm-notification-container", "{$name} has been updated.");

    // Go for Ckeck Your Editor, Click on Send Mail
    $this->click("//a[@id='crm-contact-actions-link']/span");
    $this->click('link=Send an Email');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    sleep(10);

    $this->click('subject');
    $subject = 'Subject_' . substr(sha1(rand()), 0, 7);
    $this->type('subject', $subject);

    // Is signature correct? in Editor
    $this->_checkSignature('html_message', $signature, 'CKEditor');

    $this->click('_qf_Email_upload-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Go for Activity Search
    $this->_checkActivity($subject, $signature);
  }

  /*
   * Helper function to select Editor.
   */
  function _selectEditor($editor) {
    // Go directly to the URL of Set Default Editor.
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1');

    // Select your Editor
    $this->click('editor_id');
    $this->select('editor_id', "label=$editor");
    $this->click('_qf_Display_next-bottom');
    $this->waitForPageToLoad($this->getTimeoutMsec());
  }
  /*
   * Helper function for Check Signature in Editor.
   */
  function _checkSignature($fieldName, $signature, $editor) {
    if ($editor == 'CKEditor') {
      $this->waitForElementPresent("xpath=//div[@id='cke_{$fieldName}']//iframe");
      $this->selectFrame("xpath=//div[@id='cke_{$fieldName}']//iframe");
    }
    else {
      $this->selectFrame("xpath=//iframe[@id='{$fieldName}_ifr']");
    }

    $this->verifyText('//html/body', preg_quote("{$signature}"));
    $this->selectFrame('relative=top');
  }
  /*
   * Helper function for Check Signature in Activity.
   */
  function _checkActivity($subject, $signature) {
    $this->openCiviPage('activity/search', 'reset=1', '_qf_Search_refresh');

    $this->type('activity_subject', $subject);

    $this->click('_qf_Search_refresh');
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('Search');

    // View your Activity
    $this->click("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $this->waitForElementPresent('_qf_ActivityView_next-bottom');

    // Is signature correct? in Activity
    $this->assertTrue($this->isTextPresent($signature));
  }
}

