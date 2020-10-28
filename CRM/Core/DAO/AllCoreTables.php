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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_DAO_AllCoreTables {

  private static $tables = NULL;
  private static $daoToClass = NULL;
  private static $entityTypes = NULL;

  /**
   * Initialise.
   *
   * @param bool $fresh
   */
  public static function init($fresh = FALSE) {
    static $init = FALSE;
    if ($init && !$fresh) {
      return;
    }
    Civi::$statics[__CLASS__] = [];

    $file = preg_replace('/\.php$/', '.data.php', __FILE__);
    $entityTypes = require $file;
    CRM_Utils_Hook::entityTypes($entityTypes);

    self::$entityTypes = [];
    self::$tables = [];
    self::$daoToClass = [];
    foreach ($entityTypes as $entityType) {
      self::registerEntityType(
        $entityType['name'],
        $entityType['class'],
        $entityType['table'],
        $entityType['fields_callback'] ?? NULL,
        $entityType['links_callback'] ?? NULL
      );
    }

    $init = TRUE;
  }

  /**
   * (Quasi-Private) Do not call externally (except for unit-testing)
   *
   * @param string $daoName
   * @param string $className
   * @param string $tableName
   * @param string $fields_callback
   * @param string $links_callback
   */
  public static function registerEntityType($daoName, $className, $tableName, $fields_callback = NULL, $links_callback = NULL) {
    self::$daoToClass[$daoName] = $className;
    self::$tables[$tableName] = $className;
    self::$entityTypes[$className] = [
      'name' => $daoName,
      'class' => $className,
      'table' => $tableName,
      'fields_callback' => $fields_callback,
      'links_callback' => $links_callback,
    ];
  }

  /**
   * @return array
   *   Ex: $result['CRM_Contact_DAO_Contact']['table'] == 'civicrm_contact';
   */
  public static function get() {
    self::init();
    return self::$entityTypes;
  }

  /**
   * @return array
   *   List of SQL table names.
   */
  public static function tables() {
    self::init();
    return self::$tables;
  }

  /**
   * @return array
   *   List of indices.
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    self::init();
    foreach (self::$daoToClass as $class) {
      if (is_callable([$class, 'indices'])) {
        $indices[$class::getTableName()] = $class::indices($localize);
      }
    }
    return $indices;
  }

  /**
   * Modify indices to account for localization options.
   *
   * @param string $class DAO class
   * @param array $originalIndices index definitions before localization
   *
   * @return array
   *   index definitions after localization
   */
  public static function multilingualize($class, $originalIndices) {
    $locales = CRM_Core_I18n::getMultilingual();
    if (!$locales) {
      return $originalIndices;
    }
    $classFields = $class::fields();

    $finalIndices = [];
    foreach ($originalIndices as $index) {
      if ($index['localizable']) {
        foreach ($locales as $locale) {
          $localIndex = $index;
          $localIndex['name'] .= "_" . $locale;
          $fields = [];
          foreach ($localIndex['field'] as $field) {
            $baseField = explode('(', $field);
            if ($classFields[$baseField[0]]['localizable']) {
              // field name may have eg (3) at end for prefix length
              // last_name => last_name_fr_FR
              // last_name(3) => last_name_fr_FR(3)
              $fields[] = preg_replace('/^([^(]+)(\(\d+\)|)$/', '${1}_' . $locale . '${2}', $field);
            }
            else {
              $fields[] = $field;
            }
          }
          $localIndex['field'] = $fields;
          $finalIndices[$localIndex['name']] = $localIndex;
        }
      }
      else {
        $finalIndices[$index['name']] = $index;
      }
    }
    CRM_Core_BAO_SchemaHandler::addIndexSignature(self::getTableForClass($class), $finalIndices);
    return $finalIndices;
  }

  /**
   * @return array
   *   Mapping from brief-names to class-names.
   *   Ex: $result['Contact'] == 'CRM_Contact_DAO_Contact'.
   */
  public static function daoToClass() {
    self::init();
    return self::$daoToClass;
  }

  /**
   * @return array
   *   Mapping from table-names to class-names.
   *   Ex: $result['civicrm_contact'] == 'CRM_Contact_DAO_Contact'.
   */
  public static function getCoreTables() {
    return self::tables();
  }

  /**
   * Determine whether $tableName is a core table.
   *
   * @param string $tableName
   * @return bool
   */
  public static function isCoreTable($tableName) {
    return array_key_exists($tableName, self::tables());
  }

  /**
   * Get the DAO for a BAO class.
   *
   * @param string $baoName
   *
   * @return string|CRM_Core_DAO
   */
  public static function getCanonicalClassName($baoName) {
    return str_replace('_BAO_', '_DAO_', $baoName);
  }

  /**
   * Get the BAO for a DAO class.
   *
   * @param string $daoName
   *
   * @return string|CRM_Core_DAO
   */
  public static function getBAOClassName($daoName) {
    $baoName = str_replace('_DAO_', '_BAO_', $daoName);
    return class_exists($baoName) ? $baoName : $daoName;
  }

  /**
   * Convert possibly underscore separated words to camel case with special handling for 'UF'
   * e.g membership_payment returns MembershipPayment
   *
   * @param string $name
   * @param bool $legacyV3
   * @return string
   */
  public static function convertEntityNameToCamel(string $name, $legacyV3 = FALSE): string {
    // This map only applies to APIv3
    $map = [
      'acl' => 'Acl',
      'ACL' => 'Acl',
      'im' => 'Im',
      'IM' => 'Im',
    ];
    if ($legacyV3 && isset($map[$name])) {
      return $map[$name];
    }

    $fragments = explode('_', $name);
    foreach ($fragments as & $fragment) {
      $fragment = ucfirst($fragment);
      // Special case: UFGroup, UFJoin, UFMatch, UFField (if passed in without underscores)
      if (strpos($fragment, 'Uf') === 0 && strlen($name) > 2) {
        $fragment = 'UF' . ucfirst(substr($fragment, 2));
      }
    }
    // Special case: UFGroup, UFJoin, UFMatch, UFField (if passed in underscore-separated)
    if ($fragments[0] === 'Uf') {
      $fragments[0] = 'UF';
    }
    return implode('', $fragments);
  }

  /**
   * Convert CamelCase to snake_case, with special handling for some entity names.
   *
   * Eg. Activity returns activity
   *     UFGroup returns uf_group
   *     OptionValue returns option_value
   *
   * @param string $name
   *
   * @return string
   */
  public static function convertEntityNameToLower(string $name): string {
    if ($name === strtolower($name)) {
      return $name;
    }
    if ($name === 'PCP' || $name === 'IM' || $name === 'ACL') {
      return strtolower($name);
    }
    return strtolower(ltrim(str_replace('U_F',
      'uf',
      // That's CamelCase, beside an odd UFCamel that is expected as uf_camel
      preg_replace('/(?=[A-Z])/', '_$0', $name)
    ), '_'));
  }

  /**
   * Get a list of all DAO classes.
   *
   * @return array
   *   List of class names.
   */
  public static function getClasses() {
    return array_values(self::daoToClass());
  }

  /**
   * Get the classname for the table.
   *
   * @param string $tableName
   * @return string|CRM_Core_DAO|NULL
   */
  public static function getClassForTable($tableName) {
    //CRM-19677: on multilingual setup, trim locale from $tableName to fetch class name
    if (CRM_Core_I18n::isMultilingual()) {
      global $dbLocale;
      $tableName = str_replace($dbLocale, '', $tableName);
    }
    return CRM_Utils_Array::value($tableName, self::tables());
  }

  /**
   * Given a brief-name, determine the full class-name.
   *
   * @param string $daoName
   *   Ex: 'Contact'.
   * @return string|CRM_Core_DAO|NULL
   *   Ex: 'CRM_Contact_DAO_Contact'.
   */
  public static function getFullName($daoName) {
    return CRM_Utils_Array::value($daoName, self::daoToClass());
  }

  /**
   * Given a full class-name, determine the brief-name.
   *
   * @param string $className
   *   Ex: 'CRM_Contact_DAO_Contact'.
   * @return string|NULL
   *   Ex: 'Contact'.
   */
  public static function getBriefName($className) {
    $className = self::getCanonicalClassName($className);
    return array_search($className, self::daoToClass(), TRUE) ?: NULL;
  }

  /**
   * @param string $className DAO or BAO name
   * @return string|FALSE SQL table name
   */
  public static function getTableForClass($className) {
    return array_search(self::getCanonicalClassName($className),
      self::tables());
  }

  /**
   * Convert the entity name into a table name.
   *
   * @param string $entityBriefName
   *
   * @return FALSE|string
   */
  public static function getTableForEntityName($entityBriefName) {
    return self::getTableForClass(self::getFullName($entityBriefName));
  }

  /**
   * Reinitialise cache.
   *
   * @param bool $fresh
   */
  public static function reinitializeCache($fresh = FALSE) {
    self::init($fresh);
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * @param string $dao
   *   Ex: 'CRM_Core_DAO_Address'.
   * @param string $labelName
   *   Ex: 'address'.
   * @param bool $prefix
   * @param array $foreignDAOs
   * @return array
   */
  public static function getExports($dao, $labelName, $prefix, $foreignDAOs) {
    // Bug-level compatibility -- or sane behavior?
    $cacheKey = $dao . ':export';
    // $cacheKey = $dao . ':' . ($prefix ? 'export-prefix' : 'export');

    if (!isset(Civi::$statics[__CLASS__][$cacheKey])) {
      $exports = [];
      $fields = $dao::fields();

      foreach ($fields as $name => $field) {
        if (!empty($field['export'])) {
          if ($prefix) {
            $exports[$labelName] = & $fields[$name];
          }
          else {
            $exports[$name] = & $fields[$name];
          }
        }
      }

      foreach ($foreignDAOs as $foreignDAO) {
        $exports = array_merge($exports, $foreignDAO::export(TRUE));
      }

      Civi::$statics[__CLASS__][$cacheKey] = $exports;
    }
    return Civi::$statics[__CLASS__][$cacheKey];
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * @param string $dao
   *   Ex: 'CRM_Core_DAO_Address'.
   * @param string $labelName
   *   Ex: 'address'.
   * @param bool $prefix
   * @param array $foreignDAOs
   * @return array
   */
  public static function getImports($dao, $labelName, $prefix, $foreignDAOs) {
    // Bug-level compatibility -- or sane behavior?
    $cacheKey = $dao . ':import';
    // $cacheKey = $dao . ':' . ($prefix ? 'import-prefix' : 'import');

    if (!isset(Civi::$statics[__CLASS__][$cacheKey])) {
      $imports = [];
      $fields = $dao::fields();

      foreach ($fields as $name => $field) {
        if (!empty($field['import'])) {
          if ($prefix) {
            $imports[$labelName] = & $fields[$name];
          }
          else {
            $imports[$name] = & $fields[$name];
          }
        }
      }

      foreach ($foreignDAOs as $foreignDAO) {
        $imports = array_merge($imports, $foreignDAO::import(TRUE));
      }

      Civi::$statics[__CLASS__][$cacheKey] = $imports;
    }
    return Civi::$statics[__CLASS__][$cacheKey];
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * Apply any third-party alterations to the `fields()`.
   *
   * @param string $className
   * @param string $event
   * @param mixed $values
   */
  public static function invoke($className, $event, &$values) {
    self::init();
    if (isset(self::$entityTypes[$className][$event])) {
      foreach (self::$entityTypes[$className][$event] as $filter) {
        $args = [$className, &$values];
        \Civi\Core\Resolver::singleton()->call($filter, $args);
      }
    }
  }

}
