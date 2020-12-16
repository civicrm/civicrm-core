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

trait CryptoTestTrait {

  public static function getExampleKeys() {
    return [
      ':b64:cGxlYXNlIHVzZSAzMiBieXRlcyBmb3IgYWVzLTI1NiE',
      'aes-cbc:hkdf-sha256:abcd1234abcd1234',
      'aes-ctr::abcd1234abcd1234',
      'aes-cbc-hs::abcd1234abcd1234',
    ];
  }

  /**
   * @param CryptoRegistry $registry
   * @see \CRM_Utils_Hook::crypto()
   */
  public function registerExampleKeys($registry) {
    $origCount = count($registry->getKeys());

    $examples = self::getExampleKeys();
    $key = $registry->addSymmetricKey($registry->parseKey($examples[0]) + [
      'tags' => ['UNIT-TEST'],
      'weight' => 10,
      'id' => 'asdf-key-0',
    ]);
    $this->assertEquals(10, $key['weight']);

    $key = $registry->addSymmetricKey($registry->parseKey($examples[1]) + [
      'tags' => ['UNIT-TEST'],
      'weight' => -10,
      'id' => 'asdf-key-1',
    ]);
    $this->assertEquals(-10, $key['weight']);

    $key = $registry->addSymmetricKey($registry->parseKey($examples[2]) + [
      'tags' => ['UNIT-TEST'],
      'id' => 'asdf-key-2',
    ]);
    $this->assertEquals(0, $key['weight']);

    $key = $registry->addSymmetricKey($registry->parseKey($examples[3]) + [
      'tags' => ['UNIT-TEST'],
      'id' => 'asdf-key-3',
    ]);
    $this->assertEquals(0, $key['weight']);

    $this->assertEquals(4, count($examples));
    $this->assertEquals(4 + $origCount, count($registry->getKeys()));
  }

}
