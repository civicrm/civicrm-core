<?php

/**
 * Note that this installer has been based of the SilverStripe installer.
 * You can get more information from the SilverStripe Website at
 * http://www.silverstripe.com/.
 *
 * Copyright (c) 2006-7, SilverStripe Limited - www.silverstripe.com
 * All rights reserved.
 *
 * License: BSD-3-clause
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 *
 *  Redistributions of source code must retain the above copyright notice,
 *  this list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright
 *  notice, this list of conditions and the following disclaimer in the
 *  documentation and/or other materials provided with the distribution.
 *
 *  Neither the name of SilverStripe nor the names of its contributors may
 *  be used to endorse or promote products derived from this software
 *  without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER
 * OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * Changes and modifications (c) 2007-2017 by CiviCRM LLC
 *
 */

/**
 * CiviCRM Installer
 */
ini_set('max_execution_time', 3000);

if (stristr(PHP_OS, 'WIN')) {
  define('CIVICRM_DIRECTORY_SEPARATOR', '/');
  define('CIVICRM_WINDOWS', 1);
}
else {
  define('CIVICRM_DIRECTORY_SEPARATOR', DIRECTORY_SEPARATOR);
  define('CIVICRM_WINDOWS', 0);
}

global $installType;
global $crmPath;
global $pkgPath;
global $installDirPath;
global $installURLPath;

// Set the install type
// this is sent as a query string when the page is first loaded
// and subsequently posted to the page as a hidden field
if (isset($_POST['civicrm_install_type'])) {
  $installType = $_POST['civicrm_install_type'];
}
elseif (isset($_GET['civicrm_install_type'])) {
  $installType = strtolower($_GET['civicrm_install_type']);
}
else {
  // default value if not set
  $installType = "drupal";
}

if ($installType == 'drupal' || $installType == 'backdrop') {
  $crmPath = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
  $installDirPath = $installURLPath = '';
}
elseif ($installType == 'wordpress') {
  $crmPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR;
  $installDirPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR;
  $installURLPath = WP_PLUGIN_URL . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR;
}
else {
  $errorTitle = "Oops! Unsupported installation mode";
  $errorMsg = sprintf('%s: unknown installation mode. Please refer to the online documentation for more information.', $installType);
  errorDisplayPage($errorTitle, $errorMsg, FALSE);
}

$pkgPath = $crmPath . DIRECTORY_SEPARATOR . 'packages';

require_once $crmPath . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

$loadGenerated = 0;
if (isset($_POST['loadGenerated'])) {
  $loadGenerated = 1;
}

require_once dirname(__FILE__) . CIVICRM_DIRECTORY_SEPARATOR . 'langs.php';
foreach ($langs as $locale => $_) {
  if ($locale == 'en_US') {
    continue;
  }
  if (!file_exists(implode(CIVICRM_DIRECTORY_SEPARATOR, array($crmPath, 'sql', "civicrm_data.$locale.mysql")))) {
    unset($langs[$locale]);
  }
}

// Set the CMS
// This is mostly sympbolic, since nothing we do during the install
// really requires CIVICRM_UF to be defined.
$installTypeToUF = array(
  'wordpress' => 'WordPress',
  'drupal' => 'Drupal',
  'backdrop' => 'Backdrop',
);

$uf = (isset($installTypeToUF[$installType]) ? $installTypeToUF[$installType] : 'Drupal');
define('CIVICRM_UF', $uf);

// Set the Locale (required by CRM_Core_Config)
global $tsLocale;

$tsLocale = 'en_US';
$seedLanguage = 'en_US';

// CRM-16801 This validates that seedLanguage is valid by looking in $langs.
// NB: the variable is initial a $_REQUEST for the initial page reload,
// then becomes a $_POST when the installation form is submitted.
if (isset($_REQUEST['seedLanguage']) and isset($langs[$_REQUEST['seedLanguage']])) {
  $seedLanguage = $_REQUEST['seedLanguage'];
  $tsLocale = $_REQUEST['seedLanguage'];
}

$config = CRM_Core_Config::singleton(FALSE);
$GLOBALS['civicrm_default_error_scope'] = NULL;

// The translation files are in the parent directory (l10n)
$i18n = CRM_Core_I18n::singleton();

// Support for Arabic, Hebrew, Farsi, etc.
// Used in the template.html
$short_lang_code = CRM_Core_I18n_PseudoConstant::shortForLong($tsLocale);
$text_direction = (CRM_Core_I18n::isLanguageRTL($tsLocale) ? 'rtl' : 'ltr');

global $cmsPath;
if ($installType == 'drupal') {
  //CRM-6840 -don't force to install in sites/all/modules/
  $object = new CRM_Utils_System_Drupal();
  $cmsPath = $object->cmsRootPath();

  $siteDir = getSiteDir($cmsPath, $_SERVER['SCRIPT_FILENAME']);
  $alreadyInstalled = file_exists($cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
    'sites' . CIVICRM_DIRECTORY_SEPARATOR .
    $siteDir . CIVICRM_DIRECTORY_SEPARATOR .
    'civicrm.settings.php'
  );
}
elseif ($installType == 'backdrop') {
  $object = new CRM_Utils_System_Backdrop();
  $cmsPath = $object->cmsRootPath();
  $siteDir = getSiteDir($cmsPath, $_SERVER['SCRIPT_FILENAME']);
  $alreadyInstalled = file_exists($cmsPath . CIVICRM_DIRECTORY_SEPARATOR .     'civicrm.settings.php');
}
elseif ($installType == 'wordpress') {
  $cmsPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm';
  $upload_dir = wp_upload_dir();
  $files_dirname = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm';
  $wp_civi_settings = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm.settings.php';
  $wp_civi_settings_deprectated = CIVICRM_PLUGIN_DIR . 'civicrm.settings.php';
  if (file_exists($wp_civi_settings_deprectated)) {
    $alreadyInstalled = $wp_civi_settings_deprectated;
  }
  elseif (file_exists($wp_civi_settings)) {
    $alreadyInstalled = $wp_civi_settings;
  }
}

if ($installType == 'drupal') {
  // Lets check only /modules/.
  $pattern = '/' . preg_quote(CIVICRM_DIRECTORY_SEPARATOR . 'modules', CIVICRM_DIRECTORY_SEPARATOR) . '/';

  if (!preg_match($pattern, str_replace("\\", "/", $_SERVER['SCRIPT_FILENAME']))) {
    $directory = implode(CIVICRM_DIRECTORY_SEPARATOR, array('sites', 'all', 'modules'));
    $errorTitle = ts("Oops! Please correct your install location");
    $errorMsg = ts("Please untar (uncompress) your downloaded copy of CiviCRM in the <strong>%1</strong> directory below your Drupal root directory.", array(1 => $directory));
    errorDisplayPage($errorTitle, $errorMsg);
  }
}

if ($installType == 'backdrop') {
  // Lets check only /modules/.
  $pattern = '/' . preg_quote(CIVICRM_DIRECTORY_SEPARATOR . 'modules', CIVICRM_DIRECTORY_SEPARATOR) . '/';

  if (!preg_match($pattern, str_replace("\\", "/", $_SERVER['SCRIPT_FILENAME']))) {
    $directory = 'modules';
    $errorTitle = ts("Oops! Please correct your install location");
    $errorMsg = ts("Please untar (uncompress) your downloaded copy of CiviCRM in the <strong>%1</strong> directory below your Drupal root directory.", array(1 => $directory));
    errorDisplayPage($errorTitle, $errorMsg);
  }
}

