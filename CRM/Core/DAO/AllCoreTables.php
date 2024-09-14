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

use Civi\Schema\EntityRepository;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_DAO_AllCoreTables {

  /**
   * @deprecated in 5.73 will be removed in 5.90
   *
   * @param bool $fresh Deprecated parameter, use flush() to flush.
   */
  public static function init(bool $fresh = FALSE): void {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_AllCoreTables::flush()');
    if ($fresh) {
      EntityRepository::flush();
    }
  }

  /**
   * Flush class cache.
   */
  public static function flush(): void {
    EntityRepository::flush();
  }

  /**
   * @return array[]
   *   [EntityName => [table => table_name, class => CRM_DAO_ClassName]][]
   */
  public static function getEntities(): array {
    $allEntities = EntityRepository::getEntities();
    // Filter out entities without a table or class

    return array_filter($allEntities, fn($entity) => (!empty($entity['table']) && !empty($entity['class'])));
  }

  /**
   * @return string[]
   *   [table_name => EntityName][]
   */
  private static function getEntitiesByTable(): array {
    return EntityRepository::getTableIndex();
  }

  /**
   * This one is problematic because it's not strictly required to have one class
   * per table. It's possible for multiple tables to share a class.
   *
   * @return string[]
   *   [CRM_DAO_ClassName => EntityName]
   */
  private static function getEntitiesByClass(): array {
    return EntityRepository::getClassIndex();
  }

  /**
   * @deprecated in 5.72 will be removed in 5.90.
   */
  public static function get() {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_AllCoreTables::getEntities');
    $entities = [];
    foreach (self::getEntities() as $name => $entity) {
      $entities[$name] = $entity + [
        'name' => $name,
        'fields_callback' => $entity['fields_callback'] ?? NULL,
        'links_callback' => $entity['links_callback'] ?? NULL,
      ];
    }
    return $entities;
  }

  /**
   * Mapping from table-names to class-names.
   * @return string[]
   *   [table_name => CRM_DAO_ClassName]
   */
  public static function tables() {
    return array_column(self::getEntities(), 'class', 'table');
  }

  /**
   * Get the declared token classes.
   * @return string[]
   *   [table_name => token class]
   */
  public static function tokenClasses() {
    return array_column(self::getEntities(), 'token_class', 'name');
  }

  /**
   * @return array
   *   List of indices.
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    foreach (self::getEntities() as $entity) {
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
   * Mapping from entity-names to class-names.
   * @return string[]
   *   [EntityName => CRM_DAO_ClassName]
   */
  public static function daoToClass() {
    $entities = self::getEntities();
    return array_combine(array_keys($entities), array_column($entities, 'class'));
  }

  /**
   * @deprecated in 5.72 will be removed in 5.90
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
    return array_key_exists($tableName, self::getEntitiesByTable());
  }

  /**
   * Get the DAO for a BAO class.
   *
   * @param string $className
   *
   * @return string
   */
  public static function getCanonicalClassName($className) {
    while (!str_contains($className, '_DAO_')) {
      $parent = get_parent_class($className);
      if (!$parent || $parent === 'CRM_Core_DAO') {
        return $className;
      }
      $className = $parent;
    }
    return $className;
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
    return array_keys(self::getEntitiesByClass());
  }

  /**
   * Get a list of all extant BAO classes, keyed by entityName.
   *
   * @return string[]
   *   [EntityName => CRM_BAO_ClassName]
   */
  public static function getBaoClasses() {
    $r = [];
    foreach (self::getEntities() as $name => $entity) {
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
  public static function getClassForTable(string $tableName) {
    //CRM-19677: on multilingual setup, trim locale from $tableName to fetch class name
    if (CRM_Core_I18n::isMultilingual()) {
      global $dbLocale;
      $tableName = str_replace($dbLocale, '', $tableName);
    }
    $entityName = self::getEntitiesByTable()[$tableName] ?? '';
    return self::getEntities()[$entityName]['class'] ?? NULL;
  }

  /**
   * Given an entity name, determine the DAO class-name.
   *
   * @param string|null $entityName
   *   Ex: 'Contact'.
   * @return string|CRM_Core_DAO|NULL
   *   Ex: 'CRM_Contact_DAO_Contact'.
   */
  public static function getDAONameForEntity(?string $entityName) {
    return self::getEntities()[$entityName]['class'] ?? NULL;
  }

  /**
   * @deprecated in 5.72 will be removed in 5.96
   */
  public static function getFullName($entityName) {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_AllCoreTables::getDAONameForEntity');
    return self::getDAONameForEntity((string) $entityName);
  }

  /**
   * Given a DAO or BAO class-name, return the entity name.
   *
   * @param string|null $className
   *   Ex: 'CRM_Contact_DAO_Contact'.
   * @return string|NULL
   *   Ex: 'Contact'.
   */
  public static function getEntityNameForClass(?string $className): ?string {
    $className = self::getCanonicalClassName($className);
    return self::getEntitiesByClass()[$className] ?? NULL;
  }

  /**
   * @deprecated in 5.72 will be removed in 5.102
   */
  public static function getBriefName($className): ?string {
    return self::getEntityNameForClass((string) $className);
  }

  /**
   * @param string $className DAO or BAO name
   * @return string|FALSE SQL table name
   */
  public static function getTableForClass($className) {
    $entityName = self::getEntityNameForClass($className);
    return self::getEntities()[$entityName]['table'] ?? FALSE;
  }

  /**
   * Convert the entity name into a table name.
   *
   * @param string $entityName
   *   e.g. 'Activity'
   *
   * @return string
   *   e.g. 'civicrm_activity'
   */
  public static function getTableForEntityName($entityName): string {
    return self::getEntities()[$entityName]['table'];
  }

  /**
   * Convert table name to entity name.
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
    return self::getEntitiesByTable()[$tableName] ?? NULL;
  }

  /**
   * @deprecated in 5.54 will be removed in 5.85
   */
  public static function reinitializeCache(): void {
    CRM_Core_Error::deprecatedFunctionWarning('CRM_Core_DAO_AllCoreTables::flush');
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
   *   Will merge in exportable fields from other DAOs.
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

    // Merge in exportable fields from other DAOs
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
   *   Will merge in importable fields from other DAOs.   * @return array
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

    // Merge in importable fields from other DAOs
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
   * TODO: This function should probably take entityName as the key instead of className
   * because the latter is not always unique (e.g. virtual entities)
   *
   * @param string $className
   * @param string $event
   * @param mixed $values
   * @internal
   */
  public static function invoke($className, $event, &$values) {
    $entityName = self::getEntityNameForClass($className);
    $entityTypes = self::getEntities();
    if (isset($entityTypes[$entityName][$event])) {
      foreach ($entityTypes[$entityName][$event] as $filter) {
        $args = [$className, &$values];
        \Civi\Core\Resolver::singleton()->call($filter, $args);
      }
    }
  }

}
