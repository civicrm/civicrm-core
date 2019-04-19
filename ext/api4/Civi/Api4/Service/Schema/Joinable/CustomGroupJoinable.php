<?php

namespace Civi\Api4\Service\Schema\Joinable;

use Civi\Api4\CustomField;

class CustomGroupJoinable extends Joinable {

  /**
   * @var string
   */
  protected $joinSide = self::JOIN_SIDE_LEFT;

  /**
   * @var string
   *
   * Name of the custom field column.
   */
  protected $columns;

  /**
   * @param $targetTable
   * @param $alias
   * @param bool $isMultiRecord
   * @param null $entity
   */
  public function __construct($targetTable, $alias, $isMultiRecord, $entity, $columns) {
    $this->entity = $entity;
    $this->columns = $columns;
    parent::__construct($targetTable, 'entity_id', $alias);
    $this->joinType = $isMultiRecord ?
      self::JOIN_TYPE_ONE_TO_MANY : self::JOIN_TYPE_ONE_TO_ONE;
  }

  public function getEntityFields() {
    if (!$this->entityFields) {
      $fields = CustomField::get()
        ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value'])
        ->addWhere('custom_group.table_name', '=', $this->getTargetTable())
        ->execute();
      foreach ($fields as $field) {
        $this->entityFields[] = \Civi\Api4\Service\Spec\SpecFormatter::arrayToField($field, $this->getEntity());
      }
    }
    return $this->entityFields;
  }

  /**
   * @return string
   */
  public function getSqlColumn($fieldName) {
    return $this->columns[$fieldName];
  }

}
