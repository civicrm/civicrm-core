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
 * Verify that CRM_Utils_Cache_FastArrayDecorator complies with PSR-16.
 *
 * @group e2e
 */
class E2E_Cache_FastArrayDecoratorTest extends E2E_Cache_ArrayDecoratorTest {

  /**
   * @var CRM_Utils_Cache_Interface
   */
  protected $a;

  public function createSimpleCache() {
    return new CRM_Utils_Cache_FastArrayDecorator(
      $this->a = CRM_Utils_Cache::create([
        'name' => 'e2e fast-arr-dec test',
        'type' => ['ArrayCache'],
      ])
    );
  }

  public function testSetTtl() {
    $this->markTestSkipped('FastArrayDecorator breaks convention: Does not track TTL locally. However, TTL is passed along to delegate.');
  }

  public function testSetMultipleTtl() {
    $this->markTestSkipped('FastArrayDecorator breaks convention: Does not track TTL locally. However, TTL is passed along to delegate.');
  }

  public function testDoubleLifeWithDelete() {
    $this->markTestSkipped('FastArrayDecorator breaks convention: Does not track TTL locally. However, TTL is passed along to delegate.');
  }

  public function testDoubleLifeWithClear() {
    $this->markTestSkipped('FastArrayDecorator breaks convention: Does not track TTL locally. However, TTL is passed along to delegate.');
  }

  public function testObjectDoesNotChangeInCache() {
    $this->markTestSkipped('FastArrayDecorator breaks convention: No deep-copying cache content');
  }

}
