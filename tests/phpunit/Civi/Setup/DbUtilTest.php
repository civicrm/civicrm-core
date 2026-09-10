<?php
namespace Civi\Setup;

/**
 * Class DbUtilTest
 * @package Civi\Setup
 * @group headless
 */
class DbUtilTest extends \CiviUnitTestCase {

  /**
   * Test parseSSL
   * @dataProvider queryStringProvider
   * @param string $input
   * @param array $expected
   */
  public function testParseSSL(string $input, array $expected) {
    $this->assertSame($expected, \Civi\Setup\DbUtil::parseSSL($input));
  }

  /**
   * A DSN which is not a well-formed URL should be reported as such.
   *
   * @dataProvider malformedDsnProvider
   * @param string $dsn
   */
  public function testParseDsnRejectsMalformed(string $dsn) {
    $this->expectException(\InvalidArgumentException::class);
    \Civi\Setup\DbUtil::parseDsn($dsn);
  }

  /**
   * Data provider for testParseDsnRejectsMalformed
   * @return array
   */
  public static function malformedDsnProvider():array {
    return [
      // Unencoded reserved characters in the password.
      'unencoded hash' => ['mysql://user:pa#ss@host:3306/db'],
      'unencoded slash' => ['mysql://user:pa/ss@host:3306/db'],
      'unencoded question mark' => ['mysql://user:pa?ss@host:3306/db'],
      // Empty host, eg. from an unset environment variable.
      'no host' => ['mysql://user:pass@:3306/db'],
      'no components at all' => ['mysql://:@:/'],
      'no scheme' => ['user:pass@host/db'],
    ];
  }

  /**
   * A well-formed DSN should survive parsing, including the socket notation.
   *
   * @dataProvider wellFormedDsnProvider
   * @param string $dsn
   * @param array $expected
   */
  public function testParseDsn(string $dsn, array $expected) {
    $this->assertSame($expected, \Civi\Setup\DbUtil::parseDsn($dsn));
  }

  /**
   * Data provider for testParseDsn
   * @return array
   */
  public static function wellFormedDsnProvider():array {
    return [
      'simple' => [
        'mysql://user:pass@host:3306/db',
        [
          'server' => 'host:3306',
          'username' => 'user',
          'password' => 'pass',
          'database' => 'db',
          'ssl_params' => [],
        ],
      ],
      'encoded password' => [
        'mysql://user:pa%23ss@host:3306/db',
        [
          'server' => 'host:3306',
          'username' => 'user',
          'password' => 'pa#ss',
          'database' => 'db',
          'ssl_params' => [],
        ],
      ],
      'unix socket' => [
        'mysql://user:pass@unix(/var/lib/mysql/mysql.sock)/db',
        [
          'server' => 'unix(/var/lib/mysql/mysql.sock)',
          'username' => 'user',
          'password' => 'pass',
          'database' => 'db',
          'ssl_params' => [],
        ],
      ],
    ];
  }

  /**
   * Data provider for testParseSSL
   * @return array
   */
  public static function queryStringProvider():array {
    return [
      ['', []],
      ['new_link=true', []],
      ['ssl=1', ['ssl' => '1']],
      ['new_link=true&ssl=1', ['ssl' => '1']],
      ['ca=%2Ftmp%2Fcacert.crt', ['ca' => '/tmp/cacert.crt']],
      [
        'ca=%2Ftmp%2Fcacert.crt&cert=%2Ftmp%2Fcert.crt&key=%2Ftmp%2Fmy.key',
        [
          'ca' => '/tmp/cacert.crt',
          'cert' => '/tmp/cert.crt',
          'key' => '/tmp/my.key',
        ],
      ],
      [
        'ca=%2Fpath%20with%20spaces%2Fcacert.crt',
        [
          'ca' => '/path with spaces/cacert.crt',
        ],
      ],
      ['cipher=aes', ['cipher' => 'aes']],
      ['capath=%2Ftmp', ['capath' => '/tmp']],
      [
        'cipher=aes&capath=%2Ftmp&food=banana',
        [
          'cipher' => 'aes',
          'capath' => '/tmp',
        ],
      ],
      ['food=banana&cipher=aes', ['cipher' => 'aes']],
    ];
  }

}
