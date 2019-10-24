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

/**
 * Class CiviMailUtils
 */
class CiviMailUtils extends PHPUnit\Framework\TestCase {

  /**
   * @var mixed current outbound email option
   */
  protected $_outBound_option = NULL;

  /**
   * @var bool is this a webtest
   */
  protected $_webtest = FALSE;

  /**
   * Constructor.
   *
   * @param CiviSeleniumTestCase|CiviUnitTestCase $unit_test The currently running test
   * @param bool $startImmediately
   *   Start writing to db now or wait until start() is called.
   */
  public function __construct(&$unit_test, $startImmediately = TRUE) {
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
   * Start writing emails to db instead of current option.
   */
  public function start() {
    if ($this->_webtest) {
      // Change outbound mail setting
      $this->_ut->openCiviPage('admin/setting/smtp', "reset=1", "_qf_Smtp_next");

      // First remember the current setting
      $this->_outBound_option = $this->getSelectedOutboundOption();

      $this->_ut->click('xpath=//input[@name="outBound_option" and @value="' . CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB . '"]');
      $this->_ut->clickLink("_qf_Smtp_next");

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

      Civi::settings()->set('mailing_backend', $mailingBackend);

      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );
    }
  }

  public function stop() {
    if ($this->_webtest) {
      if ($this->_outBound_option != CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB) {
        // Change outbound mail setting
        $this->_ut->openCiviPage('admin/setting/smtp', "reset=1", "_qf_Smtp_next");
        $this->_ut->click('xpath=//input[@name="outBound_option" and @value="' . $this->_outBound_option . '"]');
        // There will be a warning when switching from test to live mode
        if ($this->_outBound_option != CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED) {
          $this->_ut->getAlert();
        }
        $this->_ut->clickLink("_qf_Smtp_next");
      }
    }
    else {

      $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
        'mailing_backend'
      );

      $mailingBackend['outBound_option'] = $this->_outBound_option;

      Civi::settings()->set('mailing_backend', $mailingBackend);
    }
  }

  /**
   * @param string $type
   *
   * @return ezcMail|string
   */
  public function getMostRecentEmail($type = 'raw') {
    $msg = '';

    if ($this->_webtest) {
      // I don't understand but for some reason we have to load the page twice for a recent mailing to appear.
      $this->_ut->openCiviPage('mailing/browse/archived', 'reset=1');
      $this->_ut->openCiviPage('mailing/browse/archived', 'reset=1', 'css=td.crm-mailing-name');
    }
    // We can't fetch mailing headers from webtest so we'll only try if the format is raw
    if ($this->_webtest && $type == 'raw') {
      // This should select the first "Report" link in the table, which is sorted by Completion Date descending, so in theory is the most recent email. Not sure of a more robust way at the moment.
      $this->_ut->clickLink('xpath=//tr[contains(@id, "crm-mailing_")]//a[text()="Report"]');

      // Also not sure how robust this is, but there isn't a good
      // identifier for this link either.
      $this->_ut->waitForElementPresent('xpath=//a[contains(text(), "View complete message")]');
      $this->_ut->clickAjaxLink('xpath=//a[contains(text(), "View complete message")]');
      $msg = $this->_ut->getText('css=.ui-dialog-content.crm-ajax-container');
    }
    else {
      $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id DESC LIMIT 1');
      if ($dao->fetch()) {
        $msg = $dao->headers . "\n\n" . $dao->body;
      }
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
   * @param string $type
   *   'raw'|'ezc'.
   *
   * @throws Exception
   * @return array(ezcMail)|array(string)
   */
  public function getAllMessages($type = 'raw') {
    $msgs = array();

    if ($this->_webtest) {
      throw new Exception("Not implemented: getAllMessages for WebTest");
    }
    else {
      $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id');
      while ($dao->fetch()) {
        $msgs[] = $dao->headers . "\n\n" . $dao->body;
      }
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

  /**
   * @return int
   */
  public function getSelectedOutboundOption() {
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
   * Check contents of mail log.
   *
   * @param array $strings
   *   Strings that should be included.
   * @param array $absentStrings
   *   Strings that should not be included.
   * @param string $prefix
   *
   * @return \ezcMail|string
   */
  public function checkMailLog($strings, $absentStrings = array(), $prefix = '') {
    $mail = $this->getMostRecentEmail('raw');
    return $this->checkMailForStrings($strings, $absentStrings, $prefix, $mail);
  }

  /**
   * Check contents of mail log.
   *
   * @param array $strings
   *   Strings that should be included.
   * @param array $absentStrings
   *   Strings that should not be included.
   * @param string $prefix
   *
   * @return \ezcMail|string
   */
  public function checkAllMailLog($strings, $absentStrings = array(), $prefix = '') {
    $mails = $this->getAllMessages('raw');
    $mail = implode(',', $mails);
    return $this->checkMailForStrings($strings, $absentStrings, $prefix, $mail);
  }

  /**
   * Check that mail log is empty.
   * @param string $prefix
   */
  public function assertMailLogEmpty($prefix = '') {
    $mail = $this->getMostRecentEmail('raw');
    $this->_ut->assertEmpty($mail, 'mail sent when it should not have been ' . $prefix);
  }

  /**
   * Assert that $expectedRecipients (and no else) have received emails
   *
   * @param array $expectedRecipients
   *   Array($msgPos => array($recipPos => $emailAddr)).
   */
  public function assertRecipients($expectedRecipients) {
    $recipients = array();
    foreach ($this->getAllMessages('ezc') as $message) {
      $recipients[] = CRM_Utils_Array::collect('email', $message->to);
    }
    $cmp = function($a, $b) {
      if ($a[0] == $b[0]) {
        return 0;
      }
      return ($a[0] < $b[0]) ? 1 : -1;
    };
    usort($recipients, $cmp);
    usort($expectedRecipients, $cmp);
    $this->_ut->assertEquals(
      $expectedRecipients,
      $recipients,
      "Incorrect recipients: " . print_r(array('expected' => $expectedRecipients, 'actual' => $recipients), TRUE)
    );
  }

  /**
   * Assert that $expectedSubjects (and no other subjects) were sent.
   *
   * @param array $expectedSubjects
   *   Array(string $subj).
   */
  public function assertSubjects($expectedSubjects) {
    $subjects = array();
    foreach ($this->getAllMessages('ezc') as $message) {
      /** @var ezcMail $message */
      $subjects[] = $message->subject;
    }
    sort($subjects);
    sort($expectedSubjects);
    $this->_ut->assertEquals(
      $expectedSubjects,
      $subjects,
      "Incorrect subjects: " . print_r(array('expected' => $expectedSubjects, 'actual' => $subjects), TRUE)
    );
  }

  /**
   * Remove any sent messages from the log.
   *
   * @param int $limit
   *  How many recent messages to remove, defaults to 0 (all).
   *
   * @throws \CRM_Core_Exception
   */
  public function clearMessages($limit = 0) {
    if ($this->_webtest) {
      throw new \CRM_Core_Exception("Not implemented: clearMessages for WebTest");
    }
    else {
      $sql = 'DELETE FROM civicrm_mailing_spool ORDER BY id DESC';
      if ($limit) {
        $sql .= ' LIMIT ' . $limit;
      }
      CRM_Core_DAO::executeQuery($sql);
    }
  }

  /**
   * @param string $msg
   *   Email header and body.
   * @return ezcMail
   */
  private function convertToEzc($msg) {
    $set = new ezcMailVariableSet($msg);
    $parser = new ezcMailParser();
    $mail = $parser->parseMail($set);
    $this->_ut->assertNotEmpty($mail, 'Cannot parse mail');
    return $mail[0];
  }

  /**
   * @param $strings
   * @param $absentStrings
   * @param $prefix
   * @param $mail
   * @return mixed
   */
  public function checkMailForStrings($strings, $absentStrings, $prefix, $mail) {
    foreach ($strings as $string) {
      $this->_ut->assertContains($string, $mail, "$string .  not found in  $mail  $prefix");
    }
    foreach ($absentStrings as $string) {
      $this->_ut->assertEmpty(strstr($mail, $string), "$string  incorrectly found in $mail $prefix");;
    }
    return $mail;
  }

}
