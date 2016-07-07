<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  public function testCHeckIndexNotExists() {
    $this->assertFalse(CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_contact', 'magic_button'));
  }

  /**
   * Test the drop index if exists function for a non-existent index.
   */
  public function testCHeckIndexExists() {
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

}
