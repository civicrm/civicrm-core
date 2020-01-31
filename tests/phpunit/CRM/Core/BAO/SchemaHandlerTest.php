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
 * Class CRM_Core_BAO_SchemaHandlerTest.
 *
 * These tests create and drop indexes on the civicrm_uf_join table. The indexes
 * being added and dropped we assume will never exist.
 *
 * @group headless
 */
class CRM_Core_BAO_SchemaHandlerTest extends CiviUnitTestCase {

  /**
   * Ensure any removed indices are put back.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    parent::tearDown();
    $this->callAPISuccess('System', 'updateindexes', []);
  }

  /**
   * Test creating an index.
   *
   * We want to be sure it creates an index and exits gracefully if the index
   * already exists.
   */
  public function testCreateIndex() {
    $tables = ['civicrm_uf_join' => ['weight']];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    $dao = CRM_Core_DAO::executeQuery('SHOW INDEX FROM civicrm_uf_join');
    $count = 0;

    while ($dao->fetch()) {
      if ($dao->Column_name === 'weight') {
        $count++;
        CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_uf_join DROP INDEX ' . $dao->Key_name);
      }
    }
    $this->assertEquals(1, $count);
  }

  /**
   * Test CRM_Core_BAO_SchemaHandler::getIndexes() function
   */
  public function testGetIndexes() {
    $indexes = CRM_Core_BAO_SchemaHandler::getIndexes(['civicrm_contact']);
    $this->assertTrue(array_key_exists('index_contact_type', $indexes['civicrm_contact']));
  }

  /**
   * Test creating an index.
   *
   * We want to be sure it creates an index and exits gracefully if the index
   * already exists.
   */
  public function testCombinedIndex() {
    $tables = ['civicrm_uf_join' => ['weight']];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);

