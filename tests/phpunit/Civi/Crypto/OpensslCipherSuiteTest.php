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

/**
 * @group headless
 * @group crypto
 */
class OpensslCipherSuiteTest extends \PHPUnit\Framework\TestCase {

  public function testGetIvLength(): void {
    $suite = new OpensslCipherSuite();
    $this->assertSame(16, Invasive::call([$suite, 'getIvLength'], ['aes-256-cbc']));
  }

  public function testGetIvLengthThrowsOnUnsupportedCipher(): void {
    set_error_handler(static fn() => TRUE, E_WARNING);
    try {
      $this->expectException(CryptoException::class);
      Invasive::call([new OpensslCipherSuite(), 'getIvLength'], ['invalid-cipher']);
    }
    finally {
      restore_error_handler();
    }
  }

  public function testGetIvLengthThrowsOnZeroLengthIv(): void {
    $this->expectException(CryptoException::class);
    Invasive::call([new OpensslCipherSuite(), 'getIvLength'], ['aes-256-ecb']);
  }

}
