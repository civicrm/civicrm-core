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
 * Class CRM_Mailing_MailStore_Pop3
 */
class CRM_Mailing_MailStore_Pop3 extends CRM_Mailing_MailStore {

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
   * Connect to the supplied POP3 server and make sure the two mail dirs exist
   *
   * @param string $host
   *   Host to connect to.
   * @param string $username
   *   Authentication username.
   * @param string $password
   *   Authentication password.
   * @param bool $ssl
   *   Whether to use POP3 or POP3S.
   *
   * @return \CRM_Mailing_MailStore_Pop3
   */
  public function __construct($host, $username, $password, $ssl = TRUE) {
    if ($this->_debug) {
      print "connecting to $host and authenticating as $username\n";
    }

    $options = ['ssl' => $ssl];
    $this->_transport = new ezcMailPop3Transport($host, NULL, $options);
    $this->_transport->authenticate($username, $password);

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
   * Fetch the specified message to the local ignore folder.
   *
   * @param int $nr
   *   Number of the message to fetch.
   */
  public function markIgnored($nr) {
    if ($this->_debug) {
      print "fetching message $nr and putting it in the ignored mailbox\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_ignored);
    $parser = new ezcMailParser();
    $parser->parseMail($set);
    $this->_transport->delete($nr);
  }

  /**
   * Fetch the specified message to the local processed folder.
   *
   * @param int $nr
   *   Number of the message to fetch.
   */
  public function markProcessed($nr) {
    if ($this->_debug) {
      print "fetching message $nr and putting it in the processed mailbox\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_processed);
    $parser = new ezcMailParser();
    $parser->parseMail($set);
    $this->_transport->delete($nr);
  }

}
