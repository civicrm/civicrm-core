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

/**
 *  Mail utils for use during unit testing to allow retrieval
 *  and examination of 'sent' emails.
 *
 *  Basic usage:
 *
 *  $mut = new CiviMailUtils( $this, true ); //true automatically starts spooling
 *  ... do stuff ...
 *  $msg = $mut->getMostRecentEmail( 'raw' ); // or 'ezc' to get an ezc mail object
 *  ... assert stuff about $msg ...
 *  $mut->stop();
 *
 *
 * @package CiviCRM
 */

require_once 'ezc/Base/src/ezc_bootstrap.php';
require_once 'ezc/autoload/mail_autoload.php';

class CiviMailUtils extends PHPUnit_Framework_TestCase {

  /**
   * @var mixed current outbound email option
   */
  protected $_outBound_option = NULL;

  /**
   * @var bool is this a webtest
   */
  protected $_webtest = FALSE;

  /**
   * Constructor
   *
   * @param $unit_test object The currently running test
   * @param $startImmediately bool Start writing to db now or wait until start() is called
   */
  function __construct(&$unit_test, $startImmediately = TRUE) {
    $this->_ut = $unit_test;

    // Check if running under webtests or not
    if (is_subclass_of($unit_test, 'CiviSeleniumTestCase')) {
      $this->_webtest = TRUE;
    }

    if ($startImmediately) {
      $this->start();
    }
  }

