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
      'jwt-hs256::abcd1234abcd1234',
      'jwt-hs384:b64:8h5wNGnJbdVHpXms2RwcVx+jxCNdYEsYCdNlPpVgNLRMg9Q2xKYnxSfuihS6YCRi',
      'jwt-hs256::fdsafdsafdsa',
      'jwt-eddsa-keypair:b64:1mLWxLLESEJphn4V8RHZfkk5UtoxBHXzrWFjdf7swNdQvgc+K4gd/xnwjFaInkfnHiFFjRRckbfZZXrYVLeRgFC+Bz4riB3/GfCMVoieR+ceIUWNFFyRt9llethUt5GA',
      // Example EdDSA keypair generated with `base64_encode(sodium_crypto_sign_keypair())`
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

    $key = $registry->addSymmetricKey($registry->parseKey($examples[4]) + [
      'tags' => ['SIGN-TEST'],
      'id' => 'sign-key-1',
      'weight' => 1,
    ]);
    $this->assertEquals(1, $key['weight']);

    $key = $registry->addSymmetricKey($registry->parseKey($examples[4]) + [
      'tags' => ['SIGN-TEST'],
      'id' => 'sign-key-0',
    ]);
    $this->assertEquals(0, $key['weight']);

    $key = $registry->addSymmetricKey($registry->parseKey($examples[4]) + [
      'tags' => ['SIGN-TEST-ALT'],
      'id' => 'sign-key-alt',
    ]);
    $this->assertEquals(0, $key['weight']);

    $key = $registry->addKey($registry->parseKey($examples[7]) + [
      'tags' => ['SIGN-TEST-EDDSA'],
      'id' => 'sign-key-eddsa',
    ]);
    $this->assertEquals(0, $key['weight']);

    $this->assertEquals(8, count($examples));
    $this->assertEquals(8 + $origCount, count($registry->getKeys()));
  }

}
