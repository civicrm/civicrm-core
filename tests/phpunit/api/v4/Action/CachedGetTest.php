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


namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\CustomGroup;

/**
 * Test Api4-level caching (currently = CustomGroup)
 *
 *
 * @group headless
 */
class CachedGetTest extends Api4TestBase {

  /**
   * @inheritdoc
   */
  public function setUp(): void {
    parent::setUp();

    CustomGroup::create(FALSE)
      ->addValue('name', 'LemonPreferences')
      ->addValue('title', 'Lemon')
      ->addValue('extends', 'Contact')
      ->addValue('pre_help', 'Some people think lemons are all the same, but some have preferences.')
      ->execute();
  }

  /**
   * @inheritDoc
   */
  public function tearDown(): void {
    CustomGroup::delete(FALSE)
      ->addWhere('name', '=', 'LemonPreferences')
      ->execute();
    parent::tearDown();
  }

  /**
   * @return \Civi\Api4\Action\AbstractAction[]
   */
  protected function cacheableCalls(): array {
    $calls = [];

    $calls[] = CustomGroup::get(FALSE)
      ->addWhere('name', 'CONTAINS', 'lemon');

    // note: comparison should be case-insensitive
    $calls[] = CustomGroup::get(FALSE)
      ->addWhere('extends', '=', 'contact');

    return $calls;
  }

  /**
   * @return \Civi\Api4\Action\AbstractAction[]
   */
  protected function uncacheableCalls(): array {
    $calls = [];

    // cache cant GROUP BY
    $calls[] = CustomGroup::get(FALSE)
      ->addSelect('extends', 'COUNT(id) AS count')
      ->addGroupBy('extends');

    // cache cant do SQL functions
    $calls[] = CustomGroup::get(FALSE)
      ->addSelect('UPPER(name)');

    // TODO: cache cant do implicit joins (but none available on CustomGroup)

    return $calls;
  }

  /**
   * For easy calls the cached result should
   * match the database result
   */
  public function testCachedGetMatchesDatabase(): void {
    foreach ($this->cacheableCalls() as $call) {
      // we need two copies of the API action object
      $dbCall = clone $call;

      $cacheResult = (array) $call->setUseCache(TRUE)->execute();

      $dbResult = (array) $dbCall->setUseCache(FALSE)->execute();

      $this->assertEquals($cacheResult, $dbResult);
    }
  }

  /**
   * For hard calls the default result should
   * match the database result
   * (the API should determine it needs to escalate
   * to a DB call if `useCache` is left unspecified)
   */
  public function testHardQueryUsesDatabaseByDefault(): void {
    foreach ($this->uncacheableCalls() as $call) {
      // we need two copies of the API action object
      $dbCall = clone $call;

      $defaultResult = (array) $call->execute();

      $dbResult = (array) $dbCall->setUseCache(FALSE)->execute();

      $this->assertEquals($defaultResult, $dbResult);
    }
  }

}