// Exit with error if CiviCRM has already been installed.
if ($alreadyInstalled) {
  $errorTitle = ts("Oops! CiviCRM is already installed");
  $settings_directory = $cmsPath;

  if ($installType == 'drupal') {
    $settings_directory = implode(CIVICRM_DIRECTORY_SEPARATOR, array(
      ts('[your Drupal root directory]'),
      'sites',
      $siteDir,
    ));
  }
  if ($installType == 'backdrop') {
    $settings_directory = implode(CIVICRM_DIRECTORY_SEPARATOR, array(
      ts('[your Backdrop root directory]'),
      $siteDir,
    ));
  }

  $docLink = CRM_Utils_System::docURL2('Installation and Upgrades', FALSE, ts('Installation Guide'), NULL, NULL, "wiki");
  $errorMsg = ts("CiviCRM has already been installed. <ul><li>To <strong>start over</strong>, you must delete or rename the existing CiviCRM settings file - <strong>civicrm.settings.php</strong> - from <strong>%1</strong>.</li><li>To <strong>upgrade an existing installation</strong>, refer to the online documentation: %2.</li></ul>", array(1 => $settings_directory, 2 => $docLink));
  errorDisplayPage($errorTitle, $errorMsg, FALSE);
}

$versionFile = $crmPath . CIVICRM_DIRECTORY_SEPARATOR . 'civicrm-version.php';
if (file_exists($versionFile)) {
  require_once $versionFile;
  $civicrm_version = civicrmVersion();
}
else {
  $civicrm_version = 'unknown';
}

if ($installType == 'drupal') {
  // Ensure that they have downloaded the correct version of CiviCRM
  if ($civicrm_version['cms'] != 'Drupal' && $civicrm_version['cms'] != 'Drupal6') {
    $errorTitle = ts("Oops! Incorrect CiviCRM version");
    $errorMsg = ts("This installer can only be used for the Drupal version of CiviCRM.");
    errorDisplayPage($errorTitle, $errorMsg);
  }

  define('DRUPAL_ROOT', $cmsPath);
  $drupalVersionFiles = array(
    // D6
    implode(CIVICRM_DIRECTORY_SEPARATOR, array($cmsPath, 'modules', 'system', 'system.module')),
    // D7
    implode(CIVICRM_DIRECTORY_SEPARATOR, array($cmsPath, 'includes', 'bootstrap.inc')),
  );
  foreach ($drupalVersionFiles as $drupalVersionFile) {
    if (file_exists($drupalVersionFile)) {
      require_once $drupalVersionFile;
    }
  }

  // Bootstrap Drupal to get settings and user
  $base_root = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
  $base_root .= '://' . $_SERVER['HTTP_HOST'];
  $base_url = $base_root;
  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

  // Check that user is logged in and has administrative permissions
  // This is necessary because the script exposes the database settings in the form and these could be viewed by unauthorised users
  if ((!function_exists('user_access')) || (!user_access('administer site configuration'))) {
    $errorTitle = ts("You don't have permission to access this page");
    $errorMsg = ts("The installer can only be run by a user with the permission to administer site configuration.");
    errorDisplayPage($errorTitle, $errorMsg);
    exit();
  }

  if (!defined('VERSION') or version_compare(VERSION, '6.0') < 0) {
    $errorTitle = ts("Oops! Incorrect Drupal version");
    $errorMsg = ts("This version of CiviCRM can only be used with Drupal 6.x or 7.x. Please ensure that '%1' exists if you are running Drupal 7.0 and over.", array(1 => implode("' or '", $drupalVersionFiles)));
    errorDisplayPage($errorTitle, $errorMsg);
  }
}
elseif ($installType == 'backdrop') {
  // Ensure that they have downloaded the correct version of CiviCRM
  if ($civicrm_version['cms'] != 'Backdrop') {
    $errorTitle = ts("Oops! Incorrect CiviCRM version");
    $errorMsg = ts("This installer can only be used for the Backdrop version of CiviCRM.");
    errorDisplayPage($errorTitle, $errorMsg);
  }

  define('BACKDROP_ROOT', $cmsPath);

  $backdropVersionFiles = array(
    // Backdrop
    implode(CIVICRM_DIRECTORY_SEPARATOR, array($cmsPath, 'core', 'includes', 'bootstrap.inc')),
  );
  foreach ($backdropVersionFiles as $backdropVersionFile) {
    if (file_exists($backdropVersionFile)) {
      require_once $backdropVersionFile;
    }
  }
  if (!defined('BACKDROP_VERSION') or version_compare(BACKDROP_VERSION, '1.0') < 0) {
    $errorTitle = ts("Oops! Incorrect Backdrop version");
    $errorMsg = ts("This version of CiviCRM can only be used with Backdrop 1.x. Please ensure that '%1' exists if you are running Backdrop 1.0 and over.", array(1 => implode("' or '", $backdropVersionFiles)));
    errorDisplayPage($errorTitle, $errorMsg);
  }
}
elseif ($installType == 'wordpress') {
  //HACK for now
  $civicrm_version['cms'] = 'WordPress';

  // Ensure that they have downloaded the correct version of CiviCRM
  if ($civicrm_version['cms'] != 'WordPress') {
    $errorTitle = ts("Oops! Incorrect CiviCRM version");
    $errorMsg = ts("This installer can only be used for the WordPress version of CiviCRM.");
    errorDisplayPage($errorTitle, $errorMsg);
  }
}

// Load CiviCRM database config
if (isset($_POST['mysql'])) {
  $databaseConfig = $_POST['mysql'];
}

if ($installType == 'wordpress') {
  // Load WP database config
  if (isset($_POST['mysql'])) {
    $databaseConfig = $_POST['mysql'];
  }
  else {
    $databaseConfig = array(
      "server" => DB_HOST,
      "username" => DB_USER,
      "password" => DB_PASSWORD,
      "database" => DB_NAME,
    );
  }
}

if ($installType == 'drupal') {
  // Load drupal database config
  if (isset($_POST['drupal'])) {
    $drupalConfig = $_POST['drupal'];
  }
  else {
    $dbServer = $databases['default']['default']['host'];
    if (!empty($databases['default']['default']['port'])) {
      $dbServer .= ':' . $databases['default']['default']['port'];
    }
    $drupalConfig = array(
      "server" => $dbServer,
      "username" => $databases['default']['default']['username'],
      "password" => $databases['default']['default']['password'],
      "database" => $databases['default']['default']['database'],
    );
  }
}

if ($installType == 'backdrop') {
  // Load backdrop database config
  if (isset($_POST['backdrop'])) {
    $backdropConfig = $_POST['backdrop'];
  }
  else {
    $backdropConfig = array(
      "server" => "localhost",
      "username" => "backdrop",
      "password" => "",
      "database" => "backdrop",
    );
  }
}

// By default set CiviCRM database to be same as CMS database
if (!isset($databaseConfig)) {
  if (($installType == 'drupal') && (isset($drupalConfig))) {
    $databaseConfig = $drupalConfig;
  }
  if (($installType == 'backdrop') && (isset($backdropConfig))) {
    $databaseConfig = $backdropConfig;
  }
}

// Check requirements
$req = new InstallRequirements();
$req->check();

if ($req->hasErrors()) {
  $hasErrorOtherThanDatabase = TRUE;
}

