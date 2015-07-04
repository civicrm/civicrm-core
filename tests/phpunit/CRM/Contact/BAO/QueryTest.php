<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

/**
 *  Include dataProvider for tests
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
    );
    $this->quickCleanup($tablesToTruncate);
  }

  /**
   *  Test CRM_Contact_BAO_Query::searchQuery()
   * @dataProvider dataProvider
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
    $expectedSQL = "SELECT contact_a.id as contact_id, contact_a.contact_type  as `contact_type`, contact_a.contact_sub_type  as `contact_sub_type`, contact_a.sort_name  as `sort_name`, civicrm_address.id as address_id, civicrm_address.city as `city`  FROM civicrm_contact contact_a LEFT JOIN civicrm_address ON ( contact_a.id = civicrm_address.contact_id AND civicrm_address.is_primary = 1 ) WHERE  (  ( LOWER(civicrm_address.city) = 'cool city' )  )  AND (contact_a.is_deleted = 0)    ORDER BY contact_a.sort_name asc, contact_a.id ";
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

}
