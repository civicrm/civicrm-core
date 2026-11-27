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

namespace Civi\Schema;

/**
 * Gathers and stores schema metadata
 */
class EntityRepository {

  private static $entities;

  private static $tableIndex;

  private static $classIndex;

  private static $facades;

  private static $facadesByParent;

  /**
   * @internal
   * @return array
   */
  public static function getEntities(): array {
    self::loadAll();
    return self::$entities;
  }

  /**
   * Get facades: virtual entities which present a subset of another entity's records.
   *
   * Facades are not included in getEntities() because they share their parent's table.
   *
   * @internal
   * @return array
   */
  public static function getFacades(): array {
    self::loadAll();
    return self::$facades;
  }

  /**
   * @internal
   * @return array{name: string, table: string, class: string, module: string, getInfo: callable, getPaths: callable, getIndices: callable, getFields: callable, metaProvider: callable, storageProvider: callable}
   */
  public static function getEntity(string $entityName): ?array {
    self::loadAll();
    return self::$entities[$entityName] ?? self::$facades[$entityName] ?? NULL;
  }

  public static function entityExists(string $entityName): bool {
    self::loadAll();
    return isset(self::$entities[$entityName]) || isset(self::$facades[$entityName]);
  }

  /**
   * Get the name of the entity a facade is based on.
   *
   * @param string $entityName
   * @return string|null
   *   NULL if $entityName is not a facade.
   */
  public static function getFacadeParent(string $entityName): ?string {
    self::loadAll();
    return self::$facades[$entityName]['facade_of'] ?? NULL;
  }

  /**
   * Get the info declared by a facade itself, which overrides its parent's.
   *
   * @param string $entityName
   * @return array
   *   e.g. ['where' => ['contact_type' => 'Individual'], 'title' => 'Individual', ...]
   */
  public static function getFacadeInfo(string $entityName): array {
    self::loadAll();
    return isset(self::$facades[$entityName]) ? self::$facades[$entityName]['getFacadeInfo']() : [];
  }

  /**
   * Get the names of the facades of an entity.
   *
   * @param string $entityName
   *   e.g. 'Contact'
   * @return string[]
   *   e.g. ['Household', 'Individual', 'Organization']
   */
  public static function getFacadesOf(string $entityName): array {
    self::loadAll();
    return self::$facadesByParent[$entityName] ?? [];
  }

  /**
   * @internal
   * @return array
   */
  public static function getTableIndex(): array {
    self::loadAll();
    return self::$tableIndex;
  }

  public static function tableExists(string $tableName): bool {
    self::loadAll();
    return isset(self::$tableIndex[$tableName]);
  }

  /**
   * @internal
   * @return array
   */
  public static function getClassIndex(): array {
    self::loadAll();
    return self::$classIndex;
  }

  public static function flush(): void {
    self::$entities = NULL;
    self::$facades = NULL;
    self::$facadesByParent = NULL;
  }

  private static function loadAll(): void {
    if (self::$entities !== NULL) {
      return;
    }
    $entityTypes = self::loadCoreEntities();
    // Extensions should be online when we're called.
    \CRM_Utils_Hook::entityTypes($entityTypes);
    $facadeTypes = array_filter($entityTypes, fn($entityType) => !empty($entityType['facade_of']));
    $entityTypes = array_diff_key($entityTypes, $facadeTypes);
    $entities = array_column($entityTypes, NULL, 'name');
    // Load facades before setting self::$entities (which marks everything as loaded), in case of an invalid declaration
    self::$facades = self::loadFacades($facadeTypes, $entities);
    self::$facadesByParent = [];
    foreach (self::$facades as $facadeName => $facade) {
      self::$facadesByParent[$facade['facade_of']][] = $facadeName;
    }
    self::$tableIndex = array_column(array_filter($entityTypes, fn($entityType) => !empty($entityType['table'])), 'name', 'table');
    self::$classIndex = array_column(array_filter($entityTypes, fn($entityType) => !empty($entityType['class'])), 'name', 'class');
    self::$entities = $entities;
  }

  private static function loadCoreEntities(): array {
    static $cache;

    $entityTypes = [];
    $path = dirname(__DIR__, 2) . '/schema/*/*.entityType.php';
    $files = (array) glob($path);
    // awkwardly on some systems (docker on mac) globs can be unreliable
    // trying again can help with volume caching
    if (!$files) {
      $files = (array) glob($path);
    }
    foreach ($files as $file) {
      if (isset($cache[$file])) {
        $entity = $cache[$file];
      }
      else {
        $entity = include $file;
        $entity['module'] = 'civicrm';
        $cache[$file] = $entity;
      }
      $entityTypes[$entity['name']] = $entity;
    }
    return $entityTypes;
  }

  /**
   * Build facades from their declarations.
   *
   * A facade is declared in its own .entityType.php file, like an entity, with:
   *  - `facade_of`: the name of the entity whose records it presents.
   *  - `where`: [fieldName => value] conditions which restrict the facade to a subset of records.
   *    These become the APIv4 'where' info item, which is enforced when reading and writing.
   *  - `getInfo` (optional): info which overrides the parent's (title, description, icon, etc).
   *  - `alterFields` (optional): callback which receives the parent's fields and returns the facade's.
   *    A field set to FALSE is not part of the facade, e.g. `fn(array $fields) => ['description' => FALSE] + $fields`.
   *
   * Everything else (table, class, fields, paths, indices) comes from the parent entity.
   *
   * @param array $facadeTypes
   * @param array $entities
   * @return array
   * @throws \CRM_Core_Exception
   */
  private static function loadFacades(array $facadeTypes, array $entities): array {
    $facades = [];
    foreach ($facadeTypes as $facade) {
      if (empty($facade['where']) || !is_array($facade['where'])) {
        throw new \CRM_Core_Exception("Facade {$facade['name']} must declare 'where' conditions");
      }
      $parent = $entities[$facade['facade_of']] ?? NULL;
      // The parent may be unavailable, e.g. if it belongs to a disabled extension
      if (!$parent) {
        \Civi::log()->debug("Facade {$facade['name']} skipped because its parent entity {$facade['facade_of']} is not available");
        continue;
      }
      $getFacadeInfo = fn() => ['where' => $facade['where']] + (isset($facade['getInfo']) ? $facade['getInfo']() : []);
      $getParentInfo = $parent['getInfo'] ?? fn() => [];
      $facades[$facade['name']] = [
        'getInfo' => fn() => $getFacadeInfo() + $getParentInfo(),
        'getFacadeInfo' => $getFacadeInfo,
      ] + $facade + $parent;
    }
    return $facades;
  }

}
