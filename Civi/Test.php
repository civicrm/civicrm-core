<?php
namespace Civi;

use PDO;
use PDOException;

/**
 * Class Test
 *
 * A facade for managing the test environment.
 */
class Test {

  /**
   * @var array
   */
  private static $singletons = [];

  /**
   * @var array
   */
  public static $statics = [];

  /**
   * Run code in a pre-boot fashion.
   *
   * @param callable $callback
   * @return mixed
   *   Pass through the result of the callback.
   */
  public static function asPreInstall($callback) {
    $conn = \Civi\Test::pdo();

    $oldEscaper = $GLOBALS['CIVICRM_SQL_ESCAPER'] ?? NULL;
    \Civi\Test::$statics['testPreInstall'] = (\Civi\Test::$statics['testPreInstall'] ?? 0) + 1;
    try {
      $GLOBALS['CIVICRM_SQL_ESCAPER'] = function ($text) use ($conn) {
        return substr($conn->quote($text), 1, -1);
      };
      return $callback();
    } finally {
      $GLOBALS['CIVICRM_SQL_ESCAPER'] = $oldEscaper;
      \Civi\Test::$statics['testPreInstall']--;
      if (\Civi\Test::$statics['testPreInstall'] <= 0) {
        unset(\Civi\Test::$statics['testPreInstall']);
      }
    }
  }

  /**
   * Get the data source used for testing.
   *
   * @param string|null $part
   *   One of NULL, 'hostspec', 'port', 'username', 'password', 'database'.
   * @return string|array|NULL
   *   If $part is omitted, return full DSN array.
   *   If $part is a string, return that part of the DSN.
   */
  public static function dsn($part = NULL) {
    if (!isset(self::$singletons['dsn'])) {
      require_once "DB.php";
      $dsn = \CRM_Utils_SQL::autoSwitchDSN(CIVICRM_DSN);
      self::$singletons['dsn'] = \DB::parseDSN($dsn);
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
   * @return \PDO
   */
  public static function pdo() {
    if (!isset(self::$singletons['pdo'])) {
      $dsninfo = self::dsn();
      $host = $dsninfo['hostspec'];
      $port = @$dsninfo['port'];
      try {
        self::$singletons['pdo'] = new PDO("mysql:host={$host}" . ($port ? ";port=$port" : ""),
          $dsninfo['username'], $dsninfo['password'],
          [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE]
        );
      }
      catch (PDOException $e) {
        echo "Can't connect to MySQL server:" . PHP_EOL . $e->getMessage() . PHP_EOL;
        exit(1);
      }
    }
    return self::$singletons['pdo'];
  }

  /**
   * Create a builder for the headless environment.
   *
   * ```
   * \Civi\Test::headless()->apply();
   * \Civi\Test::headless()->sqlFile('ex.sql')->apply();
   * ```
   *
   * @return \Civi\Test\CiviEnvBuilder
   */
  public static function headless() {
    $civiRoot = dirname(__DIR__);
    $builder = new \Civi\Test\CiviEnvBuilder('Headless System');
    $builder
      ->callback(function ($builder) {
        if (CIVICRM_UF !== 'UnitTests') {
          throw new \RuntimeException("\\Civi\\Test::headless() requires CIVICRM_UF=UnitTests");
        }
        $dbName = \Civi\Test::dsn('database');
        \Civi\Test::schema()->dropAll();
      }, 'headless-drop')
      ->coreSchema()
      ->sql("DELETE FROM civicrm_extension")
      ->callback(function ($ctx) {
        \Civi\Test::data()->populate();
      }, 'populate');
    $builder->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'authx']);
    return $builder;
  }

  /**
   * Create a builder for end-to-end testing on the live environment.
   *
   * ```
   * \Civi\Test::e2e()->apply();
   * \Civi\Test::e2e()->install('foo.bar')->apply();
   * ```
   *
   * @return \Civi\Test\CiviEnvBuilder
   */
  public static function e2e() {
    $builder = new \Civi\Test\CiviEnvBuilder();
    $builder
      ->callback(function ($ctx) {
        if (CIVICRM_UF === 'UnitTests') {
          throw new \RuntimeException("\\Civi\\Test::e2e() requires a real CMS. Found CIVICRM_UF=UnitTests.");
        }
      }, 'e2e-check');
    return $builder;
  }

  /**
   * @return \Civi\Test\Schema
   */
  public static function schema() {
    if (!isset(self::$singletons['schema'])) {
      self::$singletons['schema'] = new \Civi\Test\Schema();
    }
    return self::$singletons['schema'];
  }

  /**
   * @return \CRM_Core_CodeGen_Main
   */
  public static function codeGen() {
    if (!isset(self::$singletons['codeGen'])) {
      $civiRoot = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__));
      $codeGen = new \CRM_Core_CodeGen_Main("$civiRoot/CRM/Core/DAO", "$civiRoot/sql", $civiRoot, "$civiRoot/templates", NULL, "UnitTests", NULL, "$civiRoot/xml/schema/Schema.xml", NULL);
      $codeGen->setVerbose(FALSE);
      $codeGen->init();
      self::$singletons['codeGen'] = $codeGen;
    }
    return self::$singletons['codeGen'];
  }

  /**
   * @return \Civi\Test\Data
   */
  public static function data() {
    if (!isset(self::$singletons['data'])) {
      self::$singletons['data'] = new \Civi\Test\Data('CiviTesterData');
    }
    return self::$singletons['data'];
  }

  /**
   * @return \Civi\Test\ExampleDataLoader
   */
  public static function examples(): \Civi\Test\ExampleDataLoader {
    if (!isset(self::$singletons['examples'])) {
      self::$singletons['examples'] = new \Civi\Test\ExampleDataLoader();
    }
    return self::$singletons['examples'];
  }

  /**
   * Lookup the content of an example data-set.
   *
   * This helper is for the common case of looking up the data for a specific example.
   * If you need more detailed information (eg the list of examples or other metadata),
   * then use `\Civi\Test::examples(): ExampleDataLoader`. It  provides more methods.
   *
   * @param string $name
   *   Symbolic name of the data-set.
   * @return array
   *   The example data.
   */
  public static function example(string $name): array {
    $result = static::examples()->getFull($name);
    if (!isset($result['data'])) {
      throw new \CRM_Core_Exception("Failed to load example data-set: $name");
    }
    return $result['data'];
  }

  /**
   * @return \Civi\Test\EventChecker
   */
  public static function eventChecker() {
    if (!isset(self::$singletons['eventChecker'])) {
      self::$singletons['eventChecker'] = new \Civi\Test\EventChecker();
    }
    return self::$singletons['eventChecker'];
  }

  /**
   * Prepare and execute a batch of SQL statements.
   *
   * @param string $query
   * @return bool
   */
  public static function execute($query) {
    $pdo = \Civi\Test::pdo();

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
          throw new \RuntimeException('Cannot execute query: ' . json_encode([$query, $pdo->errorInfo()], JSON_PRETTY_PRINT));
        }
      }
    }
    return TRUE;
  }

}
