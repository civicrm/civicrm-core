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

use Civi\Api4\Group;

/**
 * Test class for CRM_Contact_BAO_GroupContact BAO
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Contact_BAO_GroupContactCacheTest extends CiviUnitTestCase {

  /**
   * Manually add and remove contacts from a smart group.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testManualAddRemove(): void {
    [$group, $living, $deceased] = $this->setupSmartGroup();

    // Add $n1 to $g
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $living[0],
      'group_id' => $group->id,
    ]);

    CRM_Contact_BAO_GroupContactCache::load($group);
    $this->assertCacheMatches(
      [$deceased[0], $deceased[1], $deceased[2], $living[0]],
      $this->ids['Group'][0]
    );

    // Remove $y1 from $g
    $this->callAPISuccess('group_contact', 'create', [
      'contact_id' => $deceased[0],
      'group_id' => $group->id,
      'status' => 'Removed',
    ]);

    CRM_Contact_BAO_GroupContactCache::load($group);
    $this->assertCacheMatches(
      [
        $deceased[1],
        $deceased[2],
        $living[0],
      ],
      $group->id
    );
  }

  /**
   * Allow removing contact from a parent group even if contact is in a child
   * group. (CRM-8858).
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testRemoveFromParentSmartGroup(): void {
    // Create $c1, $c2, $c3
    $deceased[] = $this->individualCreate(['is_deceased' => 1]);
    $deceased[] = $this->individualCreate(['is_deceased' => 1]);
    $deceased[] = $this->individualCreate(['is_deceased' => 1]);
    // Create smart group $parent
    $params = [
      'name' => 'Deceased Contacts',
      'title' => 'Deceased Contacts',
      'is_active' => 1,
      'formValues' => ['is_deceased' => 1],
    ];
    $parent = $this->createSmartGroup($params);

    // Create group $child in $parent
    $child = $this->callAPISuccess('Group', 'create', [
      'name' => 'Child Group',
      'title' => 'Child Group',
      'is_active' => 1,
      'parents' => [$parent->id => 1],
    ]);

    // Add $c1, $c2, $c3 to $child
    foreach ($deceased as $contact) {
      $this->callAPISuccess('group_contact', 'create', [
        'contact_id' => $contact,
        'group_id' => $child['id'],
      ]);
    }

    CRM_Contact_BAO_GroupContactCache::load($parent);
    $this->assertCacheMatches(
      [$deceased[0], $deceased[1], $deceased[2]],
      $parent->id
    );

    // Remove $c1 from $parent
    $this->callAPISuccess('GroupContact', 'create', [
      'contact_id' => $deceased[0],
      'group_id' => $parent->id,
      'status' => 'Removed',
    ]);

    // Assert $c1 not in $parent
    CRM_Contact_BAO_GroupContactCache::load($parent);
    $this->assertCacheMatches(
      [
        $deceased[1],
        $deceased[2],
      ],
      $parent->id
    );

    // Assert $c1 still in $child
    $this->assertDBQuery(1,
      'select count(*) from civicrm_group_contact where group_id=%1 and contact_id=%2 and status=%3',
      [
        1 => [$child['id'], 'Integer'],
        2 => [$deceased[0], 'Integer'],
        3 => ['Added', 'String'],
      ]
    );
  }

  /**
   * Assert that the cache for a group contains exactly the listed contacts.
   *
   * @param array $expectedContactIds
   *   Array(int).
   * @param int $groupId
   */
  public function assertCacheMatches(array $expectedContactIds, int $groupId): void {
    $sql = 'SELECT contact_id FROM civicrm_group_contact_cache WHERE group_id = %1';
    $params = [1 => [$groupId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $actualContactIds = [];
    while ($dao->fetch()) {
      $actualContactIds[] = $dao->contact_id;
    }

    sort($expectedContactIds);
    sort($actualContactIds);
    $this->assertEquals($expectedContactIds, $actualContactIds);
  }

  /**
   * Test the opportunistic refresh cache function does not touch non-expired entries.
   */
  public function testOpportunisticRefreshCacheNoChangeIfNotExpired(): void {
    [$group, , $deceased] = $this->setupSmartGroup();
    $this->callAPISuccess('Contact', 'create', ['id' => $deceased[0], 'is_deceased' => 0]);
    $this->assertCacheMatches(
      [$deceased[0], $deceased[1], $deceased[2]],
      $group->id
    );
    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    $this->assertCacheNotRefreshed($deceased, $group->id, $group->cache_date);
  }

  /**
   * Test the opportunistic refresh cache function does refresh stale entries.
   */
  public function testOpportunisticRefreshChangeIfCacheDateFieldStale(): void {
    [$group, , $deceased] = $this->setupSmartGroup();
    $this->callAPISuccess('Contact', 'create', ['id' => $deceased[0], 'is_deceased' => 0]);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_group SET cache_date = DATE_SUB(NOW(), INTERVAL 7 MINUTE) WHERE id = ' . $group->id);
    $group->find(TRUE);
    Civi::$statics['CRM_Contact_BAO_GroupContactCache']['is_refresh_init'] = FALSE;
    sleep(1);
    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    $this->assertCacheRefreshed($this->ids['Group'][0]);
  }

  /**
   * Test the opportunistic refresh cache function does refresh expired entries
   * if mode is deterministic.
   *
   * @throws \API_Exception
   */
  public function testOpportunisticRefreshNoChangeWithDeterministicSetting(): void {
    [, , $deceased] = $this->setupSmartGroup();
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'deterministic']);
    $this->callAPISuccess('Contact', 'create', ['id' => $this->ids['Contact']['dead1'], 'is_deceased' => 0]);
    $this->makeCacheStale($this->ids['Group'][0]);
    $cacheDate = Group::get()
      ->addWhere('id', '=', $this->ids['Group'][0])
      ->addSelect('cache_date')->execute()->first()['cache_date'];
    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();
    $this->assertCacheNotRefreshed($deceased, $this->ids['Group'][0], $cacheDate);
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'opportunistic']);
  }

  /**
   * Test the deterministic cache function refreshes with the deterministic setting.
   */
  public function testDeterministicRefreshChangeWithDeterministicSetting(): void {
    $this->setupSmartGroup();
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'deterministic']);
    $this->callAPISuccess('Contact', 'create', ['id' => $this->ids['Contact']['dead1'], 'is_deceased' => 0]);
    $this->makeCacheStale($this->ids['Group'][0]);
    CRM_Contact_BAO_GroupContactCache::deterministicCacheFlush();
    $this->assertCacheRefreshed($this->ids['Group'][0]);
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'opportunistic']);
  }

  /**
   * Test the deterministic cache function refresh doesn't mess up non-expired.
   */
  public function testDeterministicRefreshChangeDoesNotTouchNonExpired(): void {
    [$group, , $deceased] = $this->setupSmartGroup();
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'deterministic']);
    $this->callAPISuccess('Contact', 'create', ['id' => $deceased[0], 'is_deceased' => 0]);
    CRM_Contact_BAO_GroupContactCache::deterministicCacheFlush();
    $this->assertCacheNotRefreshed($deceased, $group->id, $group->cache_date);
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'opportunistic']);
  }

  /**
   * Test the deterministic cache function refreshes with the opportunistic setting.
   *
   * (hey it's an opportunity!).
   */
  public function testDeterministicRefreshChangeWithOpportunisticSetting(): void {
    [, , $deceased] = $this->setupSmartGroup();
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'opportunistic']);
    $this->callAPISuccess('Contact', 'create', ['id' => $deceased[0], 'is_deceased' => 0]);
    $this->makeCacheStale($this->ids['Group'][0]);
    CRM_Contact_BAO_GroupContactCache::deterministicCacheFlush();
    $this->assertCacheRefreshed($this->ids['Group'][0]);
  }

  /**
   * Test the api job wrapper around the deterministic refresh works.
   */
  public function testJobWrapper(): void {
    $this->setupSmartGroup();
    $this->callAPISuccess('Setting', 'create', ['smart_group_cache_refresh_mode' => 'opportunistic']);
    $this->callAPISuccess('Contact', 'create', ['id' => $this->ids['Contact']['dead1'], 'is_deceased' => 0]);
    $this->makeCacheStale($this->ids['Group'][0]);
    $this->callAPISuccess('Job', 'group_cache_flush', []);
    $this->assertCacheRefreshed($this->ids['Group'][0]);
  }

  // *** Everything below this should be moved to parent class ****

  /**
   * Tears down the fixture, for example, closes a network connection.
   *
   * This method is called after a test is executed.
   */
  protected function tearDown(): void {
    $this->quickCleanup(['civicrm_contact', 'civicrm_group', 'civicrm_saved_search', 'civicrm_group_contact']);
    parent::tearDown();
  }

  /**
   * Set up a smart group testing scenario.
   *
   * @return array
   */
  protected function setupSmartGroup(): array {
    try {
      // Create contacts $y1, $y2, $y3 which do match $g; create $n1, $n2, $n3 which do not match $g
      [$living, $deceased] = $this->createLivingDead();

      $params = [
        'name' => 'Deceased Contacts',
        'title' => 'Deceased Contacts',
        'is_active' => 1,
        'formValues' => ['is_deceased' => 1],
      ];
      $group = $this->createSmartGroup($params);
      $this->ids['Group'][0] = $group->id;
      // Assert: $g cache has exactly $y1, $y2, $y3
      CRM_Contact_BAO_GroupContactCache::load($group);
      $group->find(TRUE);
      $this->assertCacheMatches(
        [$deceased[0], $deceased[1], $deceased[2]],
        $group->id
      );
      return [$group, $living, $deceased];
    }
    catch (CRM_Core_Exception | API_Exception | CiviCRM_API3_Exception $e) {
      $this->fail('failed test setup' . $e->getMessage());
    }
    // unreachable but it cheers up IDE analysis.
    return [];
  }

  /**
   * @param array $deceased
   * @param int $groupID
   * @param string $cacheDate
   */
  protected function assertCacheNotRefreshed(array $deceased, int $groupID, string $cacheDate): void {
    $this->assertCacheMatches(
      [$deceased[0], $deceased[1], $deceased[2]],
      $groupID
    );
    $afterGroup = $this->callAPISuccessGetSingle('Group', ['id' => $groupID]);
    $this->assertEquals($cacheDate, $afterGroup['cache_date']);
  }

  /**
   * Make the cache for the group stale, resetting it to before the timeout period.
   *
   * @param int $groupID
   */
  protected function makeCacheStale(int $groupID): void {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_group SET cache_date = DATE_SUB(NOW(), INTERVAL 7 MINUTE) WHERE id = ' . $groupID);
    Civi::$statics['CRM_Contact_BAO_GroupContactCache']['is_refresh_init'] = FALSE;
  }

  /**
   * @param int $groupID
   */
  protected function assertCacheRefreshed(int $groupID): void {
    $this->assertCacheMatches(
      [],
      $groupID
    );

    $afterGroup = $this->callAPISuccessGetSingle('Group', ['id' => $groupID]);
    $this->assertArrayNotHasKey('cache_date', $afterGroup, 'cache date should not be set as the cache is not built');
  }

  /**
   * Test Smart group search
   *
   * @throws \CRM_Core_Exception
   */
  public function testSmartGroupSearchBuilder(): void {
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'group' => 1,
    ];
    [$group] = $this->setupSmartGroup();

    $params = [
      'name' => 'Living Contacts',
      'title' => 'Living Contacts',
      'is_active' => 1,
      'formValues' => ['is_deceased' => 0],
    ];
    $group2 = $this->createSmartGroup($params);

    //Filter on smart group with =, !=, IN and NOT IN operator.
    $params = [['group', '=', $group2->id, 1, 0]];
    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      FALSE, FALSE
    );
    $query->searchQuery(0, 0, NULL,
      FALSE, FALSE, FALSE,
      TRUE
    );
    $key = $query->getGroupCacheTableKeys()[0];
    $expectedWhere = "civicrm_group_contact_cache_$key.group_id IN (\"$group2->id\")";
    $this->assertStringContainsString($expectedWhere, $query->_whereClause);
    $this->assertContactIDsAreInCache($query, "group_id = $group2->id");

    $params = [['group', '!=', $group->id, 1, 0]];
    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      FALSE, FALSE
    );
    $key = $query->getGroupCacheTableKeys()[0];
    //Assert if proper where clause is present.
    $expectedWhere = "civicrm_group_contact_$key.group_id != $group->id AND civicrm_group_contact_cache_$key.group_id IS NULL OR  ( civicrm_group_contact_cache_$key.contact_id NOT IN (SELECT contact_id FROM civicrm_group_contact_cache cgcc WHERE cgcc.group_id IN ( $group->id ) ) )";
    $this->assertStringContainsString($expectedWhere, $query->_whereClause);
    $this->assertContactIDsAreInCache($query, "group_id != $group->id");

    $params = [['group', 'IN', [$group->id, $group2->id], 1, 0]];
    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      FALSE, FALSE
    );
    $key = $query->getGroupCacheTableKeys()[0];
    $expectedWhere = "civicrm_group_contact_cache_$key.group_id IN (\"$group->id\", \"$group2->id\")";
    $this->assertStringContainsString($expectedWhere, $query->_whereClause);
    $this->assertContactIDsAreInCache($query, "group_id IN ($group->id, $group2->id)");

    $params = [['group', 'NOT IN', [$group->id], 1, 0]];
    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      FALSE, FALSE
    );
    $key = $query->getGroupCacheTableKeys()[0];
    $expectedWhere = "civicrm_group_contact_$key.group_id NOT IN ( $group->id ) AND civicrm_group_contact_cache_$key.group_id IS NULL OR  ( civicrm_group_contact_cache_$key.contact_id NOT IN (SELECT contact_id FROM civicrm_group_contact_cache cgcc WHERE cgcc.group_id IN ( $group->id ) ) )";
    $this->assertStringContainsString($expectedWhere, $query->_whereClause);
    $this->assertContactIDsAreInCache($query, "group_id NOT IN ($group->id)");
    $this->callAPISuccess('group', 'delete', ['id' => $group->id]);
    $this->callAPISuccess('group', 'delete', ['id' => $group2->id]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testMultipleGroupWhereClause(): void {
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'group' => 1,
    ];
    [$group] = $this->setupSmartGroup();

    $params = [
      'name' => 'Living Contacts',
      'title' => 'Living Contacts',
      'is_active' => 1,
      'formValues' => ['is_deceased' => 0],
    ];
    $group2 = $this->createSmartGroup($params);

    //Filter on smart group with =, !=, IN and NOT IN operator.
    $params = [['group', '=', $group2->id, 1, 0], ['group', '=', $group->id, 1, 0]];
    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTACTS,
      FALSE,
      FALSE, FALSE
    );
    $query->searchQuery(0, 0, NULL,
      FALSE, FALSE, FALSE,
      TRUE
    );
    [$key1, $key2] = $query->getGroupCacheTableKeys();

    $expectedWhere = 'civicrm_group_contact_cache_' . $key1 . '.group_id IN ("' . $group2->id . '") )  )  AND  (  ( civicrm_group_contact_cache_' . $key2 . '.group_id IN ("' . $group->id . '")';
    $this->assertStringContainsString($expectedWhere, $query->_whereClause);
    // Check that we have 3 joins to the group contact cache 1 for each of the group where clauses and 1 for the fact we are returning groups in the select.
    $expectedFrom1 = 'LEFT JOIN civicrm_group_contact_cache civicrm_group_contact_cache_' . $key1 . ' ON contact_a.id = civicrm_group_contact_cache_' . $key1 . '.contact_id';
    $this->assertStringContainsString($expectedFrom1, $query->_fromClause);
    $expectedFrom2 = 'LEFT JOIN civicrm_group_contact_cache civicrm_group_contact_cache_' . $key2 . ' ON contact_a.id = civicrm_group_contact_cache_' . $key2 . '.contact_id';
    $this->assertStringContainsString($expectedFrom2, $query->_fromClause);
    $expectedFrom3 = 'LEFT JOIN civicrm_group_contact_cache ON contact_a.id = civicrm_group_contact_cache.contact_id';
    $this->assertStringContainsString($expectedFrom3, $query->_fromClause);
  }

  /**
   * Check if contact ids are fetched correctly.
   *
   * @param \CRM_Contact_BAO_Query $query
   * @param string $groupWhereClause
   */
  public function assertContactIDsAreInCache(CRM_Contact_BAO_Query $query, string $groupWhereClause): void {
    $contactIds = explode(',', $query->searchQuery(0, 0, NULL,
      FALSE, FALSE, FALSE,
      TRUE
    ));
    $expectedContactIds = [];
    $groupDAO = CRM_Core_DAO::executeQuery("SELECT contact_id FROM civicrm_group_contact_cache WHERE $groupWhereClause");
    while ($groupDAO->fetch()) {
      $expectedContactIds[] = $groupDAO->contact_id;
    }
    $this->assertEquals(sort($expectedContactIds), sort($contactIds));
  }

  /**
   * @return array[]
   */
  protected function createLivingDead(): array {
    $living[] = $this->individualCreate();
    $living[] = $this->individualCreate();
    $living[] = $this->individualCreate();
    $deceased[] = $this->ids['Contact']['dead1'] = $this->individualCreate(['is_deceased' => 1]);
    $deceased[] = $this->ids['Contact']['dead2'] = $this->individualCreate(['is_deceased' => 1]);
    $deceased[] = $this->ids['Contact']['dead3'] = $this->individualCreate(['is_deceased' => 1]);
    return [$living, $deceased];
  }

  /**
   * Creates two entities: a Group and a SavedSearch
   *
   * @param array $params
   *
   * @return CRM_Contact_BAO_Group
   */
  protected function createSmartGroup(array $params): CRM_Contact_BAO_Group {
    $ssParams = ['form_values' => $params['formValues'], 'is_active' => 1];
    $savedSearch = CRM_Contact_BAO_SavedSearch::create($ssParams);
    $params['saved_search_id'] = $savedSearch->id;
    return CRM_Contact_BAO_Group::create($params);
  }

}
