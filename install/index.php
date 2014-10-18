<?php

/**
 * Note that this installer has been based of the SilverStripe installer.
 * You can get more information from the SilverStripe Website at
 * http://www.silverstripe.com/. Please check
 * http://www.silverstripe.com/licensing for licensing details.
 *
 * Copyright (c) 2006-7, SilverStripe Limited - www.silverstripe.com
 * All rights reserved.
 *
 * Changes and modifications (c) 2007-8 by CiviCRM LLC
 *
 */

/**
 * CiviCRM Installer
 */

ini_set('max_execution_time', 3000);

if (stristr(PHP_OS, 'WIN')) {
  define('CIVICRM_DIRECTORY_SEPARATOR', '/');
  define('CIVICRM_WINDOWS', 1 );
}
else {
  define('CIVICRM_DIRECTORY_SEPARATOR', DIRECTORY_SEPARATOR);
  define('CIVICRM_WINDOWS', 0 );
}

// set installation type - drupal
if (!session_id()) {
  session_start();
}

// unset civicrm session if any
if (array_key_exists('CiviCRM', $_SESSION)) {
  unset($_SESSION['CiviCRM']);
}

if (isset($_GET['civicrm_install_type'])) {
  $_SESSION['civicrm_install_type'] = $_GET['civicrm_install_type'];
}
else {
  if (!isset($_SESSION['civicrm_install_type'])) {
    $_SESSION['civicrm_install_type'] = "drupal";
  }
}

global $installType;
$installType = strtolower($_SESSION['civicrm_install_type']);

if (!in_array($installType, array(
  'drupal', 'wordpress'))) {
  $errorTitle = "Oops! Unsupported installation mode";
  $errorMsg = "";
  errorDisplayPage($errorTitle, $errorMsg);
}

global $crmPath;
global $installDirPath;
global $installURLPath;
if ($installType == 'drupal') {
  $crmPath = dirname(dirname($_SERVER['SCRIPT_FILENAME']));
  $installDirPath = $installURLPath = '';
}
elseif ($installType == 'wordpress') {
  $crmPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR;
  $installDirPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR;

  $installURLPath = WP_PLUGIN_URL . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'civicrm' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR;
}

set_include_path(get_include_path() . PATH_SEPARATOR . $crmPath);

require_once $crmPath . '/CRM/Core/ClassLoader.php';
CRM_Core_ClassLoader::singleton()->register();

$docLink = CRM_Utils_System::docURL2('Installation and Upgrades', FALSE, 'Installation Guide',NULL,NULL,"wiki");

if ($installType == 'drupal') {
  //lets check only /modules/.
  $pattern = '/' . preg_quote(CIVICRM_DIRECTORY_SEPARATOR . 'modules', CIVICRM_DIRECTORY_SEPARATOR) . '/';

  if (!preg_match($pattern,
      str_replace("\\", "/", $_SERVER['SCRIPT_FILENAME'])
    )) {
    $errorTitle = "Oops! Please Correct Your Install Location";
    $errorMsg = "Please untar (uncompress) your downloaded copy of CiviCRM in the <strong>" . implode(CIVICRM_DIRECTORY_SEPARATOR, array(
      'sites', 'all', 'modules')) . "</strong> directory below your Drupal root directory. Refer to the online " . $docLink . " for more information.";
    errorDisplayPage($errorTitle, $errorMsg);
  }
}

// Load civicrm database config
if (isset($_REQUEST['mysql'])) {
  $databaseConfig = $_REQUEST['mysql'];
}
else {
  $databaseConfig = array(
    "server" => "localhost",
    "username" => "civicrm",
    "password" => "",
    "database" => "civicrm",
  );
}

if ($installType == 'drupal') {
  // Load drupal database config
  if (isset($_REQUEST['drupal'])) {
    $drupalConfig = $_REQUEST['drupal'];
  }
  else {
    $drupalConfig = array(
      "server" => "localhost",
      "username" => "drupal",
      "password" => "",
      "database" => "drupal",
    );
  }
}

$loadGenerated = 0;
if (isset($_REQUEST['loadGenerated'])) {
  $loadGenerated = 1;
}

require_once dirname(__FILE__) . CIVICRM_DIRECTORY_SEPARATOR . 'langs.php';
foreach ($langs as $locale => $_) {
  if ($locale == 'en_US') {
    continue;
  }
  if (!file_exists(implode(CIVICRM_DIRECTORY_SEPARATOR, array($crmPath, 'sql', "civicrm_data.$locale.mysql"))))unset($langs[$locale]);
}

