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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'ezc/Base/src/ezc_bootstrap.php';
require_once 'ezc/autoload/mail_autoload.php';
class CRM_Mailing_MailStore_Imap extends CRM_Mailing_MailStore {

  /**
   * Connect to the supplied IMAP server and make sure the two mailboxes exist
   *
   * @param string $host      host to connect to
   * @param string $username  authentication username
   * @param string $password  authentication password
   * @param bool   $ssl       whether to use IMAP or IMAPS
   * @param string $folder    name of the inbox folder
   *
   * @return void
   */
  function __construct($host, $username, $password, $ssl = TRUE, $folder = 'INBOX') {
    // default to INBOX if an empty string
    if (!$folder) {
      $folder = 'INBOX';
    }

    if ($this->_debug) {

      print "connecting to $host, authenticating as $username and selecting $folder\n";

    }

    $options = array('ssl' => $ssl, 'uidReferencing' => TRUE);
    $this->_transport = new ezcMailImapTransport($host, NULL, $options);
    $this->_transport->authenticate($username, $password);
    $this->_transport->selectMailbox($folder);

    $this->_ignored   = implode($this->_transport->getHierarchyDelimiter(), array($folder, 'CiviMail', 'ignored'));
    $this->_processed = implode($this->_transport->getHierarchyDelimiter(), array($folder, 'CiviMail', 'processed'));
    $boxes            = $this->_transport->listMailboxes();

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
  function expunge() {
    $this->_transport->expunge();
  }

  /**
   * Move the specified message to the ignored folder
   *
   * @param integer $nr  number of the message to move
   *
   * @return void
   */
  function markIgnored($nr) {
    if ($this->_debug) {
      print "setting $nr as seen and moving it to the ignored mailbox\n";
    }
    $this->_transport->setFlag($nr, 'SEEN');
    $this->_transport->copyMessages($nr, $this->_ignored);
    $this->_transport->delete($nr);
  }

  /**
   * Move the specified message to the processed folder
   *
   * @param integer $nr  number of the message to move
   *
   * @return void
   */
  function markProcessed($nr) {
    if ($this->_debug) {
      print "setting $nr as seen and moving it to the processed mailbox\n";
    }
    $this->_transport->setFlag($nr, 'SEEN');
    $this->_transport->copyMessages($nr, $this->_processed);
    $this->_transport->delete($nr);
  }
}

