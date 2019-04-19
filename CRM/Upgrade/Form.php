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
 * $Id$
 *
 */
class CRM_Upgrade_Form extends CRM_Core_Form {
  const QUEUE_NAME = 'CRM_Upgrade';

  /**
   * Minimum size of MySQL's thread_stack option
   *
   * @see install/index.php MINIMUM_THREAD_STACK
   */
  const MINIMUM_THREAD_STACK = 192;

  /**
   * Minimum previous CiviCRM version we can directly upgrade from
   */
  const MINIMUM_UPGRADABLE_VERSION = '4.2.9';

  /**
   * Minimum php version required to run (equal to or lower than the minimum install version)
   */
  const MINIMUM_PHP_VERSION = '5.6';

  /**
   * @var \CRM_Core_Config
   */
  protected $_config;

  /**
   * Upgrade for multilingual.
   *
   * @var boolean
   */
  public $multilingual = FALSE;

  /**
   * Locales available for multilingual upgrade.
   *
   * @var array
   */
  public $locales;

  /**
   * Constructor for the basic form page.
   *
   * We should not use QuickForm directly. This class provides a lot
   * of default convenient functions, rules and buttons
   *
   * @param object $state
   *   State associated with this form.
   * @param const|\enum|int $action The mode the form is operating in (None/Create/View/Update/Delete)
   * @param string $method
   *   The type of http method used (GET/POST).
   * @param string $name
   *   The name of the form if different from class name.
   */
  public function __construct(
    $state = NULL,
    $action = CRM_Core_Action::NONE,
    $method = 'post',
    $name = NULL
  ) {
    $this->_config = CRM_Core_Config::singleton();

    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

    $this->multilingual = (bool) $domain->locales;
    $this->locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);

    $smarty = CRM_Core_Smarty::singleton();
    //$smarty->compile_dir = $this->_config->templateCompileDir;
    $smarty->assign('multilingual', $this->multilingual);
    $smarty->assign('locales', $this->locales);

    // we didn't call CRM_Core_BAO_ConfigSetting::retrieve(), so we need to set $dbLocale by hand
    if ($this->multilingual) {
      global $dbLocale;
      $dbLocale = "_{$this->_config->lcMessages}";
    }