$seedLanguage = 'en_US';
if (isset($_REQUEST['seedLanguage']) and isset($langs[$_REQUEST['seedLanguage']])) {
  $seedLanguage = $_REQUEST['seedLanguage'];
}

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
elseif ($installType == 'wordpress') {
  $cmsPath = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'civicrm';
  $alreadyInstalled = file_exists($cmsPath . CIVICRM_DIRECTORY_SEPARATOR .
    'civicrm.settings.php'
  );
}

// Exit with error if CiviCRM has already been installed.
if ($alreadyInstalled) {
  $errorTitle = "Oops! CiviCRM is Already Installed";
  if ($installType == 'drupal') {

    $errorMsg = "CiviCRM has already been installed in this Drupal site. <ul><li>To <strong>start over</strong>, you must delete or rename the existing CiviCRM settings file - <strong>civicrm.settings.php</strong> - from <strong>" . implode(CIVICRM_DIRECTORY_SEPARATOR, array(
      '[your Drupal root directory]', 'sites', $siteDir)) . "</strong>.</li><li>To <strong>upgrade an existing installation</strong>, refer to the online " . $docLink . ".</li></ul>";
  }
  elseif ($installType == 'wordpress') {
    $errorMsg = "CiviCRM has already been installed in this WordPress site. <ul><li>To <strong>start over</strong>, you must delete or rename the existing CiviCRM settings file - <strong>civicrm.settings.php</strong> - from <strong>" . $cmsPath . "</strong>.</li><li>To <strong>upgrade an existing installation</strong>, refer to the online " . $docLink . ".</li></ul>";
  }
  errorDisplayPage($errorTitle, $errorMsg);
}

$versionFile = $crmPath . CIVICRM_DIRECTORY_SEPARATOR . 'civicrm-version.php';
if (file_exists($versionFile)) {
  require_once ($versionFile);
  $civicrm_version = civicrmVersion();
}
else {
  $civicrm_version = 'unknown';
}

