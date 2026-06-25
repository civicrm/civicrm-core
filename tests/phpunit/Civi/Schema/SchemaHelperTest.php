<?php

namespace Civi\Schema;

class SchemaHelperTest extends \CiviUnitTestCase {

  public function testGetExistingTables(): void {
    $tables = \Civi::schemaHelper()->getExistingTables(['civicrm_activity', 'civicrm_contact', 'civicrm_false_nothing']);
    sort($tables); /* Ex: On MariaDB 10.6, result order is unpredictable. */
    $this->assertEquals(['civicrm_activity', 'civicrm_contact'], array_values($tables));

    $deprecations = static::captureErrors(E_USER_DEPRECATED, function (): void {
      $tables = \Civi::schemaHelper()->getExistingTables(['civicrm_activity', 'civicrm_contact', 'CiviCRM_TAG']);
      sort($tables);
      $this->assertEquals(['civicrm_activity', 'civicrm_contact', 'civicrm_tag'], array_values($tables));
    });
    $this->assertEquals(['SchemaHelper should be called with portable table-names (alphanumeric, lowercase). Found non-portable table-name: CiviCRM_TAG'], $deprecations);
  }

  public function testTableExists(): void {
    $this->assertTrue(\Civi::schemaHelper()->tableExists('civicrm_activity'));
    // Function is case-insensitive.
    $deprecations = static::captureErrors(E_USER_DEPRECATED, function (): void {
      $this->assertTrue(\Civi::schemaHelper()->tableExists('CiviCRM_Activity'));
    });
    $this->assertEquals(['SchemaHelper should be called with portable table-names (alphanumeric, lowercase). Found non-portable table-name: CiviCRM_Activity'], $deprecations);
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

  public function testAlterSchemaFieldWithForeignKey(): void {
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

  /**
   * Execute a callback. Capture and return any PHP errors (which match the given filter).
   *
   * @param int $errorMask
   *   Ex: E_USER_DEPRECATED|E_DEPRECATED
   * @param callable $callback
   * @return string[]
   */
  public static function captureErrors(int $errorMask, callable $callback): array {
    $deprecations = [];

    $previousHandler = set_error_handler(
      function (int $errno, string $errstr, string $errfile = '', int $errline = 0) use (&$deprecations, &$previousHandler, $errorMask) {
        if ($errno & $errorMask) {
          $deprecations[] = $errstr;
          return TRUE;
        }
        elseif ($previousHandler !== NULL) {
          return (bool) $previousHandler($errno, $errstr, $errfile, $errline);
        }
        else {
          return FALSE;
        }
      }
    );

    try {
      $callback();
    }
    finally {
      restore_error_handler();
    }

    return $deprecations;
  }

}
