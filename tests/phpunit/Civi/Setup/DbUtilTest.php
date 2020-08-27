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
   * Data provider for testParseSSL
   * @return array
   */
  public function queryStringProvider():array {
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
