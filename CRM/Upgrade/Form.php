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
 * Class CRM_Upgrade_Form
 */
class CRM_Upgrade_Form extends CRM_Core_Form {
  const QUEUE_NAME = 'CRM_Upgrade';

  /**
   * Minimum size of MySQL's thread_stack option
   *
   * @see CRM_Upgrade_Form::MINIMUM_THREAD_STACK
   */
  const MINIMUM_THREAD_STACK = 192;

  /**
   * Minimum previous CiviCRM version we can directly upgrade from
   */
  const MINIMUM_UPGRADABLE_VERSION = '4.7.31';

  /**
   * @var \CRM_Core_Config
   */
  protected $_config;

  /**
   * Upgrade for multilingual.
   *
   * @var bool
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

    $locales = CRM_Core_I18n::getMultilingual();

    $this->multilingual = (bool) $locales;
    $this->locales = $locales;

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
   * @param string $version
   *   Ex: '5.22' or '5.22.3'
   *
   * @return CRM_Upgrade_Incremental_Base
   *   Ex: CRM_Upgrade_Incremental_php_FiveTwentyTwo
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
   * @return array
   *   ex: ['5.13', '5.14', '5.15']
   */
  public static function incrementalPhpObjectVersions() {
    $versions = [];

    $phpDir = implode(DIRECTORY_SEPARATOR, [dirname(__FILE__), 'Incremental', 'php']);
    $phpFiles = glob("$phpDir/*.php");
    foreach ($phpFiles as $phpFile) {
      $phpWord = substr(basename($phpFile), 0, -4);
      if (CRM_Utils_EnglishNumber::isNumeric($phpWord)) {
        /** @var \CRM_Upgrade_Incremental_Base $instance */
        $className = 'CRM_Upgrade_Incremental_php_' . $phpWord;
        $instance = new $className();
        $versions[] = $instance->getMajorMinor();
      }
    }

    usort($versions, 'version_compare');
    return $versions;
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
    return CRM_Core_DAO::executeQuery($query);
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
   * Get a list of all patch-versions that appear in upgrade steps, whether
   * as *.mysql.tpl or as *.php.
   *
   * @return array
   * @throws Exception
   */
  public function getRevisionSequence() {
    $revList = [];

    foreach (self::incrementalPhpObjectVersions() as $majorMinor) {
      $phpUpgrader = self::incrementalPhpObject($majorMinor);
      $revList = array_merge($revList, array_values($phpUpgrader->getRevisionSequence()));
    }

    usort($revList, 'version_compare');
    return $revList;
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
    $tempVars = [
      'upgradeRev' => $rev,
    ];

    $this->source($smarty->fetchWith($tplFile, $tempVars), TRUE);

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
        throw new CRM_Core_Exception("sqlfile - $rev.mysql not found.");
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
      throw new CRM_Core_Exception(ts('Version information missing in civicrm database.'));
    }
    elseif (stripos($currentVer, 'upgrade')) {
      throw new CRM_Core_Exception(ts('Database check failed - the database looks to have been partially upgraded. You may want to reload the database with the backup and try the upgrade process again.'));
    }
    if (!$latestVer) {
      throw new CRM_Core_Exception(ts('Version information missing in civicrm codebase.'));
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

    if (version_compare(phpversion(), CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER) < 0) {
      $error = ts('CiviCRM %3 requires PHP version %1 (or newer), but the current system uses %2 ',
        [
          1 => CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER,
          2 => phpversion(),
          3 => $latestVer,
        ]);
    }

    if (version_compare(CRM_Utils_SQL::getDatabaseVersion(), CRM_Upgrade_Incremental_General::MIN_INSTALL_MYSQL_VER) < 0) {
      $error = ts('CiviCRM %4 requires MySQL version v%1 or MariaDB v%3 (or newer), but the current system uses %2 ',
        [
          1 => CRM_Upgrade_Incremental_General::MIN_INSTALL_MYSQL_VER,
          2 => CRM_Utils_SQL::getDatabaseVersion(),
          3 => CRM_Upgrade_Incremental_General::MIN_INSTALL_MARIADB_VER,
          4 => $latestVer,
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
   * The queue is a priority-queue (sorted by tuple weight+id). Here are some common weights:
   *
   * - `weight=0`: Add a typical upgrade step for revising core schema.
   * - `weight=-1`: In the middle of the upgrade, add an extra step for immediate execution.
   * - `weight=1000`: Add some general core upgrade-logic that runs after all schema have change.d
   * - `weight=2000`: Add some post-upgrade logic. If a task absolutely requires full system services
   *    (eg enabling a new extension), then place it here.
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
      throw new CRM_Core_Exception(ts('Failed to find or create queueing table'));
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
    $queue->createItem($task, ['weight' => 0]);

    if (empty(CRM_Upgrade_Snapshot::getActivationIssues())) {
      $task = new CRM_Queue_Task(
        ['CRM_Upgrade_Snapshot', 'cleanupTask'],
        ['civicrm'],
        "Cleanup old upgrade snapshots"
      );
      $queue->createItem($task, ['weight' => 0]);
    }

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'disableOldExtensions'],
      [$postUpgradeMessageFile],
      "Checking extensions"
    );
    $queue->createItem($task, ['weight' => 0]);

    $revisions = $upgrade->getRevisionSequence();
    $maxRevision = empty($revisions) ? NULL : end($revisions);
    reset($revisions);
    if (version_compare($latestVer, $maxRevision, '<')) {
      throw new CRM_Core_Exception("Malformed upgrade sequence.  The incremental update $maxRevision exceeds target version $latestVer");
    }

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
        $queue->createItem($beginTask, ['weight' => 0]);

        $task = new CRM_Queue_Task(
        // callback
          ['CRM_Upgrade_Form', 'doIncrementalUpgradeStep'],
          // arguments
          [$rev, $currentVer, $latestVer, $postUpgradeMessageFile],
          "Upgrade DB to $rev"
        );
        $queue->createItem($task, ['weight' => 0]);

        $task = new CRM_Queue_Task(
        // callback
          ['CRM_Upgrade_Form', 'doIncrementalUpgradeFinish'],
          // arguments
          [$rev, $currentVer, $latestVer, $postUpgradeMessageFile],
          "Finish Upgrade DB to $rev"
        );
        $queue->createItem($task, ['weight' => 0]);
      }
    }

    // It's possible that xml/version.xml points to a version that doesn't have any concrete revision steps.
    if (!in_array($latestVer, $revisions)) {
      $task = new CRM_Queue_Task(
        ['CRM_Upgrade_Form', 'doIncrementalUpgradeFinish'],
        [$rev, $latestVer, $latestVer, $postUpgradeMessageFile],
        "Finish Upgrade DB to $latestVer"
      );
      $queue->createItem($task, ['weight' => 0]);
    }

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Incremental_MessageTemplates', 'updateReservedAndMaybeDefaultTemplates'],
      [],
      "Update all reserved message templates"
    );
    $queue->createItem($task, ['weight' => 990]);

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'doCoreFinish'],
      [$rev, $latestVer, $latestVer, $postUpgradeMessageFile],
      "Finish core DB updates $latestVer"
    );
    $queue->createItem($task, ['weight' => 1000]);

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'enqueueExtUpgrades'],
      [$rev, $latestVer, $latestVer, $postUpgradeMessageFile],
      "Assess extension upgrades"
    );
    // This places the extension-upgrades after `doCoreFinish` - but before new extensions (`addExtensionTask()`)
    $queue->createItem($task, ['weight' => 1500]);

    $task = new CRM_Queue_Task(
      ['CRM_Upgrade_Form', 'doFinalMessages'],
      [$currentVer, $latestVer, $postUpgradeMessageFile],
      'Generate final messages'
    );
    $queue->createItem($task, ['weight' => 3000]);

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
   * Disable/uninstall any extensions not compatible with this new version.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $postUpgradeMessageFile
   * @return bool
   */
  public static function disableOldExtensions(CRM_Queue_TaskContext $ctx, $postUpgradeMessageFile) {
    $messages = [];
    $manager = CRM_Extension_System::singleton()->getManager();
    foreach ($manager->getStatuses() as $key => $status) {
      $enableReplacement = CRM_Core_DAO::singleValueQuery('SELECT is_active FROM civicrm_extension WHERE full_name = %1', [1 => [$key, 'String']]);
      $obsolete = $manager->isIncompatible($key);
      if ($obsolete) {
        if (!empty($obsolete['disable']) && in_array($status, [$manager::STATUS_INSTALLED, $manager::STATUS_INSTALLED_MISSING])) {
          try {
            $manager->disable($key);
            // Update the status for the sake of uninstall below.
            $status = $status == $manager::STATUS_INSTALLED ? $manager::STATUS_DISABLED : $manager::STATUS_DISABLED_MISSING;
            // This message is intentionally overwritten by uninstall below as it would be redundant
            $messages[$key] = ts('The extension %1 is now obsolete and has been disabled.', [1 => $key]);
          }
          catch (CRM_Extension_Exception $e) {
            $messages[] = ts('The obsolete extension %1 could not be removed due to an error. It is recommended to remove this extension manually.', [1 => $key]);
          }
        }
        if (!empty($obsolete['uninstall']) && in_array($status, [$manager::STATUS_DISABLED, $manager::STATUS_DISABLED_MISSING])) {
          try {
            $manager->uninstall($key);
            $messages[$key] = ts('The extension %1 is now obsolete and has been uninstalled.', [1 => $key]);
            if ($status == $manager::STATUS_DISABLED) {
              $messages[$key] .= ' ' . ts('You can remove it from your extensions directory.');
            }
          }
          catch (CRM_Extension_Exception $e) {
            $messages[] = ts('The obsolete extension %1 could not be removed due to an error. It is recommended to remove this extension manually.', [1 => $key]);
          }
        }
        if (!empty($obsolete['force-uninstall'])) {
          CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = 0 WHERE full_name = %1', [
            1 => [$key, 'String'],
          ]);
        }
        if (!empty($obsolete['replacement']) && $enableReplacement) {
          try {
            $manager->enable($manager->install($obsolete['replacement']));
            $messages[] = ts('A replacement extension %1 has been installed as you had the obsolete extension %2 installed', [1 => $obsolete['replacement'], 2 => $key]);
          }
          catch (CRM_Extension_Exception $e) {
            $messages[] = ts('The replacement extension %1 could not be installed due to an error. It is recommended to enable this extension manually.', [1 => $obsolete['replacement']]);
          }
        }
      }
    }
    if ($messages) {
      file_put_contents($postUpgradeMessageFile,
        '<br/><br/><ul><li>' . implode("</li>\n<li>", $messages) . '</li></ul>',
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

    $upgrade->setSchemaStructureTables($rev);

    if (is_callable([$versionObject, $phpFunctionName])) {
      $versionObject->$phpFunctionName($rev, $originalVer, $latestVer);
    }
    else {
      $ctx->log->info("Upgrade DB to $rev: SQL");
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
   * Mark an incremental update as finished.
   *
   * This method may be called in two cases:
   *
   * - After performing each incremental update (`X.X.X.mysql.tpl` or `upgrade_X_X_X()`)
   * - If needed, one more time at the end of the upgrade for the final version-number.
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

    return TRUE;
  }

  /**
   * Finalize the core upgrade.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function doCoreFinish(CRM_Queue_TaskContext $ctx): bool {
    $upgrade = new CRM_Upgrade_Form();
    [$ignore, $latestVer] = $upgrade->getUpgradeVersions();
    // Seems extraneous in context, but we'll preserve old behavior
    $upgrade->setVersion($latestVer);
    // Going forward, any new tasks will run in `upgrade.finish` mode.
    // @see \CRM_Upgrade_DispatchPolicy::pick()

    // (1) For web-upgrade (multi-process), we should resume as another step. It implicitly switches to 'upgrade.finish' on new requests.
    $ctx->queue->createItem(
      new CRM_Queue_Task([static::CLASS, 'doRebuild'], [], ts('Rebuild')),
      ['weight' => -1]
    );

    // (2) For CLI-upgrade (single-process), we must force-switch to 'upgrade.finish' policy.
    Civi::dispatcher()->setDispatchPolicy(\CRM_Upgrade_DispatchPolicy::get('upgrade.finish'));
    return TRUE;
  }

  public static function doRebuild(CRM_Queue_TaskContext $ctx): bool {
    $config = CRM_Core_Config::singleton(TRUE, TRUE);
    $config->userSystem->flush();

    CRM_Core_Invoke::rebuildMenuAndCaches(FALSE, FALSE);
    // NOTE: triggerRebuild is FALSE becaues it will run again in a moment (via fixSchemaDifferences).
    // sessionReset is FALSE because upgrade status/postUpgradeMessages are needed by the Page. We reset later in doFinish().

    $versionCheck = new CRM_Utils_VersionCheck();
    $versionCheck->flushCache();

    // Rebuild all triggers and re-enable logging if needed
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();
    // Force a rebuild of CiviCRM asset cache in case things have changed.
    \Civi::service('asset_builder')->clear(FALSE);

    return TRUE;
  }

  /**
   * After core schema is up-to-date, ensure that
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function enqueueExtUpgrades(CRM_Queue_TaskContext $ctx): bool {
    CRM_Core_Config::singleton(TRUE, TRUE);
    CRM_Upgrade_DispatchPolicy::assertActive('upgrade.finish');
    if (CRM_Extension_Upgrades::hasPending()) {
      CRM_Extension_Upgrades::fillQueue($ctx->queue);
      // ISSUE: Core-upgrade tasks and ext-upgrade tasks need to run with different `DispatchPolicy`s
      // (`upgrade.main` vs `upgrade.finish` or `NULL`).
      // Can we make policy transitions sticky -- eg maybe a setting or session-variable?
    }
    return TRUE;
  }

  /**
   * Generate any standard post-upgrade messages (which are not version-specific).
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @param string $originalVer
   *   the original revision.
   * @param string $latestVer
   *   the target (final) revision.
   * @param string $postUpgradeMessageFile
   *   path of a modifiable file which lists the post-upgrade messages.
   *
   * @return bool
   */
  public static function doFinalMessages(CRM_Queue_TaskContext $ctx, $originalVer, $latestVer, $postUpgradeMessageFile): bool {
    // There are currently no final messages to list. However, this stub may be useful for future messages.
    return TRUE;
  }

  /**
   * After finishing the queue, the upgrade-runner calls `doFinish()`.
   *
   * This is called by all upgrade-runners (inside or outside of `civicrm-core.git`).
   * Removing it would be a breaky-annoying process; it would foreclose future use;
   * and it would produce no tangible benefits.
   *
   * @return bool
   */
  public static function doFinish(): bool {
    $session = CRM_Core_Session::singleton();
    $session->reset('keep_login');
    return TRUE;
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
