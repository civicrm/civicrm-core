<?php

namespace Civi\Install;

/**
 * Class Requirements
 * @package Civi\Install
 */
class Requirements {

  /**
   * Requirement severity -- Requirement successfully met.
   */
  const REQUIREMENT_OK = 0;

  /**
   * Requirement severity -- Warning condition; proceed but flag warning.
   */
  const REQUIREMENT_WARNING = 1;

  /**
   * Requirement severity -- Error condition; abort installation.
   */
  const REQUIREMENT_ERROR = 2;

  protected $system_checks = array(
    'checkMemory',
    'checkServerVariables',
    'checkMysqlConnectExists',
    'checkJsonEncodeExists',
  );

  protected $database_checks = array(
    'checkMysqlConnection',
    'checkMysqlVersion',
    'checkMysqlInnodb',
    'checkMysqlTempTables',
    'checkMySQLAutoIncrementIncrementOne',
    'checkMysqlTrigger',
    'checkMysqlThreadStack',
    'checkMysqlLockTables',
  );

  /**
   * Run all requirements tests.
   *
   * @param array $config
   *   An array with two keys:
   *     - file_paths
   *     - db_config
   *
   * @return array
   *   An array of check summaries. Each array contains the keys 'title', 'severity', and 'details'.
   */
  public function checkAll(array $config) {
    return array_merge($this->checkSystem($config['file_paths']), $this->checkDatabase($config['db_config']));
  }

  /**
   * Check system requirements are met, such as sufficient memory,
   * necessary file paths are writable and required php extensions
   * are available.
   *
   * @param array $file_paths
   *   An array of file paths that will be checked to confirm they
   *   are writable.
   *
   * @return array
   */
  public function checkSystem(array $file_paths) {
    $errors = array();

    $errors[] = $this->checkFilepathIsWritable($file_paths);
    foreach ($this->system_checks as $check) {
      $errors[] = $this->$check();
    }

    return $errors;
  }

  /**
   * Check database connection, database version and other
   * database requirements are met.
   *
   * @param array $db_config
   *   An array with keys:
   *   - host (with optional port specified eg. localhost:12345)
   *   - database (name of database to select)
   *   - username
   *   - password
   *
   * @return array
   */
  public function checkDatabase(array $db_config) {
    $errors = array();

    foreach ($this->database_checks as $check) {
      $errors[] = $this->$check($db_config);
    }

    return $errors;
  }

  /**
   * Check configured php Memory.
   * @return array
   */
  public function checkMemory() {
    $min = 1024 * 1024 * 32;
    $recommended = 1024 * 1024 * 64;

    $mem = $this->getPHPMemory();
    $mem_string = ini_get('memory_limit');

    $results = array(
      'title' => 'CiviCRM memory check',
      'severity' => $this::REQUIREMENT_OK,
      'details' => "You have $mem_string allocated (minimum 32Mb, recommended 64Mb)",
    );

    if ($mem < $min && $mem > 0) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
    }
    elseif ($mem < $recommended && $mem != 0) {
      $results['severity'] = $this::REQUIREMENT_WARNING;
    }
    elseif ($mem == 0) {
      $results['details'] = "Cannot determine PHP memory allocation. Install only if you're sure you've allocated at least 32 MB.";
      $results['severity'] = $this::REQUIREMENT_WARNING;
    }

