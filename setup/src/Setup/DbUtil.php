<?php
namespace Civi\Setup;

use Civi\Setup\Exception\SqlException;

class DbUtil {

  /**
   * @param string $dsn
   * @return array
   */
  public static function parseDsn($dsn) {
    $parsed = array_map('urldecode', parse_url($dsn));
    // parse_url parses 'mysql://admin:secret@unix(/var/lib/mysql/mysql.sock)/otherdb' like:
    // [
    //   'host'   => 'unix(',
    //   'path'   => '/var/lib/mysql/mysql.sock)/otherdb',
    //   ...
    // ]
    if ($parsed['host'] == 'unix(') {
      preg_match('/(unix\(.*\))(\/(.+)?)?$/', $dsn, $matches);
      $server = $matches[1];
      $database = $matches[3] ?? NULL;
    }
    else {
      $server = self::encodeHostPort($parsed['host'], $parsed['port'] ?? NULL);
      $database = $parsed['path'] ? ltrim($parsed['path'], '/') : NULL;
    }

    return array(
      'server' => $server,
      'username' => $parsed['user'] ?: NULL,
      'password' => $parsed['pass'] ?? NULL,
      'database' => $database,
      'ssl_params' => self::parseSSL($parsed['query'] ?? NULL),
    );
  }

  /**
   * Convert a datasource from array notation to URL notation.
   *
   * FIXME: Doesn't support SSL
   *
   * @param array $db
   * @return string
   */
  public static function encodeDsn($db) {
    $escapedHostPort = implode(':', array_map('urlencode', explode(':', $db['server'])));
    return sprintf('mysql://%s:%s@%s/%s',
      urlencode($db['username']),
      urlencode($db['password']),
      $escapedHostPort,
      urlencode($db['database'])
    );
  }

  /**
   * @param array $db
   * @return \mysqli
   */
  public static function softConnect($db) {
    list($host, $port, $socket) = self::decodeHostPort($db['server']);
    if (empty($db['ssl_params'])) {
      $conn = @mysqli_connect($host, $db['username'], $db['password'], $db['database'], $port, $socket);
    }
    else {
      $conn = NULL;
      $init = mysqli_init();
      mysqli_ssl_set(
        $init,
        $db['ssl_params']['key'] ?? NULL,
        $db['ssl_params']['cert'] ?? NULL,
        $db['ssl_params']['ca'] ?? NULL,
        $db['ssl_params']['capath'] ?? NULL,
        $db['ssl_params']['cipher'] ?? NULL
      );
      if (@mysqli_real_connect($init, $host, $db['username'], $db['password'], $db['database'], $port, $socket, MYSQLI_CLIENT_SSL)) {
        $conn = $init;
      }
    }
    return $conn;
  }

  /**
   * @param array $db
   * @return \mysqli
   * @throws SqlException
   */
  public static function connect($db) {
    if (!extension_loaded('mysqli')) {
      throw new SqlException(sprintf("Connection failed: Missing mysqli\n"));
    }

    // During installation, we need to test proposed credentials. Ensure that tests report failure the same way on php7+php8.
    if (version_compare(PHP_VERSION, '8', '>=')) {
      mysqli_report(MYSQLI_REPORT_OFF);
    }

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
   *   Ex: 'localhost:/path/to/socket.sock
   *   Ex: 'unix(/path/to/socket.sock)
   * @return array
   *   Combination: [0 => string $host, 1 => numeric|NULL $port, 2 => string|NULL].
   *   Ex: ['localhost', NULL, NULL].
   *   Ex: ['127.0.0.1', 3306, NULL]
   */
  public static function decodeHostPort($host) {
    $port = NULL;
    $socket = NULL;
    if (preg_match('/^unix\(([^)]+)\)$/', $host, $matches) === 1) {
      $host = 'localhost';
      $socket = $matches[1];
    }
    else {
      $hostParts = explode(':', $host);
      if (count($hostParts) > 1 && strrpos($host, ']') !== strlen($host) - 1) {
        $portOrSocket = array_pop($hostParts);
        if (substr($portOrSocket, /*start*/ 0, /*length*/ 1) == '/') {
          $socket = $portOrSocket;
        }
        else {
          $port = $portOrSocket;
        }
        $host = implode(':', $hostParts);
      }
    }
    return array($host, $port, $socket);
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
   * For SSL you can have client certificates, which has some required and
   * optional parameters, or you can have anonymous SSL, which just requires
   * some indication that you want that.
   *
   * @param string $query_string
   * @return array
   */
  public static function parseSSL($query_string) {
    if (empty($query_string)) {
      return [];
    }
    parse_str($query_string, $parsed_query);
    $sensible_parameters = [
      // ssl=1 alone means no client certificate - it's not a real mysqli option
      'ssl' => NULL,
      'key' => NULL,
      'cert' => NULL,
      'ca' => NULL,
      'capath' => NULL,
      'cipher' => NULL,
    ];
    // Only want to include a param if it's in our list of sensibles, e.g.
    // we don't want new_link=true.
    return array_intersect_key($parsed_query, $sensible_parameters);
  }

  /**
   * @param array $db
   * @param string $SQLcontent
   * @param bool $lineMode
   *   What does this mean? Seems weird.
   */
  public static function sourceSQL($db, $SQLcontent, $lineMode = FALSE) {
    $conn = self::connect($db);

    $conn->query('SET NAMES ' . ($conn->server_version < 50503 ? 'utf8' : 'utf8mb4'));

    if (!$lineMode) {
      $string = $SQLcontent;

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
      throw new \RuntimeException("Not implemented: lineMode");
      //      $fd = fopen($SQLcontent, "r");
      //      while ($string = fgets($fd)) {
      //        $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      //        $string = preg_replace("/^(--[^-]).*/m", "\n", $string);
      //
      //        $string = trim($string);
      //        if (!empty($string)) {
      //          if ($result = $conn->query($string)) {
      //            if (is_object($result)) {
      //              mysqli_free_result($result);
      //            }
      //          }
      //          else {
      //            throw new SqlException("Cannot execute $string: " . mysqli_error($conn));
      //          }
      //        }
      //      }
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

    return array_map(function($arr) {
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

    return array_map(function ($arr) {
      return $arr['table_name'] ?? $arr['TABLE_NAME'];
    }, self::fetchAll($conn, $sql));
  }

}