if ($databaseConfig) {
  $dbReq = new InstallRequirements();
  $dbReq->checkdatabase($databaseConfig, 'CiviCRM');
  if ($installType == 'drupal') {
    $dbReq->checkdatabase($drupalConfig, 'Drupal');
  }
  if ($installType == 'backdrop') {
    $dbReq->checkdatabase($backdropConfig, 'Backdrop');
  }
}

// Actual processor
if (isset($_POST['go']) && !$req->hasErrors() && !$dbReq->hasErrors()) {
  // Confirm before reinstalling
  if (!isset($_POST['force_reinstall']) && $alreadyInstalled) {
    include $installDirPath . 'template.html';
  }
  else {
    $inst = new Installer();
    $inst->install($_POST);
  }

  // Show the config form
}
else {
  include $installDirPath . 'template.html';
}

/**
 * This class checks requirements
 * Each of the requireXXX functions takes an argument which gives a user description of the test.  It's an array
 * of 3 parts:
 *  $description[0] - The test category
 *  $description[1] - The test title
 *  $description[2] - The test error to show, if it goes wrong
 */
class InstallRequirements {
  var $errors, $warnings, $tests, $conn;

  // @see CRM_Upgrade_Form::MINIMUM_THREAD_STACK
  const MINIMUM_THREAD_STACK = 192;

