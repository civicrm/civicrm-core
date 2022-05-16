<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
   * Current outbound email option
   * @var mixed
   */
  protected $_outBound_option = NULL;

  /**
   * Constructor.
   *
   * @param CiviUnitTestCase $unit_test The currently running test
   * @param bool $startImmediately
   *   Start writing to db now or wait until start() is called.
   */
  public function __construct(&$unit_test, $startImmediately = TRUE) {
    $this->_ut = $unit_test;

    if ($startImmediately) {
      $this->start();
    }
  }

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function __destruct() {
    $this->stop();
    $this->clearMessages();
  }

  /**
   * Start writing emails to db instead of current option.
   */
  public function start() {
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

  public function stop() {
    $mailingBackend = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );

    $mailingBackend['outBound_option'] = $this->_outBound_option;

    Civi::settings()->set('mailing_backend', $mailingBackend);
  }

  /**
   * @param string $type
   *
   * @return ezcMail|string
   */
  public function getMostRecentEmail($type = 'raw') {
    $msg = '';

    $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id DESC LIMIT 1');
    if ($dao->fetch()) {
      $msg = $dao->headers . "\n\n" . $dao->body;
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
   * @throws CRM_Core_Exception
   *
   * @return array(ezcMail)|array(string)
   */
  public function getAllMessages($type = 'raw') {
    $msgs = [];

    $dao = CRM_Core_DAO::executeQuery('SELECT headers, body FROM civicrm_mailing_spool ORDER BY id');
    while ($dao->fetch()) {
      $msgs[] = $dao->headers . "\n\n" . $dao->body;
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
  public function checkMailLog($strings, $absentStrings = [], $prefix = '') {
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
  public function checkAllMailLog($strings, $absentStrings = [], $prefix = '') {
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
    $recipients = [];
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
    $subjects = [];
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
    $sql = 'DELETE FROM civicrm_mailing_spool ORDER BY id DESC';
    if ($limit) {
      $sql .= ' LIMIT ' . $limit;
    }
    CRM_Core_DAO::executeQuery($sql);
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
   * @param array $strings
   * @param $absentStrings
   * @param $prefix
   * @param $mail
   * @return mixed
   */
  public function checkMailForStrings(array $strings, $absentStrings, $prefix, $mail) {
    foreach ($strings as $string) {
      $this->_ut->assertStringContainsString($string, $mail, "$string .  not found in  $mail  $prefix");
    }
    foreach ($absentStrings as $string) {
      $this->_ut->assertEmpty(strstr($mail, $string), "$string  incorrectly found in $mail $prefix");
    }
    return $mail;
  }

}
