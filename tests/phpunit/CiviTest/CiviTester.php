<?php

class CiviTester {

  /**
   * @var array
   */
  private static $singletons = array();

  /**
   * Get the data source used for testing.
   *
   * @param string|NULL $part
   *   One of NULL, 'hostspec', 'port', 'username', 'password', 'database'.
   * @return string|array|NULL
   *   If $part is omitted, return full DSN array.
   *   If $part is a string, return that part of the DSN.
   */
  public static function dsn($part = NULL) {
    if (!isset(self::$singletons['dsn'])) {
      require_once "DB.php";
      self::$singletons['dsn'] = DB::parseDSN(CIVICRM_DSN);
    }

    if ($part === NULL) {
      return self::$singletons['dsn'];
    }

    if (isset(self::$singletons['dsn'][$part])) {
      return self::$singletons['dsn'][$part];
    }

    return NULL;
  }

  /**
   * Get a connection to the test database.
   *
   * @return PDO
   */
  public static function pdo() {
    if (!isset(self::$singletons['pdo'])) {
      $dsninfo = self::dsn();
      $host = $dsninfo['hostspec'];
      $port = @$dsninfo['port'];
      try {
        self::$singletons['pdo'] = new PDO("mysql:host={$host}" . ($port ? ";port=$port" : ""),
          $dsninfo['username'], $dsninfo['password'],
          array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE)
        );
      }
      catch (PDOException$e) {
        echo "Can't connect to MySQL server:" . PHP_EOL . $e->getMessage() . PHP_EOL;
        exit(1);
      }
    }
    return self::$singletons['pdo'];
  }

  /**
   * Get the schema manager.
   *
   * @return \CiviTesterBuilder
   *
   * @code
   * CiviTester::headless()->apply();
   * CiviTester::headless()->sqlFile('ex.sql')->apply();
   * @endCode
   */
  public static function headless() {
    $civiRoot = dirname(dirname(dirname(dirname(__FILE__))));
    $builder = new CiviTesterBuilder('CiviTesterSchema');
    $builder
      ->callback(function ($ctx) {
        if (CIVICRM_UF !== 'UnitTests') {
          throw new \RuntimeException("CiviTester::headless() requires CIVICRM_UF=UnitTests");
        }
        $dbName = CiviTester::dsn('database');
        echo "Installing {$dbName} schema\n";
        CiviTester::schema()->dropAll();
      }, 'msg-drop')
      ->sqlFile($civiRoot . "/sql/civicrm.mysql")
      ->callback(function ($ctx) {
        CiviTester::data()->populate();
      }, 'populate');
    return $builder;
  }

  /**
   * @return \CiviTesterSchema
   */
  public static function schema() {
    if (!isset(self::$singletons['schema'])) {
      self::$singletons['schema'] = new CiviTesterSchema();
    }
    return self::$singletons['schema'];
  }


  /**
   * @return \CiviTesterData
   */
  public static function data() {
    if (!isset(self::$singletons['data'])) {
      self::$singletons['data'] = new CiviTesterData('CiviTesterData');
    }
    return self::$singletons['data'];
  }

  /**
   * Prepare and execute a batch of SQL statements.
   *
   * @param string $query
   * @return bool
   */
  public static function execute($query) {
    $pdo = CiviTester::pdo();

    $string = preg_replace("/^#[^\n]*$/m", "\n", $query);
    $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

    $queries = preg_split('/;\s*$/m', $string);
    foreach ($queries as $query) {
      $query = trim($query);
      if (!empty($query)) {
        $result = $pdo->query($query);
        if ($pdo->errorCode() == 0) {
          continue;
        }
        else {
          var_dump($result);
          var_dump($pdo->errorInfo());
          // die( "Cannot execute $query: " . $pdo->errorInfo() );
        }
      }
    }
    return TRUE;
  }

}

/**
 * Class CiviTesterSchema
 *
 * Manage the entire database. This is useful for destroying or loading the schema.
 */
class CiviTesterSchema {

  /**
   * @param string $type
   *   'BASE TABLE' or 'VIEW'.
   * @return array
   */
  public function getTables($type) {
    $pdo = CiviTester::pdo();
    // only consider real tables and not views
    $query = sprintf(
      "SELECT table_name FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = %s AND TABLE_TYPE = %s",
      $pdo->quote(CiviTester::dsn('database')),
      $pdo->quote($type)
    );
    $tables = $pdo->query($query);
    $result = array();
    foreach ($tables as $table) {
      $result[] = $table['table_name'];
    }
    return $result;
  }

