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
use Civi\Test\Invasive;
use Firebase\JWT\JWT;

/**
 * Test major use-cases of the 'crypto.token' service.
 *
 * @group headless
 * @group crypto
 */
class CryptoJwtTest extends \CiviUnitTestCase {

  use CryptoTestTrait;

  protected function setUp(): void {
    parent::setUp();
    \CRM_Utils_Hook::singleton()->setHook('civicrm_crypto', [$this, 'registerExampleKeys']);
    JWT::$timestamp = NULL;
  }

  public function testSignVerifyExpire(): void {
    /** @var \Civi\Crypto\CryptoJwt $cryptoJwt */
    $cryptoJwt = \Civi::service('crypto.jwt');

    $enc = $cryptoJwt->encode([
      'exp' => \CRM_Utils_Time::time() + 60,
      'sub' => 'me',
    ], 'SIGN-TEST');
    $this->assertTrue(is_string($enc) && !empty($enc), 'CryptoJwt::encode() should return valid string');

    $dec = $cryptoJwt->decode($enc, 'SIGN-TEST');
    $this->assertTrue(is_array($dec) && !empty($dec));
    $this->assertEquals('me', $dec['sub']);

    JWT::$timestamp = \CRM_Utils_Time::time() + 90;
    try {
      $cryptoJwt->decode($enc, 'SIGN-TEST');
      $this->fail('Expected decode to fail with exception');
    }
    catch (CryptoException $e) {
      $this->assertMatchesRegularExpression(';Expired token;', $e->getMessage());
    }
  }

  /**
   * If you register a public-key (*without* a corresponding private key)... can you still validate signatures?
   *
   * @return void
   * @throws \Civi\Crypto\Exception\CryptoException
   * @throws \SodiumException
   */
  public function testPublicKeyVerify(): void {
    /** @var \Civi\Crypto\CryptoRegistry $registry */
    $registry = \Civi::service('crypto.registry');
    /** @var \Civi\Crypto\CryptoJwt $cryptoJwt */
    $cryptoJwt = \Civi::service('crypto.jwt');

    $keyPair = sodium_crypto_sign_keypair();
    $publicKey = sodium_crypto_sign_publickey($keyPair);

    // First, we use the key-pair to generate signature...
    $registeredKeyPair = $registry->addKey([
      'suite' => 'jwt-eddsa-keypair',
      'key' => $keyPair,
    ]);
    $enc = $cryptoJwt->encode([
      'exp' => \CRM_Utils_Time::time() + 600,
      'sub' => 'me',
      'tags' => ['ASYMMETRIC-EXAMPLE'],
    ], $registeredKeyPair['id']);
    $this->assertTrue(is_string($enc) && !empty($enc), 'CryptoJwt::encode() should return valid string');
    $registry->removeKey($registeredKeyPair['id']);

    // Now, we use the public-key (only) to validate signature...
    $registeredPublicKey = $registry->addKey([
      'suite' => 'jwt-eddsa-public',
      'key' => $publicKey,
      'tags' => ['ASYMMETRIC-EXAMPLE'],
    ]);
    $dec = $cryptoJwt->decode($enc, 'ASYMMETRIC-EXAMPLE');
    $this->assertTrue(is_array($dec) && !empty($dec));
    $this->assertEquals('me', $dec['sub']);
    $this->assertEquals($registeredPublicKey['id'], $registeredKeyPair['id'], 'Keypair and public-key should default to same KID.');
  }

  public function getMixKeyExamples() {
    return [
      ['SIGN-TEST', 'SIGN-TEST', TRUE],
      ['SIGN-TEST-EDDSA', 'SIGN-TEST-EDDSA', TRUE],
      ['sign-key-0', 'SIGN-TEST', TRUE],
      ['sign-key-1', 'SIGN-TEST', TRUE],
      ['sign-key-alt', 'SIGN-TEST', FALSE],
    ];
  }

  /**
   * @param $encKey
   * @param $decKey
   * @param $expectOk
   * @throws \Civi\Crypto\Exception\CryptoException
   * @dataProvider  getMixKeyExamples
   */
  public function testSignMixKeys($encKey, $decKey, $expectOk) {
    /** @var \Civi\Crypto\CryptoJwt $cryptoJwt */
    $cryptoJwt = \Civi::service('crypto.jwt');

    $enc = $cryptoJwt->encode([
      'exp' => \CRM_Utils_Time::time() + 60,
      'sub' => 'me',
    ], $encKey);
    $this->assertTrue(is_string($enc) && !empty($enc), 'CryptoJwt::encode() should return valid string');

    if ($expectOk) {
      $dec = $cryptoJwt->decode($enc, $decKey);
      $this->assertTrue(is_array($dec) && !empty($dec));
      $this->assertEquals('me', $dec['sub']);
    }
    else {
      try {
        $cryptoJwt->decode($enc, $decKey);
        $this->fail('Expected decode to fail with exception');
      }
      catch (CryptoException $e) {
        $this->assertMatchesRegularExpression(';Signature verification failed;', $e->getMessage());
      }
    }
  }

  public function testSuiteToAlg(): void {
    $this->assertEquals('HS256', Invasive::call([CryptoJwt::class, 'suiteToAlg'], ['jwt-hs256']));
    $this->assertEquals(NULL, Invasive::call([CryptoJwt::class, 'suiteToAlg'], ['aes-cbc']));
  }

}
