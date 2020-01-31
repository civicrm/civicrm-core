<?php

/**
 *  Include dataProvider for tests
 *
 * @group headless
 */
class CRM_Contact_BAO_QueryTest extends CiviUnitTestCase {

  use CRMTraits_Financial_FinancialACLTrait;
  use CRMTraits_Financial_PriceSetTrait;

  /**
   * @return CRM_Contact_BAO_QueryTestDataProvider
   */
  public function dataProvider() {
    return new CRM_Contact_BAO_QueryTestDataProvider();
  }

  public function setUp() {
    parent::setUp();
  }

  /**
   * Clean up after test.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $tablesToTruncate = [
      'civicrm_group_contact',
      'civicrm_group',
      'civicrm_saved_search',
      'civicrm_entity_tag',
      'civicrm_tag',
      'civicrm_contact',
      'civicrm_address',
    ];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  /**
   *  Test CRM_Contact_BAO_Query::searchQuery().
   *
   * @dataProvider dataProvider
   *
   * @param $fv
   * @param $count
   * @param $ids
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearch($fv, $count, $ids) {
    $this->callAPISuccess('SavedSearch', 'create', ['form_values' => 'a:9:{s:5:"qfKey";s:32:"0123456789abcdef0123456789abcdef";s:13:"includeGroups";a:1:{i:0;s:1:"3";}s:13:"excludeGroups";a:0:{}s:11:"includeTags";a:0:{}s:11:"excludeTags";a:0:{}s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";s:14:"customSearchID";s:1:"4";s:17:"customSearchClass";s:36:"CRM_Contact_Form_Search_Custom_Group";}']);
    $this->callAPISuccess('SavedSearch', 'create', ['form_values' => 'a:9:{s:5:"qfKey";s:32:"0123456789abcdef0123456789abcdef";s:13:"includeGroups";a:1:{i:0;s:1:"3";}s:13:"excludeGroups";a:0:{}s:11:"includeTags";a:0:{}s:11:"excludeTags";a:0:{}s:4:"task";s:2:"14";s:8:"radio_ts";s:6:"ts_all";s:14:"customSearchID";s:1:"4";s:17:"customSearchClass";s:36:"CRM_Contact_Form_Search_Custom_Group";}']);

    $tag7 = $this->ids['Tag'][7] = $this->tagCreate(['name' => 'Test Tag 7', 'description' => 'Test Tag 7'])['id'];
    $tag9 = $this->ids['Tag'][9] = $this->tagCreate(['name' => 'Test Tag 9', 'description' => 'Test Tag 9'])['id'];
    $this->tagCreate(['name' => 'Test Tag 10']);
    $groups = [
      3 => ['name' => 'Test Group 3'],
      4 => ['name' => 'Test Smart Group 4', 'saved_search_id' => 1],
      5 => ['name' => 'Test Group 5'],
      6 => ['name' => 'Test Smart Group 6', 'saved_search_id' => 2],
    ];

    foreach ($groups as $id => $group) {
      $this->ids['Group'][$id] = $this->groupCreate(array_merge($group, ['title' => $group['name']]));
    }
    $individuals = [
      ['first_name' => 'Test', 'last_name' => 'Test Contact 9', 'gender_id' => 1, 'prefix_id' => 1, 'suffix_id' => 1],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 10', 'gender_id' => 2, 'prefix_id' => 2, 'suffix_id' => 2, 'api.entity_tag.create' => ['tag_id' => $tag9]],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 11', 'gender_id' => 3, 'prefix_id' => 3, 'suffix_id' => 3, 'api.entity_tag.create' => ['tag_id' => $tag7]],
      [
        'first_name' => 'Test',
        'last_name' => 'Test Contact 12',
        'gender_id' => 3,
        'prefix_id' => 4,
        'suffix_id' => 4,
        'api.entity_tag.create' => ['tag_id' => $tag9],
        'api.entity_tag.create.2' => ['tag_id' => $tag7],
      ],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 13', 'gender_id' => 2, 'prefix_id' => 2, 'suffix_id' => 2],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 14', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 4, 'api.entity_tag.create' => ['tag_id' => $tag9]],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 15', 'gender_id' => 3, 'prefix_id' => 4, 'suffix_id' => 5, 'api.entity_tag.create' => ['tag_id' => $tag7]],
      [
        'first_name' => 'Test',
        'last_name' => 'Test Contact 16',
        'gender_id' => 3,
        'prefix_id' => 4,
        'suffix_id' => 6,
        'api.entity_tag.create' => ['tag_id' => $tag9],
        'api.entity_tag.create.2' => ['tag_id' => $tag7],
      ],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 17', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 7],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 18', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 4, 'api.entity_tag.create' => ['tag_id' => $tag9]],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 19', 'gender_id' => 2, 'prefix_id' => 4, 'suffix_id' => 6, 'api.entity_tag.create.2' => ['tag_id' => $tag7]],
      [
        'first_name' => 'Test',
        'last_name' => 'Test Contact 20',
        'gender_id' => 1,
        'prefix_id' => 4,
        'suffix_id' => 6,
        'api.entity_tag.create' => ['tag_id' => $tag9],
        'api.entity_tag.create.2' => ['tag_id' => $tag7],
      ],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 21', 'gender_id' => 3, 'prefix_id' => 1, 'suffix_id' => 6],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 22', 'gender_id' => 1, 'prefix_id' => 1, 'suffix_id' => 1, 'api.entity_tag.create' => ['tag_id' => $tag9]],
      ['first_name' => 'Test', 'last_name' => 'Test Contact 23', 'gender_id' => 3, 'prefix_id' => 1, 'suffix_id' => 1, 'api.entity_tag.create' => ['tag_id' => $tag7]],
      [
        'first_name' => 'Test',
        'last_name' => 'Test Contact 24',
        'gender_id' => 3,
        'prefix_id' => 3,
        'suffix_id' => 2,
        'api.entity_tag.create' => ['tag_id' => $tag9],
        'api.entity_tag.create.2' => ['tag_id' => $tag7],
      ],
    ];
    foreach ($individuals as $individual) {
      $this->ids['Contact'][$individual['last_name']] = $this->individualCreate($individual);
    }
    $groupContacts = [
      [5 => 13],
      [5 => 14],
      [5 => 15],
      [5 => 16],
      [5 => 21],
      [5 => 22],
      [5 => 23],
      [5 => 24],
      [3 => 17],
      [3 => 18],
      [3 => 19],
      [3 => 20],
      [3 => 21],
      [3 => 22],
      [3 => 23],
      [3 => 24],
    ];
    foreach ($groupContacts as $group) {
      $groupID = $this->ids['Group'][key($group)];
      $contactID = $this->ids['Contact']['Test Contact ' . reset($group)];
      $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID, 'status' => 'Added']);
    }

    // We have migrated from a hard-coded dataset to a dynamic one but are still working with the same
    // dataprovider at this stage -> wrangle.
    foreach ($fv as $key => $value) {
      $entity = ucfirst($key);
      if (!array_key_exists($entity, $this->ids)) {
        continue;
      }
      if (is_numeric($value)) {
        $fv[$key] = $this->ids[$entity][$value];
      }
      elseif (!empty($value[0])) {
        foreach ($value as $index => $oldGroup) {
          $fv[$key][$index] = $this->ids[$entity][$oldGroup];
        }
      }
      else {
        foreach (array_keys($value) as $index) {
          unset($fv[$key][$index]);
          $fv[$key][$this->ids[$entity][$index]] = 1;
        }
      }
    }

    $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    $obj = new CRM_Contact_BAO_Query($params);

    // let's set useGroupBy=true since we are listing contacts here who might belong to
    // more than one group / tag / notes etc.
    $obj->_useGroupBy = TRUE;

    $dao = $obj->searchQuery();

    $contacts = [];
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    $expectedIDs = [];
    foreach ($ids as $id) {
      $expectedIDs[] = $this->ids['Contact']['Test Contact ' . $id];
    }
    $this->assertEquals($expectedIDs, $contacts);
  }

  /**
   * Check that we get a successful result querying for home address.
   * CRM-14263 search builder failure with search profile & address in criteria
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchProfileHomeCityCRM14263() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactID,
      'city' => 'Cool City',
      'location_type_id' => 1,
    ]);
    $params = [
      0 => [
        0 => 'city-1',
        1 => '=',
        2 => 'Cool City',
        3 => 1,
        4 => 0,
      ],
    ];
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    ];

    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $resultDAO = $queryObj->searchQuery();
      $this->assertTrue($resultDAO->fetch());
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
  }

  /**
   * Check that we get a successful result querying for home address.
   * CRM-14263 search builder failure with search profile & address in criteria
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchProfileHomeCityNoResultsCRM14263() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactID,
      'city' => 'Cool City',
      'location_type_id' => 1,
    ]);
    $params = [
      0 => [
        0 => 'city-1',
        1 => '=',
        2 => 'Dumb City',
        3 => 1,
        4 => 0,
      ],
    ];
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    ];

    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $resultDAO = $queryObj->searchQuery();
      $this->assertFalse($resultDAO->fetch());
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
  }

  /**
   * Test searchPrimaryDetailsOnly setting.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchPrimaryLocTypes() {
    $contactID = $this->individualCreate();
    $params = [
      'contact_id' => $contactID,
      'email' => 'primary@example.com',
      'is_primary' => 1,
    ];
    $this->callAPISuccess('email', 'create', $params);

    unset($params['is_primary']);
    $params['email'] = 'secondary@team.com';
    $this->callAPISuccess('email', 'create', $params);

    foreach ([0, 1] as $searchPrimary) {
      Civi::settings()->set('searchPrimaryDetailsOnly', $searchPrimary);

      $params = [
        0 => [
          0 => 'email',
          1 => 'LIKE',
          2 => 'sEcondary@example.com',
          3 => 0,
          4 => 1,
        ],
      ];
      $returnProperties = [
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
      ];

      $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
      $resultDAO = $queryObj->searchQuery();

      if ($searchPrimary) {
        $this->assertEquals($resultDAO->N, 0);
      }
      else {
        //Assert secondary email gets included in search results.
        while ($resultDAO->fetch()) {
          $this->assertEquals('secondary@example.com', $resultDAO->email);
        }
      }

      // API should always return primary email.
      $result = $this->callAPISuccess('Contact', 'get', ['contact_id' => $contactID]);
      $this->assertEquals('primary@example.com', $result['values'][$contactID]['email']);
    }
  }

  /**
   *  Test created to prove failure of search on state when location
   *  display name is different form location name (issue 607)
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchOtherLocationUpperLower() {

    $params = [
      0 => [
        0 => 'state_province-4',
        1 => 'IS NOT EMPTY',
        2 => '',
        3 => 1,
        4 => 0,
      ],
    ];
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
      'location' => [
        'other' => [
          'location_type' => 4,
          'state_province' => 1,
        ],
      ],
    ];

    // update with the api does not work because it updates both the name and the
    // the display_name. Plain SQL however does the job
    CRM_Core_DAO::executeQuery('update civicrm_location_type set name=%2 where id=%1',
      [
        1 => [4, 'Integer'],
        2 => ['other', 'String'],
      ]);

    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);

    $resultDAO = $queryObj->searchQuery();
    $resultDAO->fetch();
  }

  /**
   * CRM-14263 search builder failure with search profile & address in criteria.
   *
   * We are retrieving primary here - checking the actual sql seems super prescriptive - but since the massive query object has
   * so few tests detecting any change seems good here :-)
   *
   * @dataProvider getSearchProfileData
   *
   * @param array $params
   * @param string $selectClause
   * @param string $whereClause
   *
   * @throws \CRM_Core_Exception
   */
  public function testSearchProfilePrimaryCityCRM14263($params, $selectClause, $whereClause) {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', [
      'contact_id' => $contactID,
      'city' => 'Cool CITY',
      'street_address' => 'Long STREET',
      'location_type_id' => 1,
    ]);
    $returnProperties = [
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    ];
    $expectedSQL = "SELECT contact_a.id as contact_id, contact_a.contact_type as `contact_type`, contact_a.contact_sub_type as `contact_sub_type`, contact_a.sort_name as `sort_name`, civicrm_address.id as address_id, " . $selectClause . "  FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 ) WHERE  (  ( " . $whereClause . " )  )  AND (contact_a.is_deleted = 0)    ORDER BY `contact_a`.`sort_name` ASC, `contact_a`.`id` ";
    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $this->assertLike($expectedSQL, $queryObj->getSearchSQL());
      list($select, $from, $where, $having) = $queryObj->query();
      $dao = CRM_Core_DAO::executeQuery("$select $from $where $having");
      $dao->fetch();
      $this->assertEquals('Anderson, Anthony', $dao->sort_name);
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
  }

  /**
   * Get data sets to test for search.
   */
  public function getSearchProfileData() {
    return [
      [
        [['city', '=', 'Cool City', 1, 0]],
        "civicrm_address.city as `city`",
        "civicrm_address.city = 'Cool City'",
      ],
      [
        // Note that in the query 'long street' is lower cased. We eventually want to change that & not mess with the vars - it turns out
        // it doesn't work on some charsets. However, the the lcasing affects more vars & we are looking to stagger removal of lcasing 'in case'
        // (although we have been removing without blowback since 2017)
        [['street_address', '=', 'Long Street', 1, 0]],
        "civicrm_address.street_address as `street_address`",
        "civicrm_address.street_address LIKE '%Long Street%'",
      ],
    ];
  }

  /**
   * Test set up to test calling the query object per GroupContactCache BAO usage.
   *
   * CRM-17254 ensure that if only the contact_id is required other fields should
   * not be appended.
   */
  public function testGroupContactCacheAddSearch() {
    $returnProperties = ['contact_id'];
    $params = [['group', 'IN', [1], 0, 0]];

    $query = new CRM_Contact_BAO_Query(
      $params, $returnProperties,
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );

    list($select) = $query->query(FALSE);
    $this->assertEquals('SELECT contact_a.id as contact_id', $select);
  }

  /**
   * Test smart groups with non-numeric don't fail on range queries.
   *
   * CRM-14720
   *
   * @throws \CRM_Core_Exception
   */
  public function testNumericPostal() {
    // Precaution as hitting some inconsistent set up running in isolation vs in the suite.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_address SET postal_code = NULL');

    $this->individualCreate(['api.address.create' => ['postal_code' => 5, 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => 'EH10 4RB-889', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => '4', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => '6', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['street_address' => 'just a street', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => '12345678444455555555555555555555555555555555551314151617181920', 'location_type_id' => 'Main']]);

    $params = [['postal_code_low', '=', 5, 0, 0]];
    CRM_Contact_BAO_Query::convertFormValues($params);

    $query = new CRM_Contact_BAO_Query(
      $params, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );

    $sql = $query->query(FALSE);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(2, $result->N);

    // We save this as a smart group and then load it. With mysql warnings on & CRM-14720 this
    // results in mysql warnings & hence fatal errors.
    /// I was unable to get mysql warnings to activate in the context of the unit tests - but
    // felt this code still provided a useful bit of coverage as it runs the various queries to load
    // the group & could generate invalid sql if a bug were introduced.
    $groupParams = ['title' => 'postal codes', 'formValues' => $params, 'is_active' => 1];
    $group = CRM_Contact_BAO_Group::createSmartGroup($groupParams);
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
  }

  /**
   * Test searches are case insensitive.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCaseInsensitive() {
    $orgID = $this->organizationCreate(['organization_name' => 'BOb']);
    $params = [
      'display_name' => 'Minnie Mouse',
      'first_name' => 'Minnie',
      'last_name' => 'Mouse',
      'employer_id' => $orgID,
      'contact_type' => 'Individual',
      'nick_name' => 'Mins',
    ];
    $this->callAPISuccess('Contact', 'create', $params);
    unset($params['contact_type']);
    foreach ($params as $key => $value) {
      if ($key === 'employer_id') {
        $searchParams = [['current_employer', '=', 'bob', 0, 1]];
      }
      else {
        $searchParams = [[$key, '=', strtolower($value), 0, 1]];
      }

      $result = CRM_Contact_BAO_Query::apiQuery($searchParams);
      $this->assertCount(1, $result[0], 'search for ' . $key);
      $contact = reset($result[0]);
      $this->assertEquals('Minnie Mouse', $contact['display_name']);
      $this->assertEquals('BOb', $contact['current_employer']);
    }
  }

  /**
   * Test smart groups with non-numeric don't fail on equal queries.
   *
   * CRM-14720
   *
   * @throws \CRM_Core_Exception
   */
  public function testNonNumericEqualsPostal() {
    $this->individualCreate(['api.address.create' => ['postal_code' => 5, 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => 'EH10 4RB-889', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => '4', 'location_type_id' => 'Main']]);
    $this->individualCreate(['api.address.create' => ['postal_code' => '6', 'location_type_id' => 'Main']]);

    $params = [['postal_code', '=', 'EH10 4RB-889', 0, 0]];
    CRM_Contact_BAO_Query::convertFormValues($params);

    $query = new CRM_Contact_BAO_Query(
      $params, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );

    $sql = $query->query(FALSE);
    $this->assertEquals("WHERE  ( civicrm_address.postal_code = 'EH10 4RB-889' )  AND (contact_a.is_deleted = 0)", $sql[2]);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(1, $result->N);

  }

  /**
   * Test relationship description.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipDescription() {
    $relType = $this->callAPISuccess('RelationshipType', 'create', [
      'name_a_b' => 'blah',
      'name_b_a' => 'other blah',
    ]);
    $contactID_a = $this->individualCreate([], 1);
    $contactID_b = $this->individualCreate([], 2);
    $contactID_c = $this->individualCreate([], 3);
    $contactID_d = $this->individualCreate([], 4);
    $desc = uniqid('rel', TRUE);
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $contactID_a,
      'contact_id_b' => $contactID_b,
      'relationship_type_id' => $relType['id'],
      'is_active' => 1,
      'description' => $desc,
    ]);
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $contactID_c,
      'contact_id_b' => $contactID_d,
      'relationship_type_id' => $relType['id'],
      'is_active' => 1,
      'description' => 'nothing of interest',
    ]);
    $params = [
      ['relation_description', '=', substr($desc, 3, 18), 0, 0],
    ];

    $query = new CRM_Contact_BAO_Query($params);
    $dao = $query->searchQuery();
    // This is a little weird but seems consistent with the behavior of the search form in general.
    // Technically there are 2 contacts who share a relationship with the description searched for,
    // so one might expect the search form to return both of them instead of just Contact A... but it doesn't.
    $this->assertEquals('1', $dao->N, 'Search query returns exactly 1 result?');
    $this->assertTrue($dao->fetch(), 'Search query returns success?');
    $this->assertEquals($contactID_a, $dao->contact_id, 'Search query returns contact A?');
  }

  /**
   * Test non-reciprocal relationship.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNonReciprocalRelationshipTargetGroupIsCorrectResults() {
    $contactID_a = $this->individualCreate();
    $contactID_b = $this->individualCreate();
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $contactID_a,
      'contact_id_b' => $contactID_b,
      'relationship_type_id' => 1,
      'is_active' => 1,
    ]);
    // Create a group and add contact A to it.
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID_a, 'status' => 'Added']);

    // Add another (sans-relationship) contact to the group,
    $contactID_c = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID_c, 'status' => 'Added']);

    $params = [
      [
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
          [
            0 => '1_b_a',
          ],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
          [
            0 => $groupID,
          ],
        3 => 0,
        4 => 0,
      ],
    ];

    $query = new CRM_Contact_BAO_Query($params);
    $dao = $query->searchQuery();
    $this->assertEquals('1', $dao->N, "Search query returns exactly 1 result?");
    $this->assertTrue($dao->fetch(), "Search query returns success?");
    $this->assertEquals($contactID_b, $dao->contact_id, "Search query returns parent of contact A?");
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testReciprocalRelationshipTargetGroupIsCorrectResults() {
    $contactID_a = $this->individualCreate();
    $contactID_b = $this->individualCreate();
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $contactID_a,
      'contact_id_b' => $contactID_b,
      'relationship_type_id' => 2,
      'is_active' => 1,
    ]);
    // Create a group and add contact A to it.
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID_a, 'status' => 'Added']);

    // Add another (sans-relationship) contact to the group,
    $contactID_c = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $contactID_c, 'status' => 'Added']);

    $params = [
      [
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
          [
            0 => '2_a_b',
          ],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
          [
            0 => $groupID,
          ],
        3 => 0,
        4 => 0,
      ],
    ];

    $query = new CRM_Contact_BAO_Query($params);
    $dao = $query->searchQuery();
    $this->assertEquals('1', $dao->N, "Search query returns exactly 1 result?");
    $this->assertTrue($dao->fetch(), "Search query returns success?");
    $this->assertEquals($contactID_b, $dao->contact_id, "Search query returns spouse of contact A?");
  }

  public function testReciprocalRelationshipTargetGroupUsesTempTable() {
    $groupID = $this->groupCreate();
    $params = [
      [
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
          [
            0 => '2_a_b',
          ],
        3 => 0,
        4 => 0,
      ],
      [
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
          [
            0 => $groupID,
          ],
        3 => 0,
        4 => 0,
      ],
    ];
    $sql = CRM_Contact_BAO_Query::getQuery($params);
    $this->assertContains('INNER JOIN civicrm_tmp_e', $sql, "Query appears to use temporary table of compiled relationships?", TRUE);
  }

  /**
   * Test relationship permission clause.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipPermissionClause() {
    $params = [['relation_type_id', 'IN', ['1_b_a'], 0, 0], ['relation_permission', 'IN', [2], 0, 0]];
    $sql = CRM_Contact_BAO_Query::getQuery($params);
    $this->assertContains('(civicrm_relationship.is_permission_a_b IN (2))', $sql);
  }

  /**
   * Test Relationship Clause
   *
   * @throws \CRM_Core_Exception
   */
  public function testRelationshipClause() {
    $today = date('Ymd');
    $from1 = " FROM civicrm_contact contact_a LEFT JOIN civicrm_relationship ON (civicrm_relationship.contact_id_a = contact_a.id ) LEFT JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_b = contact_b.id )";
    $from2 = " FROM civicrm_contact contact_a LEFT JOIN civicrm_relationship ON (civicrm_relationship.contact_id_b = contact_a.id ) LEFT JOIN civicrm_contact contact_b ON (civicrm_relationship.contact_id_a = contact_b.id )";
    $where1 = "WHERE  ( (
civicrm_relationship.is_active = 1 AND
( civicrm_relationship.end_date IS NULL OR civicrm_relationship.end_date >= {$today} ) AND
( civicrm_relationship.start_date IS NULL OR civicrm_relationship.start_date <= {$today} )
) AND (contact_b.is_deleted = 0) AND civicrm_relationship.relationship_type_id IN (8) )  AND (contact_a.is_deleted = 0)";
    $where2 = "WHERE  ( (
