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
 *  Include parent class definition
 */

/**
 *  Include class under test
 */

/**
 *  Include form definitions
 */

/**
 *  Include DAO to do queries
 */

/**
 *  Include dataProvider for tests
 */

/**
 *  Test contact custom search functions
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_Custom_SampleTest extends CiviUnitTestCase {
  protected $_tablesToTruncate = [
    'civicrm_address',
    'civicrm_saved_search',
    'civicrm_contact',
    'civicrm_option_value',
    'civicrm_option_group',
  ];

  /**
   * @return CRM_Contact_Form_Search_Custom_SamplTestDataProvider
   */
  public function dataProvider() {
    return new CRM_Contact_Form_Search_Custom_SampleTestDataProvider();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::count()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testCount($fv, $count, $ids, $full) {
    $this->quickCleanup($this->_tablesToTruncate);

    $this->loadXMLDataSet(dirname(__FILE__) . '/datasets/sample-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);

    $this->assertEquals($count, $obj->count());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::all()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testAll($fv, $count, $ids, $full) {
    // Truncate affected tables
    $this->quickCleanup($this->_tablesToTruncate);

    $this->loadXMLDataSet(dirname(__FILE__) . '/datasets/sample-dataset.xml');

    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
    $sql = $obj->all(0, 0, 'contact_id');
    $this->assertTrue(is_string($sql));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $all = [];
    while ($dao->fetch()) {
      $all[] = [
        'contact_id' => $dao->contact_id,
        'contact_type' => $dao->contact_type,
        'household_name' => $dao->sort_name,
      ];
    }
    asort($all);
    $this->assertEquals($full, $all);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::contactIDs()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testContactIDs($fv, $count, $ids, $full) {
    // Truncate affected tables
    $this->quickCleanup($this->_tablesToTruncate);

    $this->loadXMLDataSet(dirname(__FILE__) . '/datasets/sample-dataset.xml');
    $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
    $sql = $obj->contactIDs();
    $this->assertTrue(is_string($sql));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = [];
    while ($dao->fetch()) {
      $contacts[$dao->contact_id] = 1;
    }
    $contacts = array_keys($contacts);
    sort($contacts, SORT_NUMERIC);
    $this->assertEquals($ids, $contacts);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::columns()
   *  It returns an array of translated name => keys
   */
  public function testColumns() {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Sample($formValues);
    $columns = $obj->columns();
    $this->assertTrue(is_array($columns));
    foreach ($columns as $key => $value) {
      $this->assertTrue(is_string($key));
      $this->assertTrue(is_string($value));
    }
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::from()
   * @todo write this test
   */
  public function SKIPPED_testFrom() {
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::summary()
   *  It returns NULL
   */
  public function testSummary() {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertNull($obj->summary());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample::templateFile()
   *  Returns the path to the file as a string
   */
  public function testTemplateFile() {
    $formValues = [];
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $fileName = $obj->templateFile();
    $this->assertTrue(is_string($fileName));
    //FIXME: we would need to search the include path to do the following
    //$this->assertTrue( file_exists( $fileName ) );
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Sample with saved_search_id
   *  With true argument it returns list of contact IDs
   */
  public function testSavedSearch() {
    $this->quickCleanup($this->_tablesToTruncate);

    $this->loadXMLDataSet(dirname(__FILE__) . '/datasets/sample-dataset.xml');

    $dataset[1] = ['id' => [12]];
    $dataset[2] = ['id' => [10, 11]];

    $ssdao = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_saved_search");
    while ($ssdao->fetch()) {
      $fv = CRM_Contact_BAO_SavedSearch::getFormValues($ssdao->id);
      $obj = new CRM_Contact_Form_Search_Custom_Sample($fv);
      $sql = $obj->contactIDs();
      $this->assertTrue(is_string($sql));
      $dao = CRM_Core_DAO::executeQuery($sql);
      $contacts = [];
      while ($dao->fetch()) {
        $contacts[] = $dao->contact_id;
      }
      sort($contacts, SORT_NUMERIC);
      $this->assertEquals($dataset[$ssdao->id]['id'], $contacts);
    }
  }

}
