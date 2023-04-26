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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Class CRM_Mailing_MailStore_Mbox
 */
class CRM_Mailing_MailStore_Mbox extends CRM_Mailing_MailStore {

  /**
   * Path to a local directory to store ignored emails
   *
   * @var string
   */
  private $_ignored;

  /**
   * Path to a local directory to store ignored emails
   *
   * @var string
   */
  private $_processed;

  /**
   * Count of messages left to process
   *
   * @var int
   */
  private $_leftToProcess;

  /**
   * Connect to and lock the supplied file and make sure the two mail dirs exist.
   *
   * @param string $file
   *   Mbox to operate upon.
   *
   * @return \CRM_Mailing_MailStore_Mbox
   */
  public function __construct($file) {
    $this->_transport = new ezcMailMboxTransport($file);
    flock($this->_transport->fh, LOCK_EX);

    $this->_leftToProcess = count($this->_transport->listMessages());

    $this->_ignored = $this->maildir(implode(DIRECTORY_SEPARATOR, [
      'CiviMail.ignored',
      date('Y'),
      date('m'),
      date('d'),
    ]));
    $this->_processed = $this->maildir(implode(DIRECTORY_SEPARATOR, [
      'CiviMail.processed',
      date('Y'),
      date('m'),
      date('d'),
    ]));
  }

  /**
   * Empty the mail source (if it was processed fully) and unlock the file.
   */
  public function __destruct() {
    if ($this->_leftToProcess === 0) {
      // FIXME: the ftruncate() call does not work for some reason
      if ($this->_debug) {
        print "trying to delete the mailbox\n";
      }
      ftruncate($this->_transport->fh, 0);
    }
    flock($this->_transport->fh, LOCK_UN);
  }

  /**
   * Fetch the specified message to the local ignore folder.
   *
   * @param int $nr
   *   Number of the message to fetch.
   */
  public function markIgnored($nr) {
    if ($this->_debug) {
      print "copying message $nr to ignored folder\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_ignored);
    $parser = new ezcMailParser();
    $parser->parseMail($set);
    $this->_leftToProcess--;
  }

  /**
   * Fetch the specified message to the local processed folder.
   *
   * @param int $nr
   *   Number of the message to fetch.
   */
  public function markProcessed($nr) {
    if ($this->_debug) {
      print "copying message $nr to processed folder\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_processed);
    $parser = new ezcMailParser();
    $parser->parseMail($set);
    $this->_leftToProcess--;
  }

}
