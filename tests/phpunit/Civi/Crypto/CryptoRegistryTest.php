<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Crypto;

use Civi\Crypto\Exception\CryptoException;

/**
 * Test major use-cases of the 'crypto.registry' service.
 */
class CryptoRegistryTest extends \CiviUnitTestCase {

  use CryptoTestTrait;

  protected function setUp(): void {
    parent::setUp();
    \CRM_Utils_Hook::singleton()->setHook('civicrm_crypto', [$this, 'registerExampleKeys']);
  }

  public function testParseKey() {
    $examples = self::getExampleKeys();
    $registry = \Civi::service('crypto.registry');

    $key0 = $registry->parseKey($examples[0]);
    $this->assertEquals("please use 32 bytes for aes-256!", $key0['key']);
    $this->assertEquals('aes-cbc', $key0['suite']);

    $key1 = $registry->parseKey($examples[1]);
    $this->assertEquals(32, strlen($key1['key']));
    $this->assertEquals('aes-cbc', $key1['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key1['key']));

    $key2 = $registry->parseKey($examples[2]);
    $this->assertEquals(32, strlen($key2['key']));
    $this->assertEquals('aes-ctr', $key2['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key2['key']));

    $key3 = $registry->parseKey($examples[3]);
    $this->assertEquals(32, strlen($key3['key']));
    $this->assertEquals('aes-cbc-hs', $key3['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key3['key']));
  }

  public function testRegisterAndFindKeys() {
    /** @var CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');

    $key = $registry->findKey('asdf-key-0');
    $this->assertEquals(32, strlen($key['key']));
    $this->assertEquals('aes-cbc', $key['suite']);

    $key = $registry->findKey('asdf-key-1');
    $this->assertEquals(32, strlen($key['key']));
    $this->assertEquals('aes-cbc', $key['suite']);

    $key = $registry->findKey('asdf-key-2');
    $this->assertEquals(32, strlen($key['key']));
    $this->assertEquals('aes-ctr', $key['suite']);

    $key = $registry->findKey('asdf-key-3');
    $this->assertEquals(32, strlen($key['key']));
    $this->assertEquals('aes-cbc-hs', $key['suite']);

    $key = $registry->findKey('UNIT-TEST');
    $this->assertEquals(32, strlen($key['key']));
    $this->assertEquals('asdf-key-1', $key['id']);
  }

  public function testValidKeyId() {
    $valids = ['abc', 'a.b-c_d+e/', 'f\\g:h;i='];
    $invalids = [chr(0), chr(1), chr(1) . 'abc', 'a b', "ab\n", "ab\nc", "\r", "\n"];

    /** @var CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');

    foreach ($valids as $valid) {
      $this->assertEquals(TRUE, $registry->isValidKeyId($valid), "Key ID \"$valid\" should be valid");
    }

    foreach ($invalids as $invalid) {
      $this->assertEquals(FALSE, $registry->isValidKeyId($invalid), "Key ID \"$invalid\" should be invalid");
    }
  }

  public function testAddBadKeyId() {
    /** @var CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');

    try {
      $registry->addSymmetricKey([
        'key' => 'abcd',
        'id' => "foo\n",
      ]);
      $this->fail("Expected crypto exception");
    }
    catch (CryptoException $e) {
      $this->assertRegExp(';Malformed key ID;', $e->getMessage());
    }
  }

}
