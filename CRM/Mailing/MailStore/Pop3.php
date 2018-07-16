<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Class CRM_Mailing_MailStore_Pop3
 */
class CRM_Mailing_MailStore_Pop3 extends CRM_Mailing_MailStore {

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

    $options = array('ssl' => $ssl);
    $this->_transport = new ezcMailPop3Transport($host, NULL, $options);
    $this->_transport->authenticate($username, $password);

    $this->_ignored = $this->maildir(implode(DIRECTORY_SEPARATOR, array(
          'CiviMail.ignored',
          date('Y'),
          date('m'),
          date('d'),
        )));
    $this->_processed = $this->maildir(implode(DIRECTORY_SEPARATOR, array(
          'CiviMail.processed',
          date('Y'),
          date('m'),
          date('d'),
        )));
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
