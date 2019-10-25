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
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
