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
      self::$singletons['dsn'] = \DB::parseDSN(CIVICRM_DSN);
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
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @code
   * \Civi\Test::headless()->apply();
   * \Civi\Test::headless()->sqlFile('ex.sql')->apply();
   * @endCode
   */
  public static function headless() {
    $civiRoot = dirname(__DIR__);
    $builder = new \Civi\Test\CiviEnvBuilder('CiviEnvBuilder');
    $builder
      ->callback(function ($ctx) {
        if (CIVICRM_UF !== 'UnitTests') {
          throw new \RuntimeException("\\Civi\\Test::headless() requires CIVICRM_UF=UnitTests");
        }
        $dbName = \Civi\Test::dsn('database');
        echo "Installing {$dbName} schema\n";
        \Civi\Test::schema()->dropAll();
      }, 'headless-drop')
      ->sqlFile($civiRoot . "/sql/civicrm.mysql")
      ->sql("DELETE FROM civicrm_extension")
      ->callback(function ($ctx) {
        \Civi\Test::data()->populate();
      }, 'populate');
    return $builder;
  }

  /**
   * Create a builder for end-to-end testing on the live environment.
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @code
   * \Civi\Test::e2e()->apply();
   * \Civi\Test::e2e()->install('foo.bar')->apply();
   * @endCode
   */
  public static function e2e() {
    $builder = new \Civi\Test\CiviEnvBuilder('CiviEnvBuilder');
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
   * @return \Civi\Test\Data
   */
  public static function data() {
    if (!isset(self::$singletons['data'])) {
      self::$singletons['data'] = new \Civi\Test\Data('CiviTesterData');
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
          var_dump($result);
          var_dump($pdo->errorInfo());
          // die( "Cannot execute $query: " . $pdo->errorInfo() );
        }
      }
    }
    return TRUE;
  }

}
