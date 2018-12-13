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
 * Class WebTest_Contact_SignatureTest
 */
class WebTest_Contact_SignatureTest extends CiviSeleniumTestCase {

  protected function setUp() {
    parent::setUp();
  }

  /**
   *  Test Signature in CKEditor.
   */
  public function testCKEditor() {
    $this->webtestLogin();

    $this->openCiviPage('dashboard', 'reset=1', 'crm-recently-viewed');
    $this->click("//div[@id='crm-recently-viewed']/ul/li/a");
    $this->waitForPageToLoad($this->getTimeoutMsec());
    $name = $this->getText("xpath=//div[@class='crm-summary-display_name']");

    // Get contact id from url.
    $contactId = $this->urlArg('cid');

    // Select Your Editor
    $this->_selectEditor('CKEditor');

    $this->openCiviPage("contact/add", "reset=1&action=update&cid={$contactId}");
    $this->click("//tr[@id='Email_Block_1']/td[1]/div[3]/div[1]");

    // HTML format message
    $signature = 'Contact Signature in html';
    $this->fireEvent('email_1_signature_html', 'focus');
    $this->fillRichTextField('email_1_signature_html', $signature, 'CKEditor');

    // TEXT Format Message
    $this->type('email_1_signature_text', 'Contact Signature in text');
    $this->click('_qf_Contact_upload_view-top');
    $this->waitForPageToLoad($this->getTimeoutMsec());

    // Is status message correct?
    $this->waitForText('crm-notification-container', "{$name} has been updated.");

    // Go for Ckeck Your Editor, Click on Send Mail
    $this->click("//a[@id='crm-contact-actions-link']/span");
    $this->clickLink('link=Send an Email', 'subject', FALSE);

    $this->click('subject');
    $subject = 'Subject_' . substr(sha1(rand()), 0, 7);
    $this->type('subject', $subject);

    // Is signature correct? in Editor
    $this->_checkSignature('html_message', $signature, 'CKEditor');

    $this->click('_qf_Email_upload-top');
    $this->waitForElementPresent("//a[@id='crm-contact-actions-link']/span");

    // Go for Activity Search
    $this->_checkActivity($subject, $signature);
  }

  /**
   * Helper function to select Editor.
   * @param $editor
   */
  public function _selectEditor($editor) {
    $this->openCiviPage('admin/setting/preferences/display', 'reset=1');

    // Change editor if not already selected
    if ($this->getSelectedLabel('editor_id') != $editor) {
      $this->click('editor_id');
      $this->select('editor_id', "label=$editor");
      $this->click('_qf_Display_next-bottom');
      $this->waitForPageToLoad($this->getTimeoutMsec());
    }
  }

  /**
   * Helper function for Check Signature in Editor.
   * @param $fieldName
   * @param $signature
   * @param $editor
   */
  public function _checkSignature($fieldName, $signature, $editor) {
    if ($editor == 'CKEditor') {
      $this->waitForElementPresent("xpath=//div[@id='cke_{$fieldName}']//iframe");
      $this->selectFrame("xpath=//div[@id='cke_{$fieldName}']//iframe");
    }
    else {
      $this->selectFrame("xpath=//iframe[@id='{$fieldName}_ifr']");
    }

    $this->assertElementContainsText("//html/body", "$signature");
    $this->selectFrame('relative=top');
  }

  /**
   * Helper function for Check Signature in Activity.
   * @param $subject
   * @param $signature
   */
  public function _checkActivity($subject, $signature) {
    $this->openCiviPage('activity/search', 'reset=1', '_qf_Search_refresh');

    $this->type('activity_subject', $subject);

    $this->clickLink('_qf_Search_refresh', 'Search');

    // View your Activity
    $this->clickLink("xpath=id('Search')/div[3]/div/div[2]/table/tbody/tr[2]/td[9]/span/a[text()='View']", '_qf_ActivityView_cancel-bottom', FALSE);

    // Is signature correct? in Activity
    $this->assertTextPresent($signature);
  }

}
