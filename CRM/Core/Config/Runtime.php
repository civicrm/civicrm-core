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
 * Class CRM_Core_Config_Runtime
 *
 * The runtime describes the environment in which CiviCRM executes -- ie
 * the DSN, CMS type, CMS URL, etc. Generally, runtime properties must be
 * determined externally (before loading CiviCRM).
 */
class CRM_Core_Config_Runtime {

  public $dsn;

  /**
   * The name of user framework
   *
   * @var string
   */
  public $userFramework;

  public $userFrameworkBaseURL;

  public $userFrameworkClass;

  /**
   * The dsn of the database connection for user framework
   *
   * @var string
   */
  public $userFrameworkDSN;

  /**
   * The name of user framework url variable name
   *
   * @var string
   */
  public $userFrameworkURLVar = 'q';

  public $userFrameworkVersion;

  public $useFrameworkRelativeBase;

  public $userHookClass;

  /**
   * Are we generating clean url's and using mod_rewrite
   * @var string
   */
  public $cleanURL;

  /**
   * The root directory of our template tree.
   * @var string
   */
  public $templateDir;

  /**
   * @var bool
   */
  public $initialized;

  /**
   * @param bool $loadFromDB
   */
  public function initialize($loadFromDB = TRUE) {
    if (!defined('CIVICRM_DSN') && $loadFromDB) {
      $this->fatal('You need to define CIVICRM_DSN in civicrm.settings.php');
    }
    $this->dsn = defined('CIVICRM_DSN') ? CIVICRM_DSN : NULL;

    if (!defined('CIVICRM_UF')) {
      $this->fatal('You need to define CIVICRM_UF in civicrm.settings.php');
    }

    $this->userFramework = CIVICRM_UF;
    $this->userFrameworkClass = 'CRM_Utils_System_' . CIVICRM_UF;
    $this->userHookClass = 'CRM_Utils_Hook_' . CIVICRM_UF;

    if (CIVICRM_UF == 'Joomla') {
      $this->userFrameworkURLVar = 'task';
    }

    if (defined('CIVICRM_UF_DSN')) {
      $this->userFrameworkDSN = CIVICRM_UF_DSN;
    }

    // this is dynamically figured out in the civicrm.settings.php file
    if (defined('CIVICRM_CLEANURL')) {
      $this->cleanURL = CIVICRM_CLEANURL;
    }
    else {
      $this->cleanURL = 0;
    }

    $this->templateDir = [dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR];

    $this->initialized = 1;
  }

  /**
   * Exit processing after a fatal event, outputting the message.
   *
   * @param string $message
   */
  private function fatal($message) {
    echo $message;
    exit();
  }

  /**
   * Include custom PHP and template paths
   */
  public function includeCustomPath() {
    $customProprtyName = ['customPHPPathDir', 'customTemplateDir'];
    foreach ($customProprtyName as $property) {
      $value = Civi::settings()->get($property);
      if (!empty($value)) {
        $customPath = Civi::paths()->getPath($value);
        set_include_path($customPath . PATH_SEPARATOR . get_include_path());
      }
    }
  }

  /**
   * Create a unique identification code for this runtime.
   *
   * If two requests involve a different hostname, different
   * port, different DSN, etc., then they should also have a
   * different runtime ID.
   *
   * @return mixed
   */
  public static function getId() {
    if (!isset(Civi::$statics[__CLASS__]['id'])) {
      Civi::$statics[__CLASS__]['id'] = md5(implode(\CRM_Core_DAO::VALUE_SEPARATOR, [
        // e.g. one database, multi URL
        defined('CIVICRM_DOMAIN_ID') ? CIVICRM_DOMAIN_ID : 1,
        // e.g. one codebase, multi database
        parse_url(CIVICRM_DSN, PHP_URL_PATH),

        // e.g. when you load a new version of the codebase, use different caches
        // Note: in principle, the version number is just a proxy for a dozen other signals (new versions of file A, B, C).
        // Proper caches should reset whenever the underlying signal (file A, B, or C) changes. However, bugs in this
        // behavior often go un-detected during dev/test. Including the software-version basically mitigates the problem
        // for sysadmin-workflows - so that such bugs should only impact developer-workflows.
        \CRM_Utils_System::version(),

        // e.g. CMS vs extern vs installer
        \CRM_Utils_Array::value('SCRIPT_FILENAME', $_SERVER, ''),
        // e.g. name-based vhosts
        \CRM_Utils_Array::value('HTTP_HOST', $_SERVER, ''),
        // e.g. port-based vhosts
        \CRM_Utils_Array::value('SERVER_PORT', $_SERVER, ''),
        // e.g. unit testing
        defined('CIVICRM_TEST') ? 1 : 0,
        // Depending on deployment arch, these signals *could* be redundant, but who cares?
      ]));
    }
    return Civi::$statics[__CLASS__]['id'];
  }

}