  public function setStrict($checks) {
    $dbName = CiviTester::dsn('database');
    if ($checks) {
      $queries = array(
        "USE {$dbName};",
        "SET global innodb_flush_log_at_trx_commit = 1;",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET foreign_key_checks = 1;",
      );
    }
    else {
      $queries = array(
        "USE {$dbName};",
        "SET foreign_key_checks = 0",
        "SET SQL_MODE='STRICT_ALL_TABLES';",
        "SET global innodb_flush_log_at_trx_commit = 2;",
      );
    }
    foreach ($queries as $query) {
      if (CiviTester::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    return $this;
  }

  public function dropAll() {
    $queries = array();
    foreach ($this->getTables('VIEW') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP VIEW $table";
      }
    }

    foreach ($this->getTables('BASE TABLE') as $table) {
      if (preg_match('/^(civicrm_|log_)/', $table)) {
        $queries[] = "DROP TABLE $table";
      }
    }

    $this->setStrict(FALSE);
    foreach ($queries as $query) {
      if (CiviTester::execute($query) === FALSE) {
        throw new RuntimeException("dropSchema: Query failed: $query");
      }
    }
    $this->setStrict(TRUE);

    return $this;
  }

  /**
   * @return array
   */
  public function truncateAll() {
    $tables = CiviTester::schema()->getTables('BASE TABLE');

    $truncates = array();
    $drops = array();
    foreach ($tables as $table) {
      // skip log tables
      if (substr($table, 0, 4) == 'log_') {
        continue;
      }

      // don't change list of installed extensions
      if ($table == 'civicrm_extension') {
        continue;
      }

      if (substr($table, 0, 14) == 'civicrm_value_') {
        $drops[] = 'DROP TABLE ' . $table . ';';
      }
      elseif (substr($table, 0, 9) == 'civitest_') {
        // ignore
      }
      else {
        $truncates[] = 'TRUNCATE ' . $table . ';';
      }
    }

    CiviTester::schema()->setStrict(FALSE);
    $queries = array_merge($truncates, $drops);
    foreach ($queries as $query) {
      if (CiviTester::execute($query) === FALSE) {
        throw new RuntimeException("Query failed: $query");
      }
    }
    CiviTester::schema()->setStrict(TRUE);

    return $this;
  }

}

/**
 * Class CiviTesterData
 */
class CiviTesterData {

  /**
   * @return bool
   */
  public function populate() {
    CiviTester::schema()->truncateAll();

    CiviTester::schema()->setStrict(FALSE);
    //  initialize test database
    $sql_file2 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/civicrm_data.mysql";
    $sql_file3 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data.mysql";
    $sql_file4 = dirname(dirname(dirname(dirname(__FILE__)))) . "/sql/test_data_second_domain.mysql";

    $query2 = file_get_contents($sql_file2);
    $query3 = file_get_contents($sql_file3);
    $query4 = file_get_contents($sql_file4);
    if (CiviTester::execute($query2) === FALSE) {
      throw new RuntimeException("Cannot load civicrm_data.mysql. Aborting.");
    }
    if (CiviTester::execute($query3) === FALSE) {
      throw new RuntimeException("Cannot load test_data.mysql. Aborting.");
    }
    if (CiviTester::execute($query4) === FALSE) {
      throw new RuntimeException("Cannot load test_data.mysql. Aborting.");
    }

    unset($query, $query2, $query3);

    CiviTester::schema()->setStrict(TRUE);

    // Rebuild triggers
    civicrm_api('system', 'flush', array('version' => 3, 'triggers' => 1));

    CRM_Core_BAO_ConfigSetting::setEnabledComponents(array(
      'CiviEvent',
      'CiviContribute',
      'CiviMember',
      'CiviMail',
      'CiviReport',
      'CiviPledge',
    ));

    return TRUE;
  }

}

/**
 * Class CiviTesterBuilder
 *
 * Provides a fluent interface for tracking a set of steps.
 * By computing and storing a signature for the list steps, we can
 * determine whether to (a) do nothing with the list or (b)
 * reapply all the steps.
 */
class CiviTesterBuilder {
  protected $name;

  private $steps = array();

  /**
   * @var string|NULL
   *   A digest of the values in $steps.
   */
  private $targetSignature = NULL;

  public function __construct($name) {
    $this->name = $name;
  }

  public function addStep(CiviTesterStep $step) {
    $this->targetSignature = NULL;
    $this->steps[] = $step;
    return $this;
  }

  public function callback($callback, $signature = NULL) {
    return $this->addStep(new CiviTesterCallbackStep($callback, $signature));
  }

  public function sql($sql) {
    return $this->addStep(new CiviTesterSqlStep($sql));
  }

  public function sqlFile($file) {
    return $this->addStep(new CiviTesterSqlFileStep($file));
  }

  /**
   * Require an extension (based on its name).
   *
   * @param string $name
   * @return \CiviTesterBuilder
   */
  public function ext($name) {
    return $this->addStep(new CiviTesterExtensionStep($name));
  }

