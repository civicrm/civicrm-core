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

  public $useFrameworkRelativeBase;

  public $userHookClass;

  public $userPermissionClass;

  /**
   * Manager for temporary permissions.
   * @todo move to container
   *
   * @var CRM_Core_Permission_Temp
   */
  public $userPermissionTemp;

  /**
   * The connector module for the CMS/UF
   * @todo Introduce an interface.
   * @todo move to container
   *
   * @var CRM_Utils_System_Base
   */
  public $userSystem;

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

  //public $customFileUploadDir, $customPHPPathDir, $customTemplateDir, $extensionsDir, $imageUploadDir, $resourceBase, $uploadDir;
  //public $userFrameworkResourceURL, $customCSSURL, $extensionsURL, $imageUploadURL;
  //public $geocodeMethod, $defaultCurrencySymbol;

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

    CRM_Core_DAO::init($this->dsn);

    if (!defined('CIVICRM_UF')) {
      $this->fatal('You need to define CIVICRM_UF in civicrm.settings.php');
    }
    $this->setUserFramework(CIVICRM_UF);

    $this->templateDir = array(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR);
    //if ($loadFromDB) {
    //  // //$this->enableComponents = \Civi::settings()->get('enable_components');
    //
    //  $this->customFileUploadDir = CRM_Core_Config_Defaults::getCustomFileUploadDir();
    //  $this->customPHPPathDir = CRM_Core_Config_Defaults::getCustomPhpPathDir();
    //  $this->customTemplateDir = CRM_Core_Config_Defaults::getCustomTemplateDir();
    //  $this->extensionsDir = CRM_Core_Config_Defaults::getExtensionsDir();
    //  $this->imageUploadDir = CRM_Core_Config_Defaults::getImageUploadDir();
    //  $this->uploadDir = CRM_Core_Config_Defaults::getUploadDir();
    //
    //  $this->resourceBase = CRM_Core_Config_Defaults::getResourceBase();
    //  $this->useFrameworkRelativeBase = CRM_Core_Config_Defaults::getUserFrameworkRelativeBase();
    //
    //  $this->userFrameworkResourceURL = CRM_Core_Config_Defaults::getUserFrameworkResourceUrl();
    //  $this->customCSSURL = CRM_Core_Config_Defaults::getCustomCssUrl();
    //  $this->extensionsURL = CRM_Core_Config_Defaults::getExtensionsUrl();
    //  $this->imageUploadURL = CRM_Core_Config_Defaults::getImageUploadUrl();
    //
    //  $this->geocodeMethod = CRM_Utils_Geocode::getProviderClass();
    //  $this->defaultCurrencySymbol = CRM_Core_Config_Defaults::getDefaultCurrencySymbol();
    //}

    if (CRM_Utils_System::isSSL()) {
      $this->userSystem->mapConfigToSSL();
    }

    if (isset($this->customPHPPathDir) && $this->customPHPPathDir) {
      set_include_path($this->customPHPPathDir . PATH_SEPARATOR . get_include_path());
    }

    $this->initialized = 1;
  }

  public function setUserFramework($userFramework) {
    $this->userFramework = $userFramework;
    $this->userFrameworkClass = 'CRM_Utils_System_' . $userFramework;
    $this->userHookClass = 'CRM_Utils_Hook_' . $userFramework;
    $userPermissionClass = 'CRM_Core_Permission_' . $userFramework;
    $this->userPermissionClass = new $userPermissionClass();

    $class = $this->userFrameworkClass;
    $this->userSystem = new $class();

    if ($userFramework == 'Joomla') {
      $this->userFrameworkURLVar = 'task';
    }

    if (defined('CIVICRM_UF_BASEURL')) {
      $this->userFrameworkBaseURL = CRM_Utils_File::addTrailingSlash(CIVICRM_UF_BASEURL, '/');

      //format url for language negotiation, CRM-7803
      $this->userFrameworkBaseURL = CRM_Utils_System::languageNegotiationURL($this->userFrameworkBaseURL);

      if (CRM_Utils_System::isSSL()) {
        $this->userFrameworkBaseURL = str_replace('http://', 'https://', $this->userFrameworkBaseURL);
      }

      $base = parse_url($this->userFrameworkBaseURL);
      $this->useFrameworkRelativeBase = $base['path'];
      //$this->useFrameworkRelativeBase = empty($base['path']) ? '/' : $base['path'];
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
  }

  private function fatal($message) {
    echo $message;
    exit();
  }

}
