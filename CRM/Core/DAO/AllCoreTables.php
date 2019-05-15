<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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
        isset($entityType['fields_callback']) ? $entityType['fields_callback'] : NULL,
        isset($entityType['links_callback']) ? $entityType['links_callback'] : NULL
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
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);
    $locales = explode(CRM_Core_DAO::VALUE_SEPARATOR, $domain->locales);
    if (CRM_Utils_System::isNull($locales)) {
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
   * Get the DAO for the class.
   *
   * @param string $className
   *
   * @return string
   */
  public static function getCanonicalClassName($className) {
    return str_replace('_BAO_', '_DAO_', $className);
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
   * @return string
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
   * @return string|NULL
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
    return CRM_Utils_Array::value($className, array_flip(self::daoToClass()));
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
        if (CRM_Utils_Array::value('export', $field)) {
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
        if (CRM_Utils_Array::value('import', $field)) {
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
