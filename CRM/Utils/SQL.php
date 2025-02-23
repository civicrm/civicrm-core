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
 * Just another collection of static utils functions.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_SQL {

  /**
   * Given a string like "UPDATE some_table SET !field = @value", replace "!field" and "@value".
   *
   * This is syntactic sugar for using CRM_Utils_SQL_*::interpolate() without an OOP representation of the query.
   *
   * @param string $expr SQL expression
   * @param null|array $args a list of values to insert into the SQL expression; keys are prefix-coded:
   *   prefix '@' => escape SQL
   *   prefix '#' => literal number, skip escaping but do validation
   *   prefix '!' => literal, skip escaping and validation
   *   if a value is an array, then it will be imploded
   *
   * PHP NULL's will be treated as SQL NULL's. The PHP string "null" will be treated as a string.
   *
   * @return string
   */
  public static function interpolate($expr, $args) {
    if (!isset(Civi::$statics[__CLASS__][__FUNCTION__])) {
      Civi::$statics[__CLASS__][__FUNCTION__] = new class extends CRM_Utils_SQL_BaseParamQuery {

        public function __construct() {
          $this->mode = CRM_Utils_SQL_BaseParamQuery::INTERPOLATE_INPUT;
          $this->strict();
        }

      };
    }
    /** @var \CRM_Utils_SQL_BaseParamQuery $qb */
    $qb = Civi::$statics[__CLASS__][__FUNCTION__];
    return $qb->strict()->interpolate($expr, $args);
  }

  /**
   * Helper function for adding the permissioned subquery from one entity onto another
   *
   * @param string $entityName
   * @param string $joinColumn
   * @return array
   */
  public static function mergeSubquery($entityName, $joinColumn = 'id') {
    $baoName = CRM_Core_DAO_AllCoreTables::getBAOClassName(CRM_Core_DAO_AllCoreTables::getDAONameForEntity($entityName));
    $bao = new $baoName();
    $fields = $bao::getSupportedFields();
    $fieldNames = array_keys($fields);
    $mergeClauses = $subClauses = [];
    foreach ($bao->addSelectWhereClause($entityName) as $fieldName => $fieldClauses) {
      if ($fieldClauses) {
        foreach ((array) $fieldClauses as $fieldClause) {
          $originalClause = $fieldClause;
          CRM_Utils_SQL::prefixFieldNames($fieldClause, $fieldNames, $bao->tableName());
          // Same as join column with no additional fields - can be added directly
          if ($fieldName === $joinColumn && $originalClause === $fieldClause) {
            $mergeClauses[] = $fieldClause;
          }
          // Arrays of arrays get joined with OR (similar to CRM_Core_Permission::check)
          elseif (is_array($fieldClause)) {
            $subClauses[] = "(($fieldName " . implode(") OR ($fieldName ", $fieldClause) . '))';
          }
          else {
            $subClauses[] = "$fieldName $fieldClause";
          }
        }
      }
    }
    if ($subClauses) {
      $mergeClauses[] = "IN (SELECT `$joinColumn` FROM `" . $bao->tableName() . "` WHERE " . implode(' AND ', $subClauses) . ")";
    }
    return $mergeClauses;
  }

  /**
   * Walk a nested array and replace "{field_name}" with "`tableAlias`.`field_name`"
   *
   * @param string|array $clause
   * @param array $fieldNames
   * @param string $tableAlias
   * @return string|array
   */
  public static function prefixFieldNames(&$clause, array $fieldNames, string $tableAlias) {
    if (is_array($clause)) {
      foreach ($clause as $index => $subclause) {
        $clause[$index] = self::prefixFieldNames($subclause, $fieldNames, $tableAlias);
      }
    }
    if (is_string($clause) && str_contains($clause, '{')) {
      $find = $replace = [];
      foreach ($fieldNames as $fieldName) {
        $find[] = '{' . $fieldName . '}';
        $replace[] = '`' . $tableAlias . '`.`' . $fieldName . '`';
      }
      $clause = str_replace($find, $replace, $clause);
    }
    return $clause;
  }

  /**
   * Get current sqlModes of the session
   * @return array
   */
  public static function getSqlModes() {
    $sqlModes = explode(',', CRM_Core_DAO::singleValueQuery('SELECT @@sql_mode'));
    return $sqlModes;
  }

  /**
   * Checks if this system enforce the MYSQL mode ONLY_FULL_GROUP_BY.
   * This function should be named supportsAnyValueAndEnforcesFullGroupBY(),
   * but should be deprecated instead.
   *
   * @return mixed
   * @deprecated
   */
  public static function supportsFullGroupBy() {
    // CRM-21455 MariaDB 10.2 does not support ANY_VALUE
    $version = self::getDatabaseVersion();

    if (stripos($version, 'mariadb') !== FALSE) {
      return FALSE;
    }

    return version_compare($version, '5.7', '>=');
  }

  /**
   * Disable ONLY_FULL_GROUP_BY for MySQL versions lower then 5.7
   *
   * @return bool
   */
  public static function disableFullGroupByMode() {
    $sqlModes = self::getSqlModes();

    // Disable only_full_group_by mode for lower sql versions.
    if (!self::supportsFullGroupBy() || (!empty($sqlModes) && !in_array('ONLY_FULL_GROUP_BY', $sqlModes))) {
      if ($key = array_search('ONLY_FULL_GROUP_BY', $sqlModes)) {
        unset($sqlModes[$key]);
        CRM_Core_DAO::executeQuery("SET SESSION sql_mode = '" . implode(',', $sqlModes) . "'");
      }
      return TRUE;
    }

    return FALSE;
  }

  /**
   * CHeck if ONLY_FULL_GROUP_BY is in the global sql_modes
   * @return bool
   */
  public static function isGroupByModeInDefault() {
    $sqlModes = explode(',', CRM_Core_DAO::singleValueQuery('SELECT @@global.sql_mode'));
    if (!in_array('ONLY_FULL_GROUP_BY', $sqlModes)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Get the version string for the database.
   *
   * @return string
   */
  public static function getDatabaseVersion() {
    return CRM_Core_DAO::singleValueQuery('SELECT VERSION()');
  }

  public static function connect($dsn) {
    $dsn = CRM_Utils_SQL::autoSwitchDSN($dsn);
    $options = CRM_Utils_SQL::isSSLDSN($dsn) ? ['ssl' => TRUE] : [];
    return DB::connect($dsn, $options);
  }

  /**
   * Does the DSN indicate the connection should use ssl.
   *
   * @param string $dsn
   *
   * @return bool
   */
  public static function isSSLDSN(string $dsn):bool {
    // Note that ssl= below is not an official PEAR::DB option. It doesn't know
    // what to do with it. We made it up because it's not required
    // to have client-side certificates to use ssl, so here you can specify
    // you want that by putting ssl=1 in the DSN string.
    //
    // Cast to bool in case of error which we interpret as no ssl.
    return (bool) preg_match('/[\?&](key|cert|ca|capath|cipher|ssl)=/', $dsn);
  }

  /**
   * If DB_DSN_MODE is auto then we should replace mysql with mysqli if mysqli is available or the other way around as appropriate
   * @param string $dsn
   *
   * @return string
   */
  public static function autoSwitchDSN($dsn) {
    if (defined('DB_DSN_MODE') && DB_DSN_MODE === 'auto') {
      if (extension_loaded('mysqli')) {
        $dsn = preg_replace('/^mysql:/', 'mysqli:', $dsn);
      }
      else {
        $dsn = preg_replace('/^mysqli:/', 'mysql:', $dsn);
      }
    }
    return $dsn;
  }

  /**
   * Filter out Emojis in where clause if the database (determined by checking the create table for civicrm_contact)
   * cannot support emojis
   * @param mixed $criteria - filter criteria to check
   *
   * @return bool|string
   */
  public static function handleEmojiInQuery($criteria) {
    if (!CRM_Core_BAO_SchemaHandler::databaseSupportsUTF8MB4()) {
      foreach ((array) $criteria as $criterion) {
        if (!empty($criterion) && !is_numeric($criterion)
          // The first 2 criteria are redundant but are added as they
          // seem like they would
          // be quicker than this 3rd check.
          && max(array_map('ord', str_split($criterion))) >= 240) {
          // String contains unsupported emojis.
          // We return a clause that resolves to false as an emoji string by definition cannot be saved.
          // note that if we return just 0 for false if gets lost in empty checks.
          // https://stackoverflow.com/questions/16496554/can-php-detect-4-byte-encoded-utf8-chars
          return '0 = 1';
        }
      }
      return TRUE;
    }
  }

}
