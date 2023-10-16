<?php

/**
 * Update temporal schema (TIMESTAMPs<=>DATETIMEs).
 */
class CRM_Temporary_Schema {

  /**
   * If the temporal schema requires any triggers, define them here.
   *
   * @param $info
   * @param null $tableName
   */
  public static function createSqlTriggers(&$info, $tableName = NULL) {
    if (temporary_timestamps() !== 'hybrid') {
      return;
    }

    if ($tableName && !preg_match(TEMPORARY_TIMESTAMP_TABLES, $tableName)) {
      // Optimization
      return;
    }

    $tables = $tableName ? [$tableName] : static::walkTables();
    foreach ($tables as $table => $daoClass) {
      if (preg_match(TEMPORARY_TIMESTAMP_TABLES, $table)) {
        foreach (static::walkFields([$table => $daoClass]) as $field) {
          $tsField = $field['name'];
          $gmtField = $field['name'] . '_gmt';

          // The trigger-content varies based on whether before-trigger has good visibility into changes:
          // - For fields that use `DEFAULT CURRENT_TIMESTAMP` and/or `ON UPDATE CURRENT_TIMESTAMP`, the before-trigger does
          //   not have good visibility. It's sipler to just re-implement equivalent behavior.
          // - For all other fields, do a conditional sync (DATETIME<=>TIMESTAMP).

          if (mb_strpos($field['default'] ?? '', 'CURRENT_TIMESTAMP') !== FALSE) {
            $onInsert = ["SET NEW.{$gmtField} = UTC_TIMESTAMP();"];
          }
          else {
            $onInsert = [
              "IF NEW.{$gmtField} IS NULL AND NEW.{$tsField} IS NOT NULL THEN",
              "  SET NEW.{$gmtField} = CONVERT_TZ(NEW.{$tsField}, @@time_zone, '+0:00');",
              "ELSEIF NEW.{$tsField} IS NULL AND NEW.{$gmtField} IS NOT NULL THEN",
              "  SET NEW.{$tsField} = CONVERT_TZ(NEW.{$gmtField}, '+0:00', @@time_zone);",
              "END IF;",
            ];
          }

          if (mb_strtoupper($field['default'] ?? '') === 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP') {
            $onUpdate = ["SET NEW.{$gmtField} = UTC_TIMESTAMP();"];
          }
          else {
            $onUpdate = [
              "IF NEW.{$tsField} <> OLD.{$tsField} THEN",
              "  SET NEW.{$gmtField} = CONVERT_TZ(NEW.{$tsField}, @@time_zone, '+0:00');",
              "ELSEIF NEW.{$gmtField} <> OLD.{$gmtField} THEN",
              "  SET NEW.{$tsField} = CONVERT_TZ(NEW.{$gmtField}, '+0:00', @@time_zone);",
              "END IF;",
            ];
          }

          $info[] = [
            'table' => $table,
            'when' => 'BEFORE',
            'event' => 'INSERT',
            'sql' => "\n" . implode("\n", $onInsert) . "\n",
          ];
          $info[] = [
            'table' => $table,
            'when' => 'BEFORE',
            'event' => 'UPDATE',
            'sql' => "\n" . implode("\n", $onUpdate) . "\n",
          ];

        }
      }
    }
  }

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
    $plan = self::createPlan($expectColumns[$newMode]['gmt'], $expectColumns[$newMode]['ts'], static::walkFields(static::walkTables()));
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
      if (!empty($field['default']) && ($expectGmt xor $expectTs)) {
        $desc[] = sprintf('DEFAULT %s', $field['default']);
      }
      if (!empty($field['description'])) {
        $desc[] = sprintf('COMMENT \'%s\'', CRM_Core_DAO::escapeString($field['description']));
      }

      if (!$gmtFieldExists && $expectGmt) {
        $plan[] = sprintf('ALTER TABLE %s ADD COLUMN `%s` datetime NULL ', $table, $gmtField) . implode(' ', $desc);
        $plan[] = sprintf('UPDATE %s SET %s = CONVERT_TZ(%s, @@time_zone, "+0:00")', $table, $gmtField, $tsField);
        if (!empty($field['required'])) {
          $plan[] = sprintf('ALTER TABLE %s MODIFY COLUMN `%s` datetime NOT NULL ', $table, $gmtField) . implode(' ', $desc);
        }
      }
      if (!$tsFieldExists && $expectTs) {
        $plan[] = sprintf('ALTER TABLE %s ADD COLUMN `%s` timestamp NULL ', $table, $tsField) . implode(' ', $desc);
        $plan[] = sprintf('UPDATE %s SET %s = CONVERT_TZ(%s, @@time_zone, "+0:00")', $table, $tsField, $gmtField);
        if (!empty($field['required'])) {
          $plan[] = sprintf('ALTER TABLE %s MODIFY COLUMN `%s` timestamp NOT NULL ', $table, $tsField) . implode(' ', $desc);
        }
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

  protected static function walkFields(iterable $tables): iterable {
    yield from [];
    foreach ($tables as $table => $daoClass) {
      $fields = $daoClass::fields();
      foreach ($fields as $fieldName => $field) {
        if ($field['type'] === CRM_Utils_Type::T_TIMESTAMP) {
          yield $fieldName => $field;
        }
      }
    }
  }

}
