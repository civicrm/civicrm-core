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

use Civi\Test\Invasive;

/**
 * Verify that the "withCrypto" option.
 *
 * @group e2e
 */
class E2E_Cache_CryptoTest extends E2E_Cache_CacheTestCase {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $a;

  protected function setUp() {
    parent::setUp();

    Civi::service('crypto.registry')->addPlainText([
      'tags' => ['E2E_TEST_KEY'],
    ]);

    Civi::service('crypto.registry')->addSymmetricKey([
      'key' => hash_hkdf('sha256', 'yadda yadda'),
      'suite' => 'aes-cbc',
      'tags' => ['E2E_TEST_KEY'],
      'id' => 'some-e2e-stuff',
    ]);
  }

  public function createSimpleCache() {
    return CRM_Utils_Cache::create([
      'name' => 'e2e array crypto test',
      'type' => ['ArrayCache'],
      'withCrypto' => 'E2E_TEST_KEY',
    ]);
  }

  public function testEncryptionEnabled() {
    $cache = $this->createSimpleCache();
    $cache->set('something secret', 'banana');

    $_cacheData = Invasive::get([$cache, '_cache']);
    $this->assertTrue(CRM_Utils_String::startsWith(
      $_cacheData['something secret'],
      chr(2) . 'CTK?k=some-e2e-stuff'
    ));

    $this->assertEquals('banana', $cache->get('something secret'));
  }

  public function testEncryptionDisabled() {
    // This matches createSimpleCache(), except without crypto.
    $cache = CRM_Utils_Cache::create([
      'name' => 'e2e array crypto test',
      'type' => ['ArrayCache'],
      'withCrypto' => FALSE,
    ]);
    $cache->set('something plain', 'apple');

    $_cacheData = Invasive::get([$cache, '_cache']);
    $this->assertEquals('s:5:"apple";', $_cacheData['something plain']);
    $this->assertEquals('apple', $cache->get('something plain'));
  }

}
