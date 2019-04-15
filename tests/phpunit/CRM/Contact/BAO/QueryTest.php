<?php

/**
 *  Include dataProvider for tests
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

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $tablesToTruncate = array(
      'civicrm_group_contact',
      'civicrm_group',
      'civicrm_saved_search',
      'civicrm_entity_tag',
      'civicrm_tag',
      'civicrm_contact',
      'civicrm_address',
    );
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test CRM_Contact_BAO_Query::searchQuery().
   *
   * @dataProvider dataProvider
   *
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   */
  public function testSearch($fv, $count, $ids, $full) {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/queryDataset.xml'
      )
    );

    $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    $obj = new CRM_Contact_BAO_Query($params);

    // let's set useGroupBy=true since we are listing contacts here who might belong to
    // more than one group / tag / notes etc.
    $obj->_useGroupBy = TRUE;

    $dao = $obj->searchQuery();

    $contacts = array();
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    $this->assertEquals($ids, $contacts);
  }

  /**
   * Check that we get a successful result querying for home address.
   * CRM-14263 search builder failure with search profile & address in criteria
   */
  public function testSearchProfileHomeCityCRM14263() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'city' => 'Cool City',
      'location_type_id' => 1,
    ));
    $params = array(
      0 => array(
        0 => 'city-1',
        1 => '=',
        2 => 'Cool City',
        3 => 1,
        4 => 0,
      ),
    );
    $returnProperties = array(
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    );

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
   */
  public function testSearchProfileHomeCityNoResultsCRM14263() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'city' => 'Cool City',
      'location_type_id' => 1,
    ));
    $params = array(
      0 => array(
        0 => 'city-1',
        1 => '=',
        2 => 'Dumb City',
        3 => 1,
        4 => 0,
      ),
    );
    $returnProperties = array(
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    );

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
   */
  public function testSearchPrimaryLocTypes() {
    $contactID = $this->individualCreate();
    $params = array(
      'contact_id' => $contactID,
      'email' => 'primary@example.com',
      'is_primary' => 1,
    );
    $this->callAPISuccess('email', 'create', $params);

    unset($params['is_primary']);
    $params['email'] = 'secondary@team.com';
    $this->callAPISuccess('email', 'create', $params);

    foreach (array(0, 1) as $searchPrimary) {
      Civi::settings()->set('searchPrimaryDetailsOnly', $searchPrimary);

      $params = array(
        0 => array(
          0 => 'email',
          1 => 'LIKE',
          2 => 'sEcondary@example.com',
          3 => 0,
          4 => 1,
        ),
      );
      $returnProperties = array(
        'contact_type' => 1,
        'contact_sub_type' => 1,
        'sort_name' => 1,
      );

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
      $result = $this->callAPISuccess('Contact', 'get', array('contact_id' => $contactID));
      $this->assertEquals('primary@example.com', $result['values'][$contactID]['email']);
    }
  }

  /**
   *  Test created to prove failure of search on state when location
   *  display name is different form location name (issue 607)
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
   */
  public function testSearchProfilePrimaryCityCRM14263($params, $selectClause, $whereClause) {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array(
      'contact_id' => $contactID,
      'city' => 'Cool CITY',
      'street_address' => 'Long STREET',
      'location_type_id' => 1,
    ));
    $returnProperties = array(
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    );
    $expectedSQL = "SELECT contact_a.id as contact_id, contact_a.contact_type as `contact_type`, contact_a.contact_sub_type as `contact_sub_type`, contact_a.sort_name as `sort_name`, civicrm_address.id as address_id, " . $selectClause . "  FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 ) WHERE  (  ( " . $whereClause . " )  )  AND (contact_a.is_deleted = 0)    ORDER BY `contact_a`.`sort_name` ASC, `contact_a`.`id` ";
    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $this->assertEquals($expectedSQL, $queryObj->getSearchSQL());
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
        [['city', '=', 'Cool City', 1, 0]], "civicrm_address.city as `city`", "civicrm_address.city = 'Cool City'",
      ],
      [
        // Note that in the query 'long street' is lower cased. We eventually want to change that & not mess with the vars - it turns out
        // it doesn't work on some charsets. However, the the lcasing affects more vars & we are looking to stagger removal of lcasing 'in case'
        // (although we have been removing without blowback since 2017)
        [['street_address', '=', 'Long Street', 1, 0]], "civicrm_address.street_address as `street_address`", "civicrm_address.street_address LIKE '%Long Street%'",
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
    $returnProperties = array('contact_id');
    $params = array(array('group', 'IN', array(1), 0, 0));

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
   */
  public function testNumericPostal() {
    // Precaution as hitting some inconsistent set up running in isolation vs in the suite.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_address SET postal_code = NULL');

    $this->individualCreate(array('api.address.create' => array('postal_code' => 5, 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => 'EH10 4RB-889', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '4', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '6', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('street_address' => 'just a street', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '12345678444455555555555555555555555555555555551314151617181920', 'location_type_id' => 'Main')));

    $params = array(array('postal_code_low', '=', 5, 0, 0));
    CRM_Contact_BAO_Query::convertFormValues($params);

    $query = new CRM_Contact_BAO_Query(
      $params, array('contact_id'),
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
    $groupParams = array('title' => 'postal codes', 'formValues' => $params, 'is_active' => 1);
    $group = CRM_Contact_BAO_Group::createSmartGroup($groupParams);
    CRM_Contact_BAO_GroupContactCache::load($group, TRUE);
  }

  /**
   * Test searches are case insensitive.
   */
  public function testCaseInsensitive() {
    $orgID = $this->organizationCreate(array('organization_name' => 'BOb'));
    $params = array(
      'display_name' => 'Minnie Mouse',
      'first_name' => 'Minnie',
      'last_name' => 'Mouse',
      'employer_id' => $orgID,
      'contact_type' => 'Individual',
      'nick_name' => 'Mins',
    );
    $this->callAPISuccess('Contact', 'create', $params);
    unset($params['contact_type']);
    foreach ($params as $key => $value) {
      if ($key == 'employer_id') {
        $searchParams = array(array('current_employer', '=', 'bob', 0, 1));
      }
      else {
        $searchParams = array(array($key, '=', strtolower($value), 0, 1));
      }
      $query = new CRM_Contact_BAO_Query($searchParams);
      $result = $query->apiQuery($searchParams);
      $this->assertEquals(1, count($result[0]), 'search for ' . $key);
      $contact = reset($result[0]);
      $this->assertEquals('Minnie Mouse', $contact['display_name']);
      $this->assertEquals('BOb', $contact['current_employer']);
    }
  }

  /**
   * Test smart groups with non-numeric don't fail on equal queries.
   *
   * CRM-14720
   */
  public function testNonNumericEqualsPostal() {
    $this->individualCreate(array('api.address.create' => array('postal_code' => 5, 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => 'EH10 4RB-889', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '4', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '6', 'location_type_id' => 'Main')));

    $params = array(array('postal_code', '=', 'EH10 4RB-889', 0, 0));
    CRM_Contact_BAO_Query::convertFormValues($params);

    $query = new CRM_Contact_BAO_Query(
      $params, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );

    $sql = $query->query(FALSE);
    $this->assertEquals("WHERE  ( civicrm_address.postal_code = 'EH10 4RB-889' )  AND (contact_a.is_deleted = 0)", $sql[2]);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(1, $result->N);

  }

  public function testNonReciprocalRelationshipTargetGroupIsCorrectResults() {
    $contactID_a = $this->individualCreate();
    $contactID_b = $this->individualCreate();
    $this->callAPISuccess('Relationship', 'create', array(
      'contact_id_a' => $contactID_a,
      'contact_id_b' => $contactID_b,
      'relationship_type_id' => 1,
      'is_active' => 1,
    ));
    // Create a group and add contact A to it.
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $contactID_a, 'status' => 'Added'));

    // Add another (sans-relationship) contact to the group,
    $contactID_c = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $contactID_c, 'status' => 'Added'));

    $params = array(
      array(
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
        array(
          0 => '1_b_a',
        ),
        3 => 0,
        4 => 0,
      ),
      array(
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
        array(
          0 => $groupID,
        ),
        3 => 0,
        4 => 0,
      ),
    );

    $query = new CRM_Contact_BAO_Query($params);
    $dao = $query->searchQuery();
    $this->assertEquals('1', $dao->N, "Search query returns exactly 1 result?");
    $this->assertTrue($dao->fetch(), "Search query returns success?");
    $this->assertEquals($contactID_b, $dao->contact_id, "Search query returns parent of contact A?");
  }

  public function testReciprocalRelationshipTargetGroupIsCorrectResults() {
    $contactID_a = $this->individualCreate();
    $contactID_b = $this->individualCreate();
    $this->callAPISuccess('Relationship', 'create', array(
      'contact_id_a' => $contactID_a,
      'contact_id_b' => $contactID_b,
      'relationship_type_id' => 2,
      'is_active' => 1,
    ));
    // Create a group and add contact A to it.
    $groupID = $this->groupCreate();
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $contactID_a, 'status' => 'Added'));

    // Add another (sans-relationship) contact to the group,
    $contactID_c = $this->individualCreate();
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $contactID_c, 'status' => 'Added'));

    $params = array(
      array(
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
        array(
          0 => '2_a_b',
        ),
        3 => 0,
        4 => 0,
      ),
      array(
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
        array(
          0 => $groupID,
        ),
        3 => 0,
        4 => 0,
      ),
    );

    $query = new CRM_Contact_BAO_Query($params);
    $dao = $query->searchQuery();
    $this->assertEquals('1', $dao->N, "Search query returns exactly 1 result?");
    $this->assertTrue($dao->fetch(), "Search query returns success?");
    $this->assertEquals($contactID_b, $dao->contact_id, "Search query returns spouse of contact A?");
  }

  public function testReciprocalRelationshipTargetGroupUsesTempTable() {
    $groupID = $this->groupCreate();
    $params = array(
      array(
        0 => 'relation_type_id',
        1 => 'IN',
        2 =>
        array(
          0 => '2_a_b',
        ),
        3 => 0,
        4 => 0,
      ),
      array(
        0 => 'relation_target_group',
        1 => 'IN',
        2 =>
        array(
          0 => $groupID,
        ),
        3 => 0,
        4 => 0,
      ),
    );
    $sql = CRM_Contact_BAO_Query::getQuery($params);
    $this->assertContains('INNER JOIN civicrm_rel_temp_', $sql, "Query appears to use temporary table of compiled relationships?", TRUE);
  }

  public function testRelationshipPermissionClause() {
    $params = [['relation_type_id', 'IN', ['1_b_a'], 0, 0], ['relation_permission', 'IN', [2], 0, 0]];
    $sql = CRM_Contact_BAO_Query::getQuery($params);
    $this->assertContains('(civicrm_relationship.is_permission_a_b IN (2))', $sql);
  }

  /**
   * Test Relationship Clause
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
    $params1 = array(array('relation_type_id', '=', '8_a_b', 0, 0));
    $query1 = new CRM_Contact_BAO_Query(
      $params1, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql1 = $query1->query(FALSE);
    $this->assertEquals($from1, $sql1[1]);
    $this->assertEquals($where1, $sql1[2]);
    // Test single relationship type selected in multiple select.
    $params2 = array(array('relation_type_id', 'IN', array('8_a_b'), 0, 0));
    $query2 = new CRM_Contact_BAO_Query(
      $params2, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql2 = $query2->query(FALSE);
    $this->assertEquals($from1, $sql2[1]);
    $this->assertEquals($where1, $sql2[2]);
    // Test multiple relationship types selected.
    $params3 = array(array('relation_type_id', 'IN', array('8_a_b', '10_a_b'), 0, 0));
    $query3 = new CRM_Contact_BAO_Query(
      $params3, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql3 = $query3->query(FALSE);
    $this->assertEquals($from1, $sql3[1]);
    $this->assertEquals($where2, $sql3[2]);
    // Test Multiple Relationship type selected where one doesn't actually exist.
    $params4 = array(array('relation_type_id', 'IN', array('8_a_b', '10_a_b', '14_a_b'), 0, 0));
    $query4 = new CRM_Contact_BAO_Query(
      $params4, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql4 = $query4->query(FALSE);
    $this->assertEquals($from1, $sql4[1]);
    $this->assertEquals($where2, $sql4[2]);

    // Test Multiple b to a Relationship type  .
    $params5 = array(array('relation_type_id', 'IN', array('8_b_a', '10_b_a', '14_b_a'), 0, 0));
    $query5 = new CRM_Contact_BAO_Query(
      $params5, array('contact_id'),
      NULL, TRUE, FALSE, 1,
      TRUE,
      TRUE, FALSE
    );
    $sql5 = $query5->query(FALSE);
    $this->assertEquals($from2, $sql5[1]);
    $this->assertEquals($where2, $sql5[2]);
  }

  /**
   * Test the group contact clause does not contain an OR.
   *
   * The search should return 3 contacts - 2 households in the smart group of
   * Contact Type = Household and one Individual hard-added to it. The
   * Household that meets both criteria should be returned once.
   */
  public function testGroupClause() {
    $this->householdCreate();
    $householdID = $this->householdCreate();
    $individualID = $this->individualCreate();
    $groupID = $this->smartGroupCreate();
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $individualID, 'status' => 'Added'));
    $this->callAPISuccess('GroupContact', 'create', array('group_id' => $groupID, 'contact_id' => $householdID, 'status' => 'Added'));

    // Refresh the cache for test purposes. It would be better to alter to alter the GroupContact add function to add contacts to the cache.
    CRM_Contact_BAO_GroupContactCache::clearGroupContactCache($groupID);

    $sql = CRM_Contact_BAO_Query::getQuery(
      array(array('group', 'IN', array($groupID), 0, 0)),
      array('contact_id')
    );

    $dao = CRM_Core_DAO::executeQuery($sql);
    $this->assertEquals(3, $dao->N);
    $this->assertFalse(strstr($sql, ' OR '));

    $sql = CRM_Contact_BAO_Query::getQuery(
      array(array('group', 'IN', array($groupID), 0, 0)),
      array('contact_id' => 1, 'group' => 1)
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
   */
  public function testContactIDClause() {
    $params = array(
      array("mark_x_2", "=", 1, 0, 0),
      array("mark_x_foo@example.com", "=", 1, 0, 0),
    );
    $returnProperties = array(
      "sort_name" => 1,
      "email" => 1,
      "do_not_email" => 1,
      "is_deceased" => 1,
      "on_hold" => 1,
      "display_name" => 1,
      "preferred_mail_format" => 1,
    );
    $numberOfContacts = 2;
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberOfContacts);
    }
    catch (Exception $e) {
      $this->assertEquals(
        "A fatal error was triggered: One of parameters  (value: foo@example.com) is not of the type Positive",
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
   * When we have a relative date in search criteria, check that convertFormValues() sets _low & _high date fields and returns other criteria.
   * CRM-21816 fix relative dates in search bug
   */
  public function testConvertFormValuesCRM21816() {
    $fv = array(
    // next 60 days
      "member_end_date_relative" => "starting_2.month",
      "member_end_date_low" => "20180101000000",
      "member_end_date_high" => "20180331235959",
      "membership_is_current_member" => "1",
      "member_is_primary" => "1",
    );
    // $fv is modified by convertFormValues()
    $fv_orig = $fv;
    $params = CRM_Contact_BAO_Query::convertFormValues($fv);

    // restructure for easier testing
    $modparams = array();
    foreach ($params as $p) {
      $modparams[$p[0]] = $p;
    }

    // Check member_end_date_low is in params
    $this->assertTrue(is_array($modparams['member_end_date_low']));
    // ... fv and params should match
    $this->assertEquals($modparams['member_end_date_low'][2], $fv['member_end_date_low']);
    // ... fv & fv_orig should be different
    $this->assertNotEquals($fv['member_end_date_low'], $fv_orig['member_end_date_low']);

    // same for member_end_date_high
    $this->assertTrue(is_array($modparams['member_end_date_high']));
    $this->assertEquals($modparams['member_end_date_high'][2], $fv['member_end_date_high']);
    $this->assertNotEquals($fv['member_end_date_high'], $fv_orig['member_end_date_high']);

    // Check other fv values are in params
    $this->assertEquals($modparams['membership_is_current_member'][2], $fv_orig['membership_is_current_member']);
    $this->assertEquals($modparams['member_is_primary'][2], $fv_orig['member_is_primary']);
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

}
