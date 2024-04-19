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
 * Gathers and stores schema meatadata
 */
class EntityRepository {

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
  }

  private static function loadAll(): void {
    if (self::$entities) {
      return;
    }
    // Temporary until the data file is moved
    $allCoreTables = new \ReflectionClass('CRM_Core_DAO_AllCoreTables');
    $dataFile = preg_replace('/\.php$/', '.data.php', $allCoreTables->getFileName());
    $entityTypes = require $dataFile;
    \CRM_Utils_Hook::entityTypes($entityTypes);
    self::$entities = array_column($entityTypes, NULL, 'name');
    self::$tableIndex = array_column($entityTypes, 'name', 'table');
    self::$classIndex = array_column($entityTypes, 'name', 'class');
  }

}
