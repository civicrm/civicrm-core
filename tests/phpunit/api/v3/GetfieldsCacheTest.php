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
 * Test the APIv3 getfields request cache.
 *
 * @group headless
 */
class api_v3_GetfieldsCacheTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    Civi::$statics['civicrm_api3_generic_getfields'] = [];
    Civi::$statics['api_v3_getfields_cache_test'] = ['spec_calls' => 0];
  }

  protected function tearDown(): void {
    unset(Civi::$statics['civicrm_api3_generic_getfields']);
    unset(Civi::$statics['api_v3_getfields_cache_test']);
    parent::tearDown();
  }

  public function testIdenticalRequestsAndCacheClear(): void {
    $first = $this->getFields([
      'cache_clear' => 1,
      'variant' => 'first',
    ]);
    $second = $this->getFields(['variant' => 'first']);

    $this->assertSame('first', $first['values']['cache_probe']['title']);
    $this->assertSame($first, $second);
    $this->assertSame(1, $this->getSpecCallCount());

    $rebuilt = $this->getFields([
      'cache_clear' => 1,
      'variant' => 'first',
    ]);
    $afterRebuild = $this->getFields(['variant' => 'first']);

    $this->assertSame($rebuilt, $afterRebuild);
    $this->assertSame(2, $this->getSpecCallCount());
  }

  public function testParametersUseSeparateEntries(): void {
    $first = $this->getFields([
      'cache_clear' => 1,
      'variant' => 'first',
    ]);
    $second = $this->getFields(['variant' => 'second']);
    $firstAgain = $this->getFields(['variant' => 'first']);

    $this->assertSame('first', $first['values']['cache_probe']['title']);
    $this->assertSame('second', $second['values']['cache_probe']['title']);
    $this->assertSame($first, $firstAgain);
    $this->assertSame(2, $this->getSpecCallCount());
  }

  public function testUniqueModesUseSeparateEntries(): void {
    $this->getFields(['cache_clear' => 1], TRUE);
    $this->getFields([], FALSE);
    $this->getFields([], FALSE);

    $this->assertSame(2, $this->getSpecCallCount());
  }

  public function testSequentialModesUseSeparateEntries(): void {
    $this->getFields(['cache_clear' => 1]);
    $this->getFields(['sequential' => 1]);
    $this->getFields(['sequential' => 1]);

    $this->assertSame(2, $this->getSpecCallCount());
  }

  public function testOptionRequestsBypassCache(): void {
    $params = [
      'options' => ['get_options' => []],
    ];
    $this->getFields(['cache_clear' => 1] + $params);
    $this->getFields($params);

    $this->assertSame(2, $this->getSpecCallCount());
  }

  public function testMetadataClearInvalidatesCache(): void {
    $first = $this->getFields([
      'cache_clear' => 1,
      'variant' => 'first',
    ]);
    $cached = $this->getFields(['variant' => 'first']);

    $this->assertSame($first, $cached);
    $this->assertSame(1, $this->getSpecCallCount());

    Civi::cache('metadata')->clear();
    $rebuilt = $this->getFields(['variant' => 'first']);

    $this->assertSame($first, $rebuilt);
    $this->assertSame(2, $this->getSpecCallCount());
  }

  /**
   * Invoke the generic getfields implementation for the test action.
   */
  private function getFields(array $params = [], bool $unique = TRUE): array {
    $params += ['action' => 'cacheprobe'];
    return civicrm_api3_generic_getfields([
      'entity' => 'Contact',
      'version' => 3,
      'params' => $params,
    ], $unique);
  }

  /**
   * Get the number of times the test action's spec callback has run.
   */
  private function getSpecCallCount(): int {
    return Civi::$statics['api_v3_getfields_cache_test']['spec_calls'];
  }

}

/**
 * Test-only API action used to make its getfields spec observable.
 */
function civicrm_api3_contact_cacheprobe($params) {
  return civicrm_api3_create_success([], $params, 'Contact', 'cacheprobe');
}

/**
 * Add an observable, parameter-dependent field to the test action's spec.
 */
function _civicrm_api3_contact_cacheprobe_spec(&$fields, $apiRequest): void {
  Civi::$statics['api_v3_getfields_cache_test']['spec_calls']++;
  $fields['cache_probe'] = [
    'title' => $apiRequest['params']['variant'] ?? 'default',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}
