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
    foreach (AllCoreTables::getEntities() as $name => $data) {
      $table = new Table($data['table']);
      foreach ($data['class']::fields() as $fieldData) {
        $this->addJoins($table, $fieldData['name'], $fieldData);
      }
      $map->addTable($table);
      if (in_array($name, $this->apiEntities)) {
        $this->addCustomFields($map, $table, $name);
      }
    }
  }

  /**
   * @param Table $table
   * @param string $field
   * @param array $data
   */
  private function addJoins(Table $table, $field, array $data) {
    $fkClass = $data['FKClassName'] ?? NULL;

    // can there be multiple methods e.g. pseudoconstant and fkclass
    if ($fkClass) {
      $tableName = AllCoreTables::getTableForClass($fkClass);
      $fkKey = $data['FKKeyColumn'] ?? 'id';
      $joinable = new Joinable($tableName, $fkKey, $field);
      $joinable->setJoinType($joinable::JOIN_TYPE_MANY_TO_ONE);
      $table->addTableLink($field, $joinable);
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

      // Add joins for entityReference fields
      foreach ($customGroup['fields'] as $field) {
        if ($field['data_type'] === 'EntityReference' && isset($field['fk_entity'])) {
          $targetTable = self::getTableName($field['fk_entity']);
          $joinable = new Joinable($targetTable, 'id', $field['name']);
          $customTable->addTableLink($field['column_name'], $joinable);
        }

        if ($field['data_type'] === 'ContactReference') {
          $joinable = new Joinable('civicrm_contact', 'id', $field['name']);
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
   * @return string
   */
  private static function getTableName(string $entityName) {
    if (CoreUtil::isContact($entityName)) {
      return 'civicrm_contact';
    }
    return AllCoreTables::getTableForEntityName($entityName);
  }

}
