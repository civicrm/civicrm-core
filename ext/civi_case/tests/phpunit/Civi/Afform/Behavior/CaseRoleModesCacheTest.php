<?php
namespace Civi\Afform\Behavior;

use Civi\Api4\AfformBehavior;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * ContactAutofillBasedOnCase builds its case-role autofill options from CaseType
 * and RelationshipType. Those don't change within a request and the options
 * depend only on the contact type, so the options are cached per contact type and
 * the supporting data is fetched once per request. This guards that caching.
 *
 * afform is installed to provide the AfformBehavior entity used to trigger the
 * behavior.
 *
 * @group headless
 */
class CaseRoleModesCacheTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.afform', 'civi_case'])
      ->apply();
  }

  /**
   * Counts selected get-API calls fired while running $callback.
   *
   * @param string[] $entities
   * @param callable $callback
   * @return array
   *   Entity name => number of get calls.
   */
  private function countApiGets(array $entities, callable $callback): array {
    $counts = array_fill_keys($entities, 0);
    $listener = function ($e) use (&$counts) {
      $request = $e->getApiRequest();
      if (is_object($request)
        && method_exists($request, 'getActionName') && $request->getActionName() === 'get'
        && method_exists($request, 'getEntityName') && isset($counts[$request->getEntityName()])
      ) {
        $counts[$request->getEntityName()]++;
      }
    };
    \Civi::dispatcher()->addListener('civi.api.prepare', $listener);
    try {
      $callback();
    }
    finally {
      \Civi::dispatcher()->removeListener('civi.api.prepare', $listener);
    }
    return $counts;
  }

  /**
   * The first behavior lookup queries CaseType/RelationshipType; a second lookup
   * in the same request is served from cache and returns the same options.
   */
  public function testCaseRoleModesAreCachedPerRequest(): void {
    $first = NULL;
    $firstPass = $this->countApiGets(['CaseType', 'RelationshipType'], function () use (&$first) {
      $first = AfformBehavior::get(FALSE)->addWhere('key', '=', 'autofill')->execute()->single();
    });
    $this->assertGreaterThan(0, $firstPass['CaseType'], 'The first behavior lookup should query CaseType');

    $second = NULL;
    $secondPass = $this->countApiGets(['CaseType', 'RelationshipType'], function () use (&$second) {
      $second = AfformBehavior::get(FALSE)->addWhere('key', '=', 'autofill')->execute()->single();
    });
    $this->assertSame(0, $secondPass['CaseType'], 'Case-role modes should be cached after the first lookup');
    $this->assertSame(0, $secondPass['RelationshipType'], 'Case-role modes should be cached after the first lookup');

    // The cached lookup returns exactly the same options as the uncached one.
    $this->assertSame($first['modes'], $second['modes']);
  }

}
