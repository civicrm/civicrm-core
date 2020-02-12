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
 * Class CRM_Mailing_MailStore_Imap
 */
class CRM_Mailing_MailStore_Imap extends CRM_Mailing_MailStore {

  /**
   * Connect to the supplied IMAP server and make sure the two mailboxes exist.
   *
   * @param string $host
   *   Host to connect to.
   * @param string $username
   *   Authentication username.
   * @param string $password
   *   Authentication password.
   * @param bool $ssl
   *   Whether to use IMAP or IMAPS.
   * @param string $folder
   *   Name of the inbox folder.
   *
   * @return \CRM_Mailing_MailStore_Imap
   */
  public function __construct($host, $username, $password, $ssl = TRUE, $folder = 'INBOX') {
    // default to INBOX if an empty string
    if (!$folder) {
      $folder = 'INBOX';
    }

    if ($this->_debug) {

      print "connecting to $host, authenticating as $username and selecting $folder\n";

    }

    $options = ['ssl' => $ssl, 'uidReferencing' => TRUE];
    $this->_transport = new ezcMailImapTransport($host, NULL, $options);
    $this->_transport->authenticate($username, $password);
    $this->_transport->selectMailbox($folder);

    $this->_ignored = implode($this->_transport->getHierarchyDelimiter(), [$folder, 'CiviMail', 'ignored']);
    $this->_processed = implode($this->_transport->getHierarchyDelimiter(), [$folder, 'CiviMail', 'processed']);
    $boxes = $this->_transport->listMailboxes();

    if ($this->_debug) {
      print 'mailboxes found: ' . implode(', ', $boxes) . "\n";
    }

    if (!in_array(strtolower($this->_ignored), array_map('strtolower', $boxes))) {
      $this->_transport->createMailbox($this->_ignored);
    }

    if (!in_array(strtolower($this->_processed), array_map('strtolower', $boxes))) {
      $this->_transport->createMailbox($this->_processed);
    }
  }

  /**
   * Expunge the messages marked for deletion, CRM-7356
   */
  public function expunge() {
    $this->_transport->expunge();
  }

  /**
   * Move the specified message to the ignored folder.
   *
   * @param int $nr
   *   Number of the message to move.
   */
  public function markIgnored($nr) {
    if ($this->_debug) {
      print "setting $nr as seen and moving it to the ignored mailbox\n";
    }
    $this->_transport->setFlag($nr, 'SEEN');
    $this->_transport->copyMessages($nr, $this->_ignored);
    $this->_transport->delete($nr);
  }

  /**
   * Move the specified message to the processed folder.
   *
   * @param int $nr
   *   Number of the message to move.
   */
  public function markProcessed($nr) {
    if ($this->_debug) {
      print "setting $nr as seen and moving it to the processed mailbox\n";
    }
    $this->_transport->setFlag($nr, 'SEEN');
    $this->_transport->copyMessages($nr, $this->_processed);
    $this->_transport->delete($nr);
  }

}
