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
class CRM_Mailing_MailStore_Mbox extends CRM_Mailing_MailStore {

  /**
   * Connect to and lock the supplied file and make sure the two mail dirs exist
   *
   * @param string $file  mbox to operate upon
   *
   * @return void
   */
  function __construct($file) {
    $this->_transport = new ezcMailMboxTransport($file);
    flock($this->_transport->fh, LOCK_EX);

    $this->_leftToProcess = count($this->_transport->listMessages());

    $this->_ignored = $this->maildir(implode(DIRECTORY_SEPARATOR, array('CiviMail.ignored', date('Y'), date('m'), date('d'))));
    $this->_processed = $this->maildir(implode(DIRECTORY_SEPARATOR, array('CiviMail.processed', date('Y'), date('m'), date('d'))));
  }

  /**
   * Empty the mail source (if it was processed fully) and unlock the file
   *
   * @return void
   */
  function __destruct() {
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
   * Fetch the specified message to the local ignore folder
   *
   * @param integer $nr  number of the message to fetch
   *
   * @return void
   */
  function markIgnored($nr) {
    if ($this->_debug) {
      print "copying message $nr to ignored folder\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_ignored);
    $parser = new ezcMailParser;
    $parser->parseMail($set);
    $this->_leftToProcess--;
  }

  /**
   * Fetch the specified message to the local processed folder
   *
   * @param integer $nr  number of the message to fetch
   *
   * @return void
   */
  function markProcessed($nr) {
    if ($this->_debug) {
      print "copying message $nr to processed folder\n";
    }
    $set = new ezcMailStorageSet($this->_transport->fetchByMessageNr($nr), $this->_processed);
    $parser = new ezcMailParser;
    $parser->parseMail($set);
    $this->_leftToProcess--;
  }
}

