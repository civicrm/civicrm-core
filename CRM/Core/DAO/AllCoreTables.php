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

  /**
   * @var array
   */
  private static $entityTypes = NULL;

  /**
   * Initialise.
   *
   * @param bool $fresh Deprecated parameter, use flush() to flush.
   */
  public static function init(bool $fresh = FALSE): void {
    if (!empty(Civi::$statics[__CLASS__]['initialised']) && !$fresh) {
      return;
    }
    if ($fresh) {
      CRM_Core_Error::deprecatedWarning('Use CRM_Core_DAO_AllCoreTables::flush()');
    }

    Civi::$statics[__CLASS__] = [];

    $file = preg_replace('/\.php$/', '.data.php', __FILE__);
    $entityTypes = require $file;
    CRM_Utils_Hook::entityTypes($entityTypes);

    self::$entityTypes = [];
    foreach ($entityTypes as $entityType) {
      self::registerEntityType(
        $entityType['name'],
        $entityType['class'],
        $entityType['table'],
        $entityType['fields_callback'] ?? NULL,
        $entityType['links_callback'] ?? NULL
      );
    }

    Civi::$statics[__CLASS__]['initialised'] = TRUE;
  }

  /**
   * Flush class cache.
   */
  public static function flush(): void {
    Civi::$statics[__CLASS__]['initialised'] = FALSE;
  }

  /**
   * (Quasi-Private) Do not call externally (except for unit-testing)
   *
   * @param string $briefName
   * @param string $className
   * @param string $tableName
   * @param string $fields_callback
   * @param string $links_callback
   */
  public static function registerEntityType($briefName, $className, $tableName, $fields_callback = NULL, $links_callback = NULL) {
    self::$entityTypes[$briefName] = [
      'name' => $briefName,
      'class' => $className,
      'table' => $tableName,
      'fields_callback' => $fields_callback,
      'links_callback' => $links_callback,
    ];
  }

  /**
   * @return array
   *   Ex: $result['Contact']['table'] == 'civicrm_contact';
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
    return array_column(self::get(), 'class', 'table');
  }

  /**
   * @return array
   *   List of indices.
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    foreach (self::get() as $entity) {
      if (is_callable([$entity['class'], 'indices'])) {
        $indices[$entity['class']::getTableName()] = $entity['class']::indices($localize);
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
    return array_column(self::get(), 'class', 'name');
  }

  /**
   * @deprecated in 5.72 will be removed in 5.90
   * @return array
   *   Mapping from table-names to class-names.
   *   Ex: $result['civicrm_contact'] == 'CRM_Contact_DAO_Contact'.
   */
  public static function getCoreTables() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_AllCoreTables::tables');
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
   * @return string
   */
  public static function getCanonicalClassName($baoName) {
    return str_replace('_BAO_', '_DAO_', ($baoName ?? ''));
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
    return $daoName === $baoName || class_exists($baoName) ? $baoName : $daoName;
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
      'im' => 'Im',
      'pcp' => 'Pcp',
    ];
    if ($legacyV3 && isset($map[strtolower($name)])) {
      return $map[strtolower($name)];
    }

    $fragments = explode('_', $name);
    foreach ($fragments as & $fragment) {
      $fragment = ucfirst($fragment);
      // Special case: UFGroup, UFJoin, UFMatch, UFField (if passed in without underscores)
      if (strpos($fragment, 'Uf') === 0 && strlen($name) > 2) {
        $fragment = 'UF' . ucfirst(substr($fragment, 2));
      }
    }
    // Exceptions to CamelCase: UFGroup, UFJoin, UFMatch, UFField, ACL, IM, PCP
    $exceptions = ['Uf', 'Acl', 'Im', 'Pcp'];
    if (in_array($fragments[0], $exceptions)) {
      $fragments[0] = strtoupper($fragments[0]);
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
    return array_column(self::get(), 'class');
  }

  /**
   * Get a list of all extant BAO classes.
   *
   * @return array
   *   Ex: ['Contact' => 'CRM_Contact_BAO_Contact']
   */
  public static function getBaoClasses() {
    $r = [];
    foreach (self::get() as $name => $entity) {
      $baoClass = str_replace('_DAO_', '_BAO_', $entity['class']);
      if (class_exists($baoClass)) {
        $r[$name] = $baoClass;
      }
    }
    return $r;
  }

  /**
   * Get the classname for the table.
   *
   * @param string $tableName
   * @return string|CRM_Core_DAO|NULL
   */
  public static function getClassForTable(string $tableName): ?string {
    //CRM-19677: on multilingual setup, trim locale from $tableName to fetch class name
    if (CRM_Core_I18n::isMultilingual()) {
      global $dbLocale;
      $tableName = str_replace($dbLocale, '', $tableName);
    }
    foreach (self::get() as $entity) {
      if ($entity['table'] === $tableName) {
        return $entity['class'];
      }
    }
    return NULL;
  }

  /**
   * Given a brief-name, determine the full class-name.
   *
   * @param string $briefName
   *   Ex: 'Contact'.
   * @return string|CRM_Core_DAO|NULL
   *   Ex: 'CRM_Contact_DAO_Contact'.
   */
  public static function getFullName($briefName) {
    return self::get()[$briefName]['class'] ?? NULL;
  }

  /**
   * Given a full class-name, determine the brief-name.
   *
   * @param string $className
   *   Ex: 'CRM_Contact_DAO_Contact'.
   * @return string|NULL
   *   Ex: 'Contact'.
   */
  public static function getBriefName($className): ?string {
    $className = self::getCanonicalClassName($className);
    foreach (self::get() as $entity) {
      if ($entity['class'] === $className) {
        return $entity['name'];
      }
    }
    return NULL;
  }

  /**
   * @param string $className DAO or BAO name
   * @return string|FALSE SQL table name
   */
  public static function getTableForClass($className) {
    $className = self::getCanonicalClassName($className);
    foreach (self::get() as $entity) {
      if ($entity['class'] === $className) {
        return $entity['table'];
      }
    }
    return FALSE;
  }

  /**
   * Convert the entity name into a table name.
   *
   * @param string $briefName
   *
   * @return string
   */
  public static function getTableForEntityName($briefName): string {
    return self::get()[$briefName]['table'];
  }

  /**
   * Convert table name to brief entity name.
   *
   * @param string $tableName
   *
   * @return FALSE|string
   */
  public static function getEntityNameForTable(string $tableName) {
    // CRM-19677: on multilingual setup, trim locale from $tableName to fetch class name
    if (CRM_Core_I18n::isMultilingual()) {
      global $dbLocale;
      $tableName = str_replace($dbLocale, '', $tableName);
    }
    foreach (self::get() as $entity) {
      if ($entity['table'] === $tableName) {
        return $entity['name'];
      }
    }
    return NULL;
  }

  /**
   * Reinitialise cache.
   *
   * @deprecated
   */
  public static function reinitializeCache(): void {
    self::flush();
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * @param string|CRM_Core_DAO $dao
   *   Ex: 'CRM_Core_DAO_Address'.
   * @param string $labelName
   *   Ex: 'address'.
   * @param bool $prefix
   * @param array $foreignDAOs
   *   Historically used for... something? Currently never set by any core BAO.
   * @return array
   * @internal
   */
  public static function getExports($dao, $labelName, $prefix, $foreignDAOs = []) {
    $exports = [];

    foreach ($dao::fields() as $name => $field) {
      if (!empty($field['export'])) {
        if ($prefix) {
          $exports[$labelName] = $field;
        }
        else {
          $exports[$name] = $field;
        }
      }
    }

    // TODO: Remove this bit; no core DAO actually uses it
    foreach ($foreignDAOs as $foreignDAO) {
      $exports = array_merge($exports, $foreignDAO::export(TRUE));
    }

    return $exports;
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * @param string|CRM_Core_DAO $dao
   *   Ex: 'CRM_Core_DAO_Address'.
   * @param string $labelName
   *   Ex: 'address'.
   * @param bool $prefix
   * @param array $foreignDAOs
   *   Historically used for... something? Currently never set by any core BAO.
   * @return array
   * @internal
   */
  public static function getImports($dao, $labelName, $prefix, $foreignDAOs = []): array {
    $imports = [];

    foreach ($dao::fields() as $name => $field) {
      if (!empty($field['import'])) {
        if ($prefix) {
          $imports[$labelName] = $field;
        }
        else {
          $imports[$name] = $field;
        }
      }
    }

    // TODO: Remove this bit; no core DAO actually uses it
    foreach ($foreignDAOs as $foreignDAO) {
      $imports = array_merge($imports, $foreignDAO::import(TRUE));
    }

    return $imports;
  }

  /**
   * (Quasi-Private) Do not call externally. For use by DAOs.
   *
   * Apply any third-party alterations to the `fields()`.
   *
   * TODO: This function should probably take briefName as the key instead of className
   * because the latter is not always unique (e.g. virtual entities)
   *
   * @param string $className
   * @param string $event
   * @param mixed $values
   */
  public static function invoke($className, $event, &$values) {
    $briefName = self::getBriefName($className);
    if (isset(self::$entityTypes[$briefName][$event])) {
      foreach (self::$entityTypes[$briefName][$event] as $filter) {
        $args = [$className, &$values];
        \Civi\Core\Resolver::singleton()->call($filter, $args);
      }
    }
  }

}
