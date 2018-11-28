<?php

namespace Civi\Api4\Service\Schema;

use Civi\Api4\Entity;
use Civi\Api4\Event\Events;
use Civi\Api4\Event\SchemaMapBuildEvent;
use Civi\Api4\Service\Schema\Joinable\CustomGroupJoinable;
use Civi\Api4\Service\Schema\Joinable\Joinable;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Civi\Api4\Service\Schema\Joinable\OptionValueJoinable;
use CRM_Core_DAO_AllCoreTables as TableHelper;
use CRM_Utils_Array as UtilsArray;

class SchemaMapBuilder {
  /**
   * @var EventDispatcherInterface
   */
  protected $dispatcher;
  /**
   * @var array
   */
  protected $apiEntities;

  /**
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct(EventDispatcherInterface $dispatcher) {
    $this->dispatcher = $dispatcher;
    $this->apiEntities = array_keys((array) Entity::get()->addSelect('name')->execute()->indexBy('name'));
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
    foreach (TableHelper::get() as $daoName => $data) {
      $table = new Table($data['table']);
      foreach ($daoName::fields() as $field => $fieldData) {
        $this->addJoins($table, $field, $fieldData);
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
    $fkClass = UtilsArray::value('FKClassName', $data);

    // can there be multiple methods e.g. pseudoconstant and fkclass
    if ($fkClass) {
      $tableName = TableHelper::getTableForClass($fkClass);
      $fkKey = UtilsArray::value('FKKeyColumn', $data, 'id');
      $alias = str_replace('_id', '', $field);
      $joinable = new Joinable($tableName, $fkKey, $alias);
      $joinable->setJoinType($joinable::JOIN_TYPE_MANY_TO_ONE);
      $table->addTableLink($field, $joinable);
    }
    elseif (UtilsArray::value('pseudoconstant', $data)) {
      $this->addPseudoConstantJoin($table, $field, $data);
    }
  }

  /**
   * @param Table $table
   * @param string $field
   * @param array $data
   */
  private function addPseudoConstantJoin(Table $table, $field, array $data) {
    $pseudoConstant = UtilsArray::value('pseudoconstant', $data);
    $tableName = UtilsArray::value('table', $pseudoConstant);
    $optionGroupName = UtilsArray::value('optionGroupName', $pseudoConstant);
    $keyColumn = UtilsArray::value('keyColumn', $pseudoConstant, 'id');

    if ($tableName) {
      $alias = str_replace('civicrm_', '', $tableName);
      $joinable = new Joinable($tableName, $keyColumn, $alias);
      $condition = UtilsArray::value('condition', $pseudoConstant);
      if ($condition) {
        $joinable->addCondition($condition);
      }
      $table->addTableLink($field, $joinable);
    }
    elseif ($optionGroupName) {
      $keyColumn = UtilsArray::value('keyColumn', $pseudoConstant, 'value');
      $joinable = new OptionValueJoinable($optionGroupName, NULL, $keyColumn);

      if (!empty($data['serialize'])) {
        $joinable->setJoinType($joinable::JOIN_TYPE_ONE_TO_MANY);
      }

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
        // there are too many possible joins from option value so skip
        if ($link instanceof OptionValueJoinable) {
          continue;
        }

        $target = $map->getTableByName($link->getTargetTable());
        $tableName = $link->getBaseTable();
        $plural = str_replace('civicrm_', '', $this->getPlural($tableName));
        $joinable = new Joinable($tableName, $link->getBaseColumn(), $plural);
        $joinable->setJoinType($joinable::JOIN_TYPE_ONE_TO_MANY);
        $target->addTableLink($link->getTargetColumn(), $joinable);
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
      ->execute();

    while ($fieldData->fetch()) {
      $tableName = $fieldData->table_name;

      $customTable = $map->getTableByName($tableName);
      if (!$customTable) {
        $customTable = new Table($tableName);
      }

      if (!empty($fieldData->option_group_id)) {
        $optionValueJoinable = new OptionValueJoinable($fieldData->option_group_id, $fieldData->label);
        $customTable->addTableLink($fieldData->column_name, $optionValueJoinable);
      }

      $map->addTable($customTable);
      $alias = $fieldData->custom_group_name;
      $isMultiple = !empty($fieldData->is_multiple);
      $joinable = new CustomGroupJoinable($tableName, $alias, $isMultiple, $entity, $fieldData->column_name);
      $baseTable->addTableLink('id', $joinable);
    }
  }

}
