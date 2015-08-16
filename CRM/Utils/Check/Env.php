<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id: $
 *
 */
class CRM_Utils_Check_Env {

  /**
   * Run some sanity checks.
   *
   * @return array<CRM_Utils_Check_Message>
   */
  public function checkAll() {
    $messages = array_merge(
      $this->checkMysqlTime(),
      $this->checkDebug(),
      $this->checkOutboundMail(),
      $this->checkDomainNameEmail(),
      $this->checkDefaultMailbox(),
      $this->checkLastCron(),
      $this->checkVersion(),
      $this->checkExtensions(),
      $this->checkExtensionUpgrades(),
      $this->checkDbVersion(),
      $this->checkDbEngine()
    );
    return $messages;
  }

  /**
   * Check that the MySQL time settings match the PHP time settings.
   *
   * @return array<CRM_Utils_Check_Message> an empty array, or a list of warnings
   */
  public function checkMysqlTime() {
    $messages = array();

    $phpNow = date('Y-m-d H:i');
    $sqlNow = CRM_Core_DAO::singleValueQuery("SELECT date_format(now(), '%Y-%m-%d %H:%i')");
    if (!CRM_Utils_Time::isEqual($phpNow, $sqlNow, 2.5 * 60)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkMysqlTime',
        ts('Timestamps reported by MySQL (eg "%2") and PHP (eg "%3" ) are mismatched.<br /><a href="%1">Read more about this warning</a>', array(
          1 => CRM_Utils_System::getWikiBaseURL() . 'checkMysqlTime',
          2 => $sqlNow,
          3 => $phpNow,
        )),
        ts('Timestamp Mismatch'),
        \Psr\Log\LogLevel::ERROR
      );
    }

