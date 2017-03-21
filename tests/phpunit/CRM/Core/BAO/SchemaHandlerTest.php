<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Core_BAO_SchemaHandlerTest.
 *
 * These tests create and drop indexes on the civicrm_uf_join table. The indexes
 * being added and dropped we assume will never exist.
 * @group headless
 */
class CRM_Core_BAO_SchemaHandlerTest extends CiviUnitTestCase {

  /**
   * Test creating an index.
   *
   * We want to be sure it creates an index and exits gracefully if the index
   * already exists.
   */
  public function testCreateIndex() {
    $tables = array('civicrm_uf_join' => array('weight'));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    $dao = CRM_Core_DAO::executeQuery("SHOW INDEX FROM civicrm_uf_join");
    $count = 0;

    while ($dao->fetch()) {
      if ($dao->Column_name == 'weight') {
        $count++;
        CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_uf_join DROP INDEX " . $dao->Key_name);
      }
    }
    $this->assertEquals(1, $count);
  }

  /**
   * Test CRM_Core_BAO_SchemaHandler::getIndexes() function
   */
  public function testGetIndexes() {
    $indexes = CRM_Core_BAO_SchemaHandler::getIndexes(array('civicrm_contact'));
    $this->assertTrue(array_key_exists('index_contact_type', $indexes['civicrm_contact']));
  }

  /**
   * Test CRM_Core_BAO_SchemaHandler::getMissingIndexes() function
   */
  // public function testGetMissingIndexes() {
  //   $missing = CRM_Core_BAO_SchemaHandler::getMissingIndexes(array('civicrm_contact' => array('rabbit', 'contact_type')));
  //   $fields = CRM_Utils_Array::collect('field', $missing['civicrm_contact']);
  //   $this->assertEquals(1, count($fields));
  //   $this->assertTrue(in_array('rabbit', $fields));
  //   $this->assertFalse(in_array('contact_type', $fields));
  // }

  /**
   * Test CRM_Core_BAO_SchemaHandler::checkIndices() function
   */
  // public function testCheckIndices() {
  //   $data = array(
  //     'civicrm_contact' => array(
  //       'index_contact_type' => array(
  //         'name' => 'index_contact_type',
  //         'field' => array('contact_type'),
  //         'localizable' => NULL,
  //       ),
  //       'index_rabbit' => array(
  //         'name' => 'index_rabbit',
  //         'field' => array('rabbit'),
  //         'localizable' => NULL,
  //       ),
  //     ),
  //   );
  //   $missing = CRM_Core_BAO_SchemaHandler::checkIndices($data);
  //   $fields = CRM_Utils_Array::collect('field', $missing['civicrm_contact']);
  //   $this->assertEquals(1, count($fields));
  //   $this->assertTrue(in_array('rabbit', $fields));
  //   $this->assertFalse(in_array('contact_type', $fields));
  // }

  /**
   * Test creating an index.
   *
   * We want to be sure it creates an index and exits gracefully if the index
   * already exists.
   */
  public function testCombinedIndex() {
    $tables = array('civicrm_uf_join' => array('weight'));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);

    $tables = array('civicrm_uf_join' => array(array('weight', 'module')));
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    $dao = CRM_Core_DAO::executeQuery("SHOW INDEX FROM civicrm_uf_join");
    $weightCount = 0;
    $combinedCount = 0;
    $indexes = array();

    while ($dao->fetch()) {
      if ($dao->Column_name == 'weight') {
        $weightCount++;
        $indexes[$dao->Key_name] = $dao->Key_name;
      }
      if ($dao->Column_name == 'module') {
        $combinedCount++;
        $this->assertArrayHasKey($dao->Key_name, $indexes);
      }

    }
    foreach (array_keys($indexes) as $index) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_uf_join DROP INDEX " . $index);
    }
    $this->assertEquals(2, $weightCount);
  }

  /**
   * Test the drop index if exists function for a non-existent index.
   */
  public function testCheckIndexNotExists() {
    $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_contact', 'magic_button'));
  }

  /**
   * Test the drop index if exists function for a non-existent index.
   */
  public function testCheckIndexExists() {
    $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_contact', 'index_hash'));
  }

  /**
   * Test the drop index if exists function for a non-existent index.
   */
  public function testDropIndexNoneExists() {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_contact', 'magic_button');
  }

  /**
   * Test the drop index if exists function.
   */
  public function testDropIndexExists() {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_contact', 'index_hash');
    $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_contact', 'index_hash'));

    // Recreate it to clean up after the test.
    CRM_Core_BAO_SchemaHandler::createIndexes(array('civicrm_contact' => array('hash')));
  }

  /**
   * @return array
   */
  public function columnTests() {
    $columns = array();
    $columns[] = array('civicrm_contribution', 'total_amount');
    $columns[] = array('civicrm_contact', 'first_name');
    $columns[] = array('civicrm_contact', 'xxxx');
    return $columns;
  }

  /**
   * @param $tableName
   * @param $columnName
   *
   * @dataProvider columnTests
   */
  public function testCheckIfColumnExists($tableName, $columnName) {
    if ($columnName == 'xxxx') {
      $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $columnName));
    }
    else {
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::checkIfFieldExists($tableName, $columnName));
    }
  }

  /**
   * @return array
   */
  public function foreignKeyTests() {
    $keys = array();
    $keys[] = array('civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_email_id');
    $keys[] = array('civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_id');
    return $keys;
  }

  /**
   * Test to see if we can drop foreign key
   *
   * @dataProvider foreignKeyTests
   */
  public function testSafeDropForeignKey($tableName, $key) {
    if ($key == 'FK_civicrm_mailing_recipients_id') {
      $this->assertFalse(CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_recipients', $key));
    }
    else {
      $this->assertTrue(CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_mailing_recipients', $key));
    }
  }

  /**
   * Check there are no missing indices
   */
  public function testGetMissingIndices() {
    $m = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    // print_r($m);
    $this->assertTrue(empty(CRM_Core_BAO_SchemaHandler::getMissingIndices()));
  }

  /**
   * Test that missing indices are correctly created
   */
  public function testCreateMissingIndices() {
    $indices = array(
      'test_table' => array(
        'test_index1' => array(
          'name' => 'test_index1',
          'field' => array(
            'title',
          ),
          'unique' => FALSE,
        ),
        'test_index2' => array(
          'name' => 'test_index2',
          'field' => array(
            'title',
          ),
          'unique' => TRUE,
        ),
        'test_index3' => array(
          'name' => 'test_index3',
          'field' => array(
            'title(3)',
            'name',
          ),
          'unique' => FALSE,
        ),
      ),
    );
    CRM_Core_DAO::executeQuery('drop table if exists `test_table`');
    CRM_Core_DAO::executeQuery('create table `test_table` (`title` varchar(255), `name` varchar(255))');
    CRM_Core_BAO_SchemaHandler::createMissingIndices($indices);
    $actualIndices = CRM_Core_BAO_SchemaHandler::getIndexes(array('test_table'));
    // print_r(array('actualIndices' => $actualIndices));
    $this->assertEquals($actualIndices, $indices);
  }

  /**
   * Test index signatures are added correctly
   */
  public function testAddIndexSignatures() {
    $indices = array(
      'one' => array(
        'field' => array('id', 'name(3)'),
        'unique' => TRUE,
      ),
      'two' => array(
        'field' => array('title'),
      ),
    );
    CRM_Core_BAO_SchemaHandler::addIndexSignature('my_table', $indices);
    $this->assertEquals($indices['one']['sig'], 'my_table::1::id::name(3)');
    $this->assertEquals($indices['two']['sig'], 'my_table::0::title');
  }

}
