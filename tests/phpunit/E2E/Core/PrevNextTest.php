<?php

namespace E2E\Core;

/**
 * Class PrevNextTest
 *
 * Check that the active prev-next service behaves as expected.
 *
 * @package E2E\Core
 * @group e2e
 */
class PrevNextTest extends \CiviEndToEndTestCase {

  /**
   * @var string
   */
  protected $cacheKey;

  /**
   * @var string
   */
  protected $cacheKeyB;

  /**
   * @var \CRM_Core_PrevNextCache_Interface
   */
  protected $prevNext;

  protected function setUp() {
    parent::setUp();
    $this->prevNext = \Civi::service('prevnext');
    $this->cacheKey = 'PrevNextTest_' . \CRM_Utils_String::createRandom(16, \CRM_Utils_String::ALPHANUMERIC);
    $this->cacheKeyB = 'PrevNextTest_' . \CRM_Utils_String::createRandom(16, \CRM_Utils_String::ALPHANUMERIC);
    $this->assertTrue(
      \CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact') > 25,
      'The contact table must have at least 25 records.'
    );
  }

  protected function tearDown() {
    \Civi::service('prevnext')->deleteItem(NULL, $this->cacheKey);
  }

  public function testFillSql() {
    $start = 0;
    $prefillLimit = 25;
    $sort = NULL;

    $query = new \CRM_Contact_BAO_Query(array(), NULL, NULL, FALSE, FALSE, 1, FALSE, TRUE, FALSE, NULL, 'AND');
    $sql = $query->searchQuery($start, $prefillLimit, $sort, FALSE, $query->_includeContactIds,
      FALSE, TRUE, TRUE);
    $selectSQL = "SELECT DISTINCT %1, contact_a.id, contact_a.sort_name";
    $sql = str_replace(array("SELECT contact_a.id as contact_id", "SELECT contact_a.id as id"), $selectSQL, $sql);

    $this->assertTrue(
      $this->prevNext->fillWithSql($this->cacheKey, $sql, [1 => [$this->cacheKey, 'String']]),
      "fillWithSql should return TRUE on success"
    );

    $this->assertEquals(25, $this->prevNext->getCount($this->cacheKey));
    $this->assertEquals(0, $this->prevNext->getCount('not-a-key-' . $this->cacheKey));

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    $this->assertCount($prefillLimit, $all);
    $this->assertCount($prefillLimit, array_unique(array_keys($all)));
    $this->assertEquals([1], array_unique(array_values($all)));

    $this->assertSelections([]);
  }

  public function testFillArray() {
    $rowSetA = [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 400, 'data' => 'Bob'],
      ['entity_id1' => 200, 'data' => 'Carol'],
    ];
    $rowSetB = [
      ['entity_id1' => 300, 'data' => 'Dave'],
    ];

    $this->assertTrue(
      $this->prevNext->fillWithArray($this->cacheKey, $rowSetA),
      "fillWithArray should return TRUE on success"
    );
    $this->assertTrue(
      $this->prevNext->fillWithArray($this->cacheKey, $rowSetB),
      "fillWithArray should return TRUE on success"
    );

    $this->assertEquals(4, $this->prevNext->getCount($this->cacheKey));
    $this->assertEquals(0, $this->prevNext->getCount('not-a-key-' . $this->cacheKey));

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    $this->assertEquals([100, 400, 200, 300], array_keys($all));
    $this->assertEquals([1], array_unique(array_values($all)));