  /**
   * Require an extension (based on its directory).
   *
   * @param $dir
   * @return \CiviTesterBuilder
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function extDir($dir) {
    while ($dir && dirname($dir) !== $dir && !file_exists("$dir/info.xml")) {
      $dir = dirname($dir);
    }
    if (file_exists("$dir/info.xml")) {
      $info = CRM_Extension_Info::loadFromFile("$dir/info.xml");
      $name = $info->key;
    }
    return $this->addStep(new CiviTesterExtensionStep($name));
  }

  protected function assertValid() {
    foreach ($this->steps as $step) {
      if (!$step->isValid()) {
        throw new RuntimeException("Found invalid step: " . var_dump($step, 1));
      }
    }
  }

  /**
   * @return string
   */
  protected function getTargetSignature() {
    if ($this->targetSignature === NULL) {
      $buf = '';
      foreach ($this->steps as $step) {
        $buf .= $step->getSig();
      }
      $this->targetSignature = md5($buf);
    }

    return $this->targetSignature;
  }

  /**
   * @return string
   */
  protected function getSavedSignature() {
    $liveSchemaRev = NULL;
    $pdo = CiviTester::pdo();
    $pdoStmt = $pdo->query(sprintf(
      "SELECT rev FROM %s.civitest_revs WHERE name = %s",
      CiviTester::dsn('database'),
      $pdo->quote($this->name)
    ));
    foreach ($pdoStmt as $row) {
      $liveSchemaRev = $row['rev'];
    }
    return $liveSchemaRev;
  }

  /**
   * @param $newSignature
   */
  protected function setSavedSignature($newSignature) {
    $pdo = CiviTester::pdo();
    $query = sprintf(
      'INSERT INTO %s.civitest_revs (name,rev) VALUES (%s,%s) '
      . 'ON DUPLICATE KEY UPDATE rev = %s;',
      CiviTester::dsn('database'),
      $pdo->quote($this->name),
      $pdo->quote($newSignature),
      $pdo->quote($newSignature)
    );

    if (CiviTester::execute($query) === FALSE) {
      throw new RuntimeException("Failed to flag schema version: $query");
    }
  }

  /**
   * Determine if the schema is correct. If necessary, destroy and recreate.
   *
   * @param bool $force
   * @return $this
   */
  public function apply($force = FALSE) {
    $dbName = CiviTester::dsn('database');
    $query = "USE {$dbName};"
      . "CREATE TABLE IF NOT EXISTS civitest_revs (name VARCHAR(64) PRIMARY KEY, rev VARCHAR(64));";

    if (CiviTester::execute($query) === FALSE) {
      throw new RuntimeException("Failed to flag schema version: $query");
    }

    $this->assertValid();

    if (!$force && $this->getSavedSignature() === $this->getTargetSignature()) {
      return $this;
    }
    foreach ($this->steps as $step) {
      $step->run($this);
    }
    $this->setSavedSignature($this->getTargetSignature());
    return $this;
  }

}

interface CiviTesterStep {
  public function getSig();

  public function isValid();

  public function run($ctx);

}

class CiviTesterSqlFileStep implements CiviTesterStep {
  private $file;

  /**
   * CiviTesterSqlFileStep constructor.
   * @param $file
   */
  public function __construct($file) {
    $this->file = $file;
  }


  public function getSig() {
    return implode(' ', array(
      $this->file,
      filemtime($this->file),
      filectime($this->file),
    ));
  }

  public function isValid() {
    return is_file($this->file) && is_readable($this->file);
  }

  public function run($ctx) {
    /** @var $ctx CiviTesterBuilder */
    if (CiviTester::execute(@file_get_contents($this->file)) === FALSE) {
      throw new RuntimeException("Cannot load {$this->file}. Aborting.");
    }
  }

}

class CiviTesterSqlStep implements CiviTesterStep {
  private $sql;

  /**
   * CiviTesterSqlFileStep constructor.
   * @param $sql
   */
  public function __construct($sql) {
    $this->sql = $sql;
  }


  public function getSig() {
    return md5($this->sql);
  }

  public function isValid() {
    return TRUE;
  }

  public function run($ctx) {
    /** @var $ctx CiviTesterBuilder */
    if (CiviTester::execute($this->sql) === FALSE) {
      throw new RuntimeException("Cannot execute: {$this->sql}");
    }
  }

}

class CiviTesterCallbackStep implements CiviTesterStep {
  private $callback;
  private $sig;

  /**
   * CiviTesterCallbackStep constructor.
   * @param $callback
   * @param $sig
   */
  public function __construct($callback, $sig = NULL) {
    $this->callback = $callback;
    $this->sig = $sig === NULL ? md5(var_export($callback, 1)) : $sig;
  }

  public function getSig() {
    return $this->sig;
  }

  public function isValid() {
    return is_callable($this->callback);
  }

  public function run($ctx) {
    call_user_func($this->callback, $ctx);
  }

}

class CiviTesterExtensionStep implements CiviTesterStep {
  private $name;

  /**
   * CiviTesterExtensionStep constructor.
   * @param $name
   */
  public function __construct($name) {
    $this->name = $name;
  }

  public function getSig() {
    return 'ext:' . $this->name;
  }

  public function isValid() {
    return is_string($this->name);
  }

  public function run($ctx) {
    CRM_Extension_System::singleton()->getManager()->install(array(
      $this->name,
    ));
  }

}