civicrm_relationship.is_active = 1 AND
( civicrm_relationship.end_date IS NULL OR civicrm_relationship.end_date >= {$today} ) AND
( civicrm_relationship.start_date IS NULL OR civicrm_relationship.start_date <= {$today} )
) AND (contact_b.is_deleted = 0) AND civicrm_relationship.relationship_type_id IN (8,10) )  AND (contact_a.is_deleted = 0)";
    // Test Traditional single select format
    $params1 = [['relation_type_id', '=', '8_a_b', 0, 0]];
    $query1 = new CRM_Contact_BAO_Query(
      $params1, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql1 = $query1->query(FALSE);
    $this->assertLike($from1, $sql1[1]);
    $this->assertLike($where1, $sql1[2]);
    // Test single relationship type selected in multiple select.
    $params2 = [['relation_type_id', 'IN', ['8_a_b'], 0, 0]];
    $query2 = new CRM_Contact_BAO_Query(
      $params2, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql2 = $query2->query(FALSE);
    $this->assertLike($from1, $sql2[1]);
    $this->assertLike($where1, $sql2[2]);
    // Test multiple relationship types selected.
    $params3 = [['relation_type_id', 'IN', ['8_a_b', '10_a_b'], 0, 0]];
    $query3 = new CRM_Contact_BAO_Query(
      $params3, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql3 = $query3->query(FALSE);
    $this->assertLike($from1, $sql3[1]);
    $this->assertLike($where2, $sql3[2]);
    // Test Multiple Relationship type selected where one doesn't actually exist.
    $params4 = [['relation_type_id', 'IN', ['8_a_b', '10_a_b', '14_a_b'], 0, 0]];
    $query4 = new CRM_Contact_BAO_Query(
      $params4, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql4 = $query4->query(FALSE);
    $this->assertLike($from1, $sql4[1]);
    $this->assertLike($where2, $sql4[2]);

    // Test Multiple b to a Relationship type  .
    $params5 = [['relation_type_id', 'IN', ['8_b_a', '10_b_a', '14_b_a'], 0, 0]];
    $query5 = new CRM_Contact_BAO_Query(
      $params5, ['contact_id'],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql5 = $query5->query(FALSE);
    $this->assertLike($from2, $sql5[1]);
    $this->assertLike($where2, $sql5[2]);
  }

  /**
   * Test we can narrow a group get by status.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetByGroupWithStatus() {
    $groupID = $this->groupCreate();
    $this->groupContactCreate($groupID, 3);
    $groupContactID = $this->callAPISuccessGetSingle('GroupContact', ['group_id' => $groupID, 'options' => ['limit' => 1]])['id'];
    $this->callAPISuccess('GroupContact', 'create', ['id' => $groupContactID, 'status' => 'Removed']);
    $queryObj = new CRM_Contact_BAO_Query([['group', '=', $groupID, 0, 0], ['group_contact_status', 'IN', ['Removed' => 1], 0, 0]]);
    $resultDAO = $queryObj->searchQuery();
    $this->assertEquals(1, $resultDAO->N);

    $queryObj = new CRM_Contact_BAO_Query([['group', '=', $groupID, 0, 0], ['group_contact_status', 'IN', ['Added' => 1], 0, 0]]);
    $resultDAO = $queryObj->searchQuery();
    $this->assertEquals(2, $resultDAO->N);

    $queryObj = new CRM_Contact_BAO_Query([['group', '=', $groupID, 0, 0]]);
    $resultDAO = $queryObj->searchQuery();
    $this->assertEquals(2, $resultDAO->N);
  }

  /**
   * Test we can narrow a group get by status.
   *
   * @throws \Exception
   */
  public function testGetByGroupWithStatusSmartGroup() {
    $groupID = $this->smartGroupCreate();
    // This means they are actually all hard-added, which is fine for this purpose.
    $this->groupContactCreate($groupID, 3);
    $groupContactID = $this->callAPISuccessGetSingle('GroupContact', ['group_id' => $groupID, 'options' => ['limit' => 1]])['id'];
    $this->callAPISuccess('GroupContact', 'create', ['id' => $groupContactID, 'status' => 'Removed']);

    $queryObj = new CRM_Contact_BAO_Query([['group', '=', $groupID, 0, 0], ['group_contact_status', 'IN', ['Removed' => 1], 0, 0]]);
    $resultDAO = $queryObj->searchQuery();
    $this->assertEquals(1, $resultDAO->N);
  }

  /**
   * Test the group contact clause does not contain an OR.
   *
   * The search should return 3 contacts - 2 households in the smart group of
   * Contact Type = Household and one Individual hard-added to it. The
   * Household that meets both criteria should be returned once.
   *
   * @throws \Exception
   */
  public function testGroupClause() {
    $this->householdCreate();
    $householdID = $this->householdCreate();
    $individualID = $this->individualCreate();
    $groupID = $this->smartGroupCreate();
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $individualID, 'status' => 'Added']);
    $this->callAPISuccess('GroupContact', 'create', ['group_id' => $groupID, 'contact_id' => $householdID, 'status' => 'Added']);

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::clearGroupContactCache($groupID);

    $sql = CRM_Contact_BAO_Query::getQuery(
      [['group', 'IN', [$groupID], 0, 0]],
      ['contact_id']
    );

    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->assertEquals(3, $dao->N);
    $this->assertFalse(strstr($sql, ' OR '));

    $sql = CRM_Contact_BAO_Query::getQuery(
      [['group', 'IN', [$groupID], 0, 0]],
      ['contact_id' => 1, 'group' => 1]
    );

    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->assertEquals(3, $dao->N);
    $this->assertFalse(strstr($sql, ' OR '), 'Query does not include or');
    while ($dao->fetch()) {
      $this->assertTrue(($dao->groups == $groupID || $dao->groups == ',' . $groupID), $dao->groups . ' includes ' . $groupID);
    }
  }

  /**
   * CRM-19562 ensure that only ids are used for contact_id searching.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactIDClause() {
    $params = [
      ['mark_x_2', '=', 1, 0, 0],
      ['mark_x_foo@example.com', '=', 1, 0, 0],
    ];
    $returnProperties = [
      'sort_name' => 1,
      'email' => 1,
      'do_not_email' => 1,
      'is_deceased' => 1,
      'on_hold' => 1,
      'display_name' => 1,
      'preferred_mail_format' => 1,
    ];
    $numberOfContacts = 2;

    try {
      CRM_Contact_BAO_Query::apiQuery($params, $returnProperties, NULL, NULL, 0, $numberOfContacts);
    }
    catch (Exception $e) {
      $this->assertEquals(
        'One of parameters  (value: foo@example.com) is not of the type Positive',
        $e->getMessage()
      );
      $this->assertTrue(TRUE);
      return;
    }
    $this->fail('Test failed for some reason which is not good');
  }

  /**
   * Test the sorting on the contact ID query works.
   *
   * Checking for lack of fatal.
   *
   * @param string $sortOrder
   *   Param reflecting how sort is passed in.
   *   - 1_d is column 1 descending.
   *
   * @dataProvider getSortOptions
   */
  public function testContactIDQuery($sortOrder) {
    $selector = new CRM_Contact_Selector(NULL, ['radio_ts' => 'ts_all'], NULL, ['sort_name' => 1]);
    $selector->contactIDQuery([], $sortOrder);
  }

  /**
   * Test the sorting on the contact ID query works with a profile search.
   *
   * Checking for lack of fatal.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContactIDQueryProfileSearchResults() {
    $profile = $this->callAPISuccess('UFGroup', 'create', ['group_type' => 'Contact', 'name' => 'search', 'title' => 'search']);
    $this->callAPISuccess('UFField', 'create', [
      'uf_group_id' => $profile['id'],
      'field_name' => 'postal_code',
      'field_type' => 'Contact',
      'in_selector' => TRUE,
      'is_searchable' => TRUE,
      'label' => 'postal code',
      'visibility' => 'Public Pages and Listings',
    ]);
    $selector = new CRM_Contact_Selector(NULL, ['radio_ts' => 'ts_all', 'uf_group_id' => $profile['id']], NULL, ['sort_name' => 1]);
    $selector->contactIDQuery([], '2_d');
  }

  /**
   * Get search options to reflect how a UI search would look.
   *
   * @return array
   */
  public function getSortOptions() {
    return [
      ['1_d'],
      ['2_d'],
      ['3_d'],
      ['4_d'],
      ['5_d'],
      ['6_d'],
    ];
  }

  /**
   * Test the summary query does not add an acl clause when acls not enabled..
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetSummaryQueryWithFinancialACLDisabled() {
    $this->createContributionsForSummaryQueryTests();

    // Test the function directly
    $where = $from = NULL;
    $queryObject = new CRM_Contact_BAO_Query();
    $queryObject->appendFinancialTypeWhereAndFromToQueryStrings($where,
      $from);
    $this->assertEquals(NULL, $where);
    $this->assertEquals(NULL, $from);

    // Test the function in action
    $queryObject = new CRM_Contact_BAO_Query([['contribution_source', '=', 'SSF', '', '']]);
    $summary = $queryObject->summaryContribution();
    $this->assertEquals([
      'total' => [
        'avg' => '$ 233.33',
        'amount' => '$ 1,400.00',
        'count' => 6,
      ],
      'cancel' => [
        'count' => 2,
        'amount' => '$ 100.00',
        'avg' => '$ 50.00',
      ],
    ], $summary);
  }

  /**
   * Test the summary query accurately adds financial acl filters.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetSummaryQueryWithFinancialACLEnabled() {
    $where = $from = NULL;
    $this->createContributionsForSummaryQueryTests();
    $this->enableFinancialACLs();
    $this->createLoggedInUserWithFinancialACL();

    // Test the function directly
    $queryObject = new CRM_Contact_BAO_Query();
    $queryObject->appendFinancialTypeWhereAndFromToQueryStrings($where,
      $from);
    $donationTypeID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');
    $this->assertEquals(
      " LEFT JOIN civicrm_line_item li
                  ON civicrm_contribution.id = li.contribution_id AND
                     li.entity_table = 'civicrm_contribution' AND li.financial_type_id NOT IN ({$donationTypeID}) ", $from);

    // Test the function in action
    $queryObject = new CRM_Contact_BAO_Query([['contribution_source', '=', 'SSF', '', '']]);
    $summary = $queryObject->summaryContribution();
    $this->assertEquals([
      'total' => [
        'avg' => '$ 200.00',
        'amount' => '$ 400.00',
        'count' => 2,
      ],
      'cancel' => [
        'count' => 1,
        'amount' => '$ 50.00',
        'avg' => '$ 50.00',
      ],
    ], $summary);
    $this->disableFinancialACLs();
  }

  /**
   * Test relative date filters to ensure they generate correct SQL.
   *
   * @dataProvider relativeDateFilters
   */
  public function testRelativeDateFilters($filter, $expectedWhere) {
    $params = [['created_date_relative', '=', $filter, 0, 0]];

    $dates = CRM_Utils_Date::getFromTo($filter, NULL, NULL);
    $expectedWhere = str_replace(['date0', 'date1'], [$dates[0], $dates[1]], $expectedWhere);

    $query = new CRM_Contact_BAO_Query(
      $params, [],
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );

    list($select, $from, $where, $having) = $query->query();
    $this->assertEquals($expectedWhere, $where);
  }

  public function relativeDateFilters() {
    $dataProvider[] = ['this.year', "WHERE  ( contact_a.created_date BETWEEN 'date0' AND 'date1' )  AND (contact_a.is_deleted = 0)"];
    $dataProvider[] = ['greater.day', "WHERE  ( contact_a.created_date >= 'date0' )  AND (contact_a.is_deleted = 0)"];
    $dataProvider[] = ['earlier.week', "WHERE  ( contact_a.created_date <= 'date1' )  AND (contact_a.is_deleted = 0)"];
    return $dataProvider;
  }

  /**
   * Create contributions to test summary calculations.
   *
   * financial type     | cancel_date        |total_amount| source    | line_item_financial_types  |number_line_items| line_amounts
   * Donation           |NULL                | 100.00     |SSF         | Donation                  | 1                | 100.00
   * Member Dues        |NULL                | 100.00     |SSF         | Member Dues               | 1                | 100.00
   * Donation           |NULL                | 300.00     |SSF         | Event Fee,Event Fee       | 2                | 200.00,100.00
   * Donation           |NULL                | 300.00     |SSF         | Event Fee,Donation        | 2                | 200.00,100.00
   * Donation           |NULL                | 300.00     |SSF         | Donation,Donation         | 2                | 200.00,100.00
   * Donation           |2019-02-13 00:00:00 | 50.00      |SSF         | Donation                  | 1                | 50.00
   * Member Dues        |2019-02-13 00:00:00 | 50.00      |SSF         | Member Dues               | 1                | 50.00
   *
   * @throws \CRM_Core_Exception
   */
  protected function createContributionsForSummaryQueryTests() {
    $contactID = $this->individualCreate();
    $this->contributionCreate(['contact_id' => $contactID]);
    $this->contributionCreate([
      'contact_id' => $contactID,
      'total_amount' => 100,
      'financial_type_id' => 'Member Dues',
    ]);
    $eventFeeType = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Event Fee');
    $this->createContributionWithTwoLineItemsAgainstPriceSet(['contact_id' => $contactID, 'source' => 'SSF']);
    $this->createContributionWithTwoLineItemsAgainstPriceSet(['contact_id' => $contactID, 'source' => 'SSF'], [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      $eventFeeType,
    ]);
    $this->createContributionWithTwoLineItemsAgainstPriceSet(['contact_id' => $contactID, 'source' => 'SSF'], [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
    ]);
    $this->createContributionWithTwoLineItemsAgainstPriceSet(['contact_id' => $contactID, 'source' => 'SSF', 'financial_type_id' => $eventFeeType], [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
    ]);
    $this->contributionCreate([
      'contact_id' => $contactID,
      'total_amount' => 50,
      'contribution_status_id' => 'Cancelled',
      'cancel_date' => 'yesterday',
    ]);
    $this->contributionCreate([
      'contact_id' => $contactID,
      'total_amount' => 50,
      'contribution_status_id' => 'Cancelled',
      'cancel_date' => 'yesterday',
      'financial_type_id' => 'Member Dues',
    ]);
  }

  /**
   * Test the options are handled for the qill.
   */
  public function testQillOptions() {
    $qill = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Activity_BAO_Activity', 'activity_type_id', 2, '=');
    $this->assertEquals(['=', 'Phone Call'], $qill);

    $qill = CRM_Contact_BAO_Query::buildQillForFieldValue('CRM_Activity_BAO_Activity', 'priority_id', 2, '=');
    $this->assertEquals(['=', 'Normal'], $qill);
  }

  /**
   * Test tests that a value on 'any entity' with the right metadata will be handled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGenericWhereHandling() {
    $query = new CRM_Contact_BAO_Query([['suffix_id', '=', 2, 0]]);
    $this->assertEquals("contact_a.suffix_id = 2", $query->_where[0][0]);
    $this->assertEquals('Individual Suffix = Sr.', $query->_qill[0][0]);
    $this->assertNotTrue(isset($query->_tables['civicrm_activity']));

    $query = new CRM_Contact_BAO_Query([['prefix_id', '=', 2, 0]]);
    $this->assertEquals('contact_a.prefix_id = 2', $query->_where[0][0]);
    $this->assertEquals('Individual Prefix = Ms.', $query->_qill[0][0]);
    $this->assertNotTrue(isset($query->_tables['civicrm_activity']));

    $query = new CRM_Contact_BAO_Query([['gender_id', '=', 2, 0]]);
    $this->assertEquals('contact_a.gender_id = 2', $query->_where[0][0]);
    $this->assertEquals('Gender = Male', $query->_qill[0][0]);
    $this->assertNotTrue(isset($query->_tables['civicrm_activity']));

    $query = new CRM_Contact_BAO_Query([['communication_style_id', '=', 2, 0]]);
    $this->assertEquals('contact_a.communication_style_id = 2', $query->_where[0][0]);
    $this->assertEquals('Communication Style = Familiar', $query->_qill[0][0]);

    $query = new CRM_Contact_BAO_Query([['communication_style_id', '=', 2, 0]]);
    $this->assertEquals('contact_a.communication_style_id = 2', $query->_where[0][0]);
    $this->assertEquals('Communication Style = Familiar', $query->_qill[0][0]);

    $query = new CRM_Contact_BAO_Query([['contact_type', '=', 'Household', 0]]);
    $this->assertEquals("contact_a.contact_type = 'Household'", $query->_where[0][0]);
    $this->assertEquals('Contact Type = Household', $query->_qill[0][0]);

    $query = new CRM_Contact_BAO_Query([['on_hold', '=', 0, 0]]);
    $this->assertEquals('civicrm_email.on_hold = 0', $query->_where[0][0]);
    $this->assertEquals('On Hold = 0', $query->_qill[0][0]);

    $query = new CRM_Contact_BAO_Query([['on_hold', '=', 1, 0]]);
    $this->assertEquals('civicrm_email.on_hold = 1', $query->_where[0][0]);
    $this->assertEquals('On Hold = 1', $query->_qill[0][0]);

    $query = new CRM_Contact_BAO_Query([['world_region', '=', 3, 0]]);
    $this->assertEquals('civicrm_worldregion.id = 3', $query->_where[0][0]);
    $this->assertEquals('World Region = Middle East and North Africa', $query->_qill[0][0]);
  }

}
