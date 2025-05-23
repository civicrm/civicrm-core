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

namespace Civi\Api4\Service\Schema;

use Civi\Api4\Entity;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Service\AutoService;
use Civi\Core\CiviEventDispatcherInterface;
use Civi\Schema\EntityRepository;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

/**
 * @service schema_map_builder
 */
class SchemaMapBuilder extends AutoService {

  /**
   * @var \Civi\Core\CiviEventDispatcherInterface
   */
  protected $dispatcher;
  /**
   * @var array
   */
  protected $apiEntities;

  /**
   * @inject dispatcher
   * @param \Civi\Core\CiviEventDispatcherInterface $dispatcher
   */
  public function __construct(CiviEventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
    $this->apiEntities = Entity::get(FALSE)->addSelect('name')->execute()->column('name');
  }

  /**
   * @return SchemaMap
   */
  public function build(): SchemaMap {
    $map = new SchemaMap();
    $this->loadTables($map);

    $event = new SchemaMapBuildEvent($map);
    $this->dispatcher->dispatch('api.schema_map.build', $event);

    return $map;
  }

  /**
   * Add all tables and joins
   *
   * @param SchemaMap $map
   */
  private function loadTables(SchemaMap $map) {
    /** @var \CRM_Core_DAO $daoName */
    foreach (EntityRepository::getEntities() as $name => $data) {
      if (empty($data['table'])) {
        continue;
      }
      $table = new Table($data['table']);
      $entity = \Civi::entity($name);
      foreach ($entity->getFields() as $fieldName => $fieldData) {
        $this->addJoins($table, $fieldName, $fieldData);
      }
      $map->addTable($table);
      if (in_array($name, $this->apiEntities)) {
        $this->addCustomFields($map, $table, $name);
      }
    }
  }

  /**
   * @param Table $table
   * @param string $fieldName
   * @param array $data
   */
  private function addJoins(Table $table, $fieldName, array $data) {
    $fkEntity = $data['entity_reference']['entity'] ?? NULL;
    if (!$fkEntity) {
      return;
    }
    $tableName = AllCoreTables::getTableForEntityName($fkEntity);
    if ($tableName) {
      $fkKey = $data['entity_reference']['key'] ?? 'id';
      $joinable = new Joinable($tableName, $fkKey, $fieldName);
      $joinable->setJoinType($joinable::JOIN_TYPE_MANY_TO_ONE);
      $table->addTableLink($fieldName, $joinable);
    }
  }

  /**
   * @param \Civi\Api4\Service\Schema\SchemaMap $map
   * @param \Civi\Api4\Service\Schema\Table $baseTable
   * @param string $entityName
   */
  private function addCustomFields(SchemaMap $map, Table $baseTable, string $entityName) {
    $customInfo = \Civi\Api4\Utils\CoreUtil::getCustomGroupExtends($entityName);
    // Don't be silly
    if (!$customInfo) {
      return;
    }
    $filters = [
      'extends' => $customInfo['extends'],
      'is_active' => TRUE,
      'fields' => TRUE,
    ];
    foreach (\CRM_Core_BAO_CustomGroup::getAll($filters) as $customGroup) {
      $customTable = new Table($customGroup['table_name']);

      // Add entity_id join from multi-record custom group to the base entity
      if (!empty($customGroup['is_multiple'])) {
        $newJoin = new Joinable($baseTable->getName(), $customInfo['column'], 'entity_id');
        $customTable->addTableLink('entity_id', $newJoin);
        // Deprecated "contact" join name
        $oldJoin = new Joinable($baseTable->getName(), $customInfo['column'], AllCoreTables::convertEntityNameToLower($entityName));
        $oldJoin->setDeprecatedBy('entity_id');
        $customTable->addTableLink('entity_id', $oldJoin);
      }

      // Add joins for fields with foreign keys
      foreach ($customGroup['fields'] as $field) {
        $targetEntity = \CRM_Core_BAO_CustomField::getFkEntity($field);
        if ($targetEntity) {
          $targetTable = self::getTableName($targetEntity);
          if (!$targetTable) {
            // the target entity doesn't exist - skip to avoid crashing
            \Civi::log()->warning("Custom field {$field['name']} references a missing entity {$targetEntity} - you probably want to disable it");
            continue;
          }
          $joinable = new Joinable($targetTable, 'id', $field['name']);
          if ($field['serialize']) {
            $joinable->setSerialize((int) $field['serialize']);
          }
          $customTable->addTableLink($field['column_name'], $joinable);
        }
      }
      $map->addTable($customTable);

      // Add custom join
      $joinable = new CustomGroupJoinable($customGroup['table_name'], $customGroup['name'], $customGroup['is_multiple'], $entityName);
      $baseTable->addTableLink($customInfo['column'], $joinable);
    }
  }

  /**
   * @param string $entityName
   * @return string|null
   */
  private static function getTableName(string $entityName): ?string {
    if (CoreUtil::isContact($entityName)) {
      return 'civicrm_contact';
    }
    return AllCoreTables::getEntities()[$entityName]['table'] ?? NULL;
  }

}