    $tables = ['civicrm_uf_join' => [['weight', 'module']]];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables);
    $dao = CRM_Core_DAO::executeQuery('SHOW INDEX FROM civicrm_uf_join');
    $weightCount = 0;
    $indexes = [];

    while ($dao->fetch()) {
      if ($dao->Column_name === 'weight') {
        $weightCount++;
        $indexes[$dao->Key_name] = $dao->Key_name;
      }
      if ($dao->Column_name === 'module') {
        $this->assertArrayHasKey($dao->Key_name, $indexes);
      }

    }
    foreach (array_keys($indexes) as $index) {
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_uf_join DROP INDEX ' . $index);
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
    CRM_Core_BAO_SchemaHandler::createIndexes(['civicrm_contact' => ['hash']]);
  }

  /**
   * @return array
   */
  public function columnTests() {
    $columns = [];
    $columns[] = ['civicrm_contribution', 'total_amount'];
    $columns[] = ['civicrm_contact', 'first_name'];
    $columns[] = ['civicrm_contact', 'xxxx'];
    return $columns;
  }

  /**
   * @param $tableName
   * @param $columnName
   *
   * @dataProvider columnTests
   */
  public function testCheckIfColumnExists($tableName, $columnName) {
    if ($columnName === 'xxxx') {
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
    $keys = [];
    $keys[] = ['civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_email_id'];
    $keys[] = ['civicrm_mailing_recipients', 'FK_civicrm_mailing_recipients_id'];
    return $keys;
  }

  /**
   * Test to see if we can drop foreign key
   *
   * @dataProvider foreignKeyTests
   *
   * @param string $tableName
   * @param string $key
   */
  public function testSafeDropForeignKey($tableName, $key) {
    if ($key === 'FK_civicrm_mailing_recipients_id') {
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
    $this->assertEmpty($missingIndices);
  }

  /**
   * Test that missing indices are correctly created
   */
  public function testCreateMissingIndices() {
    $indices = [
      'test_table' => [
        'test_index1' => [
          'name' => 'test_index1',
          'field' => [
            'title',
          ],
          'unique' => FALSE,
        ],
        'test_index2' => [
          'name' => 'test_index2',
          'field' => [
            'title',
          ],
          'unique' => TRUE,
        ],
        'test_index3' => [
          'name' => 'test_index3',
          'field' => [
            'title(3)',
            'name',
          ],
          'unique' => FALSE,
        ],
      ],
    ];
    CRM_Core_DAO::executeQuery('DROP table if exists `test_table`');
    CRM_Core_DAO::executeQuery('CREATE table `test_table` (`title` varchar(255), `name` varchar(255))');
    CRM_Core_BAO_SchemaHandler::createMissingIndices($indices);
    $actualIndices = CRM_Core_BAO_SchemaHandler::getIndexes(['test_table']);
    $this->assertEquals($actualIndices, $indices);
  }

  /**
   * Check there are no missing indices
   *
   * @throws \CRM_Core_Exception
   */
  public function testReconcileMissingIndices() {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contact DROP INDEX index_sort_name');
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    // Check the api also retrieves them.
    $missingIndicesAPI = $this->callAPISuccess('System', 'getmissingindices', [])['values'];
    $this->assertEquals($missingIndices, $missingIndicesAPI);
    $this->assertEquals([
      'civicrm_contact' => [
        [
          'name' => 'index_sort_name',
          'field' => ['sort_name'],
          'localizable' => FALSE,
          'sig' => 'civicrm_contact::0::sort_name',
        ],
      ],
    ], $missingIndices);
    $this->callAPISuccess('System', 'updateindexes', []);
    $missingIndices = CRM_Core_BAO_SchemaHandler::getMissingIndices();
    $this->assertEmpty($missingIndices);
  }

  /**
   * Check there are no missing indices
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetMissingIndicesWithTableFilter() {
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contact DROP INDEX index_sort_name');
    CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_contribution DROP INDEX index_total_amount_receive_date');
    $missingIndices = $this->callAPISuccess('System', 'getmissingindices', [])['values'];
    $expected = [
      'civicrm_contact' => [
        [
          'name' => 'index_sort_name',
          'field' => ['sort_name'],
          'localizable' => FALSE,
          'sig' => 'civicrm_contact::0::sort_name',
        ],
      ],
      'civicrm_contribution' => [
        [
          'name' => 'index_total_amount_receive_date',
          'field' => ['total_amount', 'receive_date'],
          'localizable' => FALSE,
          'sig' => 'civicrm_contribution::0::total_amount::receive_date',
        ],
      ],
    ];
    $this->assertEquals($expected, $missingIndices);
    $missingIndices = $this->callAPISuccess('System', 'getmissingindices', ['tables' => ['civicrm_contact']])['values'];
    $this->assertEquals(['civicrm_contact' => $expected['civicrm_contact']], $missingIndices);
    $this->callAPISuccess('System', 'updateindexes', ['tables' => 'civicrm_contribution']);
    $missingIndices = $this->callAPISuccess('System', 'getmissingindices', [])['values'];
    $this->assertEquals(['civicrm_contact' => $expected['civicrm_contact']], $missingIndices);
  }

  /**
   * Check for partial indices
   */
  public function testPartialIndices() {
    $tables = [
      'index_all' => 'civicrm_prevnext_cache',
      'UI_entity_id_entity_table_tag_id' => 'civicrm_entity_tag',
    ];
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
    $indices = [
      'one' => [
        'field' => ['id', 'name(3)'],
        'unique' => TRUE,
      ],
      'two' => [
        'field' => ['title'],
      ],
    ];
    CRM_Core_BAO_SchemaHandler::addIndexSignature('my_table', $indices);
    $this->assertEquals($indices['one']['sig'], 'my_table::1::id::name(3)');
    $this->assertEquals($indices['two']['sig'], 'my_table::0::title');
  }

  /**
   * Test that columns are dropped
   */
  public function testDropColumn() {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS `civicrm_test_drop_column`');
    CRM_Core_DAO::executeQuery('CREATE TABLE `civicrm_test_drop_column` (`id` int(10), `col1` varchar(255), `col2` varchar(255))');

    // test with logging enabled to ensure log triggers don't break anything
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();

    $alterParams = [
      'table_name' => 'civicrm_test_drop_column',
      'operation'  => 'delete',
      'name'       => 'col1',
      'type'       => 'varchar(255)',
      'required'   => FALSE,
      'searchable' => FALSE,
    ];

    // drop col1
    CRM_Core_DAO::executeQuery(CRM_Core_BAO_SchemaHandler::buildFieldChangeSql($alterParams, FALSE));

    $create_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE civicrm_test_drop_column');
    while ($create_table->fetch()) {
      $this->assertNotContains('col1', $create_table->Create_Table);
      $this->assertContains('col2', $create_table->Create_Table);
    }

    // drop col2
    $alterParams['name'] = 'col2';
    CRM_Core_DAO::executeQuery(CRM_Core_BAO_SchemaHandler::buildFieldChangeSql($alterParams, FALSE));

    $create_table = CRM_Core_DAO::executeQuery('SHOW CREATE TABLE civicrm_test_drop_column');
    while ($create_table->fetch()) {
      $this->assertNotContains('col2', $create_table->Create_Table);
    }
  }

  /**
   * Tests the function that generates sql to modify fields.
   */
  public function testBuildFieldChangeSql() {
    $params = [
      'table_name' => 'big_table',
      'operation' => 'add',
      'name' => 'big_bob',
      'type' => 'text',
    ];
    $sql = CRM_Core_BAO_SchemaHandler::buildFieldChangeSql($params, FALSE);
    $this->assertEquals('ALTER TABLE big_table
        ADD COLUMN `big_bob` text', trim($sql));

    $params['operation'] = 'modify';
    $params['comment'] = 'super big';
    $sql = CRM_Core_BAO_SchemaHandler::buildFieldChangeSql($params, FALSE);
    $this->assertEquals("ALTER TABLE big_table
        MODIFY `big_bob` text COMMENT 'super big'", trim($sql));

    $params['operation'] = 'delete';
    $sql = CRM_Core_BAO_SchemaHandler::buildFieldChangeSql($params, FALSE);
    $this->assertEquals('ALTER TABLE big_table DROP COLUMN `big_bob`', trim($sql));
  }

}
