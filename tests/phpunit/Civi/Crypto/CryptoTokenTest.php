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
 * Test major use-cases of the 'crypto.token' service.
 */
class CryptoTokenTest extends \CiviUnitTestCase {

  use CryptoTestTrait;

  protected function setUp(): void {
    parent::setUp();
    \CRM_Utils_Hook::singleton()->setHook('civicrm_crypto', [$this, 'registerExampleKeys']);
  }

  public function testIsPlainText() {
    $token = \Civi::service('crypto.token');

    $this->assertFalse($token->isPlainText(chr(2)));
    $this->assertFalse($token->isPlainText(chr(2) . 'asdf'));

    $this->assertTrue($token->isPlainText(\CRM_Utils_Array::implodePadded(['a', 'b', 'c'])));
    $this->assertTrue($token->isPlainText(""));
    $this->assertTrue($token->isPlainText("\r"));
    $this->assertTrue($token->isPlainText("\n"));
  }

  public function testDecryptInvalid() {
    $cryptoToken = \Civi::service('crypto.token');
    try {
      $cryptoToken->decrypt(chr(2) . 'CTK0' . chr(2));
      $this->fail("Expected CryptoException");
    }
    catch (CryptoException $e) {
      $this->assertRegExp(';Cannot decrypt token. Invalid format.;', $e->getMessage());
    }

    $goodExample = $cryptoToken->encrypt('mess with me', 'UNIT-TEST');
    $this->assertEquals('mess with me', $cryptoToken->decrypt($goodExample));

    try {
      $badExample = preg_replace(';CTK\?;', 'ctk9', $goodExample);
      $this->assertTrue($badExample !== $goodExample);
      $cryptoToken->decrypt($badExample);
      $this->fail("Expected CryptoException");
    }
    catch (CryptoException $e) {
      $this->assertRegExp(';Cannot decrypt token. Invalid format.;', $e->getMessage());
    }
  }

  public function getExampleTokens() {
    return [
      // [ 'Plain text', 'Encryption Key ID', 'expectTokenRegex', 'expectTokenLen', 'expectPlain' ]
      ['hello world. can you see me', 'plain', '/^hello world. can you see me/', 27, TRUE],
      ['hello world. i am secret.', 'UNIT-TEST', '/^.CTK\?k=asdf-key-1&/', 84, FALSE],
      ['hello world. we b secret.', 'asdf-key-0', '/^.CTK\?k=asdf-key-0&/', 84, FALSE],
      ['hello world. u ur secret.', 'asdf-key-1', '/^.CTK\?k=asdf-key-1&/', 84, FALSE],
      ['hello world. he z secret.', 'asdf-key-2', '/^.CTK\?k=asdf-key-2&/', 75, FALSE],
      ['hello world. whos secret.', 'asdf-key-3', '/^.CTK\?k=asdf-key-3&/', 127, FALSE],
    ];
  }

  /**
   * @param string $inputText
   * @param string $inputKeyIdOrTag
   * @param string $expectTokenRegex
   * @param int $expectTokenLen
   * @param bool $expectPlain
   *
   * @dataProvider getExampleTokens
   */
  public function testRoundtrip($inputText, $inputKeyIdOrTag, $expectTokenRegex, $expectTokenLen, $expectPlain) {
    $token = \Civi::service('crypto.token')->encrypt($inputText, $inputKeyIdOrTag);
    $this->assertRegExp($expectTokenRegex, $token);
    $this->assertEquals($expectTokenLen, strlen($token));
    $this->assertEquals($expectPlain, \Civi::service('crypto.token')->isPlainText($token));

    $actualText = \Civi::service('crypto.token')->decrypt($token);
    $this->assertEquals($inputText, $actualText);
  }

  public function testRekeyCiphertext() {
    /** @var \Civi\Crypto\CryptoRegistry $cryptoRegistry */
    $cryptoRegistry = \Civi::service('crypto.registry');
    /** @var \Civi\Crypto\CryptoToken $cryptoToken */
    $cryptoToken = \Civi::service('crypto.token');

    $first = $cryptoToken->encrypt("hello world", 'UNIT-TEST');
    $this->assertRegExp(';k=asdf-key-1;', $first);
    $this->assertEquals('hello world', $cryptoToken->decrypt($first));

    // If the keys haven't changed yet, then rekey() is a null-op.
    $second = $cryptoToken->rekey($first, 'UNIT-TEST');
    $this->assertTrue($second === NULL);

    // But if we add a newer key, then rekey() will yield new token.
    $cryptoRegistry->addSymmetricKey($cryptoRegistry->parseKey('::foo') + [
      'tags' => ['UNIT-TEST'],
      'weight' => -100,
      'id' => 'new-key',
    ]);
    $third = $cryptoToken->rekey($first, 'UNIT-TEST');
    $this->assertNotRegExp(';k=asdf-key-1;', $third);
    $this->assertRegExp(';k=new-key;', $third);
    $this->assertEquals('hello world', $cryptoToken->decrypt($third));
  }

  public function testRekeyUpgradeDowngradePlaintext() {
    /** @var \Civi\Crypto\CryptoRegistry $cryptoRegistry */
    $cryptoRegistry = \Civi::service('crypto.registry');
    /** @var \Civi\Crypto\CryptoToken $cryptoToken */
    $cryptoToken = \Civi::service('crypto.token');

    // In the first pass, we have no real key.
    $cryptoRegistry->addPlainText(['tags' => ['APPLE'], 'weight' => -1]);
    $first = $cryptoToken->encrypt("hello world", 'APPLE');
    $this->assertEquals('hello world', $first);
    $this->assertEquals('hello world', $cryptoToken->decrypt($first));

    // If the keys haven't changed yet, then rekey() is a null-op.
    $second = $cryptoToken->rekey($first, 'APPLE');
    $this->assertTrue($second === NULL);

    // But if we add a key, then it takes precedence.
    $cryptoRegistry->addSymmetricKey($cryptoRegistry->parseKey('::applepie') + [
      'tags' => ['APPLE'],
      'weight' => -3,
      'id' => 'interim-key',
    ]);
    $third = $cryptoToken->rekey($first, 'APPLE');
    $this->assertRegExp(';k=interim-key;', $third);
    $this->assertEquals('hello world', $cryptoToken->decrypt($third));

    // But if we add another key with earlier priority,
    $cryptoRegistry->addPlainText(['tags' => ['APPLE'], 'weight' => -4]);
    $fourth = $cryptoToken->rekey($third, 'APPLE');
    $this->assertEquals('hello world', $fourth);
    $this->assertEquals('hello world', $cryptoToken->decrypt($fourth));

  }

  public function testReadPlainTextWithoutRegistry() {
    // This is performance optimization - don't initialize crypto.registry unless
    // you actually need it.
    $this->assertFalse(\Civi::container()->initialized('crypto.registry'));
    $this->assertEquals("Hello world", \Civi::service('crypto.token')->decrypt("Hello world"));
    $this->assertFalse(\Civi::container()->initialized('crypto.registry'));
  }

}