  /**
   * Just check that the database configuration is okay.
   * @param $databaseConfig
   * @param $dbName
   */
  public function checkdatabase($databaseConfig, $dbName) {
    if ($this->requireFunction('mysqli_connect',
      array(
        ts("PHP Configuration"),
        ts("MySQL support"),
        ts("MySQL support not included in PHP."),
      )
    )
    ) {
      $this->requireMySQLServer($databaseConfig['server'],
        array(
          ts("MySQL %1 Configuration", array(1 => $dbName)),
          ts("Does the server exist?"),
          ts("Can't find the a MySQL server on '%1'.", array(1 => $databaseConfig['server'])),
          $databaseConfig['server'],
        )
      );
      if ($this->requireMysqlConnection($databaseConfig['server'],
        $databaseConfig['username'],
        $databaseConfig['password'],
        array(
          ts("MySQL %1 Configuration", array(1 => $dbName)),
          ts("Are the access credentials correct?"),
          ts("That username/password doesn't work"),
        )
      )
      ) {
        @$this->requireMySQLVersion("5.1",
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts("MySQL version at least %1", array(1 => '5.1')),
            ts("MySQL version %1 or higher is required, you are running MySQL %2.", array(1 => '5.1', 2 => mysqli_get_server_info($this->conn))),
            ts("MySQL %1", array(1 => mysqli_get_server_info($this->conn))),
          )
        );
        $this->requireMySQLAutoIncrementIncrementOne($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts("Is auto_increment_increment set to 1"),
            ts("An auto_increment_increment value greater than 1 is not currently supported. Please see issue CRM-7923 for further details and potential workaround."),
          )
        );
        $testDetails = array(
          ts("MySQL %1 Configuration", array(1 => $dbName)),
          ts("Is the provided database name valid?"),
          ts("The database name provided is not valid. Please use only 0-9, a-z, A-Z, _ and - as characters in the name."),
        );
        if (!CRM_Core_DAO::requireSafeDBName($databaseConfig['database'])) {
          $this->error($testDetails);
          return FALSE;
        }
        else {
          $this->testing($testDetails);
        }
        $this->requireMySQLThreadStack($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          self::MINIMUM_THREAD_STACK,
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts("Does MySQL thread_stack meet minimum (%1k)", array(1 => self::MINIMUM_THREAD_STACK)),
            "",
            // "The MySQL thread_stack does not meet minimum " . CRM_Upgrade_Form::MINIMUM_THREAD_STACK . "k. Please update thread_stack in my.cnf.",
          )
        );
      }
      $onlyRequire = ($dbName == 'Drupal' || $dbName == 'Backdrop') ? TRUE : FALSE;
      $this->requireDatabaseOrCreatePermissions(
        $databaseConfig['server'],
        $databaseConfig['username'],
        $databaseConfig['password'],
        $databaseConfig['database'],
        array(
          ts("MySQL %1 Configuration", array(1 => $dbName)),
          ts("Can I access/create the database?"),
          ts("I can't create new databases and the database '%1' doesn't exist.", array(1 => $databaseConfig['database'])),
        ),
        $onlyRequire
      );
      if ($dbName != 'Drupal' && $dbName != 'Backdrop') {
        $this->requireMySQLInnoDB($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts("Can I access/create InnoDB tables in the database?"),
            ts("Unable to create InnoDB tables. MySQL InnoDB support is required for CiviCRM but is either not available or not enabled in this MySQL database server."),
          )
        );
        $this->requireMySQLTempTables($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts('Can I create temporary tables in the database?'),
            ts('Unable to create temporary tables. This MySQL user is missing the CREATE TEMPORARY TABLES privilege.'),
          )
        );
        $this->requireMySQLLockTables($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts('Can I create lock tables in the database?'),
            ts('Unable to lock tables. This MySQL user is missing the LOCK TABLES privilege.'),
          )
        );
        $this->requireMySQLTrigger($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            ts("MySQL %1 Configuration", array(1 => $dbName)),
            ts('Can I create triggers in the database?'),
            ts('Unable to create triggers. This MySQL user is missing the CREATE TRIGGERS  privilege.'),
          )
        );
      }
    }
  }

  /**
   * Connect via mysqli.
   *
   * This is exactly the same as mysqli_connect(), except that it accepts
   * the port as part of the `$host`.
   *
   * @param string $host
   *   Ex: 'localhost', 'localhost:3307', '127.0.0.1:3307', '[::1]', '[::1]:3307'.
   * @param string $username
   * @param string $password
   * @param string $database
   * @return \mysqli
   */
  protected function connect($host, $username, $password, $database = '') {
    $hostParts = explode(':', $host);
    if (count($hostParts) > 1 && strrpos($host, ']') !== strlen($host) - 1) {
      $port = array_pop($hostParts);
      $host = implode(':', $hostParts);
    }
    else {
      $port = NULL;
    }
    $conn = @mysqli_connect($host, $username, $password, $database, $port);
    return $conn;
  }

  /**
   * Check everything except the database.
   */
  public function check() {
    global $crmPath, $installType;

    $this->errors = NULL;

    $this->requirePHPVersion(array(
      ts("PHP Configuration"),
      ts("PHP5 installed"),
    ));

    // Check that we can identify the root folder successfully
    $this->requireFile($crmPath . CIVICRM_DIRECTORY_SEPARATOR . 'README.md',
      array(
        ts("File permissions"),
        ts("Does the webserver know where files are stored?"),
        ts("The webserver isn't letting me identify where files are stored."),
        $this->getBaseDir(),
      ),
      TRUE
    );

    // CRM-6485: make sure the path does not contain PATH_SEPARATOR, as we don’t know how to escape it
    $this->requireNoPathSeparator(
      array(
        ts("File permissions"),
        ts('Does the CiviCRM path contain PATH_SEPARATOR?'),
        ts('The path %1 contains PATH_SEPARATOR (the %2 character).', array(1 => $this->getBaseDir(), 2 => PATH_SEPARATOR)),
        $this->getBaseDir(),
      )
    );

    $requiredDirectories = array('CRM', 'packages', 'templates', 'js', 'api', 'i', 'sql');
    foreach ($requiredDirectories as $dir) {
      $this->requireFile($crmPath . CIVICRM_DIRECTORY_SEPARATOR . $dir,
        array(
          ts("File permissions"),
          ts("Folder '%1' exists?", array(1 => $dir)),
          ts("There is no '%1' folder.", array(1 => $dir)),
        ), TRUE
      );
    }

    $configIDSiniDir = NULL;
    global $cmsPath;
    $siteDir = getSiteDir($cmsPath, $_SERVER['SCRIPT_FILENAME']);
    if ($installType == 'drupal') {

      // make sure that we can write to sites/default and files/
      $writableDirectories = array(
        $cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
        'sites' . CIVICRM_DIRECTORY_SEPARATOR .
        $siteDir . CIVICRM_DIRECTORY_SEPARATOR .
        'files',
        $cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
        'sites' . CIVICRM_DIRECTORY_SEPARATOR .
        $siteDir,
      );
    }
    elseif ($installType == 'backdrop') {

      // make sure that we can write to sites/default and files/
      $writableDirectories = array(
        $cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
        'files',
        $cmsPath,
      );
    }
    elseif ($installType == 'wordpress') {
      // make sure that we can write to uploads/civicrm/
      $upload_dir = wp_upload_dir();
      $files_dirname = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . 'civicrm';
      if (!file_exists($files_dirname)) {
        wp_mkdir_p($files_dirname);
      }
      $writableDirectories = array($files_dirname);
    }

    foreach ($writableDirectories as $dir) {
      $dirName = CIVICRM_WINDOWS ? $dir : CIVICRM_DIRECTORY_SEPARATOR . $dir;
      $testDetails = array(
        ts("File permissions"),
        ts("Is the %1 folder writeable?", array(1 => $dir)),
        NULL,
      );
      $this->requireWriteable($dirName, $testDetails, TRUE);
    }

    //check for Config.IDS.ini, file may exist in re-install
    $configIDSiniDir = array($cmsPath, 'sites', $siteDir, 'files', 'civicrm', 'upload', 'Config.IDS.ini');

    if (is_array($configIDSiniDir) && !empty($configIDSiniDir)) {
      $configIDSiniFile = implode(CIVICRM_DIRECTORY_SEPARATOR, $configIDSiniDir);
      if (file_exists($configIDSiniFile)) {
        unlink($configIDSiniFile);
      }
    }

    // Check for rewriting
    if (isset($_SERVER['SERVER_SOFTWARE'])) {
      $webserver = strip_tags(trim($_SERVER['SERVER_SOFTWARE']));
    }
    elseif (isset($_SERVER['SERVER_SIGNATURE'])) {
      $webserver = strip_tags(trim($_SERVER['SERVER_SIGNATURE']));
    }

    if ($webserver == '') {
      $webserver = ts("I can't tell what webserver you are running");
    }

    // Check for $_SERVER configuration
    $this->requireServerVariables(array('SCRIPT_NAME', 'HTTP_HOST', 'SCRIPT_FILENAME'), array(
      ts("Webserver config"),
      ts("Recognised webserver"),
      ts("You seem to be using an unsupported webserver. The server variables SCRIPT_NAME, HTTP_HOST, SCRIPT_FILENAME need to be set."),
    ));

    // Check for MySQL support
    $this->requireFunction('mysqli_connect', array(
      ts("PHP Configuration"),
      ts("MySQL support"),
      ts("MySQL support not included in PHP."),
    ));

    // Check for XML support
    $this->requireFunction('simplexml_load_file', array(
      ts("PHP Configuration"),
      ts("SimpleXML support"),
      ts("SimpleXML support not included in PHP."),
    ));

    // Check for JSON support
    $this->requireFunction('json_encode', array(
      ts("PHP Configuration"),
      ts("JSON support"),
      ts("JSON support not included in PHP."),
    ));

    // check for Multibyte support such as mb_substr. Required for proper handling of Multilingual setups.
    $this->requireFunction('mb_substr', array(
      ts("PHP Configuration"),
      ts("Multibyte support"),
      ts("Multibyte support not enabled in PHP."),
    ));

    // Check for xcache_isset and emit warning if exists
    $this->checkXCache(array(
      ts("PHP Configuration"),
      ts("XCache compatibility"),
      ts("XCache is installed and there are known compatibility issues between XCache and CiviCRM. Consider using an alternative PHP caching mechanism or disable PHP caching altogether."),
    ));

    // Check memory allocation
    $this->requireMemory(32 * 1024 * 1024,
      64 * 1024 * 1024,
      array(
        ts("PHP Configuration"),
        ts("Memory allocated (PHP config option 'memory_limit')"),
        ts("CiviCRM needs a minimum of %1 MB allocated to PHP, but recommends %2 MB.", array(1 => 32, 2 => 64)),
        ini_get("memory_limit"),
      )
    );

    return $this->errors;
  }

  /**
   * @param $min
   * @param $recommended
   * @param $testDetails
   */
  public function requireMemory($min, $recommended, $testDetails) {
    $this->testing($testDetails);
    $mem = $this->getPHPMemory();

    if ($mem < $min && $mem > 0) {
      $testDetails[2] .= " " . ts("You only have %1 allocated", array(1 => ini_get("memory_limit")));
      $this->error($testDetails);
    }
    elseif ($mem < $recommended && $mem > 0) {
      $testDetails[2] .= " " . ts("You only have %1 allocated", array(1 => ini_get("memory_limit")));
      $this->warning($testDetails);
    }
    elseif ($mem == 0) {
      $testDetails[2] .= " " . ts("We can't determine how much memory you have allocated. Install only if you're sure you've allocated at least %1 MB.", array(1 => 32));
      $this->warning($testDetails);
    }
  }

  /**
   * @return float
   */
  public function getPHPMemory() {
    $memString = ini_get("memory_limit");

    switch (strtolower(substr($memString, -1))) {
      case "k":
        return round(substr($memString, 0, -1) * 1024);

      case "m":
        return round(substr($memString, 0, -1) * 1024 * 1024);

      case "g":
        return round(substr($memString, 0, -1) * 1024 * 1024 * 1024);

      default:
        return round($memString);
    }
  }

  public function listErrors() {
    if ($this->errors) {
      echo "<p>" . ts("The following problems are preventing me from installing CiviCRM:") . "</p>";
      foreach ($this->errors as $error) {
        echo "<li>" . htmlentities($error) . "</li>";
      }
    }
  }

  /**
   * @param null $section
   */
  public function showTable($section = NULL) {
    if ($section) {
      $tests = $this->tests[$section];
      echo "<table class=\"testResults\" width=\"100%\">";
      foreach ($tests as $test => $result) {
        echo "<tr class=\"$result[0]\"><td>$test</td><td>" . nl2br(htmlentities($result[1])) . "</td></tr>";
      }
      echo "</table>";
    }
    else {
      foreach ($this->tests as $section => $tests) {
        echo "<h3>$section</h3>";
        echo "<table class=\"testResults\" width=\"100%\">";

        foreach ($tests as $test => $result) {
          echo "<tr class=\"$result[0]\"><td>$test</td><td>" . nl2br(htmlentities($result[1])) . "</td></tr>";
        }
        echo "</table>";
      }
    }
  }

  /**
   * @param string $funcName
   * @param $testDetails
   *
   * @return bool
   */
  public function requireFunction($funcName, $testDetails) {
    $this->testing($testDetails);

    if (!function_exists($funcName)) {
      $this->error($testDetails);
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * @param $testDetails
   */
  public function checkXCache($testDetails) {
    if (function_exists('xcache_isset') &&
      ini_get('xcache.size') > 0
    ) {
      $this->testing($testDetails);
      $this->warning($testDetails);
    }
  }

  /**
   * @param array $testDetails
   * @return bool
   */
  public function requirePHPVersion($testDetails) {

    $this->testing($testDetails);

    $phpVersion = phpversion();
    $aboveMinVersion = version_compare($phpVersion, CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER) >= 0;

    if ($aboveMinVersion) {
      if (version_compare($phpVersion, CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_PHP_VER) < 0) {
        $testDetails[2] = ts('This webserver is running an outdated version of PHP (%1). It is strongly recommended to upgrade to PHP %2 or later, as older versions can present a security risk. The preferred version is %3.', array(
          1 => $phpVersion,
          2 => CRM_Upgrade_Incremental_General::MIN_RECOMMENDED_PHP_VER,
          3 => CRM_Upgrade_Incremental_General::RECOMMENDED_PHP_VER,
        ));
        $this->warning($testDetails);
      }
      return TRUE;
    }

    if (empty($testDetails[2])) {
      $testDetails[2] = ts("You need PHP version %1 or later, only %2 is installed. Please upgrade your server, or ask your web-host to do so.", array(1 => CRM_Upgrade_Incremental_General::MIN_INSTALL_PHP_VER, 2 => $phpVersion));
    }

    $this->error($testDetails);
  }

  /**
   * @param string $filename
   * @param $testDetails
   * @param bool $absolute
   */
  public function requireFile($filename, $testDetails, $absolute = FALSE) {
    $this->testing($testDetails);
    if (!$absolute) {
      $filename = $this->getBaseDir() . $filename;
    }
    if (!file_exists($filename)) {
      $testDetails[2] .= " (" . ts("file '%1' not found", array(1 => $filename)) . ')';
      $this->error($testDetails);
    }
  }

  /**
   * @param $testDetails
   */
  public function requireNoPathSeparator($testDetails) {
    $this->testing($testDetails);
    if (substr_count($this->getBaseDir(), PATH_SEPARATOR)) {
      $this->error($testDetails);
    }
  }

  /**
   * @param string $filename
   * @param $testDetails
   */
  public function requireNoFile($filename, $testDetails) {
    $this->testing($testDetails);
    $filename = $this->getBaseDir() . $filename;
    if (file_exists($filename)) {
      $testDetails[2] .= " (" . ts("file '%1' found", array(1 => $filename)) . ")";
      $this->error($testDetails);
    }
  }

  /**
   * @param string $filename
   * @param $testDetails
   */
  public function moveFileOutOfTheWay($filename, $testDetails) {
    $this->testing($testDetails);
    $filename = $this->getBaseDir() . $filename;
    if (file_exists($filename)) {
      if (file_exists("$filename.bak")) {
        rm("$filename.bak");
      }
      rename($filename, "$filename.bak");
    }
  }

  /**
   * @param string $filename
   * @param $testDetails
   * @param bool $absolute
   */
  public function requireWriteable($filename, $testDetails, $absolute = FALSE) {
    $this->testing($testDetails);
    if (!$absolute) {
      $filename = $this->getBaseDir() . $filename;
    }

    if (!is_writable($filename)) {
      $name = NULL;
      if (function_exists('posix_getpwuid')) {
        $user = posix_getpwuid(posix_geteuid());
        $name = '- ' . $user['name'] . ' -';
      }

      if (!isset($testDetails[2])) {
        $testDetails[2] = NULL;
      }
      $testDetails[2] .= ts("The user account used by your web-server %1 needs to be granted write access to the following directory in order to configure the CiviCRM settings file:", array(1 => $name)) . "\n$filename";
      $this->error($testDetails);
    }
  }

  /**
   * @param string $moduleName
   * @param $testDetails
   */
  public function requireApacheModule($moduleName, $testDetails) {
    $this->testing($testDetails);
    if (!in_array($moduleName, apache_get_modules())) {
      $this->error($testDetails);
    }
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $testDetails
   */
  public function requireMysqlConnection($server, $username, $password, $testDetails) {
    $this->testing($testDetails);
    $this->conn = $this->connect($server, $username, $password);

    if ($this->conn) {
      return TRUE;
    }
    else {
      $testDetails[2] .= ": " . mysqli_connect_error();
      $this->error($testDetails);
    }
  }

  /**
   * @param $server
   * @param $testDetails
   */
  public function requireMySQLServer($server, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, NULL, NULL);

    if ($conn || mysqli_connect_errno() < 2000) {
      return TRUE;
    }
    else {
      $testDetails[2] .= ": " . mysqli_connect_error();
      $this->error($testDetails);
    }
  }

  /**
   * @param $version
   * @param $testDetails
   */
  public function requireMySQLVersion($version, $testDetails) {
    $this->testing($testDetails);

    if (!mysqli_get_server_info($this->conn)) {
      $testDetails[2] = ts('Cannot determine the version of MySQL installed. Please ensure at least version %1 is installed.', array(1 => $version));
      $this->warning($testDetails);
    }
    else {
      list($majorRequested, $minorRequested) = explode('.', $version);
      list($majorHas, $minorHas) = explode('.', mysqli_get_server_info($this->conn));

      if (($majorHas > $majorRequested) || ($majorHas == $majorRequested && $minorHas >= $minorRequested)) {
        return TRUE;
      }
      else {
        $testDetails[2] .= "{$majorHas}.{$minorHas}.";
        $this->error($testDetails);
      }
    }
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  public function requireMySQLInnoDB($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] .= ' ' . ts("Could not determine if MySQL has InnoDB support. Assuming no.");
      $this->error($testDetails);
      return;
    }

    $innodb_support = FALSE;
    $result = mysqli_query($conn, "SHOW ENGINES");
    while ($values = mysqli_fetch_array($result)) {
      if ($values['Engine'] == 'InnoDB') {
        if (strtolower($values['Support']) == 'yes' ||
          strtolower($values['Support']) == 'default'
        ) {
          $innodb_support = TRUE;
        }
      }
    }
    if ($innodb_support) {
      $testDetails[3] = ts('MySQL server does have InnoDB support');
    }
    else {
      $testDetails[2] .= ' ' . ts('Could not determine if MySQL has InnoDB support. Assuming no');
    }
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  public function requireMySQLTempTables($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = ts('Could not login to the database.');
      $this->error($testDetails);
      return;
    }

    if (!@mysqli_select_db($conn, $database)) {
      $testDetails[2] = ts('Could not select the database.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, 'CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)');
    if (!$result) {
      $testDetails[2] = ts('Could not create a temp table.');
      $this->error($testDetails);
    }
    $result = mysqli_query($conn, 'DROP TEMPORARY TABLE civicrm_install_temp_table_test');
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  public function requireMySQLTrigger($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = ts('Could not login to the database.');
      $this->error($testDetails);
      return;
    }

    if (!@mysqli_select_db($conn, $database)) {
      $testDetails[2] = ts('Could not select the database.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, 'CREATE TABLE civicrm_install_temp_table_test (test text)');
    if (!$result) {
      $testDetails[2] = ts('Could not create a table in the database.');
      $this->error($testDetails);
    }

    $result = mysqli_query($conn, 'CREATE TRIGGER civicrm_install_temp_table_test_trigger BEFORE INSERT ON civicrm_install_temp_table_test FOR EACH ROW BEGIN END');
    if (!$result) {
      mysqli_query($conn, 'DROP TABLE civicrm_install_temp_table_test');
      $testDetails[2] = ts('Could not create a database trigger.');
      $this->error($testDetails);
    }

    mysqli_query($conn, 'DROP TRIGGER civicrm_install_temp_table_test_trigger');
    mysqli_query($conn, 'DROP TABLE civicrm_install_temp_table_test');
  }


  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  public function requireMySQLLockTables($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = ts('Could not connect to the database server.');
      $this->error($testDetails);
      return;
    }

    if (!@mysqli_select_db($conn, $database)) {
      $testDetails[2] = ts('Could not select the database.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, 'CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)');
    if (!$result) {
      $testDetails[2] = ts('Could not create a table in the database.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, 'LOCK TABLES civicrm_install_temp_table_test WRITE');
    if (!$result) {
      $testDetails[2] = ts('Could not obtain a write lock for the database table.');
      $this->error($testDetails);
      $result = mysqli_query($conn, 'DROP TEMPORARY TABLE civicrm_install_temp_table_test');
      return;
    }

    $result = mysqli_query($conn, 'UNLOCK TABLES');
    if (!$result) {
      $testDetails[2] = ts('Could not release the lock for the database table.');
      $this->error($testDetails);
      $result = mysqli_query($conn, 'DROP TEMPORARY TABLE civicrm_install_temp_table_test');
      return;
    }

    $result = mysqli_query($conn, 'DROP TEMPORARY TABLE civicrm_install_temp_table_test');
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $testDetails
   */
  public function requireMySQLAutoIncrementIncrementOne($server, $username, $password, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = ts('Could not connect to the database server.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, "SHOW variables like 'auto_increment_increment'");
    if (!$result) {
      $testDetails[2] = ts('Could not query database server variables.');
      $this->error($testDetails);
      return;
    }
    else {
      $values = mysqli_fetch_row($result);
      if ($values[1] == 1) {
        $testDetails[3] = ts('MySQL server auto_increment_increment is 1');
      }
      else {
        $this->error($testDetails);
      }
    }
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $minValueKB
   * @param $testDetails
   */
  public function requireMySQLThreadStack($server, $username, $password, $database, $minValueKB, $testDetails) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = ts('Could not connect to the database server.');
      $this->error($testDetails);
      return;
    }

    if (!@mysqli_select_db($conn, $database)) {
      $testDetails[2] = ts('Could not select the database.');
      $this->error($testDetails);
      return;
    }

    $result = mysqli_query($conn, "SHOW VARIABLES LIKE 'thread_stack'"); // bytes => kb
    if (!$result) {
      $testDetails[2] = ts('Could not get information about the thread_stack of the database.');
      $this->error($testDetails);
    }
    else {
      $values = mysqli_fetch_row($result);
      if ($values[1] < (1024 * $minValueKB)) {
        $testDetails[2] = ts('MySQL "thread_stack" is %1 kb', array(1 => ($values[1] / 1024)));
        $this->error($testDetails);
      }
    }
  }

  /**
   * @param $server
   * @param string $username
   * @param $password
   * @param $database
   * @param $testDetails
   * @param bool $onlyRequire
   */
  public function requireDatabaseOrCreatePermissions(
    $server,
    $username,
    $password,
    $database,
    $testDetails,
    $onlyRequire = FALSE
  ) {
    $this->testing($testDetails);
    $conn = $this->connect($server, $username, $password);

    $okay = NULL;
    if (@mysqli_select_db($conn, $database)) {
      $okay = "Database '$database' exists";
    }
    elseif ($onlyRequire) {
      $testDetails[2] = ts("The database: '%1' does not exist.", array(1 => $database));
      $this->error($testDetails);
      return;
    }
    else {
      $query = sprintf("CREATE DATABASE %s", mysqli_real_escape_string($conn, $database));
      if (@mysqli_query($conn, $query)) {
        $okay = ts("Able to create a new database.");
      }
      else {
        $testDetails[2] .= " (" . ts("user '%1' doesn't have CREATE DATABASE permissions.", array(1 => $username)) . ")";
        $this->error($testDetails);
        return;
      }
    }

    if ($okay) {
      $testDetails[3] = $okay;
      $this->testing($testDetails);
    }
  }

  /**
   * @param $varNames
   * @param $errorMessage
   */
  public function requireServerVariables($varNames, $errorMessage) {
    //$this->testing($testDetails);
    foreach ($varNames as $varName) {
      if (!$_SERVER[$varName]) {
        $missing[] = '$_SERVER[' . $varName . ']';
      }
    }
    if (!isset($missing)) {
      return TRUE;
    }
    else {
      $testDetails[2] = " (" . ts('the following PHP variables are missing: %1', array(1 => implode(", ", $missing))) . ")";
      $this->error($testDetails);
    }
  }

  /**
   * @param $testDetails
   *
   * @return bool
   */
  public function isRunningApache($testDetails) {
    $this->testing($testDetails);
    if (function_exists('apache_get_modules') || stristr($_SERVER['SERVER_SIGNATURE'], 'Apache')) {
      return TRUE;
    }

    $this->warning($testDetails);
    return FALSE;
  }

  /**
   * @return string
   */
  public function getBaseDir() {
    return dirname($_SERVER['SCRIPT_FILENAME']) . CIVICRM_DIRECTORY_SEPARATOR;
  }

  /**
   * @param $testDetails
   */
  public function testing($testDetails) {
    if (!$testDetails) {
      return;
    }

    $section = $testDetails[0];
    $test = $testDetails[1];

    $message = ts("OK");
    if (isset($testDetails[3])) {
      $message .= " ($testDetails[3])";
    }

    $this->tests[$section][$test] = array("good", $message);
  }

  /**
   * @param $testDetails
   */
  public function error($testDetails) {
    $section = $testDetails[0];
    $test = $testDetails[1];

    $this->tests[$section][$test] = array("error", $testDetails[2]);
    $this->errors[] = $testDetails;
  }

  /**
   * @param $testDetails
   */
  public function warning($testDetails) {
    $section = $testDetails[0];
    $test = $testDetails[1];

    $this->tests[$section][$test] = array("warning", $testDetails[2]);
    $this->warnings[] = $testDetails;
  }

  /**
   * @return int
   */
  public function hasErrors() {
    return count($this->errors);
  }

  /**
   * @return int
   */
  public function hasWarnings() {
    return count($this->warnings);
  }

}

/**
 * Class Installer
 */
class Installer extends InstallRequirements {
  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   */
  public function createDatabaseIfNotExists($server, $username, $password, $database) {
    $conn = $this->connect($server, $username, $password);

    if (@mysqli_select_db($conn, $database)) {
      // skip if database already present
      return;
    }
    $query = sprintf("CREATE DATABASE %s", mysqli_real_escape_string($conn, $database));
    if (@mysqli_query($conn, $query)) {
    }
    else {
      $errorTitle = ts("Oops! Could not create database %1", array(1 => $database));
      $errorMsg = ts("We encountered an error when attempting to create the database. Please check your MySQL server permissions and the database name and try again.");
      errorDisplayPage($errorTitle, $errorMsg);
    }
  }

  /**
   * @param $config
   *
   * @return mixed
   */
  public function install($config) {
    global $installDirPath;

    // create database if does not exists
    $this->createDatabaseIfNotExists($config['mysql']['server'],
      $config['mysql']['username'],
      $config['mysql']['password'],
      $config['mysql']['database']
    );

    global $installDirPath;

    // Build database
    require_once $installDirPath . 'civicrm.php';
    civicrm_main($config);

    if (!$this->errors) {
      global $installType, $installURLPath;

      $registerSiteURL = "https://civicrm.org/register-site";
      $commonOutputMessage
        = "<li>" . ts("Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a %1>fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check Keep in Touch to receive technical updates (a low volume mailing list).", array(1 => "href='$registerSiteURL' target='_blank'")) . "</li>"
       . "<li>" . ts("We have integrated KCFinder with CKEditor and TinyMCE. This allows a user to upload images. All uploaded images are public.") . "</li>";

      $output = NULL;

      if (
        $installType == 'drupal' &&
        version_compare(VERSION, '7.0-rc1') >= 0
      ) {

        // clean output
        @ob_clean();

        $output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        $output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
        $output .= '<head>';
        $output .= '<title>' . ts('CiviCRM Installed') . '</title>';
        $output .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
        $output .= '</head>';
        $output .= '<body>';
        $output .= '<div style="padding: 1em;"><p class="good">' . ts('CiviCRM has been successfully installed') . '</p>';
        $output .= '<ul>';

        $drupalURL = civicrm_cms_base();
        $drupalPermissionsURL = "{$drupalURL}index.php?q=admin/people/permissions";
        $drupalURL .= "index.php?q=civicrm/admin/configtask&reset=1";

        $output .= "<li>" . ts("Drupal user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(1 => "target='_blank' href='{$drupalPermissionsURL}'", 2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'")) . "</li>";
        $output .= "<li>" . ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$drupalURL'")) . "</li>";
        $output .= $commonOutputMessage;

        // automatically enable CiviCRM module once it is installed successfully.
        // so we need to Bootstrap Drupal, so that we can call drupal hooks.
        global $cmsPath, $crmPath;

        // relative / abosolute paths are not working for drupal, hence using chdir()
        chdir($cmsPath);

        // Force the re-initialisation of the config singleton on the next call
        // since so far, we had used the Config object without loading the DB.
        $c = CRM_Core_Config::singleton(FALSE);
        $c->free();

        include_once "./includes/bootstrap.inc";
        include_once "./includes/unicode.inc";

        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        // prevent session information from being saved.
        drupal_save_session(FALSE);

        // Force the current user to anonymous.
        $original_user = $GLOBALS['user'];
        $GLOBALS['user'] = drupal_anonymous_user();

        // explicitly setting error reporting, since we cannot handle drupal related notices
        error_reporting(1);

        // rebuild modules, so that civicrm is added
        system_rebuild_module_data();

        // now enable civicrm module.
        module_enable(array('civicrm', 'civicrmtheme'));

        // SystemInstallEvent will be called from here with the first call of CRM_Core_Config,
        // which calls Core_BAO_ConfigSetting::applyLocale(), who will default to calling
        // Civi::settings()->get('lcMessages');
        // Therefore, we need to pass the seedLanguage before that.
        global $civicrm_setting;
        $civicrm_setting['domain']['lcMessages'] = $config['seedLanguage'];

        // clear block, page, theme, and hook caches
        drupal_flush_all_caches();

        //add basic drupal permissions
        civicrm_install_set_drupal_perms();

        // restore the user.
        $GLOBALS['user'] = $original_user;
        drupal_save_session(TRUE);

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</body>';
        $output .= '</html>';
        echo $output;
      }
      elseif (
        $installType == 'backdrop'
      ) {

        // clean output
        @ob_clean();

        $output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        $output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
        $output .= '<head>';
        $output .= '<title>' . ts('CiviCRM Installed') . '</title>';
        $output .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
        $output .= '</head>';
        $output .= '<body>';
        $output .= '<div style="padding: 1em;"><p class="good">' . ts('CiviCRM has been successfully installed') . '</p>';
        $output .= '<ul>';

        $backdropURL = civicrm_cms_base();
        $backdropPermissionsURL = "{$backdropURL}index.php?q=admin/config/people/permissions";
        $backdropURL .= "index.php?q=civicrm/admin/configtask&reset=1";

        $output .= "<li>" . ts("Backdrop user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(1 => "target='_blank' href='{$backdropPermissionsURL}'", 2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'")) . "</li>";
        $output .= "<li>" . ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$backdropURL'")) . "</li>";
        $output .= $commonOutputMessage;

        // automatically enable CiviCRM module once it is installed successfully.
        // so we need to Bootstrap Drupal, so that we can call drupal hooks.
        global $cmsPath, $crmPath;

        // relative / abosolute paths are not working for drupal, hence using chdir()
        chdir($cmsPath);

        // Force the re-initialisation of the config singleton on the next call
        // since so far, we had used the Config object without loading the DB.
        $c = CRM_Core_Config::singleton(FALSE);
        $c->free();

        include_once "./core/includes/bootstrap.inc";
        include_once "./core/includes/unicode.inc";
        include_once "./core/includes/config.inc";

        backdrop_bootstrap(BACKDROP_BOOTSTRAP_FULL);

        // prevent session information from being saved.
        backdrop_save_session(FALSE);

        // Force the current user to anonymous.
        $original_user = $GLOBALS['user'];
        $GLOBALS['user'] = backdrop_anonymous_user();

        // explicitly setting error reporting, since we cannot handle drupal related notices
        error_reporting(1);

        // rebuild modules, so that civicrm is added
        system_rebuild_module_data();

        // now enable civicrm module.
        module_enable(array('civicrm', 'civicrmtheme'));

        // clear block, page, theme, and hook caches
        backdrop_flush_all_caches();

        //add basic backdrop permissions
        civicrm_install_set_backdrop_perms();

        // restore the user.
        $GLOBALS['user'] = $original_user;
        backdrop_save_session(TRUE);

        //change the default language to one chosen
        if (isset($config['seedLanguage']) && $config['seedLanguage'] != 'en_US') {
          civicrm_api3('Setting', 'create', array(
              'domain_id' => 'current_domain',
              'lcMessages' => $config['seedLanguage'],
            )
          );
        }

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '</body>';
        $output .= '</html>';
        echo $output;
      }
      elseif ($installType == 'drupal' && version_compare(VERSION, '6.0') >= 0) {
        // clean output
        @ob_clean();

        $output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        $output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
        $output .= '<head>';
        $output .= '<title>' . ts('CiviCRM Installed') . '</title>';
        $output .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
        $output .= '</head>';
        $output .= '<body>';
        $output .= '<div style="padding: 1em;"><p class="good">' . ts("CiviCRM has been successfully installed") . '</p>';
        $output .= '<ul>';

        $drupalURL = civicrm_cms_base();
        $drupalPermissionsURL = "{$drupalURL}index.php?q=admin/user/permissions";
        $drupalURL .= "index.php?q=civicrm/admin/configtask&reset=1";

        $output .= "<li>" . ts("Drupal user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(1 => "target='_blank' href='{$drupalPermissionsURL}'", 2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'")) . "</li>";
        $output .= "<li>" . ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$drupalURL'")) . "</li>";
        $output .= $commonOutputMessage;

        // explicitly setting error reporting, since we cannot handle drupal related notices
        error_reporting(1);

        // automatically enable CiviCRM module once it is installed successfully.
        // so we need to Bootstrap Drupal, so that we can call drupal hooks.
        global $cmsPath, $crmPath;

        // relative / abosolute paths are not working for drupal, hence using chdir()
        chdir($cmsPath);

        // Force the re-initialisation of the config singleton on the next call
        // since so far, we had used the Config object without loading the DB.
        $c = CRM_Core_Config::singleton(FALSE);
        $c->free();

        include_once "./includes/bootstrap.inc";
        drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        // rebuild modules, so that civicrm is added
        module_rebuild_cache();

        // now enable civicrm module.
        module_enable(array('civicrm'));

        // clear block, page, theme, and hook caches
        drupal_flush_all_caches();

        //add basic drupal permissions
        db_query('UPDATE {permission} SET perm = CONCAT( perm, \', access CiviMail subscribe/unsubscribe pages, access all custom data, access uploaded files, make online contributions, profile create, profile edit, profile view, register for events, view event info\') WHERE rid IN (1, 2)');

        echo $output;
      }
      elseif ($installType == 'wordpress') {
        echo '<h1>' . ts('CiviCRM Installed') . '</h1>';
        echo '<div style="padding: 1em;"><p style="background-color: #0C0; border: 1px #070 solid; color: white;">' . ts("CiviCRM has been successfully installed") . '</p>';
        echo '<ul>';

        $cmsURL = civicrm_cms_base();
        $cmsURL .= "wp-admin/admin.php?page=CiviCRM&q=civicrm/admin/configtask&reset=1";
        $wpPermissionsURL = "wp-admin/admin.php?page=CiviCRM&q=civicrm/admin/access/wp-permissions&reset=1";

        $output .= "<li>" . ts("WordPress user permissions have been automatically set - giving Anonymous and Subscribers access to public CiviCRM forms and features. We recommend that you <a %1>review these permissions</a> to ensure that they are appropriate for your requirements (<a %2>learn more...</a>)", array(1 => "target='_blank' href='{$wpPermissionsURL}'", 2 => "target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'")) . "</li>";
        $output .= "<li>" . ts("Use the <a %1>Configuration Checklist</a> to review and configure settings for your new site", array(1 => "target='_blank' href='$cmsURL'")) . "</li>";
        $output .= $commonOutputMessage;

        $output .= '</ul>';
        $output .= '</div>';
        echo $output;

        $c = CRM_Core_Config::singleton(FALSE);
        $c->free();
        $wpInstallRedirect = admin_url('admin.php?page=CiviCRM&q=civicrm&reset=1');
        echo "<script>
         window.location = '$wpInstallRedirect';
        </script>";
      }
    }

    return $this->errors;
  }

}

function civicrm_install_set_drupal_perms() {
  if (!function_exists('db_select')) {
    db_query('UPDATE {permission} SET perm = CONCAT( perm, \', access CiviMail subscribe/unsubscribe pages, access all custom data, access uploaded files, make online contributions, profile listings and forms, register for events, view event info, view event participants\') WHERE rid IN (1, 2)');
  }
  else {
    $perms = array(
      'access all custom data',
      'access uploaded files',
      'make online contributions',
      'profile create',
      'profile edit',
      'profile view',
      'register for events',
      'view event info',
      'view event participants',
      'access CiviMail subscribe/unsubscribe pages',
    );

    // Adding a permission that has not yet been assigned to a module by
    // a hook_permission implementation results in a database error.
    // CRM-9042
    $allPerms = array_keys(module_invoke_all('permission'));
    foreach (array_diff($perms, $allPerms) as $perm) {
      watchdog('civicrm',
        'Cannot grant the %perm permission because it does not yet exist.',
        array('%perm' => $perm),
        WATCHDOG_ERROR
      );
    }
    $perms = array_intersect($perms, $allPerms);
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, $perms);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, $perms);
  }
}

function civicrm_install_set_backdrop_perms() {
  $perms = array(
    'access all custom data',
    'access uploaded files',
    'make online contributions',
    'profile create',
    'profile edit',
    'profile view',
    'register for events',
    'view event info',
    'view event participants',
    'access CiviMail subscribe/unsubscribe pages',
  );

  // Adding a permission that has not yet been assigned to a module by
  // a hook_permission implementation results in a database error.
  // CRM-9042
  $allPerms = array_keys(module_invoke_all('permission'));
  foreach (array_diff($perms, $allPerms) as $perm) {
    watchdog('civicrm',
      'Cannot grant the %perm permission because it does not yet exist.',
      array('%perm' => $perm),
      WATCHDOG_ERROR
    );
  }
  $perms = array_intersect($perms, $allPerms);
  user_role_grant_permissions(BACKDROP_AUTHENTICATED_ROLE, $perms);
  user_role_grant_permissions(BACKDROP_ANONYMOUS_ROLE, $perms);
}

/**
 * @param $cmsPath
 * @param $str
 *
 * @return string
 */
function getSiteDir($cmsPath, $str) {
  static $siteDir = '';

  if ($siteDir) {
    return $siteDir;
  }

  $sites = CIVICRM_DIRECTORY_SEPARATOR . 'sites' . CIVICRM_DIRECTORY_SEPARATOR;
  $modules = CIVICRM_DIRECTORY_SEPARATOR . 'modules' . CIVICRM_DIRECTORY_SEPARATOR;
  preg_match("/" . preg_quote($sites, CIVICRM_DIRECTORY_SEPARATOR) .
    "([\-a-zA-Z0-9_.]+)" .
    preg_quote($modules, CIVICRM_DIRECTORY_SEPARATOR) . "/",
    $_SERVER['SCRIPT_FILENAME'], $matches
  );
  $siteDir = isset($matches[1]) ? $matches[1] : 'default';

  if (strtolower($siteDir) == 'all') {
    // For this case - use drupal's way of finding out multi-site directory
    $uri = explode(CIVICRM_DIRECTORY_SEPARATOR, $_SERVER['SCRIPT_FILENAME']);
    $server = explode('.', implode('.', array_reverse(explode(':', rtrim($_SERVER['HTTP_HOST'], '.')))));
    for ($i = count($uri) - 1; $i > 0; $i--) {
      for ($j = count($server); $j > 0; $j--) {
        $dir = implode('.', array_slice($server, -$j)) . implode('.', array_slice($uri, 0, $i));
        if (file_exists($cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
          'sites' . CIVICRM_DIRECTORY_SEPARATOR . $dir
        )) {
          $siteDir = $dir;
          return $siteDir;
        }
      }
    }
    $siteDir = 'default';
  }

  return $siteDir;
}

/**
 * @param $errorTitle
 * @param $errorMsg
 * @param $showRefer
 */
function errorDisplayPage($errorTitle, $errorMsg, $showRefer = TRUE) {

  // Add a link to the documentation
  if ($showRefer) {
    if (is_callable(array('CRM_Utils_System', 'docURL2'))) {
      $docLink = CRM_Utils_System::docURL2('Installation and Upgrades', FALSE, 'Installation Guide', NULL, NULL, "wiki");
    }
    else {
      $docLink = '';
    }

    if (function_exists('ts')) {
      $errorMsg .= '<p>' . ts("Refer to the online documentation for more information: ") . $docLink . '</p>';
    }
    else {
      $errorMsg .= '<p>' . 'Refer to the online documentation for more information: ' . $docLink . '</p>';
    }
  }

  include 'error.html';
  exit();
}
