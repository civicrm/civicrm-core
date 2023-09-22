<?php
namespace Civi\Setup;

/**
 * Class DrupalUtilTest
 * @package Civi\Setup
 * @group headless
 */
class DrupalUtilTest extends \CiviUnitTestCase {

  /**
   * Test guessSslParams
   * @dataProvider pdoParamsProvider
   * @param array $input
   * @param array $expected
   */
  public function testGuessSslParams(array $input, array $expected) {
    $this->assertSame($expected, \Civi\Setup\DrupalUtil::guessSslParams($input));
  }

  /**
   * Data provider for testGuessSslParams
   * @return array
   */
  public function pdoParamsProvider():array {
    return [
      'empty' => [[], []],
      'empty2' => [['pdo' => []], []],
      'no client certificate, no server verification' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => TRUE,
            \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => FALSE,
          ],
        ],
        ['ssl' => 1],
      ],
      'typical client certificate with server verification' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => '/tmp/cacert.crt',
            \PDO::MYSQL_ATTR_SSL_KEY => '/tmp/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => '/tmp/cert.crt',
          ],
        ],
        [
          'ca' => '/tmp/cacert.crt',
          'key' => '/tmp/my.key',
          'cert' => '/tmp/cert.crt',
        ],
      ],
      'client certificate, no server verification' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => FALSE,
            \PDO::MYSQL_ATTR_SSL_KEY => '/tmp/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => '/tmp/cert.crt',
          ],
        ],
        [
          'key' => '/tmp/my.key',
          'cert' => '/tmp/cert.crt',
        ],
      ],
      'self-signed client certificate with server verification' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => '/tmp/cert.crt',
            \PDO::MYSQL_ATTR_SSL_KEY => '/tmp/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => '/tmp/cert.crt',
          ],
        ],
        [
          'ca' => '/tmp/cert.crt',
          'key' => '/tmp/my.key',
          'cert' => '/tmp/cert.crt',
        ],
      ],
      'Not sure what would happen in practice but is all the string params' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => '/tmp/cacert.crt',
            \PDO::MYSQL_ATTR_SSL_KEY => '/tmp/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => '/tmp/cert.crt',
            \PDO::MYSQL_ATTR_SSL_CAPATH => '/tmp/cacert_folder',
            \PDO::MYSQL_ATTR_SSL_CIPHER => 'aes',
          ],
        ],
        [
          'ca' => '/tmp/cacert.crt',
          'key' => '/tmp/my.key',
          'cert' => '/tmp/cert.crt',
          'capath' => '/tmp/cacert_folder',
          'cipher' => 'aes',
        ],
      ],
      'Ditto, but showing extra ones should get ignored' => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => '/tmp/cacert.crt',
            \PDO::MYSQL_ATTR_SSL_KEY => '/tmp/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => '/tmp/cert.crt',
            \PDO::MYSQL_ATTR_SSL_CAPATH => '/tmp/cacert_folder',
            \PDO::MYSQL_ATTR_SSL_CIPHER => 'aes',
            'fourteen' => 'the number fourteen',
          ],
        ],
        [
          'ca' => '/tmp/cacert.crt',
          'key' => '/tmp/my.key',
          'cert' => '/tmp/cert.crt',
          'capath' => '/tmp/cacert_folder',
          'cipher' => 'aes',
        ],
      ],
      "some windows paths shouldn't get mangled" => [
        [
          'pdo' => [
            \PDO::MYSQL_ATTR_SSL_CA => 'C:/Program Files/MariaDB 10.3/data/cacert.crt',
            \PDO::MYSQL_ATTR_SSL_KEY => 'C:/Program Files/MariaDB 10.3/data/my.key',
            \PDO::MYSQL_ATTR_SSL_CERT => 'C:\\Program Files\\MariaDB 10.3\\data\\cert.crt',
          ],
        ],
        [
          'ca' => 'C:/Program Files/MariaDB 10.3/data/cacert.crt',
          'key' => 'C:/Program Files/MariaDB 10.3/data/my.key',
          'cert' => 'C:\\Program Files\\MariaDB 10.3\\data\\cert.crt',
        ],
      ],
    ];
  }

}
