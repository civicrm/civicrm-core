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
 * Class CRM_Mailing_MailStore_Maildir
 */
class CRM_Mailing_MailStore_Maildir extends CRM_Mailing_MailStore {

  /**
   * Connect to the supplied dir and make sure the two mail dirs exist.
   *
   * @param string $dir
   *   Dir to operate upon.
   *
   * @return \CRM_Mailing_MailStore_Maildir
   */
  public function __construct($dir) {
    $this->_dir = $dir;

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
   * Return the next X messages from the mail store.
   * FIXME: in CiviCRM 2.2 this always returns all the emails
   *
   * @param int $count
   *   Number of messages to fetch FIXME: ignored in CiviCRM 2.2 (assumed to be 0, i.e., fetch all).
   *
   * @return array
   *   array of ezcMail objects
   */
  public function fetchNext($count = 0) {
    $mails = array();
    $parser = new ezcMailParser();
    // set property text attachment as file CRM-5408
    $parser->options->parseTextAttachmentsAsFiles = TRUE;

    foreach (array(
               'cur',
               'new',
             ) as $subdir) {
      $dir = $this->_dir . DIRECTORY_SEPARATOR . $subdir;
      foreach (scandir($dir) as $file) {
        if ($file == '.' or $file == '..') {
          continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $file;

        if ($this->_debug) {

          print "retrieving message $path\n";

        }

        $set = new ezcMailFileSet(array($path));
        $single = $parser->parseMail($set);
        $mails[$path] = $single[0];
      }
    }
    return $mails;
  }

  /**
   * Fetch the specified message to the local ignore folder.
   *
   * @param int $file
   *   File location of the message to fetch.
   *
   * @throws Exception
   */
  public function markIgnored($file) {
    if ($this->_debug) {
      print "moving $file to ignored folder\n";
    }
    $target = $this->_ignored . DIRECTORY_SEPARATOR . basename($file);
    if (!rename($file, $target)) {
      throw new Exception("Could not rename $file to $target");
    }
  }

  /**
   * Fetch the specified message to the local processed folder.
   *
   * @param int $file
   *   File location of the message to fetch.
   *
   * @throws Exception
   */
  public function markProcessed($file) {
    if ($this->_debug) {
      print "moving $file to processed folder\n";
    }
    $target = $this->_processed . DIRECTORY_SEPARATOR . basename($file);
    if (!rename($file, $target)) {
      throw new Exception("Could not rename $file to $target");
    }
  }

}
