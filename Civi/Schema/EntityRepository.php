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

  /**
   * @internal
   * @return array
   */
  public static function getEntities(): array {
    return self::getEntityMetadata()['entities'];
  }

  /**
   * @internal
   * @return array{name: string, table: string, class: string, module: string, getInfo: callable, getPaths: callable, getIndices: callable, getFields: callable, metaProvider: callable, storageProvider: callable}
   */
  public static function getEntity(string $entityName): ?array {
    return self::getEntities()[$entityName] ?? NULL;
  }

  public static function entityExists(string $entityName): bool {
    $entities = self::getEntities();
    return isset($entities[$entityName]);
  }

  /**
   * @internal
   * @return array
   */
  public static function getTableIndex(): array {
    return self::getEntityMetadata()['tables'];
  }

  public static function tableExists(string $tableName): bool {
    $tableIndex = self::getTableIndex();
    return isset($tableIndex[$tableName]);
  }

  /**
   * @internal
   * @return array
   */
  public static function getClassIndex(): array {
    return self::getEntityMetadata()['classes'];
  }

  public static function flush(): void {
    \Civi::cache('metadata')->delete('schema.entities');
  }

  private static function getEntityMetadata(): array {
    // Cannot use cache in pre-boot phase.
    if (!\Civi\Core\Container::isContainerBooted()) {
      return self::loadEntityTypes();
    }
    $entityMeta = \Civi::cache('metadata')->get('schema.entities');
    if (!$entityMeta) {
      $entityMeta = self::loadEntityTypes();
      \Civi::cache('metadata')->set('schema.entities', $entityMeta);
    }
    return $entityMeta;
  }

  private static function loadEntityTypes(): array {
    $entities = self::loadCoreEntities();
    \CRM_Utils_Hook::entityTypes($entities);
    return [
      'entities' => array_column($entities, NULL, 'name'),
      'tables' => array_column(array_filter($entities, fn($entityType) => !empty($entityType['table'])), 'name', 'table'),
      'classes' => array_column(array_filter($entities, fn($entityType) => !empty($entityType['class'])), 'name', 'class'),
    ];
  }

  private static function loadCoreEntities(): array {
    static $cache;

    $entityTypes = [];
    $path = dirname(__DIR__, 2) . '/schema/*/*.entityType.php';
    $files = (array) glob($path);
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

}
