<?php
namespace Civi\Setup;

use Civi\Setup\Exception\SqlException;

class DbUtil {

  /**
   * @param string $dsn
   * @return array
   */
  public static function parseDsn($dsn) {
    $parsed = parse_url($dsn);
    return array(
      'server' => self::encodeHostPort($parsed['host'], $parsed['port']),
      'username' => $parsed['user'] ?: NULL,
      'password' => $parsed['pass'] ?: NULL,
      'database' => $parsed['path'] ? ltrim($parsed['path'], '/') : NULL,
    );
  }

  /**
   * Convert an datasource from array notation to URL notation.
   *
   * @param array $db
   * @return string
   */
  public static function encodeDsn($db) {
    return sprintf('mysql://%s:%s@%s/%s',
      $db['username'],
      $db['password'],
      self::encodeHostPort($db['host'], $db['port']),
      $db['database']
    );
  }

  /**
   * @param array $db
   * @return \mysqli
   */
  public static function softConnect($db) {
    list($host, $port) = self::decodeHostPort($db['server']);
    $conn = @mysqli_connect($host, $db['username'], $db['password'], $db['database'], $port);
    return $conn;
  }

  /**
   * @param array $db
   * @return \mysqli
   * @throws SqlException
   */
  public static function connect($db) {
    $conn = self::softConnect($db);
    if (mysqli_connect_errno()) {
      throw new SqlException(sprintf("Connection failed: %s\n", mysqli_connect_error()));
    }
    return $conn;
  }

  /**
   * @param string $host
   *   Ex: 'localhost',
   *   Ex: 'localhost:123'
   *   Ex: '127.0.0.1:123'
   *   Ex: '[1234:abcd]'
   *   Ex: '[1234:abcd]:123'
   * @return array
   *   Combination: [0 => string $host, 1 => numeric|NULL $port].
   *   Ex: ['localhost', NULL].
   *   Ex: ['127.0.0.1', 3306]
   */
  public static function decodeHostPort($host) {
    $hostParts = explode(':', $host);
    if (count($hostParts) > 1 && strrpos($host, ']') !== strlen($host) - 1) {
      $port = array_pop($hostParts);
      $host = implode(':', $hostParts);
    }
    else {
      $port = NULL;
    }
    return array($host, $port);
  }

  /**
   * Combine a host and port number.
   *
   * @param string $host
   * @param int|NULL $port
   * @return string
   *   Ex: 'localhost'.
   *   Ex: '127.0.0.1:3307'.
   */
  public static function encodeHostPort($host, $port) {
    return $host . ($port ? (':' . $port) : '');
  }

  /**
   * @param array $db
   * @param string $fileName
   * @param bool $lineMode
   *   What does this mean? Seems weird.
   */
  public static function sourceSQL($db, $fileName, $lineMode = FALSE) {
    $conn = self::connect($db);

    $conn->query('SET NAMES utf8');

    if (!$lineMode) {
      $string = file_get_contents($fileName);

      // change \r\n to fix windows issues
      $string = str_replace("\r\n", "\n", $string);

      //get rid of comments starting with # and --

      $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

      $queries = preg_split('/;\s*$/m', $string);
      foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
          if ($result = $conn->query($query)) {
            if (is_object($result)) {
              mysqli_free_result($result);
            }
          }
          else {
            throw new SqlException("Cannot execute $query: " . mysqli_error($conn));
          }
        }
      }
    }
    else {
      $fd = fopen($fileName, "r");
      while ($string = fgets($fd)) {
        $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
        $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

        $string = trim($string);
        if (!empty($string)) {
          if ($result = $conn->query($string)) {
            if (is_object($result)) {
              mysqli_free_result($result);
            }
          }
          else {
            throw new SqlException("Cannot execute $string: " . mysqli_error($conn));
          }
        }
      }
    }
  }

  /**
   * Execute query. Ignore the results.
   *
   * @param \mysqli|array $conn
   *   The DB to query. Either a mysqli connection, or credentials for
   *    establishing one.
   * @param string $sql
   * @throws SqlException
   */
  public static function execute($conn, $sql) {
    $conn = is_array($conn) ? self::connect($conn) : $conn;
    $result = $conn->query($sql);
    if (!$result) {
      throw new SqlException("Cannot execute $sql: " . $conn->error);
    }

    if ($result && $result !== TRUE) {
      $result->free_result();
    }

  }

  /**
   * Get all the results of a SQL query, as an array.
   *
   * @param \mysqli|array $conn
   *   The DB to query. Either a mysqli connection, or credentials for
   *    establishing one.
   * @param string $sql
   * @return array
   * @throws \Exception
   */
  public static function fetchAll($conn, $sql) {
    $conn = is_array($conn) ? self::connect($conn) : $conn;
    $result = $conn->query($sql);
    if (!$result) {
      throw new SqlException("Cannot execute $sql: " . $conn->error);
    }

    $rows = array();
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    $result->free_result();

    return $rows;
  }

  /**
   * Get a list of views in the given database.
   *
   * @param \mysqli|array $conn
   *   The DB to query. Either a mysqli connection, or credentials for
   *    establishing one.
   * @param string $databaseName
   * @return array
   *   Ex: ['civicrm_view1', 'civicrm_view2']
   */
  public static function findViews($conn, $databaseName) {
    $sql = sprintf("SELECT table_name FROM information_schema.TABLES  WHERE TABLE_SCHEMA='%s' AND TABLE_TYPE = 'VIEW'",
      $conn->escape_string($databaseName));

    return array_map(function($arr){
      return $arr['table_name'];
    }, self::fetchAll($conn, $sql));
  }

  /**
   * Get a list of concrete tables in the given database.
   *
   * @param \mysqli|array $conn
   *   The DB to query. Either a mysqli connection, or credentials for
   *    establishing one.
   * @param string $databaseName
   * @return array
   *   Ex: ['civicrm_view1', 'civicrm_view2']
   */
  public static function findTables($conn, $databaseName) {
    $sql = sprintf("SELECT table_name FROM information_schema.TABLES  WHERE TABLE_SCHEMA='%s' AND TABLE_TYPE = 'BASE TABLE'",
      $conn->escape_string($databaseName));

    return array_map(function($arr){
      return $arr['table_name'];
    }, self::fetchAll($conn, $sql));
  }

}