  /**
   * Start writing emails to db instead of current option
   */
  function start() {
    if ($this->_webtest) {
      // Change outbound mail setting
      $this->_ut->open($this->_ut->sboxPath . "civicrm/admin/setting/smtp?reset=1");
      $this->_ut->waitForElementPresent("_qf_Smtp_next");

      // First remember the current setting
      $this->_outBound_option = $this->getSelectedOutboundOption();

      $this->_ut->click('xpath=//input[@name="outBound_option" and @value="' . CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB . '"]');
      $this->_ut->click("_qf_Smtp_next");
      $this->_ut->waitForPageToLoad("30000");

      // Is there supposed to be a status message displayed when outbound email settings are changed?
      // assert something?

    }
    else {

      // save current setting for outbound option, then change it
      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );

      $this->_outBound_option = $mailingBackend['outBound_option'];
      $mailingBackend['outBound_option'] = CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB;

      CRM_Core_BAO_Setting::setItem($mailingBackend,
        CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );

      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );
    }
  }

  function stop() {
    if ($this->_webtest) {

      $this->_ut->open($this->_ut->sboxPath . "civicrm/admin/setting/smtp?reset=1");
      $this->_ut->waitForElementPresent("_qf_Smtp_next");
      $this->_ut->click('xpath=//input[@name="outBound_option" and @value="' . $this->_outBound_option . '"]');
      $this->_ut->click("_qf_Smtp_next");
      $this->_ut->waitForPageToLoad("30000");

      // Is there supposed to be a status message displayed when outbound email settings are changed?
      // assert something?

    }
    else {

      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );

      $mailingBackend['outBound_option'] = $this->_outBound_option;

      CRM_Core_BAO_Setting::setItem($mailingBackend,
        CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );
    }
  }

  function getMostRecentEmail($type = 'raw') {
    $msg = '';

    // Check if running under webtests or not
    if ($this->_webtest) {

      $this->_ut->open($this->_ut->sboxPath . 'civicrm/mailing/browse/archived?reset=1');
      // I don't understand but for some reason we have to load the page twice for a recent mailing to appear.
      $this->_ut->waitForPageToLoad("30000");
      $this->_ut->open($this->_ut->sboxPath . 'civicrm/mailing/browse/archived?reset=1');
      $this->_ut->waitForElementPresent('css=td.crm-mailing-name');

      // This should select the first "Report" link in the table, which is sorted by Completion Date descending, so in theory is the most recent email. Not sure of a more robust way at the moment.
      $this->_ut->click('xpath=//tr[contains(@id, "crm-mailing_")]//a[text()="Report"]');

      // Also not sure how robust this is, but there isn't a good
      // identifier for this link either.
      $this->_ut->waitForElementPresent('xpath=//a[contains(text(), "View complete message")]');
      $this->_ut->click('xpath=//a[contains(text(), "View complete message")]');

      $this->_ut->waitForPopUp(NULL, 30000);
      $this->_ut->selectPopUp(NULL);
      /*
       * FIXME:
       *
       * Argh.
       * getBodyText() doesn't work because you can't get the actual html, just the rendered version.
       * getHtmlSource() doesn't work because it sees email addresses as html tags and inserts its own closing tags.
       *
       * At the moment the combination of escaping just the headers in CRM_Mailing_BAO_Spool plus using getBodyText() works well enough to do basic unit testing and also not screw up the display in the actual UI.
       */
      //$msg = $this->_ut->getHtmlSource();
      $msg = $this->_ut->getBodyText();
      $this->_ut->close();
      $this->_ut->selectWindow(NULL);

    }
    else {
      $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id DESC LIMIT 1');
      if ($dao->fetch()) {
        $msg = $dao->headers . "\n\n" . $dao->body;
      }
      $dao->free();
    }

    switch ($type) {
      case 'raw':
        // nothing to do
        break;
      case 'ezc':
        $msg = $this->convertToEzc($msg);
        break;
    }
    return $msg;
  }

  /**
   * @param string $type 'raw'|'ezc'
   * @return array(ezcMail)|array(string)
   */
  function getAllMessages($type = 'raw') {
    $msgs = array();

    if ($this->_webtest) {
      throw new Exception("Not implementated: getAllMessages for WebTest");
    }
    else {
      $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id');
      while ($dao->fetch()) {
        $msgs[] = $dao->headers . "\n\n" . $dao->body;
      }
      $dao->free();
    }

    switch ($type) {
      case 'raw':
        // nothing to do
        break;
      case 'ezc':
        foreach ($msgs as $i => $msg) {
          $msgs[$i] = $this->convertToEzc($msg);
        }
        break;
    }

    return $msgs;
  }

  function getSelectedOutboundOption() {
    $selectedOption = CRM_Mailing_Config::OUTBOUND_OPTION_MAIL;
    // Is there a better way to do this? How do you get the currently selected value of a radio button in selenium?
    for ($i = 0; $i <= 5; $i++) {
      if ($i != CRM_Mailing_Config::OUTBOUND_OPTION_MOCK) {
        if ($this->_ut->getValue('xpath=//input[@name="outBound_option" and @value="' . $i . '"]') == "on") {
          $selectedOption = $i;
          break;
        }
      }
    }
    return $selectedOption;
  }

  /*
   * Utility functions (previously part of CiviUnitTestCase)
   * Included for backward compatibility with existing tests.
   */

  /**
   * Check contents of mail log
   * @param array $strings strings that should be included
   * @param array $absentStrings strings that should not be included
   *
   */
  function checkMailLog($strings, $absentStrings = array(), $prefix = '') {
    $mail = $this->getMostRecentEmail('raw');
    foreach ($strings as $string) {
      $this->_ut->assertContains($string, $mail, "$string .  not found in  $mail  $prefix");
    }
    foreach ($absentStrings as $string) {
      $this->_ut->assertEmpty(strstr($mail, $string), "$string  incorrectly found in $mail $prefix");
      ;
    }
    return $mail;
  }

  /**
   * Check that mail log is empty
   */
  function assertMailLogEmpty($prefix = '') {
    $mail = $this->getMostRecentEmail('raw');
    $this->_ut->assertEmpty($mail, 'mail sent when it should not have been ' . $prefix);
  }

  /**
   * Assert that $expectedRecipients (and no else) have received emails
   *
   * @param array $expectedRecipients array($msgPos => array($recipPos => $emailAddr))
   */
  function assertRecipients($expectedRecipients) {
    $recipients = array();
    foreach ($this->getAllMessages('ezc') as $message) {
      $recipients[] = CRM_Utils_Array::collect('email', $message->to);
    }
    sort($recipients);
    sort($expectedRecipients);
    $this->_ut->assertEquals(
      $expectedRecipients,
      $recipients,
      "Incorrect recipients: " . print_r(array('expected' => $expectedRecipients, 'actual' => $recipients), TRUE)
    );
  }

  /**
   * Remove any sent messages from the log
   */
  function clearMessages() {
    if ($this->_webtest) {
      throw new Exception("Not implementated: clearMessages for WebTest");
    }
    else {
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_mailing_spool ORDER BY id DESC LIMIT 1');
    }
  }

  /**
   * @param string $msg email header and body
   * @return ezcMail
   */
  private function convertToEzc($msg) {
    $set = new ezcMailVariableSet($msg);
    $parser = new ezcMailParser();
    $mail = $parser->parseMail($set);
    $this->_ut->assertNotEmpty($mail, 'Cannot parse mail');
    return $mail[0];
  }
}
