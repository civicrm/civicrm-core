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


namespace Civi\Api4\Service\Schema;

use Civi\Api4\Entity;
use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use CRM_Core_DAO_AllCoreTables as AllCoreTables;

class SchemaMapBuilder {
  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;
  /**
   * @var array
   */
  protected $apiEntities;

  /**
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
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
    foreach (AllCoreTables::get() as $daoName => $data) {
      $table = new Table($data['table']);
      foreach ($daoName::fields() as $fieldData) {
        $this->addJoins($table, $fieldData['name'], $fieldData);
      }
      $map->addTable($table);
      if (in_array($data['name'], $this->apiEntities)) {
        $this->addCustomFields($map, $table, $data['name']);
      }
    }

    $this->addBackReferences($map);
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
      $alias = str_replace('_id', '', $field);
      $joinable = new Joinable($tableName, $fkKey, $alias);
      $joinable->setJoinType($joinable::JOIN_TYPE_MANY_TO_ONE);
      $table->addTableLink($field, $joinable);
    }
  }

  /**
   * Loop through existing links and provide link from the other side
   *
   * @param SchemaMap $map
   */
  private function addBackReferences(SchemaMap $map) {
    foreach ($map->getTables() as $table) {
      foreach ($table->getTableLinks() as $link) {
        $target = $map->getTableByName($link->getTargetTable());
        $tableName = $link->getBaseTable();
        // Exclude custom field tables
        if (strpos($link->getTargetTable(), 'civicrm_value_') !== 0 && strpos($link->getBaseTable(), 'civicrm_value_') !== 0) {
          $plural = str_replace('civicrm_', '', $this->getPlural($tableName));
          $joinable = new Joinable($tableName, $link->getBaseColumn(), $plural);
          $joinable->setJoinType($joinable::JOIN_TYPE_ONE_TO_MANY);
          $target->addTableLink($link->getTargetColumn(), $joinable);
        }
      }
    }
  }

  /**
   * Simple implementation of pluralization.
   * Could be replaced with symfony/inflector
   *
   * @param string $singular
   *
   * @return string
   */
  private function getPlural($singular) {
    $last_letter = substr($singular, -1);
    switch ($last_letter) {
      case 'y':
        return substr($singular, 0, -1) . 'ies';

      case 's':
        return $singular . 'es';

      default:
        return $singular . 's';
    }
  }

  /**
   * @param \Civi\Api4\Service\Schema\SchemaMap $map
   * @param \Civi\Api4\Service\Schema\Table $baseTable
   * @param string $entity
   */
  private function addCustomFields(SchemaMap $map, Table $baseTable, $entity) {
    // Don't be silly
    if (!array_key_exists($entity, \CRM_Core_SelectValues::customGroupExtends())) {
      return;
    }
    $queryEntity = (array) $entity;
    if ($entity == 'Contact') {
      $queryEntity = ['Contact', 'Individual', 'Organization', 'Household'];
    }
    $fieldData = \CRM_Utils_SQL_Select::from('civicrm_custom_field f')
      ->join('custom_group', 'INNER JOIN civicrm_custom_group g ON g.id = f.custom_group_id')
      ->select(['g.name as custom_group_name', 'g.table_name', 'g.is_multiple', 'f.name', 'label', 'column_name', 'option_group_id'])
      ->where('g.extends IN (@entity)', ['@entity' => $queryEntity])
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
        $joinable = new Joinable($baseTable->getName(), 'id', AllCoreTables::convertEntityNameToLower($entity));
        $customTable->addTableLink('entity_id', $joinable);
      }
    }

    foreach ($links as $alias => $link) {
      $joinable = new CustomGroupJoinable($link['tableName'], $alias, $link['isMultiple'], $link['columns']);
      $baseTable->addTableLink('id', $joinable);
    }
  }

}
