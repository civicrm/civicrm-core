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
   * @param string $entity
   * @param string $joinColumn
   * @return array
   */
  public static function mergeSubquery($entity, $joinColumn = 'id') {
    require_once 'api/v3/utils.php';
    $baoName = _civicrm_api3_get_BAO($entity);
    $bao = new $baoName();
    $clauses = $subclauses = [];
    foreach ((array) $bao->addSelectWhereClause() as $field => $vals) {
      if ($vals && $field == $joinColumn) {
        $clauses = array_merge($clauses, (array) $vals);
      }
      elseif ($vals) {
        $subclauses[] = "$field " . implode(" AND $field ", (array) $vals);
      }
    }
    if ($subclauses) {
      $clauses[] = "IN (SELECT `$joinColumn` FROM `" . $bao->tableName() . "` WHERE " . implode(' AND ', $subclauses) . ")";
    }
    return $clauses;
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
   * Does the DB version support mutliple locks per
   *
   * https://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
   *
   * This is a conservative measure to introduce the change which we expect to deprecate later.
   *
   * @todo we only check mariadb & mysql right now but maybe can add percona.
   */
  public static function supportsMultipleLocks() {
    static $isSupportLocks = NULL;
    if (!isset($isSupportLocks)) {
      $version = self::getDatabaseVersion();
      if (stripos($version, 'mariadb') !== FALSE) {
        $isSupportLocks = version_compare($version, '10.0.2', '>=');
      }
      else {
        $isSupportLocks = version_compare($version, '5.7.5', '>=');
      }
    }

    return $isSupportLocks;
  }

  /**
   * Get the version string for the database.
   *
   * @return string
   */
  public static function getDatabaseVersion() {
    return CRM_Core_DAO::singleValueQuery('SELECT VERSION()');
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

}
