<?php

namespace Civi\Schema;

class SchemaHelperTest extends \CiviUnitTestCase {

  public function testGetExistingTables(): void {
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
