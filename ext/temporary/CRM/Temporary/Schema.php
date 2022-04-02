<?php

/**
 * Update temporal schema (TIMESTAMPs<=>DATETIMEs).
 */
class CRM_Temporary_Schema {

  /**
   * Change the schema across all timestamp fields.
   *
   * @param string $newMode
   * @throws \CRM_Core_Exception
   */
  public static function setMode(string $newMode) {
    static::reconcileSqlTables($newMode);
    Civi::settings()->set('temporary_timestamps', $newMode);
    CRM_Core_Invoke::rebuildMenuAndCaches(TRUE, FALSE);;
  }

  public static function reconcileSqlTables(string $newMode) {
    $newMode = ($newMode && $newMode !== 'auto') ? $newMode : TEMPORARY_TIMESTAMP_AUTO;

    $expectColumns = [];
    $expectColumns['ts'] = ['ts' => TRUE, 'gmt' => FALSE];
    $expectColumns['gmt'] = ['ts' => FALSE, 'gmt' => TRUE];
    $expectColumns['hybrid'] = ['ts' => TRUE, 'gmt' => TRUE];

    if (!isset($expectColumns[$newMode])) {
      throw new \CRM_Core_Exception("Unrecognized mode: $newMode");
    }
    $plan = self::createPlan($expectColumns[$newMode]['gmt'], $expectColumns[$newMode]['ts'], static::walkFields());
    foreach ($plan as $sql) {
      // echo "$sql\n";
      CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, TRUE, FALSE);
    }

    foreach (static::walkTables() as $tableName => $daoClass) {
      unset(Civi::$statics[$daoClass]);
    }
  }

  /**
   * @param bool $expectGmt
   *   Do we expect to have `DATETIME`/`*_gmt` fields?
   * @param bool $expectTs
   *   Do we expect to have `TIMESTAMP` fields?
   * @param iterable<array> $fields
   *   List of fields (in their original `TIMESTAMP` formulation).
   * @return array
   *   List of SQL statements to run.
   * @throws \CRM_Core_Exception
   */
  protected static function createPlan(bool $expectGmt, bool $expectTs, iterable $fields): array {
    if (CRM_Core_I18n::getMultilingual()) {
      throw new \CRM_Core_Exception("Not implemented; multilingual conversion");
      // Might work... Go ahead and try...
    }

    $plan = [];

    foreach ($fields as $field) {
      $table = $field['table_name'];
      $tsField = $field['name'];
      $gmtField = $field['name'] . '_gmt';
      $tsFieldExists = CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $tsField);
      $gmtFieldExists = CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $gmtField);

      if (!$gmtFieldExists && !$tsFieldExists) {
        throw new \CRM_Core_Exception("Cannot prepare plan. $table has neither $tsField nor $gmtField");
      }

      $desc = [];
      $desc[] = empty($field['required']) ? 'NULL' : 'NOT NULL';
      if (!empty($field['default'])) {
        $desc[] = sprintf('DEFAULT %s', $field['default']);
      }
      if (!empty($field['description'])) {
        $desc[] = sprintf('COMMENT \'%s\'', $field['description']);
      }

      if (!$gmtFieldExists && $expectGmt) {
        $plan[] = sprintf('ALTER TABLE %s ADD COLUMN `%s` datetime ', $table, $gmtField) . implode(' ', $desc);
        $plan[] = sprintf('UPDATE %s SET %s = CONVERT_TZ(%s, @@time_zone, "+0:00")', $table, $gmtField, $tsField);
      }
      if (!$tsFieldExists && $expectTs) {
        $plan[] = sprintf('ALTER TABLE %s ADD COLUMN `%s` timestamp ', $table, $tsField) . implode(' ', $desc);
        $plan[] = sprintf('UPDATE %s SET %s = CONVERT_TZ(%s, @@time_zone, "+0:00")', $table, $tsField, $gmtField);
      }

      if ($gmtFieldExists && !$expectGmt) {
        $plan[] = sprintf('ALTER TABLE %s DROP COLUMN `%s`', $table, $gmtField);
      }
      if ($tsFieldExists && !$expectTs) {
        $plan[] = sprintf('ALTER TABLE %s DROP COLUMN `%s`', $table, $tsField);
      }
    }
    return $plan;
  }

  protected static function walkTables(): iterable {
    yield from [];
    foreach (CRM_Core_DAO_AllCoreTables::tables() as $table => $daoClass) {
      if (preg_match(TEMPORARY_TIMESTAMP_TABLES, $table)) {
        yield $table => $daoClass;
      }
    }
  }

  protected static function walkFields(): iterable {
    yield from [];
    foreach (static::walkTables() as $table => $daoClass) {
      $fields = $daoClass::fields();
      foreach ($fields as $fieldName => $field) {
        if ($field['type'] === CRM_Utils_Type::T_TIMESTAMP) {
          yield $fieldName => $field;
        }
      }
    }
  }

}
