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
 * Class CRM_Core_Config_Runtime
 *
 * The runtime describes the environment in which CiviCRM executes -- ie
 * the DSN, CMS type, CMS URL, etc. Generally, runtime properties must be
 * determined externally (before loading CiviCRM).
 */
class CRM_Core_Config_Runtime extends CRM_Core_Config_MagicMerge {

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
   * @var string
   */
  public $configAndLogDir;

  public $templateCompileDir;

  /**
   * The root directory of our template tree.
   * @var string
   */
  public $templateDir;

  /**
   * @param bool $loadFromDB
   */
  public function initialize($loadFromDB = TRUE) {
    if (!defined('CIVICRM_DSN') && $loadFromDB) {
      $this->fatal('You need to define CIVICRM_DSN in civicrm.settings.php');
    }
    $this->dsn = defined('CIVICRM_DSN') ? CIVICRM_DSN : NULL;

    if (!defined('CIVICRM_TEMPLATE_COMPILEDIR') && $loadFromDB) {
      $this->fatal('You need to define CIVICRM_TEMPLATE_COMPILEDIR in civicrm.settings.php');
    }

    if (defined('CIVICRM_TEMPLATE_COMPILEDIR')) {
      $this->configAndLogDir = CRM_Utils_File::baseFilePath() . 'ConfigAndLog' . DIRECTORY_SEPARATOR;
      CRM_Utils_File::createDir($this->configAndLogDir);
      CRM_Utils_File::restrictAccess($this->configAndLogDir);

      $this->templateCompileDir = defined('CIVICRM_TEMPLATE_COMPILEDIR') ? CRM_Utils_File::addTrailingSlash(CIVICRM_TEMPLATE_COMPILEDIR) : NULL;
      CRM_Utils_File::createDir($this->templateCompileDir);
      CRM_Utils_File::restrictAccess($this->templateCompileDir);
    }

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

    $this->templateDir = array(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);

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
    $customProprtyName = array('customPHPPathDir', 'customTemplateDir');
    foreach ($customProprtyName as $property) {
      $value = $this->getSettings()->get($property);
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
      Civi::$statics[__CLASS__]['id'] = md5(implode(\CRM_Core_DAO::VALUE_SEPARATOR, array(
        defined('CIVICRM_DOMAIN_ID') ? CIVICRM_DOMAIN_ID : 1, // e.g. one database, multi URL
        parse_url(CIVICRM_DSN, PHP_URL_PATH), // e.g. one codebase, multi database
        \CRM_Utils_Array::value('SCRIPT_FILENAME', $_SERVER, ''), // e.g. CMS vs extern vs installer
        \CRM_Utils_Array::value('HTTP_HOST', $_SERVER, ''), // e.g. name-based vhosts
        \CRM_Utils_Array::value('SERVER_PORT', $_SERVER, ''), // e.g. port-based vhosts
        // Depending on deployment arch, these signals *could* be redundant, but who cares?
      )));
    }
    return Civi::$statics[__CLASS__]['id'];
  }

}
