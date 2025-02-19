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

use Civi\Api4\Extension;
use Psr\Log\LogLevel;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_Env extends CRM_Utils_Check_Component {

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkPhpVersion() {
    $messages = [];
    $phpVersion = phpversion();

    if (version_compare($phpVersion, CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER) >= 0) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('This system uses PHP version %1 which meets or exceeds the recommendation of %2.',
          [
            1 => $phpVersion,
            2 => preg_replace(';^(\d+\.\d+(?:\.[1-9]\d*)?).*$;', '\1', CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER),
          ]),
          ts('PHP Up-to-Date'),
          \Psr\Log\LogLevel::INFO,
          'fa-server'
      );
    }
    elseif (version_compare($phpVersion, CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_PHP_VER) >= 0) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('This system uses PHP version %1. This meets the minimum recommendations and you do not need to upgrade immediately, but the preferred version is %2.',
          [
            1 => $phpVersion,
            2 => CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER,
          ]),
          ts('PHP Out-of-Date'),
          \Psr\Log\LogLevel::NOTICE,
          'fa-server'
      );
    }
    elseif (version_compare($phpVersion, CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER) >= 0) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('This system uses PHP version %1. This meets the minimum requirements for CiviCRM to function but is not recommended. At least PHP version %2 is recommended; the preferred version is %3.',
          [
            1 => $phpVersion,
            2 => CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_PHP_VER,
            3 => preg_replace(';^(\d+\.\d+(?:\.[1-9]\d*)?).*$;', '\1', CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER),
          ]),
          ts('PHP Out-of-Date'),
          \Psr\Log\LogLevel::WARNING,
          'fa-server'
      );
    }
    else {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('This system uses PHP version %1. To ensure the continued operation of CiviCRM, upgrade your server now. At least PHP version %2 is recommended; the preferred version is %3.',
          [
            1 => $phpVersion,
            2 => CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_PHP_VER,
            3 => preg_replace(';^(\d+\.\d+(?:\.[1-9]\d*)?).*$;', '\1', CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER),
          ]),
          ts('PHP Out-of-Date'),
          \Psr\Log\LogLevel::ERROR,
          'fa-server'
      );
    }

    return $messages;
  }

  /**
   * Check that the MySQL time settings match the PHP time settings.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkMysqlTime() {
    $messages = [];

    $phpNow = date('Y-m-d H:i');
    $sqlNow = CRM_Core_DAO::singleValueQuery("SELECT date_format(now(), '%Y-%m-%d %H:%i')");
    if (!CRM_Utils_Time::isEqual($phpNow, $sqlNow, 2.5 * 60)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Timestamps reported by MySQL (eg "%1") and PHP (eg "%2" ) are mismatched.', [
          1 => $sqlNow,
          2 => $phpNow,
        ]) . '<br />' . CRM_Utils_System::docURL2('sysadmin/requirements/#mysql-time'),
        ts('Timestamp Mismatch'),
        \Psr\Log\LogLevel::ERROR,
        'fa-server'
      );
    }

    return $messages;
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDebug() {
    $config = CRM_Core_Config::singleton();
    if ($config->debug) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Warning: Debug is enabled in <a href="%1">system settings</a>. This should not be enabled on production servers.',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/debug', 'reset=1')]),
        ts('Debug Mode Enabled'),
        CRM_Core_Config::environment() == 'Production' ? \Psr\Log\LogLevel::WARNING : \Psr\Log\LogLevel::INFO,
        'fa-bug'
      );
      $message->addAction(
        ts('Disable Debug Mode'),
        ts('Disable debug mode now?'),
        'api3',
        ['Setting', 'create', ['debug_enabled' => 0]]
      );
      return [$message];
    }

    return [];
  }

  /**
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   */
  public function checkOutboundMail($force = FALSE) {
    $messages = [];

    // CiviMail doesn't work in non-production environments; skip.
    if (!$force && CRM_Core_Config::environment() != 'Production') {
      return $messages;
    }

    $mailingInfo = Civi::settings()->get('mailing_backend');
    if (($mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_REDIRECT_TO_DB
      || (defined('CIVICRM_MAIL_LOG') && CIVICRM_MAIL_LOG)
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_DISABLED
      || $mailingInfo['outBound_option'] == CRM_Mailing_Config::OUTBOUND_OPTION_MOCK)
    ) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Warning: Outbound email is disabled in <a href="%1">system settings</a>. Proper settings should be enabled on production servers.',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/smtp', 'reset=1')]),
        ts('Outbound Email Disabled'),
        \Psr\Log\LogLevel::WARNING,
        'fa-envelope'
      );
    }

    return $messages;
  }

  /**
   * Check that domain email and org name are set
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDomainNameEmail($force = FALSE) {
    $messages = [];

    // CiviMail doesn't work in non-production environments; skip.
    if (!$force && CRM_Core_Config::environment() != 'Production') {
      return $messages;
    }

    [$domainEmailName, $domainEmailAddress] = CRM_Core_BAO_Domain::getNameAndEmail(TRUE);
    $domain        = CRM_Core_BAO_Domain::getDomain();
    $domainName    = $domain->name;
    $fixEmailUrl   = CRM_Utils_System::url("civicrm/admin/options/site_email_address");
    $fixDomainName = CRM_Utils_System::url("civicrm/admin/domain", "action=update&reset=1");

    if (!$domainEmailAddress || $domainEmailAddress == 'info@EXAMPLE.ORG') {
      if (!$domainName || $domainName == 'Default Domain Name') {
        $msg = ts("Please enter your organization's <a href=\"%1\">name, primary address </a> and <a href=\"%2\">default Site Email Address </a> (for system-generated emails).",
          [
            1 => $fixDomainName,
            2 => $fixEmailUrl,
          ]
        );
      }
      else {
        $msg = ts('Please enter a <a href="%1">default Site Email Address</a> (for system-generated emails).',
          [1 => $fixEmailUrl]);
      }
    }
    elseif (!$domainName || $domainName == 'Default Domain Name') {
      $msg = ts("Please enter your organization's <a href=\"%1\">name and primary address</a>.",
        [1 => $fixDomainName]);
    }

    if (!empty($msg)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $msg,
        ts('Organization Setup'),
        \Psr\Log\LogLevel::WARNING,
        'fa-check-square-o'
      );
    }

    return $messages;
  }

  /**
   * Checks if a default bounce handling mailbox is set up
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDefaultMailbox($force = FALSE): array {
    $messages = [];

    // CiviMail doesn't work in non-production environments; skip.
    if (!$force && CRM_Core_Config::environment() !== 'Production') {
      return $messages;
    }

    if (CRM_Core_Component::isEnabled('CiviMail') &&
      CRM_Core_BAO_MailSettings::defaultDomain() === "EXAMPLE.ORG"
    ) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Please configure a <a href="%1">default mailbox</a> for CiviMail.',
          [1 => CRM_Utils_System::url('civicrm/admin/mailSettings', "reset=1")]),
        ts('Configure Default Mailbox'),
        \Psr\Log\LogLevel::WARNING,
        'fa-envelope'
      );
      $message->addHelp(
        ts('A default mailbox must be configured for email bounce processing.') . '<br />' .
          CRM_Utils_System::docURL2('user/advanced-configuration/email-system-configuration/')
      );
      $messages[] = $message;
    }

    return $messages;
  }

  /**
   * Checks if cron has run in the past hour (3600 seconds)
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   * @throws CRM_Core_Exception
   */
  public function checkLastCron($force = FALSE) {
    // TODO: Remove this check when MINIMUM_UPGRADABLE_VERSION goes to 4.7.
    if (CRM_Utils_System::version() !== CRM_Core_BAO_Domain::version()) {
      return [];
    }

    $messages = [];

    // Cron doesn't work in non-production environments; skip.
    if (!$force && CRM_Core_Config::environment() != 'Production') {
      return $messages;
    }

    $statusPreference = new CRM_Core_DAO_StatusPreference();
    $statusPreference->domain_id = CRM_Core_Config::domainID();
    $statusPreference->name = __FUNCTION__;

    $level = \Psr\Log\LogLevel::INFO;
    $now = gmdate('U');

    // Get timestamp of last cron run
    if ($statusPreference->find(TRUE) && !empty($statusPreference->check_info)) {
      $msg = ts('Last cron run at %1.', [1 => CRM_Utils_Date::customFormat(date('c', $statusPreference->check_info))]);
    }
    // If cron record doesn't exist, this is a new install. Make a placeholder record (prefs='new').
    else {
      $statusPreference = CRM_Core_BAO_StatusPreference::create([
        'name' => __FUNCTION__,
        'check_info' => $now,
        'prefs' => 'new',
      ]);
    }
    $lastCron = $statusPreference->check_info;

    if ($statusPreference->prefs !== 'new' && $lastCron > $now - 3600) {
      $title = ts('Cron Running OK');
    }
    else {
      // If placeholder record found, give one day "grace period" for admin to set-up cron
      if ($statusPreference->prefs === 'new') {
        $title = ts('Set-up Cron');
        $msg = ts('No cron runs have been recorded.');
        // After 1 day (86400 seconds) increase the error level
        $level = ($lastCron > $now - 86400) ? \Psr\Log\LogLevel::NOTICE : \Psr\Log\LogLevel::WARNING;
      }
      else {
        $title = ts('Cron Not Running');
        // After 1 day (86400 seconds) increase the error level
        $level = ($lastCron > $now - 86400) ? \Psr\Log\LogLevel::WARNING : \Psr\Log\LogLevel::ERROR;
      }
      $msg .= '<p>' . ts('A cron job is required to execute scheduled jobs automatically.') .
       '<br />' . CRM_Utils_System::docURL2('sysadmin/setup/jobs/') . '</p>';
    }

    $messages[] = new CRM_Utils_Check_Message(
      __FUNCTION__,
      $msg,
      $title,
      $level,
      'fa-clock-o'
    );
    return $messages;
  }

  /**
   * Recommend that sites use path-variables for their directories and URLs.
   * @return CRM_Utils_Check_Message[]
   */
  public function checkUrlVariables() {
    $messages = [];
    $hasOldStyle = FALSE;
    $settingNames = [
      'userFrameworkResourceURL',
      'imageUploadURL',
      'customCSSURL',
      'extensionsURL',
    ];

    foreach ($settingNames as $settingName) {
      $settingValue = Civi::settings()->get($settingName);
      if (!empty($settingValue) && $settingValue[0] != '[') {
        $hasOldStyle = TRUE;
        break;
      }
    }

    if ($hasOldStyle) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('<a href="%1">Resource URLs</a> may use absolute paths, relative paths, or variables. Absolute paths are more difficult to maintain. To maximize portability, consider using a variable in each URL (eg "<tt>[cms.root]</tt>" or "<tt>[civicrm.files]</tt>").',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/url', "reset=1")]),
        ts('Resource URLs: Make them portable'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-server'
      );
      $messages[] = $message;
    }

    return $messages;
  }

  /**
   * Recommend that sites use path-variables for their directories and URLs.
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDirVariables() {
    $messages = [];
    $hasOldStyle = FALSE;
    $settingNames = [
      'uploadDir',
      'imageUploadDir',
      'customFileUploadDir',
      'customTemplateDir',
      'customPHPPathDir',
      'extensionsDir',
    ];

    foreach ($settingNames as $settingName) {
      $settingValue = Civi::settings()->get($settingName);
      if (!empty($settingValue) && $settingValue[0] != '[') {
        $hasOldStyle = TRUE;
        break;
      }
    }

    if ($hasOldStyle) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('<a href="%1">Directories</a> may use absolute paths, relative paths, or variables. Absolute paths are more difficult to maintain. To maximize portability, consider using a variable in each directory (eg "<tt>[cms.root]</tt>" or "<tt>[civicrm.files]</tt>").',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/path', "reset=1")]),
        ts('Directory Paths: Make them portable'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-server'
      );
      $messages[] = $message;
    }

    return $messages;
  }

  /**
   * Check that important directories are writable.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDirsWritable() {
    $notWritable = [];

    $config = CRM_Core_Config::singleton();
    $directories = [
      'uploadDir' => ts('Temporary Files Directory'),
      'imageUploadDir' => ts('Images Directory'),
      'customFileUploadDir' => ts('Custom Files Directory'),
    ];

    foreach ($directories as $directory => $label) {
      $file = CRM_Utils_File::createFakeFile($config->$directory);

      if ($file === FALSE) {
        $notWritable[] = "$label ({$config->$directory})";
      }
      else {
        $dirWithSlash = CRM_Utils_File::addTrailingSlash($config->$directory);
        unlink($dirWithSlash . $file);
      }
    }

    $messages = [];

    if (!empty($notWritable)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The %1 is not writable.  Please check your file permissions.', [
          1 => implode(', ', $notWritable),
          'count' => count($notWritable),
          'plural' => 'The following directories are not writable: %1.  Please check your file permissions.',
        ]),
        ts('Directory not writable', [
          'count' => count($notWritable),
          'plural' => 'Directories not writable',
        ]),
        \Psr\Log\LogLevel::ERROR,
        'fa-ban'
      );
    }

    return $messages;
  }

  /**
   * Checks if new versions are available
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   * @throws CRM_Core_Exception
   */
  public function checkVersion($force = FALSE) {
    $messages = [];
    try {
      $vc = new CRM_Utils_VersionCheck();
      $vc->initialize($force);
    }
    catch (Exception $e) {
      $messages[] = new CRM_Utils_Check_Message(
        'checkVersionError',
        ts('Directory %1 is not writable.  Please change your file permissions.',
          [1 => dirname($vc->cacheFile)]),
        ts('Directory not writable'),
        \Psr\Log\LogLevel::ERROR,
        'fa-times-circle-o'
      );
      return $messages;
    }

    // Show a notice if the version_check job is disabled
    if (!$force && empty($vc->cronJob['is_active'])) {
      $args = empty($vc->cronJob['id']) ? ['reset' => 1] : ['reset' => 1, 'action' => 'update', 'id' => $vc->cronJob['id']];
      $messages[] = new CRM_Utils_Check_Message(
        'checkVersionDisabled',
        ts('The check for new versions of CiviCRM has been disabled. <a %1>Re-enable the scheduled job</a> to receive important security update notifications.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/job', $args) . '"']),
        ts('Update Check Disabled'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-times-circle-o'
      );
    }

    if ($vc->isInfoAvailable) {
      foreach ($vc->getVersionMessages() ?? [] as $msg) {
        $messages[] = new CRM_Utils_Check_Message(__FUNCTION__ . '_' . $msg['name'],
          $msg['message'], $msg['title'], $msg['severity'], 'fa-cloud-upload');
      }
    }

    return $messages;
  }

  /**
   * Checks if extensions are set up properly
   * @return CRM_Utils_Check_Message[]
   */
  public function checkExtensions() {
    $messages = [];
    $extensionSystem = CRM_Extension_System::singleton();
    $mapper = $extensionSystem->getMapper();
    $manager = $extensionSystem->getManager();

    if ($extensionSystem->getDefaultContainer()) {
      $basedir = $extensionSystem->getDefaultContainer()->baseDir;
    }

    if (empty($basedir)) {
      // no extension directory
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Your extensions directory is not set.  Click <a href="%1">here</a> to set the extensions directory.',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/path', 'reset=1')]),
        ts('Directory not writable'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-plug'
      );
      return $messages;
    }

    if (!is_dir($basedir)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Your extensions directory path points to %1, which is not a directory.  Please check your file system.',
          [1 => $basedir]),
        ts('Extensions directory incorrect'),
        \Psr\Log\LogLevel::ERROR,
        'fa-plug'
      );
      return $messages;
    }
    elseif (!is_writable($basedir)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Writable',
        ts('Your extensions directory (%1) is read-only. If you would like to perform downloads or upgrades, then change the file permissions.',
          [1 => $basedir]),
        ts('Read-Only Extensions'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-plug'
      );
    }

    if (empty($extensionSystem->getDefaultContainer()->baseUrl)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'URL',
        ts('The extensions URL is not properly set. Please go to the <a href="%1">URL setting page</a> and correct it.',
          [1 => CRM_Utils_System::url('civicrm/admin/setting/url', 'reset=1')]),
        ts('Extensions url missing'),
        \Psr\Log\LogLevel::ERROR,
        'fa-plug'
      );
    }

    if (!$extensionSystem->getBrowser()->isEnabled()) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Not checking remote URL for extensions since ext_repo_url is set to false.'),
        ts('Extensions check disabled'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-plug'
      );
      return $messages;
    }

    try {
      $remotes = $extensionSystem->getBrowser()->getExtensions();
    }
    catch (CRM_Extension_Exception | \GuzzleHttp\Exception\GuzzleException $e) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $e->getMessage(),
        ts('Unable to get updates to extensions'),
        \Psr\Log\LogLevel::WARNING,
        'fa-plug'
      );
      return $messages;
    }

    $stauses = $manager->getStatuses();
    $keys = array_keys($stauses);
    $enabled = array_keys(array_filter($stauses, function($status) {
      return $status === CRM_Extension_Manager::STATUS_INSTALLED;
    }));
    // Extensions belonging to enabled components are required
    $enabledComponents = array_map(['CRM_Utils_String', 'convertStringToSnakeCase'], Civi::settings()->get('enable_components'));
    // And extensions tagged `mgmg:required` must be enabled
    $requiredExtensions = array_merge($enabledComponents, $mapper->getKeysByTag('mgmt:required'));
    sort($keys);
    $updates = $errors = $okextensions = $missingRequired = [];

    $extPrettyLabel = function($key) use ($mapper) {
      // We definitely know a $key, but we may not have a $label.
      // Which is too bad - because it would be nicer if $label could be the reliable start of the string.
      $keyFmt = '<code>' . htmlentities($key) . '</code>';
      try {
        $info = $mapper->keyToInfo($key);
        if ($info->label) {
          return sprintf('"<em>%s</em>" (%s)', htmlentities($info->label), $keyFmt);
        }
      }
      catch (CRM_Extension_Exception $ex) {
        return "($keyFmt)";
      }
    };

    foreach ($keys as $key) {
      try {
        $obj = $mapper->keyToInfo($key);
      }
      catch (CRM_Extension_Exception $ex) {
        $errors[] = ts('Failed to read extension (%1). Please refresh the extension list.', [1 => $key]);
        continue;
      }
      $row = CRM_Admin_Page_Extensions::createExtendedInfo($obj);
      switch ($row['status']) {
        case CRM_Extension_Manager::STATUS_INSTALLED_MISSING:
          $errors[] = ts('%1 is installed but missing files.', [1 => $extPrettyLabel($key)]);
          break;

        case CRM_Extension_Manager::STATUS_INSTALLED:
          $missingDependencies = array_diff($row['requires'], $enabled);
          if (!empty($row['requires']) && $missingDependencies) {
            $errors[] = ts('%1 has a missing dependency on %2', [
              1 => $extPrettyLabel($key),
              2 => implode(', ', array_map($extPrettyLabel, $missingDependencies)),
              'plural' => '%1 has missing dependencies: %2',
              'count' => count($missingDependencies),
            ]);
          }
          elseif (!empty($remotes[$key]) && version_compare($row['version'], $remotes[$key]->version, '<')) {
            $updates[] = $row['label'] . ': ' . $mapper->getUpgradeLink($remotes[$key], $row);
          }
          else {
            if (empty($row['label'])) {
              $okextensions[] = $key;
            }
            else {
              $okextensions[] = ts('%1: Version %2', [
                1 => $row['label'],
                2 => $row['version'],
              ]);
            }
          }
          break;

        default:
          if (in_array($key, $requiredExtensions, TRUE)) {
            $missingRequired[$key] = $row['label'];
          }
      }
    }

    if ($missingRequired) {
      $requiredMessage = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Required:' . implode(',', array_keys($missingRequired)),
        ts('The extension %1 is required and must be enabled.', [1 => implode(', ', $missingRequired)]),
        ts('Required Extension'),
        \Psr\Log\LogLevel::ERROR,
        'fa-exclamation-triangle'
      );
      $requiredMessage->addAction(
        ts('Enable %1', [1 => implode(', ', $missingRequired)]),
        '',
        'api3',
        ['Extension', 'install', ['keys' => array_keys($missingRequired)]]
      );
      $messages[] = $requiredMessage;
    }

    if (!$okextensions && !$updates && !$errors) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Ok',
        ts('No extensions installed. <a %1>Browse available extensions</a>.', [
          1 => 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1') . '"',
        ]),
        ts('Extensions'),
        \Psr\Log\LogLevel::INFO,
        'fa-plug'
      );
    }

    if ($errors) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Error',
          ts('There is one extension error:', [
            'count' => count($errors),
            'plural' => 'There are %count extension errors:',
          ])
          . '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>'
          . ts('To resolve any errors, go to <a %1>Manage Extensions</a>.', [
            1 => 'href="' . CRM_Utils_System::url('civicrm/admin/extensions', 'reset=1') . '"',
          ]),
        ts('Extension Error', ['count' => count($errors), 'plural' => 'Extension Errors']),
        \Psr\Log\LogLevel::ERROR,
        'fa-plug'
      );
    }

    if ($updates) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Updates',
        '<ul><li>' . implode('</li><li>', $updates) . '</li></ul>',
        ts('Extension Update Available', ['plural' => '%count Extension Updates Available', 'count' => count($updates)]),
        \Psr\Log\LogLevel::WARNING,
        'fa-plug'
      );
    }

    if ($okextensions) {
      if ($updates || $errors) {
        $message = ts('1 extension is up-to-date:', ['plural' => '%count extensions are up-to-date:', 'count' => count($okextensions)]);
      }
      else {
        $message = ts('All extensions are up-to-date:');
      }
      natcasesort($okextensions);
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__ . 'Ok',
        $message . '<ul><li>' . implode('</li><li>', $okextensions) . '</li></ul>',
        ts('Extensions'),
        \Psr\Log\LogLevel::INFO,
        'fa-plug'
      );
    }

    return $messages;
  }

  /**
   * Ensure that *some* CiviCRM components (component-extensions) are enabled.
   *
   * It is believed that some sites lost their list of active-components due to a flawed
   * upgrade-step circa 5.62/5.63. The upgrade-step has been fixed (civicrm-core#27075),
   * but some sites may still have bad configurations.
   *
   * This problem should generally be obvious after running web-based upgrader, but it's not obvious
   * in scripted+CLI upgrades.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function checkComponents(): array {
    $messages = [];

    $setting = Civi::settings()->get('enable_components');
    $exts = Extension::get(FALSE)
      ->addWhere('key', 'LIKE', 'civi_%')
      ->addWhere('status', '=', 'installed')
      ->execute()
      ->column('status', 'key');
    if (empty($setting) || empty($exts)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('None of the CiviCRM components are enabled. This is theoretically legal, but it is most likely a misconfiguration.<br/> Please inspect and re-save the <a %1>component settings</a>.', [
          1 => sprintf('target="_blank" href="%s"', Civi::url('backend://civicrm/admin/setting/component?reset=1', 'ah')),
        ]),
        ts('Missing Components'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
    }

    return $messages;
  }

  /**
   * @return CRM_Utils_Check_Message[]
   */
  public function checkScheduledJobLogErrors() {
    $jobs = civicrm_api3('Job', 'get', [
      'sequential' => 1,
      'return' => ["id", "name", "last_run"],
      'is_active' => 1,
      'options' => ['limit' => 0],
    ]);
    $html = '';
    foreach ($jobs['values'] as $job) {
      $lastExecutionMessage = civicrm_api3('JobLog', 'get', [
        'sequential' => 1,
        'return' => ["description"],
        'job_id' => $job['id'],
        'options' => ['sort' => "id desc", 'limit' => 1],
      ])['values'][0]['description'] ?? NULL;
      if (!empty($lastExecutionMessage) && str_contains($lastExecutionMessage, 'Failure')) {
        $viewLogURL = CRM_Utils_System::url('civicrm/admin/joblog', "jid={$job['id']}&reset=1");
        $html .= '<tr>
          <td>' . $job['name'] . ' </td>
          <td>' . $lastExecutionMessage . '</td>
          <td>' . $job['last_run'] . '</td>
          <td><a href="' . $viewLogURL . '">' . ts('View Job Log') . '</a></td>
        </tr>';
      }
    }
    if (empty($html)) {
      return [];
    }

    $message = '<p>' . ts('The following scheduled jobs failed on the last run:') . '</p>
      <p><table><thead><tr><th>' . ts('Job') . '</th><th>' . ts('Message') . '</th><th>' . ts('Last Run') . '</th><th></th>
      </tr></thead><tbody>' . $html . '
      </tbody></table></p>';

    $msg = new CRM_Utils_Check_Message(
      __FUNCTION__,
      $message,
      ts('Scheduled Job Failures'),
      \Psr\Log\LogLevel::WARNING,
      'fa-server'
    );
    $messages[] = $msg;
    return $messages;
  }

  /**
   * Checks if there are pending extension upgrades.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkExtensionUpgrades() {
    if (CRM_Extension_Upgrades::hasPending()) {
      $message = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Extension upgrades should be run as soon as possible.'),
        ts('Extension Upgrades Pending'),
        \Psr\Log\LogLevel::ERROR,
        'fa-plug'
      );
      $message->addAction(
        ts('Run Upgrades'),
        ts('Run extension upgrades now?'),
        'href',
        ['path' => 'civicrm/admin/extensions/upgrade', 'query' => ['reset' => 1, 'destination' => CRM_Utils_System::url('civicrm/a/#/status')]]
      );
      return [$message];
    }
    return [];
  }

  /**
   * Checks if logging is enabled but Civi-report is not.
   *
   * @return CRM_Utils_Check_Message[]
   * @throws \CRM_Core_Exception
   */
  public function checkLoggingHasCiviReport(): array {
    if (Civi::settings()->get('logging')) {
      $isEnabledCiviReport = (bool) Extension::get(FALSE)
        ->addWhere('key', '=', 'civi_report')
        ->addWhere('status', '=', 'installed')
        ->execute()->countFetched();
      return $isEnabledCiviReport ? [] : [
        new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('You have enabled detailed logging but to display this in the change log tab CiviReport must be enabled'),
          ts('CiviReport required to display detailed logging.'),
          LogLevel::WARNING,
          'fa-plug'
        ),
      ];
    }
    return [];
  }

  /**
   * Checks if CiviCRM database version is up-to-date
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDbVersion() {
    $messages = [];
    $dbVersion = CRM_Core_BAO_Domain::version();
    $upgradeUrl = CRM_Utils_System::url("civicrm/upgrade", "reset=1");

    if (!$dbVersion) {
      // if db.ver missing
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Version information found to be missing in database. You will need to determine the correct version corresponding to your current database state.'),
        ts('Database Version Missing'),
        \Psr\Log\LogLevel::ERROR,
        'fa-database'
      );
    }
    elseif (!CRM_Utils_System::isVersionFormatValid($dbVersion)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.'),
        ts('Database Version Invalid'),
        \Psr\Log\LogLevel::ERROR,
        'fa-database'
      );
    }
    elseif (stripos($dbVersion, 'upgrade')) {
      // if db.ver indicates a partially upgraded db
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Database check failed - the database looks to have been partially upgraded. You must reload the database with the backup and try the <a href=\'%1\'>upgrade process</a> again.', [1 => $upgradeUrl]),
        ts('Database Partially Upgraded'),
        \Psr\Log\LogLevel::ALERT,
        'fa-database'
      );
    }
    else {
      // if db.ver < code.ver, time to upgrade
      if (CRM_Core_BAO_Domain::isDBUpdateRequired()) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('New codebase version detected. You must visit <a href=\'%1\'>upgrade screen</a> to upgrade the database.', [1 => $upgradeUrl]),
          ts('Database Upgrade Required'),
          \Psr\Log\LogLevel::ALERT,
          'fa-database'
        );
      }

      // if db.ver > code.ver, sth really wrong
      $codeVersion = CRM_Utils_System::version();
      if (version_compare($dbVersion, $codeVersion) > 0) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__,
          ts('Your database is marked with an unexpected version number: %1. The v%2 codebase may not be compatible with your database state.
            You will need to determine the correct version corresponding to your current database state. You may want to revert to the codebase
            you were using until you resolve this problem.<br/>OR if this is a manual install from git, you might want to fix civicrm-version.php file.',
              [1 => $dbVersion, 2 => $codeVersion]
            ),
          ts('Database In Unexpected Version'),
          \Psr\Log\LogLevel::ERROR,
          'fa-database'
        );
      }
    }

    return $messages;
  }

  /**
   * Ensure that all CiviCRM tables are InnoDB
   * @return CRM_Utils_Check_Message[]
   */
  public function checkDbEngine() {
    $messages = [];

    if (CRM_Core_DAO::isDBMyISAM(150)) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Your database is configured to use the MyISAM database engine. CiviCRM requires InnoDB. You will need to convert any MyISAM tables in your database to InnoDB. Using MyISAM tables will result in data integrity issues.'),
        ts('MyISAM Database Engine'),
        \Psr\Log\LogLevel::ERROR,
        'fa-database'
      );
    }
    return $messages;
  }

  /**
   * Ensure reply id is set to any default value
   * @param bool $force
   * @return CRM_Utils_Check_Message[]
   */
  public function checkReplyIdForMailing($force = FALSE) {
    $messages = [];

    // CiviMail doesn't work in non-production environments; skip.
    if (!$force && CRM_Core_Config::environment() != 'Production') {
      return $messages;
    }

    if (!CRM_Mailing_PseudoConstant::defaultComponent('Reply', '')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('Reply Auto Responder is not set to any default value in <a %1>Headers, Footers, and Automated Messages</a>. This will disable the submit operation on any mailing created from CiviMail.', [1 => 'href="' . CRM_Utils_System::url('civicrm/admin/component', 'reset=1') . '"']),
        ts('No Default value for Auto Responder.'),
        \Psr\Log\LogLevel::WARNING,
        'fa-reply'
      );
    }
    return $messages;
  }

  /**
   * Check for required mbstring extension
   * @return CRM_Utils_Check_Message[]
   */
  public function checkMbstring() {
    $messages = [];

    if (!function_exists('mb_substr')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The PHP Multibyte String extension is needed for CiviCRM to correctly handle user input among other functionality. Ask your system administrator to install it.'),
        ts('Missing mbstring Extension'),
        \Psr\Log\LogLevel::WARNING,
        'fa-server'
      );
    }
    return $messages;
  }

  /**
   * Check if environment is Production.
   * @return CRM_Utils_Check_Message[]
   */
  public function checkEnvironment() {
    $messages = [];

    $environment = CRM_Core_Config::environment();
    if ($environment != 'Production') {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('The environment of this CiviCRM instance is set to \'%1\'. Certain functionality like scheduled jobs has been disabled.', [1 => $environment]),
        ts('Non-Production Environment'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-bug'
      );
    }
    return $messages;
  }

  /**
   * Check for utf8mb4 support by MySQL.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkMysqlUtf8mb4() {
    $messages = [];

    if (CRM_Core_DAO::getConnection()->phptype != 'mysqli') {
      return $messages;
    }

    // Use mysqli_query() to avoid logging an error message.
    $mb4testTableName = CRM_Utils_SQL_TempTable::build()->setCategory('utf8mb4test')->getName();
    if (mysqli_query(CRM_Core_DAO::getConnection()->connection, 'CREATE TEMPORARY TABLE ' . $mb4testTableName . ' (id VARCHAR(255), PRIMARY KEY(id(255))) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC ENGINE=INNODB')) {
      CRM_Core_DAO::executeQuery('DROP TEMPORARY TABLE ' . $mb4testTableName);
    }
    else {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts("Future versions of CiviCRM may require MySQL to support utf8mb4 encoding. It is recommended, though not yet required. Please discuss with your server administrator about configuring your MySQL server for utf8mb4. CiviCRM's recommended configurations are in the System Administrator Guide") . '<br />' . CRM_Utils_System::docURL2('sysadmin/requirements/#mysql-configuration'),
        ts('MySQL Emoji Support (utf8mb4)'),
        \Psr\Log\LogLevel::WARNING,
        'fa-database'
      );
    }
    // Ensure that the MySQL driver supports utf8mb4 encoding.
    $version = mysqli_get_client_info();
    if (str_contains($version, 'mysqlnd')) {
      // The mysqlnd driver supports utf8mb4 starting at version 5.0.9.
      $version = preg_replace('/^\D+([\d.]+).*/', '$1', $version);
      if (version_compare($version, '5.0.9', '<')) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . 'mysqlnd',
          ts('It is recommended, though not yet required, to upgrade your PHP MySQL driver (mysqlnd) to >= 5.0.9 for utf8mb4 support.'),
          ts('PHP MySQL Driver (mysqlnd)'),
          \Psr\Log\LogLevel::WARNING,
          'fa-server'
        );
      }
    }
    else {
      // The libmysqlclient driver supports utf8mb4 starting at version 5.5.3.
      if (version_compare($version, '5.5.3', '<')) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . 'libmysqlclient',
          ts('It is recommended, though not yet required, to upgrade your PHP MySQL driver (libmysqlclient) to >= 5.5.3 for utf8mb4 support.'),
          ts('PHP MySQL Driver (libmysqlclient)'),
          \Psr\Log\LogLevel::WARNING,
          'fa-server'
        );
      }
    }

    return $messages;
  }

  public function checkMysqlVersion() {
    $messages = [];
    $version = CRM_Utils_SQL::getDatabaseVersion();
    $minRecommendedVersion = CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_MYSQL_VER;
    $mariaDbRecommendedVersion = CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_MARIADB_VER;
    $upcomingCiviChangeVersion = '5.34';
    if (version_compare(CRM_Utils_SQL::getDatabaseVersion(), $minRecommendedVersion, '<')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('To prepare for CiviCRM v%4, please upgrade MySQL. The recommended version will be MySQL v%2 or MariaDB v%3.', [
          1 => $version,
          2 => $minRecommendedVersion . '+',
          3 => $mariaDbRecommendedVersion . '+',
          4 => $upcomingCiviChangeVersion . '+',
        ]),
        ts('MySQL Out-of-Date'),
        \Psr\Log\LogLevel::NOTICE,
        'fa-server'
      );
    }
    return $messages;
  }

  public function checkPHPIntlExists(): array {
    $messages = [];
    if (!extension_loaded('intl')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('This system currently does not have the PHP-Intl extension enabled.  Please contact your system administrator about getting the extension enabled.'),
        ts('Missing PHP Extension: INTL'),
        LogLevel::WARNING,
        'fa-server'
      );
    }
    return $messages;
  }

  /**
   * Let people know if they could improve performance by defining the exclude directory.
   *
   * @return array
   */
  public function checkExcludeDirectories(): array {
    $messages = [];
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' && !defined('CIVICRM_EXCLUDE_DIRS_PATTERN')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('You can improve performance by defining the CIVICRM_EXCLUDE_DIRS_PATTERN in your civicrm.settings.php file.') . '<br />' . CRM_Utils_System::docURL2('sysadmin/setup/optimizations/#exclude-dirs-that-do-not-need-to-be-scanned'),
        ts('Performance can be improved by configuring the exclude directories path'),
        LogLevel::NOTICE,
        'fa-flag'
      );
    }
    return $messages;
  }

  /**
   * Let people know if they could improve performance by defining the template compile check.
   *
   * @return array
   */
  public function checkTemplateCompileCheck(): array {
    $messages = [];
    $isProbablyDevelopmentSite = str_contains(CIVICRM_UF_BASEURL, 'localhost') || str_contains(CIVICRM_UF_BASEURL, 'staging') || str_contains(CIVICRM_UF_BASEURL, 'dev');
    if (!defined('CIVICRM_TEMPLATE_COMPILE_CHECK') && !$isProbablyDevelopmentSite && !\Civi::settings()->get('debug_enabled')) {
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('You can improve performance on production sites by specifying the CIVICRM_TEMPLATE_COMPILE_CHECK in the civicrm.settings.php file.') . '<br />' . CRM_Utils_System::docURL2('sysadmin/setup/optimizations/#disable-compile-check'),
        ts('Performance can be improved on live sites by defining the template compile check'),
        LogLevel::NOTICE,
        'fa-flag'
      );
    }
    return $messages;
  }

  public function checkAngularModuleSettings(): array {
    $messages = [];
    $modules = Civi::container()->get('angular')->getModules();
    foreach ($modules as $name => $module) {
      if (!empty($module['settings'])) {
        $messages[] = new CRM_Utils_Check_Message(
          __FUNCTION__ . $name,
          ts('The Angular file "%1" from extension "%2" must be updated to use "settingsFactory" instead of "settings". <a %3>Developer info...</a>', [
            1 => $name,
            2 => $module['ext'],
            3 => 'target="_blank" href="https://github.com/civicrm/civicrm-core/pull/19052"',
          ]),
          ts('Unsupported Angular Setting'),
          LogLevel::WARNING,
          'fa-code'
        );
      }
    }
    return $messages;
  }

  public function checkForMultipleL10NDirs(): array {
    $messages = [];
    $dirs = [];

    // This is what civi thinks is the current l10n path.
    // Even if the site is currently en_US, we still want to do this check
    // since they could change the language later.
    $current_l10n = self::normalizePath(\Civi::Paths()->getPath('[civicrm.l10n]/'));
    if (is_dir($current_l10n)) {
      // use array keys instead of values to automatically dedupe paths
      $dirs[$current_l10n] = 1;
    }

    // check the traditional path under civicrm_root
    $traditional = self::normalizePath(\Civi::Paths()->getPath('[civicrm.root]/l10n'));
    if (is_dir($traditional)) {
      $dirs[$traditional] = 1;
    }

    $private = self::normalizePath(\Civi::Paths()->getPath('[civicrm.private]/l10n'));
    if (is_dir($private)) {
      $dirs[$private] = 1;
    }

    // @todo where else to check? CIVICRM_L10N_BASEDIR is covered by [civicrm.l10n] above.

    if (count($dirs) > 1) {
      $dirlist = '';
      foreach (array_keys($dirs) as $dir) {
        $dirlist .= '<li>' . htmlspecialchars($dir) . '</li>';
      }
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        ts('There are multiple l10n directories, listed below. The one that appears to be in use is %1. You may wish to remove the others to avoid confusion when updating translation files.', [1 => $current_l10n])
          . '<p><ul>' . $dirlist . '</ul></p>',
        ts('Multiple l10n Directories'),
        LogLevel::WARNING,
        'fa-files-o'
      );
    }
    return $messages;
  }

  /**
   * Avoid issues with trailing slashes and mixed separators on windows.
   *
   * @param string $path
   *
   * @return string
   */
  private static function normalizePath($path): string {
    return rtrim(str_replace(DIRECTORY_SEPARATOR, '/', $path), '/');
  }

}
