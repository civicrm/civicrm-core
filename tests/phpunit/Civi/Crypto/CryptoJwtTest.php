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

  /**
   * Assert continuity/compatibility in JWT builds.
   *
   * Most JWT tests in this class will encode+decode the token anew. However, if the
   * JWT library makes a substantive change, then the change could be breaking...
   * but those tests would still pass. (It's testing new-encoder with new-decoder.)
   *
   * This test locks-in a specific example (from an old-encoder) to ensure that
   * the new-encoder is generally compatible.
   *
   * @return void
   */
  public function testUpgradeContinuity() {
    /** @var \Civi\Crypto\CryptoJwt $cryptoJwt */
    $cryptoJwt = \Civi::service('crypto.jwt');

    $oldToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiIsImtpZCI6InNpZ24ta2V5LTAifQ.eyJzY29wZSI6InRlc3QiLCJleHAiOjQxMDI0NDQ3OTl9.zwVUmt9tbzIJriX_d0C5OBBwNP1MeQHH72TOQ9SNC9w';
    $retroFuture = strtotime('2099-12-31 23:59:59 UTC');

    $decoded = $cryptoJwt->decode($oldToken, 'SIGN-TEST');
    $this->assertEquals('test', $decoded['scope'], 'Old token should decode with proper scopes');
    $this->assertEquals($retroFuture, $decoded['exp'], 'Old token should decode with proper expiration');
    // ^^ These two assertions are the most important part of interoperability...

    $newToken = $cryptoJwt->encode(['scope' => 'test', 'exp' => $retroFuture], 'SIGN-TEST');
    $this->assertEquals($oldToken, $newToken, 'Old token should generally match new token');
    // ^^ This assertion may fail if there are substantive changes in JWT conventions.
    // We can't predict when/if/what those changes are. Just raise a flag and let future-developer decide the significance.
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