    $this->assertSelections([]);
  }

  public function testFetch() {
    $this->testFillArray();

    $cids = $this->prevNext->fetch($this->cacheKey, 0, 2);
    $this->assertEquals([100, 400], $cids);

    $cids = $this->prevNext->fetch($this->cacheKey, 0, 4);
    $this->assertEquals([100, 400, 200, 300], $cids);

    $cids = $this->prevNext->fetch($this->cacheKey, 2, 2);
    $this->assertEquals([200, 300], $cids);
  }

  public function getFillFunctions() {
    return [
      ['testFillSql'],
      ['testFillArray'],
    ];
  }

  /**
   * Select and unselect one item.
   *
   * @dataProvider getFillFunctions
   */
  public function testMarkSelection_1($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    list ($id1, $id2) = array_keys($all);
    $this->prevNext->markSelection($this->cacheKey, 'select', $id1);

    $this->assertSelections([$id1]);

    $this->prevNext->markSelection($this->cacheKey, 'unselect', $id1);
    $this->assertSelections([]);
  }

  /**
   * Select and unselect two items.
   *
   * @dataProvider getFillFunctions
   */
  public function testMarkSelection_2($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    list ($id1, $id2, $id3) = array_keys($all);

    $this->prevNext->markSelection($this->cacheKey, 'select', [$id1, $id3]);
    $this->assertSelections([$id1, $id3]);

    $this->prevNext->markSelection($this->cacheKey, 'unselect', $id1);
    $this->assertSelections([$id3]);

    $this->prevNext->markSelection($this->cacheKey, 'select', $id2);
    $this->assertSelections([$id2, $id3]);

    $this->prevNext->markSelection($this->cacheKey, 'unselect');
    $this->assertSelections([]);
  }

  /**
   * Check the neighbors of the first item.
   *
   * @dataProvider getFillFunctions
   */
  public function testGetPosition_first($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    list ($id1, $id2, $id3) = array_keys($all);

    $pos = $this->prevNext->getPositions($this->cacheKey, $id1);

    $this->assertTrue((bool) $pos['foundEntry']);

    $this->assertEquals($id2, $pos['next']['id1']);
    $this->assertTrue(!empty($pos['next']['data']));

    $this->assertTrue(!isset($pos['prev']));
  }

  /**
   * Check the neighbors of a middle item.
   *
   * @dataProvider getFillFunctions
   */
  public function testGetPosition_middle($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    list ($id1, $id2, $id3) = array_keys($all);

    $pos = $this->prevNext->getPositions($this->cacheKey, $id2);
    $this->assertTrue((bool) $pos['foundEntry']);

    $this->assertEquals($id3, $pos['next']['id1']);
    $this->assertTrue(!empty($pos['next']['data']));

    $this->assertEquals($id1, $pos['prev']['id1']);
    $this->assertTrue(!empty($pos['prev']['data']));
  }

  /**
   * Check the neighbors of the last item.
   *
   * @dataProvider getFillFunctions
   */
  public function testGetPosition_last($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    list ($idLast, $idPrev) = array_reverse(array_keys($all));

    $pos = $this->prevNext->getPositions($this->cacheKey, $idLast);
    $this->assertTrue((bool) $pos['foundEntry']);

    $this->assertTrue(!isset($pos['next']));

    $this->assertEquals($idPrev, $pos['prev']['id1']);
    $this->assertTrue(!empty($pos['prev']['data']));
  }

  /**
   * Check the neighbors of the last item.
   *
   * @dataProvider getFillFunctions
   */
  public function testGetPosition_invalid($fillFunction) {
    call_user_func([$this, $fillFunction]);

    $pos = $this->prevNext->getPositions($this->cacheKey, 99999999);
    $this->assertFalse((bool) $pos['foundEntry']);
    $this->assertTrue(!isset($pos['next']));
    $this->assertTrue(!isset($pos['prev']));
  }

  public function testDeleteByCacheKey() {
    // Add background data
    $this->prevNext->fillWithArray($this->cacheKeyB, [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 150, 'data' => 'Dave'],
    ]);
    $this->prevNext->markSelection($this->cacheKeyB, 'select', 100);
    $this->assertSelections([100], 'get', $this->cacheKeyB);
    $this->assertSelections([100, 150], 'getall', $this->cacheKeyB);

    // Add some data that we're actually working with.
    $this->testFillArray();

    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    $this->assertEquals([100, 400, 200, 300], array_keys($all));

    list ($id1, $id2, $id3) = array_keys($all);
    $this->prevNext->markSelection($this->cacheKey, 'select', [$id1, $id3]);
    $this->assertSelections([$id1, $id3]);

    $this->prevNext->deleteItem(NULL, $this->cacheKey);
    $all = $this->prevNext->getSelection($this->cacheKey, 'getall')[$this->cacheKey];
    $this->assertEquals([], array_keys($all));
    $this->assertSelections([]);

    // Ensure background data was untouched.
    $this->assertSelections([100], 'get', $this->cacheKeyB);
    $this->assertSelections([100, 150], 'getall', $this->cacheKeyB);
  }

  public function testDeleteByEntityId() {
    // Fill two caches
    $this->prevNext->fillWithArray($this->cacheKey, [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 150, 'data' => 'Dave'],
    ]);
    $this->prevNext->markSelection($this->cacheKey, 'select', 100);
    $this->assertSelections([100], 'get', $this->cacheKey);
    $this->assertSelections([100, 150], 'getall', $this->cacheKey);

    $this->prevNext->fillWithArray($this->cacheKeyB, [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 400, 'data' => 'Bob'],
    ]);
    $this->prevNext->markSelection($this->cacheKeyB, 'select', [100, 400]);
    $this->assertSelections([100, 400], 'get', $this->cacheKeyB);
    $this->assertSelections([100, 400], 'getall', $this->cacheKeyB);

    // Delete
    $this->prevNext->deleteItem(100);
    $this->assertSelections([], 'get', $this->cacheKey);
    $this->assertSelections([150], 'getall', $this->cacheKey);
    $this->assertSelections([400], 'get', $this->cacheKeyB);
    $this->assertSelections([400], 'getall', $this->cacheKeyB);
  }

  public function testDeleteAll() {
    // Fill two caches
    $this->prevNext->fillWithArray($this->cacheKey, [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 150, 'data' => 'Dave'],
    ]);
    $this->prevNext->markSelection($this->cacheKey, 'select', 100);
    $this->assertSelections([100], 'get', $this->cacheKey);
    $this->assertSelections([100, 150], 'getall', $this->cacheKey);

    $this->prevNext->fillWithArray($this->cacheKeyB, [
      ['entity_id1' => 100, 'data' => 'Alice'],
      ['entity_id1' => 400, 'data' => 'Bob'],
    ]);
    $this->prevNext->markSelection($this->cacheKeyB, 'select', [100, 400]);
    $this->assertSelections([100, 400], 'get', $this->cacheKeyB);
    $this->assertSelections([100, 400], 'getall', $this->cacheKeyB);

    // Delete
    $this->prevNext->deleteItem(NULL, NULL);
    $this->assertSelections([], 'get', $this->cacheKey);
    $this->assertSelections([], 'getall', $this->cacheKey);
    $this->assertSelections([], 'get', $this->cacheKeyB);
    $this->assertSelections([], 'getall', $this->cacheKeyB);
  }

  /**
   * Assert that the current cacheKey has a list of selected contact IDs.
   *
   * @param array $ids
   *   Contact IDs that should be selected.
   * @param string $action
   * @param string|NULL $cacheKey
   */
  protected function assertSelections($ids, $action = 'get', $cacheKey = NULL) {
    if ($cacheKey === NULL) {
      $cacheKey = $this->cacheKey;
    }
    $selected = $this->prevNext->getSelection($cacheKey, $action)[$cacheKey];
    $this->assertEquals($ids, array_keys($selected));
    $this->assertCount(count($ids), $selected);
  }

}