    return $messages;
  }

  /**
   * @return array
   */
  public function checkDebug() {
    $messages = array();

    $config = CRM_Core_Config::singleton();
    if ($config->debug) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkDebug',
        ts('Warning: Debug is enabled in <a href="%1">system settings</a>. This should not be enabled on production servers.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/debug', 'reset=1'))),
        ts('Debug Mode Enabled'),
        \Psr\Log\LogLevel::WARNING
      );
    }

    return $messages;
  }

  /**
   * @return array
   */
  public function checkOutboundMail() {
    $messages = array();

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME, 'mailing_backend');
    if (($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB
      || (defined('CIVICRM_MAIL_LOG') && CIVICRM_MAIL_LOG)
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MOCK)
    ) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkOutboundMail',
        ts('Warning: Outbound email is disabled in <a href="%1">system settings</a>. Proper settings should be enabled on production servers.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1'))),
        ts('Outbound Email Disabled'),
        \Psr\Log\LogLevel::WARNING
      );
    }

    return $messages;
  }

  /**
   * Check that domain email and org name are set
   * @return array
   */
  public function checkDomainNameEmail() {
    $messages = array();

    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $domain = CRM_Core_BAO_Domain::getDomain();
    $domainName = $domain->name;
    $fixEmailUrl = CRM_Utils_System::url("civicrm/admin/domain", "action=update&reset=1");

    if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
      if (!$domainName || $domainName == 'Default Domain Name') {
        $msg = ts("Please enter your organization's <a href=\"%1\">name, primary address, and default FROM Email Address</a> (for system-generated emails).",
          array(1 => $fixEmailUrl));
      }
      else {
        $msg = ts('Please enter a <a href="%1">default FROM Email Address</a> (for system-generated emails).',
          array(1 => $fixEmailUrl));
      }
    }
    elseif (!$domainName || $domainName == 'Default Domain Name') {
      $msg = ts("Please enter your organization's <a href=\"%1\">name and primary address</a>.",
        array(1 => $fixEmailUrl));
    }

    if (!empty($msg)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkDomainNameEmail',
        $msg,
        ts('Complete Setup'),
        \Psr\Log\LogLevel::WARNING
      );
    }

    return $messages;
  }

  /**
   * Checks if a default bounce handling mailbox is set up
   * @return array
   */
  public function checkDefaultMailbox() {
    $messages = array();
    $config = CRM_Core_Config::singleton();

    if (in_array('CiviMail', $config->enableComponents) &&
      CRM_Core_BAO_MailSettings::defaultDomain() == "EXAMPLE.ORG"
    ) {
      $message = new CRM_Utils_Check_Message(
        'checkDefaultMailbox',
        ts('Please configure a <a href="%1">default mailbox</a> for CiviMail.',
          array(1 => CRM_Utils_System::url('civicrm/admin/mailSettings', "reset=1"))),
        ts('Configure Default Mailbox'),
        \Psr\Log\LogLevel::WARNING
      );
      $message->addHelp(ts('Learn more in the <a href="%1">user guide</a>', array(1 => 'http://book.civicrm.org/user/advanced-configuration/email-system-configuration/')));
      $messages[] = $message;
    }

    return $messages;
  }

  /**
   * Checks if cron has run in a reasonable amount of time
   * @return array
   */
  public function checkLastCron() {
    $messages = array();

    $statusPreference = new CRM_Core_DAO_StatusPreference();
    $statusPreference->domain_id = CRM_Core_Config::domainID();
    $statusPreference->name = 'checkLastCron';

    if ($statusPreference->find(TRUE)) {
      $lastCron = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_StatusPreference', $statusPreference->id, 'check_info');
      $msg = ts('Last cron run at %1.', array(1 => CRM_Utils_Date::customFormat(date('c', $lastCron))));
    }
    else {
      $lastCron = 0;
      $msg = ts('No cron runs have been recorded.');
    }

    if ($lastCron > gmdate('U') - 3600) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkLastCron',
        $msg,
        ts('Cron Running OK'),
        \Psr\Log\LogLevel::INFO
      );
    }
    elseif ($lastCron > gmdate('U') - 86400) {
      $message = new CRM_Utils_Check_Message(
        'checkLastCron',
        $msg,
        ts('Cron Not Running'),
        \Psr\Log\LogLevel::WARNING
      );
      $message->addHelp(ts('Learn more in the <a href="%1">Administrator\'s Guide supplement</a>', array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Managing+Scheduled+Jobs')));
      $messages[] = $message;
    }
    else {
      $message = new CRM_Utils_Check_Message(
        'checkLastCron',
        $msg,
        ts('Cron Not Running'),
        \Psr\Log\LogLevel::ERROR
      );
      $message->addHelp(ts('Learn more in the <a href="%1">Administrator\'s Guide supplement</a>', array(1 => 'http://wiki.civicrm.org/confluence/display/CRMDOC/Managing+Scheduled+Jobs')));
      $messages[] = $message;
    }

    return $messages;
  }

  /**
   * Checks if new versions are available
   * @return array
   */
  public function checkVersion() {
    $messages = array();

    if (CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME, 'versionAlert', NULL, 1)) {
      $vc = CRM_Utils_VersionCheck::singleton();
      $newerVersion = $vc->isNewerVersionAvailable();

      if ($newerVersion['version']) {
        $vInfo = array(
          1 => $newerVersion['version'],
          2 => $vc->localVersion,
        );
        if ($newerVersion['status'] == 'lts') {
          $vInfo[1] .= ' ' . ts('(long-term support)');  // LTS = long-term support version
        }

        if ($newerVersion['upgrade'] == 'security') {
          // For most new versions, just make them notice
          $severity = \Psr\Log\LogLevel::CRITICAL;
          $message = ts('New security release %1 is available. The site is currently running %2.', $vInfo);
        }
        elseif ($newerVersion['status'] == 'eol') {
          // Warn about EOL
          $severity = \Psr\Log\LogLevel::WARNING;
          $message = ts('New version %1 is available. The site is currently running %2, which has reached its end of life.', $vInfo);
        }
        else {
          // For most new versions, just make them notice
          $severity = \Psr\Log\LogLevel::NOTICE;
          $message = ts('New version %1 is available. The site is currently running %2.', $vInfo);
        }
      }
      else {
        $vNum = $vc->localVersion;
        if ($newerVersion['status'] == 'lts') {
          $vNum .= ' ' . ts('(long-term support)');  // LTS = long-term support version
        }

        $severity = \Psr\Log\LogLevel::INFO;
        $message = ts('Version %1 is up-to-date.', array(1 => $vNum));
      }

      $messages[] = new CRM_Utils_Check_Message(
        'checkVersion',
        $message,
        ts('Update Status'),
        $severity
      );
    }
    else {
      $messages[] = new CRM_Utils_Check_Message(
        'checkVersion',
        ts('The check for new versions of CiviCRM has been disabled.'),
        ts('Update Check Disabled'),
        \Psr\Log\LogLevel::NOTICE
      );
    }

    return $messages;
  }

  /**
   * Checks if extensions are set up properly
   * @return array
   */
  public function checkExtensions() {
    $messages = array();
    $extensionSystem = CRM_Extension_System::singleton();
    $mapper = $extensionSystem->getMapper();
    $manager = $extensionSystem->getManager();
    $remotes = $extensionSystem->getBrowser()->getExtensions();

    if ($extensionSystem->getDefaultContainer()) {
      $basedir = $extensionSystem->getDefaultContainer()->baseDir;
    }

    if (empty($basedir)) {
      // no extension directory
      $messages[] = new CRM_Utils_Check_Message(
        'checkExtensions',
        ts('Your extensions directory is not set.  Click <a href="%1">here</a> to set the extensions directory.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/path', 'reset=1'))),
        ts('Extensions directory not writable'),
        \Psr\Log\LogLevel::NOTICE
      );
      return $messages;
    }

    if (!is_dir($basedir)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkExtensions',
        ts('Your extensions directory path points to %1, which is not a directory.  Please check your file system.',
          array(1 => $basedir)),
        ts('Extensions directory incorrect'),
        \Psr\Log\LogLevel::ERROR
      );
      return $messages;
    }
    elseif (!is_writable($basedir)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkExtensions',
        ts('Your extensions directory, %1, is not writable.  Please change your file permissions.',
          array(1 => $basedir)),
        ts('Extensions directory not writable'),
        \Psr\Log\LogLevel::ERROR
      );
      return $messages;
    }

    if (empty($extensionSystem->getDefaultContainer()->baseUrl)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkExtensions',
        ts('The extensions URL is not properly set. Please go to the <a href="%1">URL setting page</a> and correct it.',
          array(1 => CRM_Utils_System::url('civicrm/admin/setting/url', 'reset=1'))),
        ts('Extensions directory not writable'),
        \Psr\Log\LogLevel::ERROR
      );
      return $messages;
    }

    $keys = array_keys($manager->getStatuses());
    sort($keys);
    $severity = 1;
    $msgArray = $okextensions = array();
    foreach ($keys as $key) {
      try {
        $obj = $mapper->keyToInfo($key);
      }
      catch (CRM_Extension_Exception $ex) {
        $severity = 4;
        $msgArray[] = ts('Failed to read extension (%1). Please refresh the extension list.', array(1 => $key));
        continue;
      }
      $row = CRM_Admin_Page_Extensions::createExtendedInfo($obj);
      switch ($row['status']) {
        case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
          $severity = 4;
          $msgArray[] = ts('%1 extension (%2) is installed but missing files.', array(1 => CRM_Utils_Array::value('label', $row), 2 => $key));
          break;

        case CRM_Extension_Manager::STATUS_INSTALLED:
          if (CRM_Utils_Array::value($key, $remotes)) {
            if (version_compare($row['version'], $remotes[$key]->version, '<')) {
              $severity = ($severity < 3) ? 3 : $severity;
              $msgArray[] = ts('%1 extension (%2) is upgradeable to version %3.', array(1 => CRM_Utils_Array::value('label', $row), 2 => $key, 3 => $remotes[$key]->version));
            }
            else {
              $okextensions[] = CRM_Utils_Array::value('label', $row) ? "{$row['label']} ($key)" : $key;
            }
          }
          else {
            $okextensions[] = CRM_Utils_Array::value('label', $row) ? "{$row['label']} ($key)" : $key;
          }
          break;

        case CRM_Extension_Manager::STATUS_UNINSTALLED:
        case CRM_Extension_Manager::STATUS_DISABLED:
        case CRM_Extension_Manager::STATUS_DISABLED_MISSING:
        default:
      }
    }
    $msg = implode('  ', $msgArray);
    if (empty($msgArray)) {
      $msg = (empty($okextensions)) ? ts('No extensions installed.') : ts('Extensions are up-to-date:') . ' ' . implode(', ', $okextensions);
    }
    elseif (!empty($okextensions)) {
      $msg .= '  ' . ts('Other extensions are up-to-date:') . ' ' . implode(', ', $okextensions);
    }

    // OK, return several data rows
    // $returnValues = array(
    //   array('status' => $return, 'message' => $msg),
    // );

    $messages[] = new CRM_Utils_Check_Message(
      'checkExtensions',
      $msg,
      ts('Extension Updates'),
      CRM_Utils_Check::severityMap($severity, TRUE)
    );

    return $messages;
  }


  /**
   * Checks if extensions are set up properly
   * @return array
   */
  public function checkExtensionUpgrades() {
    $messages = array();

    if (CRM_Extension_Upgrades::hasPending()) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkExtensionUpgrades',
        ts('Extension upgrades are pending.  Please visit <a href="%1">the upgrade page</a> to run them.',
          array(1 => CRM_Utils_System::url('civicrm/admin/extensions/upgrade', 'reset=1'))),
        ts('Run Extension Upgrades'),
        \Psr\Log\LogLevel::ERROR
      );
    }
    return $messages;
  }

  /**
   * Checks if CiviCRM database version is up-to-date
   * @return array
   */
  public function checkDbVersion() {
    $messages = array();
    $dbVersion = CRM_Core_BAO_Domain::version();
    $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");

    if (!$dbVersion) {
      // if db.ver missing
      $messages[] = new CRM_Utils_Check_Message(
        'checkDbVersion',
        ts('Version information found to be missing in database. You will need to determine the correct version corresponding to your current database state.'),
        ts('Database Version Missing'),
        \Psr\Log\LogLevel::ERROR
      );
    }
    elseif (!CRM_Utils_System::isVersionFormatValid($dbVersion)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkDbVersion',
        ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.'),
        ts('Database Version Invalid'),
        \Psr\Log\LogLevel::ERROR
      );
    }
    elseif (stripos($dbVersion, 'upgrade')) {
      // if db.ver indicates a partially upgraded db
      $messages[] = new CRM_Utils_Check_Message(
        'checkDbVersion',
        ts('Database check failed - the database looks to have been partially upgraded. You must reload the database with the backup and try the <a href=\'%1\'>upgrade process</a> again.', array(1 => $upgradeUrl)),
        ts('Database Partially Upgraded'),
        \Psr\Log\LogLevel::ALERT
      );
    }
    else {
      $codeVersion = CRM_Utils_System::version();

      // if db.ver < code.ver, time to upgrade
      if (version_compare($dbVersion, $codeVersion) < 0) {
        $messages[] = new CRM_Utils_Check_Message(
          'checkDbVersion',
          ts('New codebase version detected. You must visit <a href=\'%1\'>upgrade screen</a> to upgrade the database.', array(1 => $upgradeUrl)),
          ts('Database Upgrade Required'),
          \Psr\Log\LogLevel::ALERT
        );
      }

      // if db.ver > code.ver, sth really wrong
      if (version_compare($dbVersion, $codeVersion) > 0) {
        $messages[] = new CRM_Utils_Check_Message(
          'checkDbVersion',
          ts('Your database is marked with an unexpected version number: %1. The v%2 codebase may not be compatible with your database state.
            You will need to determine the correct version corresponding to your current database state. You may want to revert to the codebase
            you were using until you resolve this problem.<br/>OR if this is a manual install from git, you might want to fix civicrm-version.php file.',
              array(1 => $dbVersion, 2 => $codeVersion)
            ),
          ts('Database In Unexpected Version'),
          \Psr\Log\LogLevel::ERROR
        );
      }
    }

    return $messages;
  }

  /**
   * ensure that all CiviCRM tables are InnoDB
   * @return array
   */
  public function checkDbEngine() {
    $messages = array();

    if (CRM_Core_DAO::isDBMyISAM(150)) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkDbEngine',
        ts('Your database is configured to use the MyISAM database engine. CiviCRM requires InnoDB. You will need to convert any MyISAM tables in your database to InnoDB. Using MyISAM tables will result in data integrity issues.'),
        ts('MyISAM Database Engine'),
        \Psr\Log\LogLevel::ERROR
      );
    }
    return $messages;
  }

}
