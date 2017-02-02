<?php

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Contact_BAO_QueryTest extends CiviUnitTestCase {

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
      $resultDAO = $queryObj->searchQuery(0, 0, NULL,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE);
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
      $resultDAO = $queryObj->searchQuery(0, 0, NULL,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE);
      $this->assertFalse($resultDAO->fetch());
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
  }

  /**
   * Test searchByPrimaryEmailOnly setting.
   */
  public function testSearchByPrimaryEmailOnly() {
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
      Civi::settings()->set('searchPrimaryEmailOnly', $searchPrimary);

      $params = array(
        0 => array(
          0 => 'email',
          1 => 'LIKE',
          2 => 'secondary@example.com',
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
      $resultDAO = $queryObj->searchQuery(0, 0, NULL,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE);

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
   * CRM-14263 search builder failure with search profile & address in criteria
   * We are retrieving primary here - checking the actual sql seems super prescriptive - but since the massive query object has
   * so few tests detecting any change seems good here :-)
   */
  public function testSearchProfilePrimaryCityCRM14263() {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array(
        'contact_id' => $contactID,
        'city' => 'Cool City',
        'location_type_id' => 1,
      ));
    $params = array(
      0 => array(
        0 => 'city',
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
    $expectedSQL = "SELECT contact_a.id as contact_id, contact_a.contact_type  as `contact_type`, contact_a.contact_sub_type  as `contact_sub_type`, contact_a.sort_name  as `sort_name`, civicrm_address.id as address_id, civicrm_address.city as `city`  FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 ) WHERE  (  ( LOWER(civicrm_address.city) = 'cool city' )  )  AND (contact_a.is_deleted = 0)    ORDER BY `contact_a`.`sort_name` asc, `contact_a`.`id` ";
    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $this->assertEquals($expectedSQL, $queryObj->searchQuery(0, 0, NULL,
        FALSE, FALSE,
        FALSE, FALSE,
        TRUE));
    }
    catch (PEAR_Exception $e) {
      $err = $e->getCause();
      $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

    }
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
    $this->individualCreate(array('api.address.create' => array('postal_code' => 5, 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => 'EH10 4RB-889', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '4', 'location_type_id' => 'Main')));
    $this->individualCreate(array('api.address.create' => array('postal_code' => '6', 'location_type_id' => 'Main')));

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
    $this->callAPISuccess('Contact', 'create', array('display_name' => 'Minnie Mouse', 'employer_id' => $orgID, 'contact_type' => 'Individual'));
    $searchParams = array(array('current_employer', '=', 'bob', 0, 1));
    $query = new CRM_Contact_BAO_Query($searchParams);
    $result = $query->apiQuery($searchParams);
    $this->assertEquals(1, count($result[0]));
    $contact = reset($result[0]);
    $this->assertEquals('Minnie Mouse', $contact['display_name']);
    $this->assertEquals('BOb', $contact['current_employer']);
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
    $this->assertEquals("WHERE  ( civicrm_address.postal_code = 'eh10 4rb-889' )  AND (contact_a.is_deleted = 0)", $sql[2]);
    $result = CRM_Core_DAO::executeQuery(implode(' ', $sql));
    $this->assertEquals(1, $result->N);

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
    CRM_Contact_BAO_GroupContactCache::remove($groupID, FALSE);

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
    $numberofContacts = 2;
    $query = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $query->apiQuery($params, $returnProperties, NULL, NULL, 0, $numberofContacts);
    }
    catch (Exception $e) {
      $this->assertEquals("A fatal error was triggered: One of parameters  (value: foo@example.com) is not of the type Positive",
        $e->getMessage());
      return $this->assertTrue(TRUE);
    }
    return $this->fail('Test failed for some reason which is not good');
  }

}
