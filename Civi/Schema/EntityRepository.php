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

  private static $isBooted = FALSE;

  private static $entities;

  private static $tableIndex;

  private static $classIndex;

  /**
   * @internal
   * @return array
   */
  public static function getEntities(): array {
    self::loadAll();
    return self::$entities;
  }

  /**
   * @internal
   * @return array{name: string, table: string, class: string, module: string, getInfo: callable, getPaths: callable, getIndices: callable, getFields: callable, metaProvider: callable, storageProvider: callable}
   */
  public static function getEntity(string $entityName): ?array {
    self::loadAll();
    return self::$entities[$entityName] ?? NULL;
  }

  /**
   * @internal
   * @return array
   */
  public static function getTableIndex(): array {
    self::loadAll();
    return self::$tableIndex;
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
    self::$isBooted = FALSE;
  }

  private static function loadAll(): void {
    if (self::$isBooted) {
      return;
    }
    // In pre-boot conditions cannot call hooks so only load core entities
    $containerBooted = \Civi\Core\Container::isContainerBooted();
    if (!$containerBooted && self::$entities) {
      return;
    }
    $entityTypes = self::loadCoreEntities();
    // Cannot call hook prior to container boot. Only core entities can load.
    if ($containerBooted) {
      \CRM_Utils_Hook::entityTypes($entityTypes);
      self::$isBooted = TRUE;
    }
    self::$entities = array_column($entityTypes, NULL, 'name');
    self::$tableIndex = array_column($entityTypes, 'name', 'table');
    self::$classIndex = array_column($entityTypes, 'name', 'class');
  }

  private static function loadCoreEntities(): array {
    $entityTypes = [];
    $path = dirname(__DIR__, 2) . '/schema/*/*.entityType.php';
    $files = (array) glob($path);
    foreach ($files as $file) {
      $entity = include $file;
      $entity['module'] = 'civicrm';
      $entityTypes[$entity['name']] = $entity;
    }
    return $entityTypes;
  }

}
