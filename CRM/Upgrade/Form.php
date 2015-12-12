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
  const MINIMUM_UPGRADABLE_VERSION = '4.0.8';

  /**
   * Minimum php version we support
   */
  const MINIMUM_PHP_VERSION = '5.3.4';

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
   * @return array
   */
  protected static function getUpgradeObjects() {
    $majors = array(
      'FourOne',
      'FourTwo',
      'FourThree',
      'FourFour',
      'FourFive',
      'FourSix',
      'FourSeven',
    );
    $upgradeObjects = array();
    foreach ($majors as $major) {
      $class = "CRM_Upgrade_Incremental_php_$major";
      $upgradeObjects[] = new $class();
    }
    return $upgradeObjects;
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
      return array($pass, $fail);
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

    CRM_Utils_File::sourceSQLFile($this->_config->dsn,
      $fileName, NULL, $isQueryString
    );
  }

  public function preProcess() {
    // This function should be deleted, but I want to ensure it's not actually used, and
    // deleting it would pass requests up to parent::preProcess().
    CRM_Core_Error::fatal(sprintf("The function %s::%s should not be called.", __CLASS__, __FUNCTION__));
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
      $logParams = array(
        'entity_table' => 'civicrm_domain',
        'entity_id' => 1,
        'data' => "upgrade:{$oldVersion}->{$newVersion}",
        // lets skip 'modified_id' for now, as it causes FK issues And
        // is not very important for now.
        'modified_date' => date('YmdHis'),
      );
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
    $revList = array();
    $sqlDir = implode(DIRECTORY_SEPARATOR,
      array(dirname(__FILE__), 'Incremental', 'sql')
    );
    $sqlFiles = scandir($sqlDir);

    $sqlFilePattern = '/^((\d{1,2}\.\d{1,2})\.(\d{1,2}\.)?(\d{1,2}|\w{4,7}))\.(my)?sql(\.tpl)?$/i';
    foreach ($sqlFiles as $file) {
      if (preg_match($sqlFilePattern, $file, $matches)) {
        if ($matches[2] == '4.0') {
          CRM_Core_Error::fatal("4.0.x upgrade files shouldn't exist. Contact Lobo to discuss this. This is related to the issue CRM-7731.");
        }
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
      array(
        dirname(__FILE__),
        'Incremental',
        'sql',
        $rev . '.mysql',
      )
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

    return array($currentVer, $latestVer);
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
        array(1 => $currentVer, 2 => $latestVer)
      );
    }
    elseif (version_compare($currentVer, $latestVer) == 0) {
      $error = ts('Your database has already been upgraded to CiviCRM %1',
        array(1 => $latestVer)
      );
    }
    elseif (version_compare($currentVer, self::MINIMUM_UPGRADABLE_VERSION) < 0) {
      $error = ts('CiviCRM versions prior to %1 cannot be upgraded directly to %2. This upgrade will need to be done in stages. First download an intermediate version (the LTS may be a good choice) and upgrade to that before proceeding to this version.',
        array(1 => self::MINIMUM_UPGRADABLE_VERSION, 2 => $latestVer)
      );
    }

    if (version_compare(phpversion(), self::MINIMUM_PHP_VERSION) < 0) {
      $error = ts('CiviCRM %3 requires PHP version %1 (or newer), but the current system uses %2 ',
        array(
          1 => self::MINIMUM_PHP_VERSION,
          2 => phpversion(),
          3 => $latestVer,
        ));
    }

    // check for mysql trigger privileges
    if (!CRM_Core_DAO::checkTriggerViewPermission(FALSE, TRUE)) {
      $error = ts('CiviCRM %1 requires MySQL trigger privileges.',
        array(1 => $latestVer));
    }

    if (CRM_Core_DAO::getGlobalSetting('thread_stack', 0) < (1024 * self::MINIMUM_THREAD_STACK)) {
      $error = ts('CiviCRM %1 requires MySQL thread stack >= %2k', array(
        1 => $latestVer,
        2 => self::MINIMUM_THREAD_STACK,
      ));
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
        array(1 => $latestVer)
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

    // hack to make 4.0.x (D7,J1.6) codebase go through 3.4.x (d6, J1.5) upgrade files,
    // since schema wise they are same
    if (CRM_Upgrade_Form::getRevisionPart($currentVer) == '4.0') {
      $currentVer = str_replace('4.0.', '3.4.', $currentVer);
    }

    // Ensure that queue can be created
    if (!CRM_Queue_BAO_QueueItem::findCreateTable()) {
      CRM_Core_Error::fatal(ts('Failed to find or create queueing table'));
    }
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name' => self::QUEUE_NAME,
      'type' => 'Sql',
      'reset' => TRUE,
    ));

    $upgradeObjects = self::getUpgradeObjects();
    foreach ($upgradeObjects as $obj) {
      /** @var CRM_Upgrade_Incremental_RevisionBase $obj */
      $obj->buildQueue($queue, $postUpgradeMessageFile, $currentVer, $latestVer);
    }

    return $queue;
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

    // Rebuild all triggers and re-enable logging if needed
    $logging = new CRM_Logging_Schema();
    $logging->fixSchemaDifferences();

    //CRM-16257 update Config.IDS.ini might be an old copy
    CRM_Core_IDS::createConfigFile(TRUE);
  }

  /**
   * Compute any messages which should be displayed before upgrade
   * by calling the 'setPreUpgradeMessage' on each incremental upgrade
   * object.
   *
   * @param $currentVer
   * @param $latestVer
   * @return string
   */
  public function createPreUpgradeMessage($currentVer, $latestVer) {
    $preUpgradeMessage = NULL;

    // check for changed message templates
    CRM_Upgrade_Incremental_General::checkMessageTemplate($preUpgradeMessage, $latestVer, $currentVer);
    // set global messages
    CRM_Upgrade_Incremental_General::setPreUpgradeMessage($preUpgradeMessage, $currentVer, $latestVer);

    // Scan through all php files and see if any file is interested in setting pre-upgrade-message
    // based on $currentVer, $latestVer.
    // Please note, at this point upgrade hasn't started executing queries.
    $upgradeObjects = self::getUpgradeObjects();
    foreach ($upgradeObjects as $upgradeObject) {
      /** @var CRM_Upgrade_Incremental_Base $upgradeObject */
      $preUpgradeMessage .= $upgradeObject->createPreUpgradeMessage($currentVer, $latestVer);
    }

    return $preUpgradeMessage;
  }

}
