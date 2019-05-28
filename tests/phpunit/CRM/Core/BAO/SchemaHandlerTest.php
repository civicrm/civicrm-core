<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertTrue(empty($missingIndices));
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
    CRM_Core_DAO::executeQuery('DROP table if exists `test_table`');
    CRM_Core_DAO::executeQuery('CREATE table `test_table` (`title` varchar(255), `name` varchar(255))');
    CRM_Core_BAO_SchemaHandler::createMissingIndices($indices);
    $actualIndices = CRM_Core_BAO_SchemaHandler::getIndexes(array('test_table'));
    $this->assertEquals($actualIndices, $indices);
  }

  /**
   * Check there are no missing indices
   */
  public function testReconcileMissingIndices() {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contact DROP INDEX index_sort_name');
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertEquals(array(
      'civicrm_contact' => array(
        array(
          'name' => 'index_sort_name',
          'field' => array('sort_name'),
          'localizable' => FALSE,
          'sig' => 'civicrm_contact::0::sort_name',
        ),
      ),
    ), $missingIndices);
    $this->callAPISuccess('System', 'updateindexes', array());
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertTrue(empty($missingIndices));
  }

  /**
   * Check for partial indices
   */
  public function testPartialIndices() {
    $tables = array(
      'index_all' => 'civicrm_prevnext_cache',
      'UI_entity_id_entity_table_tag_id' => 'civicrm_entity_tag',
    );
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_prevnext_cache', 'index_all');
    //Missing Column `is_selected`.
    CRM_Core_DAO::executeQuery('CREATE INDEX index_all ON civicrm_prevnext_cache (cachekey, entity_id1, entity_id2, entity_table)');
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertNotEmpty($missingIndices);

    CRM_Core_BAO_SchemaHandler::dropIndexIfExists('civicrm_entity_tag', 'UI_entity_id_entity_table_tag_id');
    //Test incorrect Ordering(correct order defined is entity_id and then entity_table, tag_id).
    CRM_Core_DAO::executeQuery('CREATE INDEX UI_entity_id_entity_table_tag_id ON civicrm_entity_tag (entity_table, entity_id, tag_id)');
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices(TRUE);
    $this->assertNotEmpty($missingIndices);
    $this->assertEquals(array_values($tables), array_keys($missingIndices));

    //Check if both indices are deleted.
    $indices = CRM_Core_BAO_SchemaHandler::getIndexes($tables);
    foreach ($tables as $index => $tableName) {
      $this->assertFalse(in_array($index, array_keys($indices[$tableName])));
    }
    //Drop false index and create again.
    CRM_Core_BAO_SchemaHandler::createMissingIndices($missingIndices);
    //Both vars should be empty now.
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertEmpty($missingIndices);
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
