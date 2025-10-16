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
 * This files provides several classes for doing command line work with
 * CiviCRM. civicrm_cli is the base class. It's used by cli.php.
 *
 * In addition, there are several additional classes that inherit
 * civicrm_cli to do more precise functions.
 *
 */

/**
 * base class for doing all command line operations via civicrm
 * used by cli.php
 */
class civicrm_cli {
  // required values that must be passed
  /**
   * via the command line
   * @var array
   */
  public $_required_arguments = array('action', 'entity');
  public $_additional_arguments = array();
  public $_entity = NULL;
  public $_action = NULL;
  public $_output = FALSE;
  public $_joblog = FALSE;
  public $_semicolon = FALSE;
  public $_config;

  /**
   * optional arguments
   * @var string
   */
  public $_site = 'localhost';
  public $_user = NULL;
  public $_password = NULL;

  // all other arguments populate the parameters
  /**
   * array that is passed to civicrm_api
   * @var array
   */
  public $_params = array('version' => 3);

  public $_errors = array();

  /**
   * @return bool
   */
  public function initialize() {
    if (!$this->_accessing_from_cli()) {
      return FALSE;
    }
    if (!$this->_parseOptions()) {
      return FALSE;
    }
    if (!$this->_bootstrap()) {
      return FALSE;
    }
    if (!$this->_validateOptions()) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Ensure function is being run from the cli.
   *
   * @return bool
   */
  public function _accessing_from_cli() {
    if (PHP_SAPI === 'cli') {
      return TRUE;
    }
    else {
      die("cli.php can only be run from command line.");
    }
  }

  /**
   * @return bool
   */
  public function callApi() {
    require_once 'api/api.php';

    CRM_Core_Config::setPermitCacheFlushMode(FALSE);
    //  CRM-9822 -'execute' action always goes thru Job api and always writes to log
    if ($this->_action != 'execute' && $this->_joblog) {
      require_once 'CRM/Core/JobManager.php';
      $facility = new CRM_Core_JobManager();
      $facility->setSingleRunParams($this->_entity, $this->_action, $this->_params, 'From Cli.php');
      $facility->executeJobByAction($this->_entity, $this->_action);
    }
    else {
      // CRM-9822 cli.php calls don't require site-key, so bypass site-key authentication
      $this->_params['auth'] = FALSE;
      $result = civicrm_api($this->_entity, $this->_action, $this->_params);
    }
    CRM_Core_Config::setPermitCacheFlushMode(TRUE);
    CRM_Contact_BAO_Contact_Utils::clearContactCaches();

    if (!empty($result['is_error'])) {
      $this->_log($result['error_message']);
      return FALSE;
    }
    elseif ($this->_output === 'json') {
      echo json_encode($result, defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0);
    }
    elseif ($this->_output) {
      print_r($result['values']);
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  private function _parseOptions() {
    $args = $_SERVER['argv'];
    // remove the first argument, which is the name
    // of this script
    array_shift($args);

    foreach ($args as $k => $arg) {
      // sanitize all user input
      $arg = $this->_sanitize($arg);

      // if we're not parsing an option signifier
      // continue to the next one
      if (!preg_match('/^-/', $arg)) {
        continue;
      }

      // find the value of this arg
      if (preg_match('/=/', $arg)) {
        $parts = explode('=', $arg);
        $arg = $parts[0];
        $value = $parts[1];
      }
      else {
        if (isset($args[$k + 1])) {
          $next_arg = $this->_sanitize($args[$k + 1]);
          // if the next argument is not another option
          // it's the value for this argument
          if (!preg_match('/^-/', $next_arg)) {
            $value = $next_arg;
          }
        }
      }

      // parse the special args first
      if ($arg == '-e' || $arg == '--entity') {
        $this->_entity = $value;
      }
      elseif ($arg == '-a' || $arg == '--action') {
        $this->_action = $value;
      }
      elseif ($arg == '-s' || $arg == '--site') {
        $this->_site = $value;
      }
      elseif ($arg == '-u' || $arg == '--user') {
        $this->_user = $value;
      }
      elseif ($arg == '-p' || $arg == '--password') {
        $this->_password = $value;
      }
      elseif ($arg == '-o' || $arg == '--output') {
        $this->_output = TRUE;
      }
      elseif ($arg == '-J' || $arg == '--json') {
        $this->_output = 'json';
      }
      elseif ($arg == '-j' || $arg == '--joblog') {
        $this->_joblog = TRUE;
      }
      elseif ($arg == '-sem' || $arg == '--semicolon') {
        $this->_semicolon = TRUE;
      }
      else {
        foreach ($this->_additional_arguments as $short => $long) {
          if ($arg == '-' . $short || $arg == '--' . $long) {
            $property = '_' . $long;
            $this->$property = $value;
            continue;
          }
        }
        // all other arguments are parameters
        $key = ltrim($arg, '--');
        $this->_params[$key] = $value ?? NULL;
      }
    }
    return TRUE;
  }

  /**
   * @return bool
   */
  private function _bootstrap() {
    // so the configuration works with php-cli
    $_SERVER['PHP_SELF'] = "/index.php";
    $_SERVER['HTTP_HOST'] = $this->_site;
    $_SERVER['REMOTE_ADDR'] = "127.0.0.1";
    $_SERVER['SERVER_SOFTWARE'] = NULL;
    $_SERVER['REQUEST_METHOD'] = 'GET';

    // SCRIPT_FILENAME needed by CRM_Utils_System::cmsRootPath
    $_SERVER['SCRIPT_FILENAME'] = __FILE__;

    // CRM-8917 - check if script name starts with /, if not - prepend it.
    if (ord($_SERVER['SCRIPT_NAME']) != 47) {
      $_SERVER['SCRIPT_NAME'] = '/' . $_SERVER['SCRIPT_NAME'];
    }
    $isJoomla = FALSE;
    if (str_contains(__FILE__, 'administrator/components/com_civicrm/civicrm/')) {
      $isJoomla = TRUE;
      global $civicrm_root;
    }
    $civicrm_root = dirname(__DIR__);
    chdir($civicrm_root);
    if ($isJoomla && !class_exists('CRM_Core_ClassLoader')) {
      require_once $civicrm_root . '/CRM/Utils/System/Base.php';
      require_once $civicrm_root . '/CRM/Utils/System/Joomla.php';
      $joomlaClass = new CRM_Utils_System_Joomla();
      $joomlaClass->loadJoomlaFramework();
    }
    if (getenv('CIVICRM_SETTINGS')) {
      require_once getenv('CIVICRM_SETTINGS');
    }
    else {
      require_once 'civicrm.config.php';
    }
    // autoload
    if (!class_exists('CRM_Core_ClassLoader')) {
      require_once $civicrm_root . '/CRM/Core/ClassLoader.php';
    }
    CRM_Core_ClassLoader::singleton()->register();

    $this->_config = CRM_Core_Config::singleton();

    // HTTP_HOST will be 'localhost' unless overwritten with the -s argument.
    // Now we have a Config object, we can set it from the Base URL.
    if ($_SERVER['HTTP_HOST'] == 'localhost') {
      $_SERVER['HTTP_HOST'] = preg_replace(
        '!^https?://([^/]+)/$!i',
        '$1',
        $this->_config->userFrameworkBaseURL);
    }

    $class = 'CRM_Utils_System_' . $this->_config->userFramework;

    $cms = new $class();
    if (!CRM_Utils_System::loadBootstrap(array(), FALSE, FALSE, $civicrm_root)) {
      $this->_log(ts("Failed to bootstrap CMS"));
      return FALSE;
    }

    if (strtolower($this->_entity) == 'job') {
      if (!$this->_user) {
        $this->_log(ts("Jobs called from cli.php require valid user as parameter"));
        return FALSE;
      }
    }

    if (!empty($this->_user)) {
      if (!CRM_Utils_System::authenticateScript(TRUE, $this->_user, $this->_password, TRUE, FALSE, FALSE)) {
        $this->_log(ts("Failed to login as %1. Wrong username or password.", array('1' => $this->_user)));
        return FALSE;
      }
      if (($this->_config->userFramework == 'Joomla' && !$cms->loadUser($this->_user, $this->_password)) || !$cms->loadUser($this->_user)) {
        $this->_log(ts("Failed to login as %1", array('1' => $this->_user)));
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * @return bool
   */
  private function _validateOptions() {
    foreach ($this->_required_arguments as $var) {
      $index = '_' . $var;
      if (empty($this->$index)) {
        $missing_arg = '--' . $var;
        $this->_log(ts("The %1 argument is required", array(1 => $missing_arg)));
        $this->_log($this->_getUsage());
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param $value
   *
   * @return string
   */
  private function _sanitize($value) {
    // restrict user input - we should not be needing anything
    // other than normal alpha numeric plus - and _.
    return trim(preg_replace('#^[^a-zA-Z0-9\-_=/]$#', '', $value));
  }

  /**
   * @return string
   */
  private function _getUsage() {
    $out = "Usage: cli.php -e entity -a action [-u user] [-s site] [--output|--json] [PARAMS]\n";
    $out .= "  entity is the name of the entity, e.g. Contact, Event, etc.\n";
    $out .= "  action is the name of the action e.g. Get, Create, etc.\n";
    $out .= "  user is an optional username to run the script as\n";
    $out .= "  site is the domain name of the web site (for Drupal multi site installs)\n";
    $out .= "  --output will pretty print the result from the api call\n";
    $out .= "  --json will print the result from the api call as JSON\n";
    $out .= "  PARAMS is one or more --param=value combinations to pass to the api\n";
    return $out;
  }

  /**
   * @param $error
   */
  private function _log($error) {
    // fixme, this should call some CRM_Core_Error:: function
    // that properly logs
    print "$error\n";
  }

}

/**
 * class used by csv/export.php to export records from
 * the database in a csv file format.
 */
class civicrm_cli_csv_exporter extends civicrm_cli {
  public $separator = ',';

  /**
   */
  public function __construct() {
    $this->_required_arguments = array('entity');
    parent::initialize();
  }

  /**
   * Run the script.
   */
  public function run() {
    if ($this->_semicolon) {
      $this->separator = ';';
    }

    $out = fopen("php://output", 'w');

    $this->row = 1;
    $result = civicrm_api($this->_entity, 'Get', $this->_params);
    $first = TRUE;
    foreach ($result['values'] as $row) {
      if ($first) {
        $columns = array_keys($row);
        fputcsv($out, $columns, $this->separator, '"');
        $first = FALSE;
      }
      //handle values returned as arrays (i.e. custom fields that allow multiple selections) by inserting a control character
      foreach ($row as &$field) {
        if (is_array($field)) {
          //convert to string
          $field = implode(CRM_Core_DAO::VALUE_SEPARATOR, $field) . CRM_Core_DAO::VALUE_SEPARATOR;
        }
      }
      fputcsv($out, $row, $this->separator, '"');
    }
    fclose($out);
    echo "\n";
  }

}

/**
 * base class used by both civicrm_cli_csv_import
 * and civicrm_cli_csv_deleter to add or delete
 * records based on those found in a csv file
 * passed to the script.
 */
class civicrm_cli_csv_file extends civicrm_cli {
  public $header;
  public $separator = ',';

  /**
   */
  public function __construct() {
    $this->_required_arguments = array('entity', 'file');
    $this->_additional_arguments = array('f' => 'file');
    parent::initialize();
  }

  /**
   * Run CLI function.
   */
  public function run() {
    $this->row = 1;
    $handle = fopen($this->_file, "r");

    if (!$handle) {
      die("Could not open file: " . $this->_file . ". Please provide an absolute path.\n");
    }

    //header
    $header = fgetcsv($handle, 0, $this->separator, '"', '');
    // In case fgetcsv couldn't parse the header and dumped the whole line in 1 array element
    // Try a different separator char
    if (count($header) == 1) {
      $this->separator = ";";
      rewind($handle);
      $header = fgetcsv($handle, 0, $this->separator, '"', '');
    }

    $this->header = $header;
    while (($data = fgetcsv($handle, 0, $this->separator, '"', '')) !== FALSE) {
      // skip blank lines
      if (count($data) == 1 && is_null($data[0])) {
        continue;
      }
      $this->row++;
      if ($this->row % 1000 == 0) {
        // Reset PEAR_DB_DATAOBJECT cache to prevent memory leak
        CRM_Core_DAO::freeResult();
      }
      $params = $this->convertLine($data);
      $this->processLine($params);
    }
    fclose($handle);
  }

  /* return a params as expected */

  /**
   * @param $data
   *
   * @return array
   */
  public function convertLine($data) {
    $params = array();
    foreach ($this->header as $i => $field) {
      //split any multiselect data, denoted with CRM_Core_DAO::VALUE_SEPARATOR
      if (strpos($data[$i], CRM_Core_DAO::VALUE_SEPARATOR) !== FALSE) {
        $data[$i] = explode(CRM_Core_DAO::VALUE_SEPARATOR, $data[$i]);
        $data[$i] = array_combine($data[$i], $data[$i]);
      }
      $params[$field] = $data[$i];
    }
    $params['version'] = 3;
    return $params;
  }

}

/**
 * class for processing records to add
 * used by csv/import.php
 *
 */
class civicrm_cli_csv_importer extends civicrm_cli_csv_file {

  /**
   * @param array $params
   */
  public function processline($params) {
    $result = civicrm_api($this->_entity, 'Create', $params);
    if ($result['is_error']) {
      echo "\nERROR line " . $this->row . ": " . $result['error_message'] . "\n";
    }
    else {
      echo "\nline " . $this->row . ": created " . $this->_entity . " id: " . $result['id'] . "\n";
    }
  }

}

/**
 * class for processing records to delete
 * used by csv/delete.php
 *
 */
class civicrm_cli_csv_deleter extends civicrm_cli_csv_file {

  /**
   * @param array $params
   */
  public function processline($params) {
    $result = civicrm_api($this->_entity, 'Delete', $params);
    if ($result['is_error']) {
      echo "\nERROR line " . $this->row . ": " . $result['error_message'] . "\n";
    }
    else {
      echo "\nline " . $this->row . ": deleted\n";
    }
  }

}
