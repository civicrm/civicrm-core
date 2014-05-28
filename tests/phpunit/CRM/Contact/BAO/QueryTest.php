<?php
require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';

/**
 *  Include dataProvider for tests
 */
class CRM_Contact_BAO_QueryTest extends CiviUnitTestCase {
  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Contact BAO Query',
      'description' => 'Test all Contact_BAO_Query methods.',
      'group' => 'CiviCRM BAO Query Tests',
    );
  }

  /**
   * @return CRM_Contact_BAO_QueryTestDataProvider
   */
  public function dataProvider() {
    return new CRM_Contact_BAO_QueryTestDataProvider;
  }

  function setUp() {
    parent::setUp();
  }

  function tearDown() {
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
   *  @dataProvider dataProvider
   */
  function testSearch($fv, $count, $ids, $full) {
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      new PHPUnit_Extensions_Database_DataSet_FlatXMLDataSet(
        dirname(__FILE__) . '/queryDataset.xml'
      )
    );

    $params = CRM_Contact_BAO_Query::convertFormValues($fv);
    $obj    = new CRM_Contact_BAO_Query($params);

    // let's set useGroupBy=true since we are listing contacts here who might belong to
    // more than one group / tag / notes etc.
    $obj->_useGroupBy = TRUE;

    $dao    = $obj->searchQuery();

    $contacts = array();
    while ($dao->fetch()) {
      $contacts[] = $dao->contact_id;
    }

    sort($contacts, SORT_NUMERIC);

    $this->assertEquals($ids, $contacts, 'In line ' . __LINE__);
  }

  /**
   * CRM-14263 search builder failure with search profile & address in criteria
   */
  function testSearchProfile()
  {
    $contactID = $this->individualCreate();
    CRM_Core_Config::singleton()->defaultSearchProfileID = 1;
    $this->callAPISuccess('address', 'create', array('contact_id' => $contactID, 'city' => 'Cool City', 'location_type_id' => 1,));
    $params = array(
      0 => array(
        0 => 'city-1',
        1 => '=',
        2 => 'Cool City',
        3 => 1,
        4 => 0,
      )
    );
    $returnProperties = array(
      'contact_type' => 1,
      'contact_sub_type' => 1,
      'sort_name' => 1,
    );

    $queryObj = new CRM_Contact_BAO_Query($params, $returnProperties);
    try {
      $queryObj->searchQuery(0, 0, NULL,
        FALSE, FALSE,
        FALSE, FALSE,
        FALSE);
    }
  catch (PEAR_Exception $e) {
    $err = $e->getCause();
    $this->fail('invalid SQL created' . $e->getMessage() . " " . $err->userinfo);

  }

}
}