if ($installType == 'drupal') {
  // Ensure that they have downloaded the correct version of CiviCRM
  if ($civicrm_version['cms'] != 'Drupal' &&
    $civicrm_version['cms'] != 'Drupal6'
  ) {
    $errorTitle = "Oops! Incorrect CiviCRM Version";
    $errorMsg = "This installer can only be used for the Drupal version of CiviCRM. Refer to the online " . $docLink . " for information about installing CiviCRM on PHP4 servers OR installing CiviCRM for Joomla!";
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

  if (!defined('VERSION') or version_compare(VERSION, '6.0') < 0) {
    $errorTitle = "Oops! Incorrect Drupal Version";
    $errorMsg = "This version of CiviCRM can only be used with Drupal 6.x or 7.x. Please ensure that '" . implode("' or '", $drupalVersionFiles) . "' exists if you are running Drupal 7.0 and over. Refer to the online " . $docLink . " for information about installing CiviCRM.";
    errorDisplayPage($errorTitle, $errorMsg);
  }
}
elseif ($installType == 'wordpress') {
  //HACK for now
  $civicrm_version['cms'] = 'WordPress';

  // Ensure that they have downloaded the correct version of CiviCRM
  if ($civicrm_version['cms'] != 'WordPress') {
    $errorTitle = "Oops! Incorrect CiviCRM Version";
    $errorMsg = "This installer can only be used for the WordPress version of CiviCRM. Refer to the online " . $docLink . " for information about installing CiviCRM for Drupal or Joomla!";
    errorDisplayPage($errorTitle, $errorMsg);
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
}

// Actual processor
if (isset($_REQUEST['go']) && !$req->hasErrors() && !$dbReq->hasErrors()) {
  // Confirm before reinstalling
  if (!isset($_REQUEST['force_reinstall']) && $alreadyInstalled) {
    include ($installDirPath . 'template.html');
  }
  else {
    $inst = new Installer();
    $inst->install($_REQUEST);
  }

  // Show the config form
}
else {
  include ($installDirPath . 'template.html');
}

/**
 * This class checks requirements
 * Each of the requireXXX functions takes an argument which gives a user description of the test.  It's an array
 * of 3 parts:
 *  $description[0] - The test catetgory
 *  $description[1] - The test title
 *  $description[2] - The test error to show, if it goes wrong
 */
class InstallRequirements {
  var $errors, $warnings, $tests;

  // @see CRM_Upgrade_Form::MINIMUM_THREAD_STACK
  const MINIMUM_THREAD_STACK = 192;

  /**
   * Just check that the database configuration is okay
   */
  function checkdatabase($databaseConfig, $dbName) {
    if ($this->requireFunction('mysql_connect',
        array(
          "PHP Configuration",
          "MySQL support",
          "MySQL support not included in PHP.",
        )
      )) {
      $this->requireMySQLServer($databaseConfig['server'],
        array(
          "MySQL $dbName Configuration",
          "Does the server exist",
          "Can't find the a MySQL server on '$databaseConfig[server]'",
          $databaseConfig['server'],
        )
      );
      if ($this->requireMysqlConnection($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          array(
            "MySQL $dbName Configuration",
            "Are the access credentials correct",
            "That username/password doesn't work",
          )
        )) {
        @$this->requireMySQLVersion("5.1",
          array(
            "MySQL $dbName Configuration",
            "MySQL version at least 5.1",
            "MySQL version 5.1 or higher is required, you only have ",
            "MySQL " . mysql_get_server_info(),
          )
        );
        $this->requireMySQLAutoIncrementIncrementOne($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          array(
            "MySQL $dbName Configuration",
            "Is auto_increment_increment set to 1",
            "An auto_increment_increment value greater than 1 is not currently supported. Please see issue CRM-7923 for further details and potential workaround.",
          )
        );
        $this->requireMySQLThreadStack($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          self::MINIMUM_THREAD_STACK,
          array(
            "MySQL $dbName Configuration",
            "Does MySQL thread_stack meet minimum (" . self::MINIMUM_THREAD_STACK . "k)",
            "", // "The MySQL thread_stack does not meet minimum " . CRM_Upgrade_Form::MINIMUM_THREAD_STACK . "k. Please update thread_stack in my.cnf.",
          )
        );
      }
      $onlyRequire = ($dbName == 'Drupal') ? TRUE : FALSE;
      $this->requireDatabaseOrCreatePermissions(
        $databaseConfig['server'],
        $databaseConfig['username'],
        $databaseConfig['password'],
        $databaseConfig['database'],
        array(
          "MySQL $dbName Configuration",
          "Can I access/create the database",
          "I can't create new databases and the database '$databaseConfig[database]' doesn't exist",
        ),
        $onlyRequire
      );
      if ($dbName != 'Drupal') {
        $this->requireMySQLInnoDB($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            "MySQL $dbName Configuration",
            "Can I access/create InnoDB tables in the database",
            "Unable to create InnoDB tables. MySQL InnoDB support is required for CiviCRM but is either not available or not enabled in this MySQL database server.",
          )
        );
        $this->requireMySQLTempTables($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            "MySQL $dbName Configuration",
            'Can I create temporary tables in the database',
            'Unable to create temporary tables. This MySQL user is missing the CREATE TEMPORARY TABLES privilege.',
          )
        );
        $this->requireMySQLLockTables($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            "MySQL $dbName Configuration",
            'Can I create lock tables in the database',
            'Unable to lock tables. This MySQL user is missing the LOCK TABLES privilege.',
          )
        );
        $this->requireMySQLTrigger($databaseConfig['server'],
          $databaseConfig['username'],
          $databaseConfig['password'],
          $databaseConfig['database'],
          array(
            "MySQL $dbName Configuration",
            'Can I create triggers in the database',
            'Unable to create triggers. This MySQL user is missing the CREATE TRIGGERS  privilege.',
          )
        );
      }
    }
  }

  /**
   * Check everything except the database
   */
  function check() {
    global $crmPath, $installType;

    $this->errors = NULL;

    $this->requirePHPVersion('5.3.3', array("PHP Configuration", "PHP5 installed", NULL, "PHP version " . phpversion()));

    // Check that we can identify the root folder successfully
    $this->requireFile($crmPath . CIVICRM_DIRECTORY_SEPARATOR . 'README.txt',
      array(
        "File permissions",
        "Does the webserver know where files are stored?",
        "The webserver isn't letting me identify where files are stored.",
        $this->getBaseDir(),
      ),
      TRUE
    );

    // CRM-6485: make sure the path does not contain PATH_SEPARATOR, as we don’t know how to escape it
    $this->requireNoPathSeparator(
      array(
        'File permissions',
        'does the CiviCRM path contain PATH_SEPARATOR?',
        'the ' . $this->getBaseDir() . ' path contains PATH_SEPARATOR (the ' . PATH_SEPARATOR . ' character)',
        $this->getBaseDir(),
      )
    );

    $requiredDirectories = array('CRM', 'packages', 'templates', 'js', 'api', 'i', 'sql');
    foreach ($requiredDirectories as $dir) {
      $this->requireFile($crmPath . CIVICRM_DIRECTORY_SEPARATOR . $dir,
        array(
          "File permissions", "$dir folder exists", "There is no $dir folder"), TRUE
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
    elseif ($installType == 'wordpress') {
      // make sure that we can write to plugins/civicrm  and plugins/files/
      $writableDirectories = array(WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'files', $cmsPath);
    }

    foreach ($writableDirectories as $dir) {
      $dirName = CIVICRM_WINDOWS ? $dir : CIVICRM_DIRECTORY_SEPARATOR . $dir;
      $this->requireWriteable($dirName,
        array("File permissions", "Is the $dir folder writeable?", NULL),
        TRUE
      );
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
      $webserver = "I can't tell what webserver you are running";
    }

    // Check for $_SERVER configuration
    $this->requireServerVariables(array('SCRIPT_NAME', 'HTTP_HOST', 'SCRIPT_FILENAME'), array("Webserver config", "Recognised webserver", "You seem to be using an unsupported webserver.  The server variables SCRIPT_NAME, HTTP_HOST, SCRIPT_FILENAME need to be set."));

    // Check for MySQL support
    $this->requireFunction('mysql_connect',
      array("PHP Configuration", "MySQL support", "MySQL support not included in PHP.")
    );

    // Check for JSON support
    $this->requireFunction('json_encode',
      array("PHP Configuration", "JSON support", "JSON support not included in PHP.")
    );

    // Check for xcache_isset and emit warning if exists
    $this->checkXCache(array(
      "PHP Configuration",
        "XCache compatibility",
        "XCache is installed and there are known compatibility issues between XCache and CiviCRM. Consider using an alternative PHP caching mechanism or disable PHP caching altogether.",
      ));

    // Check memory allocation
    $this->requireMemory(32 * 1024 * 1024,
      64 * 1024 * 1024,
      array(
        "PHP Configuration",
        "Memory allocated (PHP config option 'memory_limit')",
        "CiviCRM needs a minimum of 32M allocated to PHP, but recommends 64M.",
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
  function requireMemory($min, $recommended, $testDetails) {
    $this->testing($testDetails);
    $mem = $this->getPHPMemory();

    if ($mem < $min && $mem > 0) {
      $testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
      $this->error($testDetails);
    }
    elseif ($mem < $recommended && $mem > 0) {
      $testDetails[2] .= " You only have " . ini_get("memory_limit") . " allocated";
      $this->warning($testDetails);
    }
    elseif ($mem == 0) {
      $testDetails[2] .= " We can't determine how much memory you have allocated. Install only if you're sure you've allocated at least 20 MB.";
      $this->warning($testDetails);
    }
  }

  /**
   * @return float
   */
  function getPHPMemory() {
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

  function listErrors() {
    if ($this->errors) {
      echo "<p>The following problems are preventing me from installing CiviCRM:</p>";
      foreach ($this->errors as $error) {
        echo "<li>" . htmlentities($error) . "</li>";
      }
    }
  }

  /**
   * @param null $section
   */
  function showTable($section = NULL) {
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
   * @param $funcName
   * @param $testDetails
   *
   * @return bool
   */
  function requireFunction($funcName, $testDetails) {
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
  function checkXCache($testDetails) {
    if (function_exists('xcache_isset') &&
      ini_get('xcache.size') > 0
    ) {
      $this->testing($testDetails);
      $this->warning($testDetails);
    }
  }

  /**
   * @param $minVersion
   * @param $testDetails
   * @param null $maxVersion
   */
  function requirePHPVersion($minVersion, $testDetails, $maxVersion = NULL) {

    $this->testing($testDetails);

    $phpVersion      = phpversion();
    $aboveMinVersion = version_compare($phpVersion, $minVersion) >= 0;
    $belowMaxVersion = $maxVersion ? version_compare($phpVersion, $maxVersion) < 0 : TRUE;

    if ($maxVersion && $aboveMinVersion && $belowMaxVersion) {
      return TRUE;
    }
    elseif (!$maxVersion && $aboveMinVersion) {
      return TRUE;
    }

    if (!$testDetails[2]) {
      if (!$aboveMinVersion) {
        $testDetails[2] = "You need PHP version $minVersion or later, only {$phpVersion} is installed.  Please upgrade your server, or ask your web-host to do so.";
      }
      else {
        $testDetails[2] = "PHP version {$phpVersion} is not supported. PHP version earlier than $maxVersion is required. You might want to downgrade your server, or ask your web-host to do so.";
      }
    }

    $this->error($testDetails);
  }

  /**
   * @param $filename
   * @param $testDetails
   * @param bool $absolute
   */
  function requireFile($filename, $testDetails, $absolute = FALSE) {
    $this->testing($testDetails);
    if (!$absolute) {
      $filename = $this->getBaseDir() . $filename;
    }
    if (!file_exists($filename)) {
      $testDetails[2] .= " (file '$filename' not found)";
      $this->error($testDetails);
    }
  }

  /**
   * @param $testDetails
   */
  function requireNoPathSeparator($testDetails) {
    $this->testing($testDetails);
    if (substr_count($this->getBaseDir(), PATH_SEPARATOR)) {
      $this->error($testDetails);
    }
  }

  /**
   * @param $filename
   * @param $testDetails
   */
  function requireNoFile($filename, $testDetails) {
    $this->testing($testDetails);
    $filename = $this->getBaseDir() . $filename;
    if (file_exists($filename)) {
      $testDetails[2] .= " (file '$filename' found)";
      $this->error($testDetails);
    }
  }

  /**
   * @param $filename
   * @param $testDetails
   */
  function moveFileOutOfTheWay($filename, $testDetails) {
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
   * @param $filename
   * @param $testDetails
   * @param bool $absolute
   */
  function requireWriteable($filename, $testDetails, $absolute = FALSE) {
    $this->testing($testDetails);
    if (!$absolute) {
      $filename = $this->getBaseDir() . $filename;
    }

    if (!is_writeable($filename)) {
      $name = NULL;
      if (function_exists('posix_getpwuid')) {
        $user = posix_getpwuid(posix_geteuid());
        $name = '- ' . $user['name'] . ' -';
      }

      if (!isset($testDetails[2])) {
        $testDetails[2] = NULL;
      }
      $testDetails[2] .= "The user account used by your web-server $name needs to be granted write access to the following directory in order to configure the CiviCRM settings file:\n$filename";
      $this->error($testDetails);
    }
  }

  /**
   * @param $moduleName
   * @param $testDetails
   */
  function requireApacheModule($moduleName, $testDetails) {
    $this->testing($testDetails);
    if (!in_array($moduleName, apache_get_modules())) {
      $this->error($testDetails);
    }
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $testDetails
   */
  function requireMysqlConnection($server, $username, $password, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);

    if ($conn) {
      return TRUE;
    }
    else {
      $testDetails[2] .= ": " . mysql_error();
      $this->error($testDetails);
    }
  }

  /**
   * @param $server
   * @param $testDetails
   */
  function requireMySQLServer($server, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, NULL, NULL);

    if ($conn || mysql_errno() < 2000) {
      return TRUE;
    }
    else {
      $testDetails[2] .= ": " . mysql_error();
      $this->error($testDetails);
    }
  }

  /**
   * @param $version
   * @param $testDetails
   */
  function requireMySQLVersion($version, $testDetails) {
    $this->testing($testDetails);

    if (!mysql_get_server_info()) {
      $testDetails[2] = 'Cannot determine the version of MySQL installed. Please ensure at least version 4.1 is installed.';
      $this->warning($testDetails);
    }
    else {
      list($majorRequested, $minorRequested) = explode('.', $version);
      list($majorHas, $minorHas) = explode('.', mysql_get_server_info());

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
   * @param $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  function requireMySQLInnoDB($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] .= ' Could not determine if mysql has innodb support. Assuming no';
      $this->error($testDetails);
      return;
    }

    $innodb_support = FALSE;
    $result = mysql_query("SHOW ENGINES", $conn);
    while ($values = mysql_fetch_array($result)) {
      if ($values['Engine'] == 'InnoDB') {
        if (strtolower($values['Support']) == 'yes' ||
          strtolower($values['Support']) == 'default'
        ) {
          $innodb_support = TRUE;
        }
      }
    }
    if ($innodb_support) {
      $testDetails[3] = 'MySQL server does have innodb support';
    }
    else {
      $testDetails[2] .= ' Could not determine if mysql has innodb support. Assuming no';
    }
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  function requireMySQLTempTables($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = 'Could not login to the database.';
      $this->error($testDetails);
      return;
    }

    if (!@mysql_select_db($database, $conn)) {
      $testDetails[2] = 'Could not select the database.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query('CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)', $conn);
    if (!$result) {
      $testDetails[2] = 'Could not create a temp table.';
      $this->error($testDetails);
    }
    $result = mysql_query('DROP TEMPORARY TABLE civicrm_install_temp_table_test');
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  function requireMySQLTrigger($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = 'Could not login to the database.';
      $this->error($testDetails);
      return;
    }

    if (!@mysql_select_db($database, $conn)) {
      $testDetails[2] = 'Could not select the database.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query('CREATE TABLE civicrm_install_temp_table_test (test text)', $conn);
    if (!$result) {
      $testDetails[2] = 'Could not create a table.';
      $this->error($testDetails);
    }

    $result = mysql_query('CREATE TRIGGER civicrm_install_temp_table_test_trigger BEFORE INSERT ON civicrm_install_temp_table_test FOR EACH ROW BEGIN END');
    if (!$result) {
      mysql_query('DROP TABLE civicrm_install_temp_table_test');
      $testDetails[2] = 'Could not create a trigger.';
      $this->error($testDetails);
    }


    mysql_query('DROP TRIGGER civicrm_install_temp_table_test_trigger');
    mysql_query('DROP TABLE civicrm_install_temp_table_test');
  }


  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   * @param $testDetails
   */
  function requireMySQLLockTables($server, $username, $password, $database, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = 'Could not login to the database.';
      $this->error($testDetails);
      return;
    }

    if (!@mysql_select_db($database, $conn)) {
      $testDetails[2] = 'Could not select the database.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query('CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)', $conn);
    if (!$result) {
      $testDetails[2] = 'Could not create a table.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query('LOCK TABLES civicrm_install_temp_table_test WRITE', $conn);
    if (!$result) {
      $testDetails[2] = 'Could not obtain a write lock for the table.';
      $this->error($testDetails);
      $result = mysql_query('DROP TEMPORARY TABLE civicrm_install_temp_table_test');
      return;
    }

    $result = mysql_query('UNLOCK TABLES', $conn);
    if (!$result) {
      $testDetails[2] = 'Could not release the lock for the table.';
      $this->error($testDetails);
      $result = mysql_query('DROP TEMPORARY TABLE civicrm_install_temp_table_test');
      return;
    }

    $result = mysql_query('DROP TEMPORARY TABLE civicrm_install_temp_table_test');
    return;
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $testDetails
   */
  function requireMySQLAutoIncrementIncrementOne($server, $username, $password, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = 'Could not connect to the database server.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query("SHOW variables like 'auto_increment_increment'", $conn);
    if (!$result) {
      $testDetails[2] = 'Could not query database server variables.';
      $this->error($testDetails);
      return;
    }
    else {
      $values = mysql_fetch_row($result);
      if ($values[1] == 1) {
        $testDetails[3] = 'MySQL server auto_increment_increment is 1';
      }
      else {
        $this->error($testDetails);
      }
    }
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   * @param $minValueKB
   * @param $testDetails
   */
  function requireMySQLThreadStack($server, $username, $password, $database, $minValueKB, $testDetails) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);
    if (!$conn) {
      $testDetails[2] = 'Could not login to the database.';
      $this->error($testDetails);
      return;
    }

    if (!@mysql_select_db($database, $conn)) {
      $testDetails[2] = 'Could not select the database.';
      $this->error($testDetails);
      return;
    }

    $result = mysql_query("SHOW VARIABLES LIKE 'thread_stack'", $conn); // bytes => kb
    if (!$result) {
      $testDetails[2] = 'Could not query thread_stack.';
      $this->error($testDetails);
    } else {
      $values = mysql_fetch_row($result);
      if ($values[1] < (1024*$minValueKB)) {
        $testDetails[2] = 'MySQL "thread_stack" is ' . ($values[1]/1024) . 'k';
        $this->error($testDetails);
      }
    }
  }

  /**
   * @param $server
   * @param $username
   * @param $password
   * @param $database
   * @param $testDetails
   * @param bool $onlyRequire
   */
  function requireDatabaseOrCreatePermissions($server,
    $username,
    $password,
    $database,
    $testDetails,
    $onlyRequire = FALSE
  ) {
    $this->testing($testDetails);
    $conn = @mysql_connect($server, $username, $password);

    $okay = NULL;
    if (@mysql_select_db($database)) {
      $okay = "Database '$database' exists";
    }
    elseif ($onlyRequire) {
      $testDetails[2] = "The database: '$database' does not exist";
      $this->error($testDetails);
      return;
    }
    else {
      if (@mysql_query("CREATE DATABASE $database")) {
        $okay = "Able to create a new database";
      }
      else {
        $testDetails[2] .= " (user '$username' doesn't have CREATE DATABASE permissions.)";
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
  function requireServerVariables($varNames, $errorMessage) {
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
      $testDetails[2] = " (the following PHP variables are missing: " . implode(", ", $missing) . ")";
      $this->error($testDetails);
    }
  }

  /**
   * @param $testDetails
   *
   * @return bool
   */
  function isRunningApache($testDetails) {
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
  function getBaseDir() {
    return dirname($_SERVER['SCRIPT_FILENAME']) . CIVICRM_DIRECTORY_SEPARATOR;
  }

  /**
   * @param $testDetails
   */
  function testing($testDetails) {
    if (!$testDetails) {
      return;
    }

    $section = $testDetails[0];
    $test = $testDetails[1];

    $message = "OK";
    if (isset($testDetails[3])) {
      $message .= " ($testDetails[3])";
    }

    $this->tests[$section][$test] = array("good", $message);
  }

  /**
   * @param $testDetails
   */
  function error($testDetails) {
    $section = $testDetails[0];
    $test = $testDetails[1];

    $this->tests[$section][$test] = array("error", $testDetails[2]);
    $this->errors[] = $testDetails;
  }

  /**
   * @param $testDetails
   */
  function warning($testDetails) {
    $section = $testDetails[0];
    $test = $testDetails[1];


    $this->tests[$section][$test] = array("warning", $testDetails[2]);
    $this->warnings[] = $testDetails;
  }

  /**
   * @return int
   */
  function hasErrors() {
    return sizeof($this->errors);
  }

  /**
   * @return int
   */
  function hasWarnings() {
    return sizeof($this->warnings);
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
  function createDatabaseIfNotExists($server, $username, $password, $database) {
    $conn = @mysql_connect($server, $username, $password);

    if (@mysql_select_db($database)) {
      // skip if database already present
      return;
    }

    if (@mysql_query("CREATE DATABASE $database")) {}
    else {
      $errorTitle = "Oops! Could not create Database $database";
      $errorMsg = "We encountered an error when attempting to create the database. Please check your mysql server permissions and the database name and try again.";
      errorDisplayPage($errorTitle, $errorMsg);
    }
  }

  /**
   * @param $config
   *
   * @return mixed
   */
  function install($config) {
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
      $commonOutputMessage = "
                      <li>Have you registered this site at CiviCRM.org? If not, please help strengthen the CiviCRM ecosystem by taking a few minutes to <a href='$registerSiteURL' target='_blank'>fill out the site registration form</a>. The information collected will help us prioritize improvements, target our communications and build the community. If you have a technical role for this site, be sure to check Keep in Touch to receive technical updates (a low volume  mailing list).</li>
                      <li>We have integrated KCFinder with CKEditor and TinyMCE. This allows a user to upload images. All uploaded images are public.</li>
";

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
        $output .= '<title>CiviCRM Installed</title>';
        $output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
        $output .= '</head>';
        $output .= '<body>';
        $output .= '<div style="padding: 1em;"><p class="good">CiviCRM has been successfully installed</p>';
        $output .= '<ul>';
        $docLinkConfig = CRM_Utils_System::docURL2('Configuring a New Site', FALSE, 'here',NULL,NULL,"wiki");
        if (!function_exists('ts')) {
          $docLinkConfig = "<a href=\"{$docLinkConfig}\">here</a>";
        }
        $drupalURL = civicrm_cms_base();
        $drupalPermissionsURL = "{$drupalURL}index.php?q=admin/people/permissions";
        $drupalURL .= "index.php?q=civicrm/admin/configtask&reset=1";

        $output .= "<li>Drupal user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a target='_blank' href={$drupalPermissionsURL}>review these permissions</a> to ensure that they are appropriate for your requirements (<a target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'>learn more...</a>)</li>
                      <li>Use the <a target='_blank' href=\"$drupalURL\">Configuration Checklist</a> to review and configure settings for your new site</li>
                      {$commonOutputMessage}";

        // automatically enable CiviCRM module once it is installed successfully.
        // so we need to Bootstrap Drupal, so that we can call drupal hooks.
        global $cmsPath, $crmPath;

        // relative / abosolute paths are not working for drupal, hence using chdir()
        chdir($cmsPath);

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
      elseif ($installType == 'drupal' && version_compare(VERSION, '6.0') >= 0) {
        // clean output
        @ob_clean();

        $output .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
        $output .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
        $output .= '<head>';
        $output .= '<title>CiviCRM Installed</title>';
        $output .= '<link rel="stylesheet" type="text/css" href="template.css" />';
        $output .= '</head>';
        $output .= '<body>';
        $output .= '<div style="padding: 1em;"><p class="good">CiviCRM has been successfully installed</p>';
        $output .= '<ul>';
        $docLinkConfig = CRM_Utils_System::docURL2('Configuring a New Site', FALSE, 'here',NULL,NULL,"wiki");
        if (!function_exists('ts')) {
          $docLinkConfig = "<a href=\"{$docLinkConfig}\">here</a>";
        }
        $drupalURL = civicrm_cms_base();
        $drupalPermissionsURL = "{$drupalURL}index.php?q=admin/user/permissions";
        $drupalURL .= "index.php?q=civicrm/admin/configtask&reset=1";

        $output .= "<li>Drupal user permissions have been automatically set - giving anonymous and authenticated users access to public CiviCRM forms and features. We recommend that you <a target='_blank' href={$drupalPermissionsURL}>review these permissions</a> to ensure that they are appropriate for your requirements (<a target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'>learn more...</a>)</li>
                      <li>Use the <a target='_blank' href=\"$drupalURL\">Configuration Checklist</a> to review and configure settings for your new site</li>
                      {$commonOutputMessage}";

        // explicitly setting error reporting, since we cannot handle drupal related notices
        error_reporting(1);

        // automatically enable CiviCRM module once it is installed successfully.
        // so we need to Bootstrap Drupal, so that we can call drupal hooks.
        global $cmsPath, $crmPath;

        // relative / abosolute paths are not working for drupal, hence using chdir()
        chdir($cmsPath);

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
        echo '<h1>CiviCRM Installed</h1>';
        echo '<div style="padding: 1em;"><p style="background-color: #0C0; border: 1px #070 solid; color: white;">CiviCRM has been successfully installed</p>';
        echo '<ul>';
        $docLinkConfig = CRM_Utils_System::docURL2('Configuring a New Site', FALSE, 'here',NULL,NULL,"wiki");
        if (!function_exists('ts')) {
          $docLinkConfig = "<a href=\"{$docLinkConfig}\">here</a>";
        }

        $cmsURL = civicrm_cms_base();
        $cmsURL .= "wp-admin/admin.php?page=CiviCRM&q=civicrm/admin/configtask&reset=1";
        $wpPermissionsURL = "wp-admin/admin.php?page=CiviCRM&q=civicrm/admin/access/wp-permissions&reset=1";

        $output .= "
           <li>WordPress user permissions have been automatically set - giving Anonymous and Subscribers access to public CiviCRM forms and features. We recommend that you <a target='_blank' href={$wpPermissionsURL}>review these permissions</a> to ensure that they are appropriate for your requirements (<a target='_blank' href='http://wiki.civicrm.org/confluence/display/CRMDOC/Default+Permissions+and+Roles'>learn more...</a>)</li>
           <li>Use the <a target='_blank' href=\"$cmsURL\">Configuration Checklist</a> to review and configure settings for your new site</li>
          {$commonOutputMessage}
";

         echo '</ul>';
         echo '</div>';
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
        array(
          '%perm' => $perm), WATCHDOG_ERROR
      );
    }
    $perms = array_intersect($perms, $allPerms);
    user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, $perms);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, $perms);
  }
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
 */
function errorDisplayPage($errorTitle, $errorMsg) {
  include ('error.html');
  exit();
}

