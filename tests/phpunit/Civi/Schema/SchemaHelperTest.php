<?php

namespace Civi\Schema;

class SchemaHelperTest extends \CiviUnitTestCase {

  /**
   * Generating SQL must not alter the database.
   */
  public function testGenerateInstallSqlHasNoSideEffects(): void {
    $before = $this->getForeignKeys();
    $this->assertNotEmpty($before);
    \Civi::schemaHelper()->generateInstallSql();
    $this->assertEquals($before, $this->getForeignKeys());
  }

  /**
   * Installing on top of pre-existing tables must not fail on existing constraints.
   *
   * This installs the full core schema, so it is declared ahead of the tests which
   * deliberately drop and re-add foreign keys.
   */
  public function testInstallOverExistingSchema(): void {
    // A sample of core constraints which the install SQL must (re)create.
    $expectedForeignKeys = [
      'civicrm_email.FK_civicrm_email_contact_id',
      'civicrm_phone.FK_civicrm_phone_contact_id',
      'civicrm_activity_contact.FK_civicrm_activity_contact_activity_id',
    ];
    // The core tables already exist, so this re-runs "CREATE TABLE IF NOT EXISTS"
    // plus "ALTER TABLE ... ADD CONSTRAINT" for every foreign key.
    \Civi::schemaHelper()->install();
    $foreignKeys = $this->getForeignKeys();
    $this->assertEmpty(array_diff($expectedForeignKeys, $foreignKeys));
    $this->assertGreaterThan(100, count($foreignKeys));
    // Installing again over the same tables must neither fail nor change anything.
    \Civi::schemaHelper()->install();
    $foreignKeys2 = $this->getForeignKeys();
    $this->assertEmpty(array_diff($expectedForeignKeys, $foreignKeys2));
    $this->assertEquals($foreignKeys, $foreignKeys2);
  }

  /**
   * Entities which share a table must each contribute their foreign keys.
   */
  public function testGetForeignKeyNamesWithSharedTable(): void {
    $generatorClass = get_class(require \Civi::paths()->getPath('[civicrm.root]/mixin/lib/civimix-schema@5/src/SqlGenerator.php'));
    $field = ['entity_reference' => ['entity' => 'Contact', 'key' => 'id']];
    $getTable = function() {
      return 'civicrm_contact';
    };
    $generator = new $generatorClass([
      'First' => [
        'name' => 'First',
        'table' => 'civicrm_shared',
        'getFields' => function() use ($field) {
          return ['first_id' => $field];
        },
      ],
      'Second' => [
        'name' => 'Second',
        'table' => 'civicrm_shared',
        'getFields' => function() use ($field) {
          return ['second_id' => $field];
        },
      ],
    ], $getTable);
    $this->assertEquals(
      ['civicrm_shared' => ['FK_civicrm_shared_first_id', 'FK_civicrm_shared_second_id']],
      $generator->getForeignKeyNames()
    );
  }

  /**
   * @return array
   *   Sorted list of foreign keys, each as "table.constraint".
   */
  private function getForeignKeys(): array {
    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT CONCAT(TABLE_NAME, '.', CONSTRAINT_NAME) AS fk
         FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
         AND CONSTRAINT_TYPE = 'FOREIGN KEY'
         ORDER BY fk"
    );
    $foreignKeys = [];
    while ($dao->fetch()) {
      $foreignKeys[] = $dao->fk;
    }
    return $foreignKeys;
  }

  public function testGetExistingTables(): void {
    $tables = \Civi::schemaHelper()->getExistingTables(['civicrm_activity', 'civicrm_contact', 'civicrm_false_nothing']);
    $this->assertEquals(['civicrm_activity', 'civicrm_contact'], array_values($tables));

    $deprecations = static::captureErrors(E_USER_DEPRECATED, function (): void {
      $tables = \Civi::schemaHelper()->getExistingTables(['civicrm_activity', 'civicrm_contact', 'CiviCRM_TAG']);
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

  public function testDropSchemaFieldWithForeignKey(): void {
    \Civi::schemaHelper()->alterSchemaField('Activity', 'parent_id', [
      'title' => 'Parent Activity ID',
      'sql_type' => 'int unsigned',
      'readonly' => TRUE,
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ]);
    $this->assertTrue(\Civi::schemaHelper()->schemaFieldExists('Activity', 'parent_id'));
    $this->assertTrue(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    \Civi::schemaHelper()->alterSchemaField('Activity', 'parent_id', [
      'title' => 'Parent Activity ID',
      'sql_type' => 'int unsigned',
      'readonly' => TRUE,
    ]);
    $this->assertFalse(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    \Civi::schemaHelper()->alterSchemaField('Activity', 'parent_id', [
      'title' => 'Parent Activity ID',
      'sql_type' => 'int unsigned',
      'readonly' => TRUE,
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ]);
    $this->assertTrue(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    \Civi::schemaHelper()->dropSchemaField('Activity', 'parent_id');

    $this->assertFalse(\Civi::schemaHelper()->schemaFieldExists('Activity', 'parent_id'));
    $this->assertFalse(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));

    \Civi::schemaHelper()->alterSchemaField('Activity', 'parent_id', [
      'title' => 'Parent Activity ID',
      'sql_type' => 'int unsigned',
      'readonly' => TRUE,
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ]);
    $this->assertTrue(\Civi::schemaHelper()->schemaFieldExists('Activity', 'parent_id'));
    $this->assertTrue(\Civi::schemaHelper()->foreignKeyExists('civicrm_activity', 'FK_civicrm_activity_parent_id'));
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