    parent::__construct($state, $action, $method, $name);
  }

  /**
   * @param $version
   *
   * @return mixed
   */
  public static function &incrementalPhpObject($version) {
    static $incrementalPhpObject = [];

    $versionParts = explode('.', $version);
    $versionName = CRM_Utils_EnglishNumber::toCamelCase($versionParts[0]) . CRM_Utils_EnglishNumber::toCamelCase($versionParts[1]);

    if (!array_key_exists($versionName, $incrementalPhpObject)) {
      $className = "CRM_Upgrade_Incremental_php_{$versionName}";
      $incrementalPhpObject[$versionName] = new $className();
    }
    return $incrementalPhpObject[$versionName];
  }

  /**
   * @param $version
   * @param $release
   *
   * @return bool
   */
  public function checkVersionRelease($version, $release) {
    $versionParts = explode('.', $version);
    return ($versionParts[2] == $release);
  }

  /**
   * @param $constraints
   *
   * @return array
   */
  public function checkSQLConstraints(&$constraints) {
    $pass = $fail = 0;
    foreach ($constraints as $constraint) {
      if ($this->checkSQLConstraint($constraint)) {
        $pass++;
      }
      else {
        $fail++;
      }
      return [$pass, $fail];
    }
  }

  /**
   * @param $constraint
   *
   * @return bool
   */
  public function checkSQLConstraint($constraint) {
    // check constraint here
    return TRUE;
  }

  /**
   * @param string $fileName
   * @param bool $isQueryString
   */
  public function source($fileName, $isQueryString = FALSE) {
    if ($isQueryString) {
      CRM_Utils_File::runSqlQuery($this->_config->dsn,
        $fileName, NULL
      );
    }
    else {
      CRM_Utils_File::sourceSQLFile($this->_config->dsn,
        $fileName, NULL
      );
    }
  }

  public function preProcess() {
    CRM_Utils_System::setTitle($this->getTitle());
    if (!$this->verifyPreDBState($errorMessage)) {
      if (!isset($errorMessage)) {
        $errorMessage = 'pre-condition failed for current upgrade step';
      }
      CRM_Core_Error::fatal($errorMessage);
    }
    $this->assign('recentlyViewed', FALSE);
  }

  public function buildQuickForm() {
    $this->addDefaultButtons($this->getButtonTitle(),
      'next',
      NULL,
      TRUE
    );
  }

  /**
   * Getter function for title. Should be over-ridden by derived class
   *
   * @return string
   */

  /**
   * @return string
   */
  public function getTitle() {
    return ts('Title not Set');
  }

  /**
   * @return string
   */
  public function getFieldsetTitle() {
    return '';
  }

  /**
   * @return string
   */
  public function getButtonTitle() {
    return ts('Continue');
  }

  /**
   * Use the form name to create the tpl file name.
   *
   * @return string
   */

  /**
   * @return string
   */
  public function getTemplateFileName() {
    $this->assign('title',
      $this->getFieldsetTitle()
    );
    $this->assign('message',
      $this->getTemplateMessage()
    );
    return 'CRM/Upgrade/Base.tpl';
  }

  public function postProcess() {
    $this->upgrade();

    if (!$this->verifyPostDBState($errorMessage)) {
      if (!isset($errorMessage)) {
        $errorMessage = 'post-condition failed for current upgrade step';
      }
      CRM_Core_Error::fatal($errorMessage);
    }
  }

  /**
   * @param $query
   *
   * @return Object
   */
  public function runQuery($query) {
    return CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );
  }

  /**
   * @param $version
   *
   * @return Object
   */
  public function setVersion($version) {
    $this->logVersion($version);

    $query = "
UPDATE civicrm_domain
SET    version = '$version'
";
    return $this->runQuery($query);
  }

  /**
   * @param $newVersion
   *
   * @return bool
   */
  public function logVersion($newVersion) {
    if ($newVersion) {
      $oldVersion = CRM_Core_BAO_Domain::version();

      $session = CRM_Core_Session::singleton();
      $logParams = [
        'entity_table' => 'civicrm_domain',
        'entity_id' => 1,
        'data' => "upgrade:{$oldVersion}->{$newVersion}",
        // lets skip 'modified_id' for now, as it causes FK issues And
        // is not very important for now.
        'modified_date' => date('YmdHis'),
      ];
      CRM_Core_BAO_Log::add($logParams);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * @param $version
   *
   * @return bool
   */
  public function checkVersion($version) {
    $domainID = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Domain',
      $version, 'id',
      'version'
    );
    return $domainID ? TRUE : FALSE;
  }

  /**
   * @return array
   * @throws Exception
   */
  public function getRevisionSequence() {
    $revList = [];
    $sqlDir = implode(DIRECTORY_SEPARATOR,
      [dirname(__FILE__), 'Incremental', 'sql']
    );
    $sqlFiles = scandir($sqlDir);

    $sqlFilePattern = '/^((\d{1,2}\.\d{1,2})\.(\d{1,2}\.)?(\d{1,2}|\w{4,7}))\.(my)?sql(\.tpl)?$/i';
    foreach ($sqlFiles as $file) {
      if (preg_match($sqlFilePattern, $file, $matches)) {
        if (!in_array($matches[1], $revList)) {
          $revList[] = $matches[1];
        }
      }
    }

    usort($revList, 'version_compare');
    return $revList;
  }

  /**
   * @param $rev
   * @param int $index
   *
   * @return null
   */
  public static function getRevisionPart($rev, $index = 1) {
    $revPattern = '/^((\d{1,2})\.\d{1,2})\.(\d{1,2}|\w{4,7})?$/i';
    preg_match($revPattern, $rev, $matches);

    return array_key_exists($index, $matches) ? $matches[$index] : NULL;
  }

  /**
   * @param $tplFile
   * @param $rev
   *
   * @return bool
   */
  public function processLocales($tplFile, $rev) {
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('domainID', CRM_Core_Config::domainID());

    $this->source($smarty->fetch($tplFile), TRUE);

    if ($this->multilingual) {
      CRM_Core_I18n_Schema::rebuildMultilingualSchema($this->locales, $rev);
    }
    return $this->multilingual;
  }

  /**
   * @param $rev
   */
  public function setSchemaStructureTables($rev) {
    if ($this->multilingual) {
      CRM_Core_I18n_Schema::schemaStructureTables($rev, TRUE);
    }
  }

  /**
   * @param $rev
   *
   * @throws Exception
   */
  public function processSQL($rev) {
    $sqlFile = implode(DIRECTORY_SEPARATOR,
      [
        dirname(__FILE__),
        'Incremental',
        'sql',
        $rev . '.mysql',
      ]
    );
    $tplFile = "$sqlFile.tpl";

    if (file_exists($tplFile)) {
      $this->processLocales($tplFile, $rev);
    }
    else {
      if (!file_exists($sqlFile)) {
        CRM_Core_Error::fatal("sqlfile - $rev.mysql not found.");
      }
      $this->source($sqlFile);
    }
  }

  /**
   * Determine the start and end version of the upgrade process.
   *
   * @return array(0=>$currentVer, 1=>$latestVer)
   */
  public function getUpgradeVersions() {
    $latestVer = CRM_Utils_System::version();
    $currentVer = CRM_Core_BAO_Domain::version(TRUE);
    if (!$currentVer) {
      CRM_Core_Error::fatal(ts('Version information missing in civicrm database.'));
    }
    elseif (stripos($currentVer, 'upgrade')) {
      CRM_Core_Error::fatal(ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the upgrade process again.'));
    }
    if (!$latestVer) {
      CRM_Core_Error::fatal(ts('Version information missing in civicrm codebase.'));
    }

    return [$currentVer, $latestVer];
  }

  /**
   * Determine if $currentVer can be upgraded to $latestVer
   *
   * @param $currentVer
   * @param $latestVer
   *
   * @return mixed, a string error message or boolean 'false' if OK
   */
  public function checkUpgradeableVersion($currentVer, $latestVer) {
    $error = FALSE;
    // since version is suppose to be in valid format at this point, especially after conversion ($convertVer),
    // lets do a pattern check -
    if (!CRM_Utils_System::isVersionFormatValid($currentVer)) {
      $error = ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.');
    }
    elseif (version_compare($currentVer, $latestVer) > 0) {
      // DB version number is higher than codebase being upgraded to. This is unexpected condition-fatal error.
      $error = ts('Your database is marked with an unexpected version number: %1. The automated upgrade to version %2 can not be run - and the %2 codebase may not be compatible with your database state. You will need to determine the correct version corresponding to your current database state. You may want to revert to the codebase you were using prior to beginning this upgrade until you resolve this problem.',
        [1 => $currentVer, 2 => $latestVer]
      );
    }
    elseif (version_compare($currentVer, $latestVer) == 0) {
      $error = ts('Your database has already been upgraded to CiviCRM %1',
        [1 => $latestVer]
      );
    }
    elseif (version_compare($currentVer, self::MINIMUM_UPGRADABLE_VERSION) < 0) {
      $error = ts('CiviCRM versions prior to %1 cannot be upgraded directly to %2. This upgrade will need to be done in stages. First download an intermediate version (the LTS may be a good choice) and upgrade to that before proceeding to this version.',
        [1 => self::MINIMUM_UPGRADABLE_VERSION, 2 => $latestVer]
      );
    }

    if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION) < 0) {
      $error = ts('CiviCRM %3 requires PHP version %1 (or newer), but the current system uses %2 ',
        [
          1 => self::MINIMUM_PHP_VERSION,
          2 => phpversion(),
          3 => $latestVer,
        ]);
    }

    // check for mysql trigger privileges
    if (!\Civi::settings()->get('logging_no_trigger_permission') && !CRM_Core_DAO::checkTriggerViewPermission(FALSE, TRUE)) {
      $error = ts('CiviCRM %1 requires MySQL trigger privileges.',
        [1 => $latestVer]);
    }

    if (CRM_Core_DAO::getGlobalSetting('thread_stack', 0) < (1024 * self::MINIMUM_THREAD_STACK)) {
      $error = ts('CiviCRM %1 requires MySQL thread stack >= %2k', [
        1 => $latestVer,
        2 => self::MINIMUM_THREAD_STACK,
      ]);
    }

    return $error;
  }

  /**
   * Determine if $currentver already matches $latestVer
   *
   * @param $currentVer
   * @param $latestVer
   *
   * @return mixed, a string error message or boolean 'false' if OK
   */
  public function checkCurrentVersion($currentVer, $latestVer) {
    $error = FALSE;

    // since version is suppose to be in valid format at this point, especially after conversion ($convertVer),
    // lets do a pattern check -
    if (!CRM_Utils_System::isVersionFormatValid($currentVer)) {
      $error = ts('Database is marked with invalid version format. You may want to investigate this before you proceed further.');
    }
    elseif (version_compare($currentVer, $latestVer) != 0) {
      $error = ts('Your database is not configured for version %1',
        [1 => $latestVer]
      );
    }
    return $error;
  }

  /**
   * Fill the queue with upgrade tasks.
   *
   * @param string $currentVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return CRM_Queue_Service
   */
  public static function buildQueue($currentVer, $latestVer, $postUpgradeMessageFile) {
    $upgrade = new CRM_Upgrade_Form();

    // Ensure that queue can be created
    if (!CRM_Queue_BAO_QueueItem::findCreateTable()) {
      CRM_Core_Error::fatal(ts('Failed to find or create queueing table'));
    }
    $queue = CRM_Queue_Service::singleton()->create([
      'name' => self::QUEUE_NAME,
      'type' => 'Sql',
      'reset' => TRUE,
    ]);

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'doFileCleanup'],
      [$postUpgradeMessageFile],
      "Cleanup old files"
    );
    $queue->createItem($task);

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'disableOldExtensions'],
      [$postUpgradeMessageFile],
      "Checking extensions"
    );
    $queue->createItem($task);

    $revisions = $upgrade->getRevisionSequence();
    foreach ($revisions as $rev) {
      // proceed only if $currentVer < $rev
      if (version_compare($currentVer, $rev) < 0) {
        $beginTask = new CRM_Queue_Task(
        // callback
          ['CRM_Upgrade_Form', 'doIncrementalUpgradeStart'],
          // arguments
          [$rev],
          "Begin Upgrade to $rev"
        );
        $queue->createItem($beginTask);

        $task = new CRM_Queue_Task(
        // callback
          ['CRM_Upgrade_Form', 'doIncrementalUpgradeStep'],
          // arguments
          [$rev, $currentVer, $latestVer, $postUpgradeMessageFile],
          "Upgrade DB to $rev"
        );
        $queue->createItem($task);

        $task = new CRM_Queue_Task(
        // callback
          ['CRM_Upgrade_Form', 'doIncrementalUpgradeFinish'],
          // arguments
          [$rev, $currentVer, $latestVer, $postUpgradeMessageFile],
          "Finish Upgrade DB to $rev"
        );
        $queue->createItem($task);
      }
    }

    return $queue;
  }

  /**
   * Find any old, orphaned files that should have been deleted.
   *
   * These files can get left behind, eg, if you use the Joomla
   * upgrade procedure.
   *
   * The earlier we can do this, the better - don't want upgrade logic
   * to inadvertently rely on old/relocated files.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $postUpgradeMessageFile
   * @return bool
   */
  public static function doFileCleanup(CRM_Queue_TaskContext $ctx, $postUpgradeMessageFile) {
    $source = new CRM_Utils_Check_Component_Source();
    $files = $source->findOrphanedFiles();
    $errors = [];
    foreach ($files as $file) {
      if (is_dir($file['path'])) {
        @rmdir($file['path']);
      }
      else {
        @unlink($file['path']);
      }

      if (file_exists($file['path'])) {
        $errors[] = sprintf("<li>%s</li>", htmlentities($file['path']));
      }
    }

    if (!empty($errors)) {
      file_put_contents($postUpgradeMessageFile,
        '<br/><br/>' . ts('Some old files could not be removed. Please remove them.')
        . '<ul>' . implode("\n", $errors) . '</ul>',
        FILE_APPEND
      );
    }

    return TRUE;
  }

  /**
   * Disable any extensions not compatible with this new version.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $postUpgradeMessageFile
   * @return bool
   */
  public static function disableOldExtensions(CRM_Queue_TaskContext $ctx, $postUpgradeMessageFile) {
    $compatInfo = CRM_Extension_System::getCompatibilityInfo();
    $disabled = [];
    $manager = CRM_Extension_System::singleton()->getManager();
    foreach ($compatInfo as $key => $ext) {
      if (!empty($ext['obsolete']) && $manager->getStatus($key) == $manager::STATUS_INSTALLED) {
        $disabled[$key] = sprintf("<li>%s</li>", ts('The extension %1 is now obsolete and has been disabled.', [1 => $key]));
      }
    }
    if ($disabled) {
      $manager->disable(array_keys($disabled));
      file_put_contents($postUpgradeMessageFile,
        '<br/><br/><ul>' . implode("\n", $disabled) . '</ul>',
        FILE_APPEND
      );
    }

    return TRUE;
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeStart(CRM_Queue_TaskContext $ctx, $rev) {
    $upgrade = new CRM_Upgrade_Form();

    // as soon as we start doing anything we append ".upgrade" to version.
    // this also helps detect any partial upgrade issues
    $upgrade->setVersion($rev . '.upgrade');

    return TRUE;
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   * @param string $originalVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeStep(CRM_Queue_TaskContext $ctx, $rev, $originalVer, $latestVer, $postUpgradeMessageFile) {
    $upgrade = new CRM_Upgrade_Form();

    $phpFunctionName = 'upgrade_' . str_replace('.', '_', $rev);

    $versionObject = $upgrade->incrementalPhpObject($rev);

    // pre-db check for major release.
    if ($upgrade->checkVersionRelease($rev, 'alpha1')) {
      if (!(is_callable([$versionObject, 'verifyPreDBstate']))) {
        CRM_Core_Error::fatal("verifyPreDBstate method was not found for $rev");
      }

      $error = NULL;
      if (!($versionObject->verifyPreDBstate($error))) {
        if (!isset($error)) {
          $error = "post-condition failed for current upgrade for $rev";
        }
        CRM_Core_Error::fatal($error);
      }

    }

    $upgrade->setSchemaStructureTables($rev);

    if (is_callable([$versionObject, $phpFunctionName])) {
      $versionObject->$phpFunctionName($rev, $originalVer, $latestVer);
    }
    else {
      $upgrade->processSQL($rev);
    }

    // set post-upgrade-message if any
    if (is_callable([$versionObject, 'setPostUpgradeMessage'])) {
      $postUpgradeMessage = file_get_contents($postUpgradeMessageFile);
      $versionObject->setPostUpgradeMessage($postUpgradeMessage, $rev);
      file_put_contents($postUpgradeMessageFile, $postUpgradeMessage);
    }

    return TRUE;
  }

  /**
   * Perform an incremental version update.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @param string $rev
   *   the target (intermediate) revision e.g '3.2.alpha1'.
   * @param string $currentVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return bool
   */
  public static function doIncrementalUpgradeFinish(CRM_Queue_TaskContext $ctx, $rev, $currentVer, $latestVer, $postUpgradeMessageFile) {
    $upgrade = new CRM_Upgrade_Form();
    $upgrade->setVersion($rev);
    CRM_Utils_System::flushCache();

    $config = CRM_Core_Config::singleton();
    $config->userSystem->flush();
    return TRUE;
  }

  public static function doFinish() {
    $upgrade = new CRM_Upgrade_Form();
    list($ignore, $latestVer) = $upgrade->getUpgradeVersions();
    // Seems extraneous in context, but we'll preserve old behavior
    $upgrade->setVersion($latestVer);

    // Clear cached metadata.
    Civi::service('settings_manager')->flush();

    // cleanup caches CRM-8739
    $config = CRM_Core_Config::singleton();
    $config->cleanupCaches(1);

    $versionCheck = new CRM_Utils_VersionCheck();
    $versionCheck->flushCache();

    // Rebuild all triggers and re-enable logging if needed
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
  }

  /**
   * Compute any messages which should be displayed before upgrade
   * by calling the 'setPreUpgradeMessage' on each incremental upgrade
   * object.
   *
   * @param string $preUpgradeMessage
   *   alterable.
   * @param $currentVer
   * @param $latestVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $currentVer, $latestVer) {
    // check for changed message templates
    CRM_Upgrade_Incremental_General::checkMessageTemplate($preUpgradeMessage, $latestVer, $currentVer);
    // set global messages
    CRM_Upgrade_Incremental_General::setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);

    // Scan through all php files and see if any file is interested in setting pre-upgrade-message
    // based on $currentVer, $latestVer.
    // Please note, at this point upgrade hasn't started executing queries.
    $revisions = $this->getRevisionSequence();
    foreach ($revisions as $rev) {
      if (version_compare($currentVer, $rev) < 0) {
        $versionObject = $this->incrementalPhpObject($rev);
        CRM_Upgrade_Incremental_General::updateMessageTemplate($preUpgradeMessage, $rev);
        if (is_callable([$versionObject, 'setPreUpgradeMessage'])) {
          $versionObject->setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
        }
      }
    }
  }

}
