<?php

namespace Civi\Schema;

class SchemaHelperTest extends \CiviUnitTestCase {

  public function testGetExistingTables(): void {
    fwrite(STDERR, "\nSQL_MODE: " . \CRM_Core_DAO::singleValueQuery('SELECT @@SQL_MODE') . "\n");
    $dao = \CRM_Core_DAO::executeQuery('SHOW VARIABLES');
    while ($dao->fetch()) {
      fwrite(STDERR, $dao->Variable_name . ': ' . $dao->Value . "\n");
    }
    $tables = \Civi::schemaHelper()->getExistingTables(['civicrm_activity', 'civicrm_contact', 'CiviCRM_TAG']);
    $this->assertEquals(['civicrm_activity', 'civicrm_contact', 'civicrm_tag'], array_values($tables));
  }

  public function testTableExists(): void {
    $this->assertTrue(\Civi::schemaHelper()->tableExists('civicrm_activity'));
    // Function is case-insensitive.
    $this->assertTrue(\Civi::schemaHelper()->tableExists('CiviCRM_Activity'));
    $this->assertFalse(\Civi::schemaHelper()->tableExists('civicrm_false_nothing'));
  }

  public function testForeignKeyExists(): void {
    $this->assertTrue(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));
    $this->assertFalse(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_false_nothing'));
  }

  public function testIndexExists(): void {
    $this->assertTrue(\Civi::schemaHelper()->indexExists('civicrm_activity', 'index_status_id'));
    $this->assertFalse(\Civi::schemaHelper()->indexExists('civicrm_activity', 'index_false_nothing'));
  }

  public function testAlterSchemaFieldWithForiegnKey(): void {
    \Civi::schemaHelper()->dropForeignKey('civicrm_activity', 'FK_civicrm_activity_parent_id');

    $this->assertFalse(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    \Civi::schemaHelper()->alterSchemaField('Activity', 'parent_id', [
      'title' => 'Parent Activity ID',
      'sql_type' => 'int unsigned',
      'readonly' => TRUE,
      'description' => 'Column altered by test.',
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ]);

    $this->assertTrue(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    $result = \CRM_Core_DAO::executeQuery(
      "SELECT COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'civicrm_activity'
       AND COLUMN_NAME = 'parent_id'"
    );
    $result->fetch();
    $this->assertEquals('Column altered by test.', $result->COLUMN_COMMENT);
  }

  public function testDropAndAddIndex(): void {
    \Civi::schemaHelper()->dropIndex('civicrm_activity', 'index_status_id');

    $this->assertFalse(\Civi::schemaHelper()->indexExists('civicrm_activity', 'index_status_id'));

    \Civi::schemaHelper()->createIndex('civicrm_activity', 'index_status_id', [
      'fields' => [
        'status_id' => TRUE,
      ],
    ]);

    $this->assertTrue(\Civi::schemaHelper()->indexExists('civicrm_activity', 'index_status_id'));
  }

}
