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
class CRM_Mailing_MailStore {
  /**
   * Flag to decide whether to print debug messages
   *
   * @var bool
   */
  public $_debug = FALSE;

  /**
   * Holds the underlying mailbox transport implementation
   *
   * @var ezcMailImapTransport|ezcMailMboxTransport|ezcMailPop3Transport|null
   */
  protected $_transport;

  /**
   * Return the proper mail store implementation, based on config settings.
   *
   * @param string $name
   *   Name of the settings set from civimail_mail_settings to use (null for default).
   *
   * @throws Exception
   * @return CRM_Mailing_MailStore
   *   mail store implementation for processing CiviMail-bound emails
   */
  public static function getStore($name = NULL) {
    $dao = new CRM_Core_DAO_MailSettings();
    $dao->domain_id = CRM_Core_Config::domainID();
    $name ? $dao->name = $name : $dao->is_default = 1;
    if (!$dao->find(TRUE)) {
      throw new Exception("Could not find entry named $name in civicrm_mail_settings");
    }

    $protocols = CRM_Core_DAO_MailSettings::buildOptions('protocol', 'validate');

    // Prepare normalized/hookable representation of the mail settings.
    $mailSettings = $dao->toArray();
    $mailSettings['protocol'] = $protocols[$mailSettings['protocol']] ?? NULL;
    $protocolDefaults = self::getProtocolDefaults($mailSettings['protocol']);
    $mailSettings = array_merge($protocolDefaults, $mailSettings);

    CRM_Utils_Hook::alterMailStore($mailSettings);

    if (!empty($mailSettings['factory'])) {
      return call_user_func($mailSettings['factory'], $mailSettings);
    }
    else {
      throw new Exception("Unknown protocol {$mailSettings['protocol']}");
    }
  }

  /**
   * @param string $protocol
   *   Ex: 'IMAP', 'Maildir'
   * @return array
   *   List of properties to merge into the $mailSettings.
   *   The most important property is 'factory' with signature:
   *
   *   function($mailSettings): CRM_Mailing_MailStore
   */
  private static function getProtocolDefaults($protocol) {
    switch ($protocol) {
      case 'IMAP':
        return [
          'auth' => 'Password',
          'factory' => function($mailSettings) {
            $useXOAuth2 = ($mailSettings['auth'] === 'XOAuth2');
            return new CRM_Mailing_MailStore_Imap($mailSettings['server'], $mailSettings['username'], $mailSettings['password'], (bool) $mailSettings['is_ssl'], $mailSettings['source'], $useXOAuth2);
          },
        ];

      case 'POP3':
        return [
          'factory' => function ($mailSettings) {
            return new CRM_Mailing_MailStore_Pop3($mailSettings['server'], $mailSettings['username'], $mailSettings['password'], (bool) $mailSettings['is_ssl']);
          },
        ];

      case 'Maildir':
        return [
          'factory' => function ($mailSettings) {
            return new CRM_Mailing_MailStore_Maildir($mailSettings['source']);
          },
        ];

      case 'Localdir':
        return [
          'factory' => function ($mailSettings) {
            return new CRM_Mailing_MailStore_Localdir($mailSettings['source']);
          },
        ];

      // DO NOT USE the mbox transport for anything other than testing
      // in particular, it does not clear the mbox afterwards
      case 'mbox':
        return [
          'factory' => function ($mailSettings) {
            return new CRM_Mailing_MailStore_Mbox($mailSettings['source']);
          },
        ];

      default:
        return [];
    }
  }

  /**
   * Return all emails in the mail store.
   *
   * @return array
   *   array of ezcMail objects
   */
  public function allMails() {
    return $this->fetchNext(0);
  }

  /**
   * Expunge the messages marked for deletion; stub function to be redefined by IMAP store.
   */
  public function expunge() {
  }

  /**
   * Return the next X messages from the mail store.
   *
   * @param int $count
   *   Number of messages to fetch (0 to fetch all).
   *
   * @return array
   *   array of ezcMail objects
   */
  public function fetchNext($count = 1) {
    $offset = 1;
    if (isset($this->_transport->options->uidReferencing) and $this->_transport->options->uidReferencing) {
      $offset = $this->_transport->listUniqueIdentifiers();
      $offset = array_shift($offset);
    }
    try {
      $set = $this->_transport->fetchFromOffset($offset, $count);
      if ($this->_debug) {
        print "fetching $count messages\n";
      }
    }
    catch (ezcMailOffsetOutOfRangeException$e) {
      if ($this->_debug) {
        print "got to the end of the mailbox\n";
      }
      return [];
    }
    $mails = [];
    $parser = new ezcMailParser();
    //set property text attachment as file CRM-5408
    $parser->options->parseTextAttachmentsAsFiles = TRUE;

    foreach ($set->getMessageNumbers() as $nr) {
      if ($this->_debug) {
        print "retrieving message $nr\n";
      }
      $single = $parser->parseMail($this->_transport->fetchByMessageNr($nr));
      $mails[$nr] = $single[0];
    }
    return $mails;
  }

  /**
   * Point to (and create if needed) a local Maildir for storing retrieved mail
   *
   * @param string $name
   *   Name of the Maildir.
   *
   * @throws Exception
   * @return string
   *   path to the Maildir's cur directory
   */
  public function maildir($name) {
    $config = CRM_Core_Config::singleton();
    $dir = $config->customFileUploadDir . $name;
    foreach (['cur', 'new', 'tmp'] as $sub) {
      if (!file_exists($dir . DIRECTORY_SEPARATOR . $sub)) {
        if ($this->_debug) {
          print "creating $dir/$sub\n";
        }
        if (!mkdir($dir . DIRECTORY_SEPARATOR . $sub, 0700, TRUE)) {
          throw new Exception('Could not create ' . $dir . DIRECTORY_SEPARATOR . $sub);
        }
      }
    }
    return $dir . DIRECTORY_SEPARATOR . 'cur';
  }

}
