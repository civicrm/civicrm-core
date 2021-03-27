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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */


namespace api\v4\Entity;

use api\v4\UnitTestCase;
use Civi\Crypto\CryptoTestTrait;
use Psr\Log\LoggerInterface;

/**
 * @group headless
 */
class RotateKeyTest extends UnitTestCase {

  use CryptoTestTrait;

  /**
   * Set up baseline for testing
   */
  public function setUp(): void {
    parent::setUp();
    \CRM_Utils_Hook::singleton()->setHook('civicrm_crypto', [$this, 'registerExampleKeys']);
    \CRM_Utils_Hook::singleton()->setHook('civicrm_cryptoRotateKey', [$this, 'onRotateKey']);
  }

  public function testRekey() {
    $result = \Civi\Api4\System::rotateKey(0)->setTag('UNIT-TEST')->execute();
    $this->assertEquals(2, count($result));
    $this->assertEquals('Updated field A using UNIT-TEST.', $result[0]['message']);
    $this->assertEquals('info', $result[0]['level']);
    $this->assertEquals('Updated field B using UNIT-TEST.', $result[1]['message']);
    $this->assertEquals('info', $result[1]['level']);
  }

  public function onRotateKey(string $tag, LoggerInterface $log) {
    $this->assertEquals('UNIT-TEST', $tag);
    $log->info('Updated field A using {tag}.', [
      'tag' => $tag,
    ]);
    $log->info('Updated field B using {tag}.', [
      'tag' => $tag,
    ]);
  }

}
