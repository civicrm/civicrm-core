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

  /**
   * @var array
   */
  protected $system_checks = [
    'checkMemory',
    'checkMysqlConnectExists',
    'checkJsonEncodeExists',
    'checkMultibyteExists',
  ];

  protected $system_checks_web = [
    'checkServerVariables',
  ];

  protected $database_checks = [
    'checkMysqlConnection',
    'checkMysqlVersion',
    'checkMysqlInnodb',
    'checkMysqlTempTables',
    'checkMySQLAutoIncrementIncrementOne',
    'checkMysqlTrigger',
    'checkMysqlThreadStack',
    'checkMysqlLockTables',
    'checkMysqlUtf8mb4',
  ];

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
    if (!class_exists('\CRM_Utils_SQL_TempTable')) {
      require_once dirname(__FILE__) . '/../../CRM/Utils/SQL/TempTable.php';
    }
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
    $errors = [];

    $errors[] = $this->checkFilepathIsWritable($file_paths);
    foreach ($this->system_checks as $check) {
      $errors[] = $this->$check();
    }

    if (PHP_SAPI !== 'cli') {
      foreach ($this->system_checks_web as $check) {
        $errors[] = $this->$check();
      }
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
    $errors = [];

    foreach ($this->database_checks as $check) {
      $errors[] = $this->$check($db_config);
    }

    return $errors;
  }

  /**
   * Generates a mysql connection
   *
   * @param array $db_config
   * @return object mysqli connection
   */
  protected function connect($db_config) {
    $host = NULL;
    if (!empty($db_config['host'])) {
      $host = $db_config['host'];
    }
    elseif (!empty($db_config['server'])) {
      $host = $db_config['server'];
    }
    if (empty($db_config['ssl_params'])) {
      $conn = @mysqli_connect($host, $db_config['username'], $db_config['password'], $db_config['database'], !empty($db_config['port']) ? $db_config['port'] : NULL, $db_config['socket'] ?? NULL);
    }
    else {
      $conn = NULL;
      $init = mysqli_init();
      mysqli_ssl_set(
        $init,
        $db_config['ssl_params']['key'] ?? NULL,
        $db_config['ssl_params']['cert'] ?? NULL,
        $db_config['ssl_params']['ca'] ?? NULL,
        $db_config['ssl_params']['capath'] ?? NULL,
        $db_config['ssl_params']['cipher'] ?? NULL
      );
      if (@mysqli_real_connect($init, $host, $db_config['username'], $db_config['password'], $db_config['database'], (!empty($db_config['port']) ? $db_config['port'] : NULL), $db_config['socket'] ?? NULL, MYSQLI_CLIENT_SSL)) {
        $conn = $init;
      }
    }
    return $conn;
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

    $results = [
      'title' => 'CiviCRM memory check',
      'severity' => $this::REQUIREMENT_OK,
      'details' => "You have $mem_string allocated (minimum 32Mb, recommended 64Mb)",
    ];

    if ($mem < $min && $mem > 0) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
    }
    elseif ($mem < $recommended && $mem != 0 && $mem != -1) {
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
    $results = [
      'title' => 'CiviCRM PHP server variables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'The required $_SERVER variables are set',
    ];

    $required_variables = ['SCRIPT_NAME', 'HTTP_HOST', 'SCRIPT_FILENAME'];
    $missing = [];

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
    $results = [
      'title' => 'CiviCRM JSON encoding support',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Function json_encode() found',
    ];
    if (!function_exists('json_encode')) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'Function json_encode() does not exist';
    }

    return $results;
  }

  /**
   * CHeck that PHP Multibyte functions are enabled.
   * @return array
   */
  public function checkMultibyteExists() {
    $results = [
      'title' => 'CiviCRM MultiByte encoding support',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'PHP Multibyte etension found',
    ];
    if (!function_exists('mb_substr')) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = 'PHP Multibyte extension has not been installed and enabled';
    }

    return $results;
  }

  /**
   * @return array
   */
  public function checkMysqlConnectExists() {
    $results = [
      'title' => 'CiviCRM MySQL check',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Function mysqli_connect() found',
    ];
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
    $results = [
      'title' => 'CiviCRM MySQL connection',
      'severity' => $this::REQUIREMENT_OK,
      'details' => "Connected",
    ];

    $conn = $this->connect($db_config);

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
    if (!class_exists('\CRM_Upgrade_Incremental_General')) {
      require_once dirname(__FILE__) . '/../../CRM/Upgrade/Incremental/General.php';
    }
    $min = \CRM_Upgrade_Incremental_General::MIN_INSTALL_MYSQL_VER;
    $results = [
      'title' => 'CiviCRM MySQL Version',
      'severity' => $this::REQUIREMENT_OK,
    ];

    $conn = $this->connect($db_config);
    if (!$conn || !($info = mysqli_get_server_info($conn))) {
      $results['severity'] = $this::REQUIREMENT_WARNING;
      $results['details'] = "Cannot determine the version of MySQL installed. Please ensure at least version {$min} is installed.";
      return $results;
    }

    $versionDetails = mysqli_query($conn, 'SELECT version() as version')->fetch_assoc();
    if (version_compare($versionDetails['version'], $min) == -1) {
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
    $results = [
      'title' => 'CiviCRM InnoDB support',
      'severity' => $this::REQUIREMENT_ERROR,
      'details' => 'Could not determine if MySQL has InnoDB support. Assuming none.',
    ];

    $conn = $this->connect($db_config);
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
    $results = [
      'title' => 'CiviCRM MySQL Temp Tables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL server supports temporary tables',
    ];

    $conn = $this->connect($db_config);
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
    $temporaryTableName = \CRM_Utils_SQL_TempTable::build()->setCategory('install')->getName();
    $r = mysqli_query($conn, 'CREATE TEMPORARY TABLE ' . $temporaryTableName . ' (test text)');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_ERROR;
      $results['details'] = "Database does not support creation of temporary tables";
      return $results;
    }

    mysqli_query($conn, 'DROP TEMPORARY TABLE ' . $temporaryTableName);
    return $results;
  }

  /**
   * @param $db_config
   *
   * @return array
   */
  public function checkMysqlTrigger($db_config) {
    $results = [
      'title' => 'CiviCRM MySQL Trigger',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Database supports MySQL triggers',
    ];

    $conn = $this->connect($db_config);
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
    $results = [
      'title' => 'CiviCRM MySQL AutoIncrementIncrement',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL server auto_increment_increment is 1',
    ];

    $conn = $this->connect($db_config);
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

    $results = [
      'title' => 'CiviCRM Mysql thread stack',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'MySQL thread_stack is OK',
    ];

    $conn = $this->connect($db_config);
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

    // bytes => kb
    $r = mysqli_query($conn, "SHOW VARIABLES LIKE 'thread_stack'");
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
    $results = [
      'title' => 'CiviCRM MySQL Lock Tables',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Can successfully lock and unlock tables',
    ];

    $conn = $this->connect($db_config);
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
    $results = [
      'title' => 'CiviCRM directories are writable',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'All required directories are writable: ' . implode(', ', $file_paths),
    ];

    $unwritable_dirs = [];
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

  /**
   * @param $db_config
   *
   * @return array
   */
  public function checkMysqlUtf8mb4($db_config) {
    $results = [
      'title' => 'CiviCRM MySQL utf8mb4 Support',
      'severity' => $this::REQUIREMENT_OK,
      'details' => 'Your system supports the MySQL utf8mb4 character set.',
    ];

    $conn = $this->connect($db_config);
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

    mysqli_query($conn, 'DROP TABLE IF EXISTS civicrm_utf8mb4_test');
    $r = mysqli_query($conn, 'CREATE TABLE civicrm_utf8mb4_test (id VARCHAR(255), PRIMARY KEY(id(255))) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC ENGINE=INNODB');
    if (!$r) {
      $results['severity'] = $this::REQUIREMENT_WARNING;
      $results['details'] = 'It is recommended, though not yet required, to configure your MySQL server for utf8mb4 support. You will need the following MySQL server configuration: innodb_large_prefix=true innodb_file_format=barracuda innodb_file_per_table=true';
      mysqli_close($conn);
      return $results;
    }
    mysqli_query($conn, 'DROP TABLE civicrm_utf8mb4_test');

    // Ensure that the MySQL driver supports utf8mb4 encoding.
    $version = mysqli_get_client_info();
    if (strpos($version, 'mysqlnd') !== FALSE) {
      // The mysqlnd driver supports utf8mb4 starting at version 5.0.9.
      $version = preg_replace('/^\D+([\d.]+).*/', '$1', $version);
      if (version_compare($version, '5.0.9', '<')) {
        $results['severity'] = $this::REQUIREMENT_WARNING;
        $results['details'] = 'It is recommended, though not yet required, to upgrade your PHP MySQL driver (mysqlnd) to >= 5.0.9 for utf8mb4 support.';
        mysqli_close($conn);
        return $results;
      }
    }
    else {
      // The libmysqlclient driver supports utf8mb4 starting at version 5.5.3.
      if (version_compare($version, '5.5.3', '<')) {
        $results['severity'] = $this::REQUIREMENT_WARNING;
        $results['details'] = 'It is recommended, though not yet required, to upgrade your PHP MySQL driver (libmysqlclient) to >= 5.5.3 for utf8mb4 support.';
        mysqli_close($conn);
        return $results;
      }
    }

    mysqli_close($conn);
    return $results;
  }

}
