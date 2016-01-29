<?php
/**
 *  File for the CRM_Contact_Form_Search_Custom_GroupTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 * @copyright Copyright CiviCRM LLC (C) 2009
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html
 *              GNU Affero General Public License version 3
 * @package CiviCRM
 *
 *   This file is part of CiviCRM
 *
 *   CiviCRM is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Affero General Public License
 *   as published by the Free Software Foundation; either version 3 of
 *   the License, or (at your option) any later version.
 *
 *   CiviCRM is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Affero General Public License for more details.
 *
 *   You should have received a copy of the GNU Affero General Public
 *   License along with this program.  If not, see
 *   <http://www.gnu.org/licenses/>.
 */

/**
 *  Include parent class definition
 */
require_once 'CiviTest/CiviUnitTestCase.php';

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
 */
class CRM_Contact_Form_Search_Custom_GroupTest extends CiviUnitTestCase {
  protected $_tablesToTruncate = array(
    'civicrm_group_contact',
    'civicrm_group',
    'civicrm_saved_search',
    'civicrm_entity_tag',
    'civicrm_tag',
    'civicrm_contact',
    'civicrm_option_value',
    'civicrm_option_group',
  );

  /**
   * @return CRM_Contact_Form_Search_Custom_GroupTestDataProvider
   */
  public function dataProvider() {
    return new CRM_Contact_Form_Search_Custom_GroupTestDataProvider();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::count()
   * @dataProvider dataProvider
   * @param $fv
   * @param $count
   * @param $ids
   * @param $full
   * @throws \Exception
   */
  public function testCount($fv, $count, $ids, $full) {
    $this->quickCleanup($this->_tablesToTruncate);

    // echo "testCount\n";
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/datasets/group-dataset.xml'
      )
    );

    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);

    $sql = $obj->all();
    $dao = CRM_Core_DAO::executeQuery($sql);

    /**
     * echo "Count: $count, OBJ: ", $obj->count( ) . "\n";
     * while ( $dao->fetch( ) ) {
     * echo "{$dao->contact_id}, {$dao->contact_type}, {$dao->sort_name}, {$dao->group_names}\n";
     * }
     **/
    $this->assertEquals($count, $obj->count(),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::all()
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

    // echo "testAll\n";
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/datasets/group-dataset.xml'
      )
    );
    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);
    $sql = $obj->all();
    $this->assertTrue(is_string($sql));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $all = array();
    while ($dao->fetch()) {
      $all[] = array(
        'contact_id' => $dao->contact_id,
        'contact_type' => $dao->contact_type,
        'sort_name' => $dao->sort_name,
      );
    }
    asort($all);
    $this->assertEquals($full, $all);
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::contactIDs()
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

    // echo "testContactIDs\n";
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/datasets/group-dataset.xml'
      )
    );
    $obj = new CRM_Contact_Form_Search_Custom_Group($fv);
    $sql = $obj->contactIDs();
    $this->assertTrue(is_string($sql));
    $dao = CRM_Core_DAO::executeQuery($sql);
    $contacts = array();
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
    $formValues = array();
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
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
    $formValues = array();
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertNull($obj->summary());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::templateFile()
   *  Returns the path to the file as a string
   */
  public function testTemplateFile() {
    $formValues = array();
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $fileName = $obj->templateFile();
    $this->assertTrue(is_string($fileName));
    //FIXME: we would need to search the include path to do the following
    //$this->assertTrue( file_exists( $fileName ) );
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With no arguments it returns '(1)'
   */
  public function testWhereNoArgs() {
    $formValues = array(
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    );
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where());
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With false argument it returns '(1)'
   */
  public function testWhereFalse() {
    $formValues = array(
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    );
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1) ', $obj->where(FALSE),
      'In line ' . __LINE__
    );
  }

  /**
   *  Test CRM_Contact_Form_Search_Custom_Group::where( )
   *  With true argument it returns list of contact IDs
   */
  public function testWhereTrue() {
    $formValues = array(
      CRM_Core_Form::CB_PREFIX . '17' => TRUE,
      CRM_Core_Form::CB_PREFIX . '23' => TRUE,
    );
    $obj = new CRM_Contact_Form_Search_Custom_Group($formValues);
    $this->assertEquals(' (1)  AND contact_a.id IN ( 17, 23 )', $obj->where(TRUE),
      'In line ' . __LINE__
    );
  }

}