    return $results;
  }

  /**
   * Get Configured PHP memory.
   * @return float
   */
  protected function getPHPMemory() {
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

  /**
   * @return array
   */
  public function checkServerVariables() {
    $results = array(
      'title' => 'CiviCRM PHP server variables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'The required $_SERVER variables are set',
    );

    $required_variables = array('SCRIPT_NAME', 'HTTP_HOST', 'SCRIPT_FILENAME');
    $missing = array();

    foreach ($required_variables as $required_variable) {
      if (empty($_SERVER[$required_variable])) {
        $missing[] = '$_SERVER[' . $required_variable . ']';
      }
    }

    if ($missing) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'The following PHP variables are not set: ' . implode(', ', $missing);
    }

    return $results;
  }

  /**
   * @return array
   */
  public function checkJsonEncodeExists() {
    $results = array(
      'title' => 'CiviCRM JSON encoding support',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Function json_encode() found',
    );
    if (!function_exists('json_encode')) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Function json_encode() does not exist';
    }

    return $results;
  }

  /**
   * @return array
   */
  public function checkMysqlConnectExists() {
    $results = array(
      'title' => 'CiviCRM MySQL check',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Function mysqli_connect() found',
    );
    if (!function_exists('mysqli_connect')) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Function mysqli_connect() does not exist';
    }

    return $results;
  }

  /**
   * @param array $db_config
   *
   * @return array
   */
  public function checkMysqlConnection(array $db_config) {
    $results = array(
      'title' => 'CiviCRM MySQL connection',
      'severity' => $this::REQUIREMENT_OK,
      'details' => "Connected",
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);

    if (!$conn) {
      $results['details'] = mysqli_connect_error();
      $results['severity'] = $this::REQUIREMENT_ERROR;
      return $results;
    }

    if (!@mysqli_select_db($conn, $db_config['database'])) {
      $results['details'] = mysqli_error($conn);
      $results['severity'] = $this::REQUIREMENT_ERROR;
      return $results;
    }

    return $results;
  }

  /**
   * @param array $db_config
   *
   * @return array
   */
  public function checkMysqlVersion(array $db_config) {
    $min = '5.1';
    $results = array(
      'title' => 'CiviCRM MySQL Version',
      'severity' => $this::REQUIREMENT_OK,
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);
    if (!$conn || !($info = mysqli_get_server_info($conn))) {
      $results['severity'] = $this::REQUIREMENT_WARNING;
      $results['details'] = "Cannot determine the version of MySQL installed. Please ensure at least version {$min} is installed.";
      return $results;
    }

    if (version_compare($info, $min) == -1) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "MySQL version is {$info}; minimum required is {$min}";
      return $results;
    }

    $results['details'] = "MySQL version is {$info}";
    return $results;
  }

  /**
   * @param array $db_config
   *
   * @return array
   */
  public function checkMysqlInnodb(array $db_config) {
    $results = array(
      'title' => 'CiviCRM InnoDB support',
      'severity' => $this::REQUIREMENT_ERROR,
      'details' => 'Could not determine if MySQL has InnoDB support. Assuming none.',
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      return $results;
    }

    $innodb_support = FALSE;
    $result = mysqli_query($conn, "SHOW ENGINES");
    while ($values = mysqli_fetch_array($result)) {
      if ($values['Engine'] == 'InnoDB') {
        if (strtolower($values['Support']) == 'yes' || strtolower($values['Support']) == 'default') {
          $innodb_support = TRUE;
          break;
        }
      }
    }

    if ($innodb_support) {
      $results['severity'] = $this::REQUIREMENT_OK;
      $results['details'] = 'MySQL supports InnoDB';
    }
    return $results;
  }

  /**
   * @param array $db_config
   *
   * @return array
   */
  public function checkMysqlTempTables(array $db_config) {
    $results = array(
      'title' => 'CiviCRM MySQL Temp Tables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL server supports temporary tables',
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "Could not connect to database";
      return $results;
    }

    if (!@mysqli_select_db($conn, $db_config['database'])) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "Could not select the database";
      return $results;
    }

    $r = mysqli_query($conn, 'CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "Database does not support creation of temporary tables";
      return $results;
    }

    mysqli_query($conn, 'DROP TEMPORARY TABLE civicrm_install_temp_table_test');
    return $results;
  }

  /**
   * @param $db_config
   *
   * @return array
   */
  public function checkMysqlTrigger($db_config) {
    $results = array(
      'title' => 'CiviCRM MySQL Trigger',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Database supports MySQL triggers',
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not connect to database';
      return $results;
    }

    if (!@mysqli_select_db($conn, $db_config['database'])) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "Could not select the database";
      return $results;
    }

    $r = mysqli_query($conn, 'CREATE TABLE civicrm_install_temp_table_test (test text)');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not create a table to run test';
      return $results;
    }

    $r = mysqli_query($conn, 'CREATE TRIGGER civicrm_install_temp_table_test_trigger BEFORE INSERT ON civicrm_install_temp_table_test FOR EACH ROW BEGIN END');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Database does not support creation of triggers';
    }
    else {
      mysqli_query($conn, 'DROP TRIGGER civicrm_install_temp_table_test_trigger');
    }

    mysqli_query($conn, 'DROP TABLE civicrm_install_temp_table_test');
    return $results;
  }

  /**
   * @param array $db_config
   *
   * @return array
   */
  public function checkMySQLAutoIncrementIncrementOne(array $db_config) {
    $results = array(
      'title' => 'CiviCRM MySQL AutoIncrementIncrement',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL server auto_increment_increment is 1',
    );

    $conn = @mysqli_connect($db_config['host'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not connect to database';
      return $results;
    }

    $r = mysqli_query($conn, "SHOW variables like 'auto_increment_increment'");
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not query database server variables';
      return $results;
    }

    $values = mysqli_fetch_row($r);
    if ($values[1] != 1) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'MySQL server auto_increment_increment is not 1';
    }
    return $results;
  }

  /**
   * @param $db_config
   *
   * @return array
   */
  public function checkMysqlThreadStack($db_config) {
    $min_thread_stack = 192;

    $results = array(
      'title' => 'CiviCRM Mysql thread stack',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL thread_stack is OK',
    );

    $conn = @mysqli_connect($db_config['server'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not connect to database';
      return $results;
    }

    if (!@mysqli_select_db($conn, $db_config['database'])) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not select the database';
      return $results;
    }

    $r = mysqli_query($conn, "SHOW VARIABLES LIKE 'thread_stack'"); // bytes => kb
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not query thread_stack value';
    }
    else {
      $values = mysqli_fetch_row($r);
      if ($values[1] < (1024 * $min_thread_stack)) {
        $results['severity'] = $this::REQUIREMENT_ERROR;
        $results['details'] = 'MySQL thread_stack is ' . ($values[1] / 1024) . "kb (minimum required is {$min_thread_stack} kb";
      }
    }

    return $results;
  }

  /**
   * @param $db_config
   *
   * @return array
   */
  public function checkMysqlLockTables($db_config) {
    $results = array(
      'title' => 'CiviCRM MySQL Lock Tables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Can successfully lock and unlock tables',
    );

    $conn = @mysqli_connect($db_config['server'], $db_config['username'], $db_config['password']);
    if (!$conn) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not connect to database';
      return $results;
    }

    if (!@mysqli_select_db($conn, $db_config['database'])) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not select the database';
      mysqli_close($conn);
      return $results;
    }

    $r = mysqli_query($conn, 'CREATE TEMPORARY TABLE civicrm_install_temp_table_test (test text)');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not create a table';
      mysqli_close($conn);
      return $results;
    }

    $r = mysqli_query($conn, 'LOCK TABLES civicrm_install_temp_table_test WRITE');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not obtain a write lock';
      mysqli_close($conn);
      return $results;
    }

    $r = mysqli_query($conn, 'UNLOCK TABLES');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Could not release table lock';
    }

    mysqli_close($conn);
    return $results;
  }

  /**
   * @param $file_paths
   *
   * @return array
   */
  public function checkFilepathIsWritable($file_paths) {
    $results = array(
      'title' => 'CiviCRM directories are writable',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'All required directories are writable: ' . implode(', ', $file_paths),
    );

    $unwritable_dirs = array();
    foreach ($file_paths as $path) {
      if (!is_writable($path)) {
        $unwritable_dirs[] = $path;
      }
    }

    if ($unwritable_dirs) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "The following directories need to be made writable by the webserver: " . implode(', ', $unwritable_dirs);
    }

    return $results;
  }

}
