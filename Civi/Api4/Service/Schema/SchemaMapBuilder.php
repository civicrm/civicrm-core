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
use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
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
    $this->apiEntities = array_keys((array) Entity::get(FALSE)->addSelect('name')->execute()->indexBy('name'));
  }

  /**
   * @return SchemaMap
   */
  public function build() {
    $map = new SchemaMap();
    $this->loadTables($map);

    $event = new SchemaMapBuildEvent($map);
    $this->dispatcher->dispatch(Events::SCHEMA_MAP_BUILD, $event);

    return $map;
  }

  /**
   * Add all tables and joins
   *
   * @param SchemaMap $map
   */
  private function loadTables(SchemaMap $map) {
    /** @var \CRM_Core_DAO $daoName */
    foreach (AllCoreTables::get() as $data) {
      $table = new Table($data['table']);
      foreach ($data['class']::fields() as $fieldData) {
        $this->addJoins($table, $fieldData['name'], $fieldData);
      }
      $map->addTable($table);
      if (in_array($data['name'], $this->apiEntities)) {
        $this->addCustomFields($map, $table, $data['name']);
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
    $select = ['f.name', 'f.data_type', 'f.label', 'f.column_name', 'f.option_group_id', 'f.serialize', 'f.fk_entity'];
    // Prevent errors during upgrade by only selecting fields supported by the current version
    $supportedFields = \CRM_Utils_Array::prefixKeys(\CRM_Core_BAO_CustomField::getSupportedFields(), 'f.');
    $select = array_intersect($select, array_keys($supportedFields));
    // Also select fields from the custom_group table (these fields are so old we don't have to worry about upgrade issues)
    $select = array_merge(['g.name as custom_group_name', 'g.table_name', 'g.is_multiple'], $select);
    $fieldData = \CRM_Utils_SQL_Select::from('civicrm_custom_field f')
      ->join('custom_group', 'INNER JOIN civicrm_custom_group g ON g.id = f.custom_group_id')
      ->select($select)
      ->where('g.extends IN (@entity)', ['@entity' => $customInfo['extends']])
      ->where('g.is_active')
      ->where('f.is_active')
      ->execute();

    $links = [];

    while ($fieldData->fetch()) {
      $tableName = $fieldData->table_name;

      $customTable = $map->getTableByName($tableName);
      if (!$customTable) {
        $customTable = new Table($tableName);
      }

      $map->addTable($customTable);

      $alias = $fieldData->custom_group_name;
      $links[$alias]['tableName'] = $tableName;
      $links[$alias]['isMultiple'] = !empty($fieldData->is_multiple);
      $links[$alias]['columns'][$fieldData->name] = $fieldData->column_name;

      // Add backreference
      if (!empty($fieldData->is_multiple)) {
        $joinable = new Joinable($baseTable->getName(), $customInfo['column'], AllCoreTables::convertEntityNameToLower($entityName));
        $customTable->addTableLink('entity_id', $joinable);
      }

      if ($fieldData->data_type === 'EntityReference' && isset($fieldData->fk_entity)) {
        $targetTable = AllCoreTables::getTableForEntityName($fieldData->fk_entity);
        $joinable = new Joinable($targetTable, 'id', $fieldData->name);
        $customTable->addTableLink($fieldData->column_name, $joinable);
      }

      if ($fieldData->data_type === 'ContactReference') {
        $joinable = new Joinable('civicrm_contact', 'id', $fieldData->name);
        if ($fieldData->serialize) {
          $joinable->setSerialize((int) $fieldData->serialize);
        }
        $customTable->addTableLink($fieldData->column_name, $joinable);
      }
    }

    foreach ($links as $alias => $link) {
      $joinable = new CustomGroupJoinable($link['tableName'], $alias, $link['isMultiple'], $link['columns']);
      $baseTable->addTableLink($customInfo['column'], $joinable);
    }
  }

}
