<?php
namespace Civi\Afform;

use Civi\Api4\Afform;
use Civi\Api4\AfformBehavior;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Api4\Utils\CoreUtil;
use Civi\Test\Api4TestTrait;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Guards against a performance regression in reference counting.
 *
 * When a search display or saved search is referenced by an Afform,
 * hook_civicrm_referenceCounts must answer from a per-request index of the
 * forms rather than re-parsing every form on every check. Parsing a form loads
 * Afform behaviors, and the CiviCase autofill behavior queries CaseType and
 * RelationshipType, so re-parsing per check produced tens of thousands of
 * identical queries on sites with many managed searches/displays.
 *
 * civi_case is installed so the CiviCase autofill behavior is active - that is
 * the behavior whose queries exposed the problem.
 *
 * @group headless
 */
class AfformReferenceCountTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  use Api4TestTrait;

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.search_kit', 'org.civicrm.afform', 'civi_case'])
      ->apply();
  }

  public function tearDown(): void {
    Afform::revert(FALSE)->addWhere('has_local', '=', TRUE)->execute();
    $this->conditionallyDeleteTestRecords();
    parent::tearDown();
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
   * Reference counting must not re-parse every form (and thereby re-run the
   * CiviCase behavior queries) for each saved search and display it checks.
   */
  public function testReferenceCountingDoesNotReParseForms(): void {
    $search = SavedSearch::save(FALSE)
      ->addRecord([
        'name' => 'TestRefCountSearch',
        'label' => 'TestRefCountSearch',
        'api_entity' => 'Contact',
        'api_params' => ['version' => 4, 'select' => ['id']],
      ])
      ->setMatch(['name'])
      ->execute()->first();

    $display = SearchDisplay::save(FALSE)
      ->addRecord([
        'name' => 'TestRefCountDisplay',
        'label' => 'TestRefCountDisplay',
        'saved_search_id.name' => 'TestRefCountSearch',
        'type' => 'table',
        'settings' => ['columns' => [['key' => 'id', 'label' => 'ID', 'type' => 'field']]],
      ])
      ->setMatch(['saved_search_id', 'name'])
      ->execute()->first();

    Afform::create(FALSE)
      ->addValue('name', 'testRefCountForm')
      ->addValue('title', 'testRefCountForm')
      ->addValue('type', 'search')
      ->setLayoutFormat('html')
      ->addValue('layout', '<div><crm-search-display-table search-name="TestRefCountSearch" display-name="TestRefCountDisplay"></crm-search-display-table></div>')
      ->execute();

    $counts = $this->countApiGets(['CaseType', 'RelationshipType', 'Afform'], function () use ($search, $display) {
      // Both checks should be answered from a single per-request index.
      $displayRefs = CoreUtil::getRefCount('SearchDisplay', $display['id']);
      $searchRefs = CoreUtil::getRefCount('SavedSearch', $search['id']);

      // The form is counted as a reference of both the display and the search.
      $this->assertEquals('Afform', $displayRefs[0]['type']);
      $this->assertEquals(1, array_column($displayRefs, 'count', 'type')['Afform']);
      $this->assertEquals(1, array_column($searchRefs, 'count', 'type')['Afform']);
    });

    // The behavior queries that scaled with (checks x forms) must not run at all:
    // reading search_displays does not require converting the layout to an array.
    $this->assertSame(0, $counts['CaseType'], 'Reference counting should not trigger CaseType lookups');
    $this->assertSame(0, $counts['RelationshipType'], 'Reference counting should not trigger RelationshipType lookups');
    // The form index is built once and reused for every check in the request.
    $this->assertLessThanOrEqual(1, $counts['Afform'], 'The Afform index should be built at most once per request');
  }

  /**
   * The CiviCase autofill behavior must build its case-role modes once per
   * request, not re-query CaseType/RelationshipType on every form it is asked about.
   */
  public function testCaseRoleModesAreCachedPerRequest(): void {
    // Warm the cache with one lookup.
    $firstPass = $this->countApiGets(['CaseType', 'RelationshipType'], function () {
      AfformBehavior::get(FALSE)->addWhere('key', '=', 'autofill')->execute();
    });
    $this->assertGreaterThan(0, $firstPass['CaseType'], 'The first behavior lookup should query CaseType');

    // A second lookup in the same request must be fully served from cache.
    $secondPass = $this->countApiGets(['CaseType', 'RelationshipType'], function () {
      AfformBehavior::get(FALSE)->addWhere('key', '=', 'autofill')->execute();
    });
    $this->assertSame(0, $secondPass['CaseType'], 'Case-role modes should be cached after the first lookup');
    $this->assertSame(0, $secondPass['RelationshipType'], 'Case-role modes should be cached after the first lookup');
  }

}
