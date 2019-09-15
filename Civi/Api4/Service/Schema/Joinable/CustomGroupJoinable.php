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
   * @param string $entity
   * @param string $columns
   */
  public function __construct($targetTable, $alias, $isMultiRecord, $entity, $columns) {
    $this->entity = $entity;
    $this->columns = $columns;
    parent::__construct($targetTable, 'entity_id', $alias);
    $this->joinType = $isMultiRecord ?
      self::JOIN_TYPE_ONE_TO_MANY : self::JOIN_TYPE_ONE_TO_ONE;
  }

  /**
   * @inheritDoc
   */
  public function getEntityFields() {
    if (!$this->entityFields) {
      $fields = CustomField::get()
        ->setCheckPermissions(FALSE)
        ->setSelect(['custom_group.name', 'custom_group_id', 'name', 'label', 'data_type', 'html_type', 'is_required', 'is_searchable', 'is_search_range', 'weight', 'is_active', 'is_view', 'option_group_id', 'default_value', 'date_format', 'time_format', 'start_date_years', 'end_date_years'])
        ->addWhere('custom_group.table_name', '=', $this->getTargetTable())
        ->execute();
      foreach ($fields as $field) {
        $this->entityFields[] = \Civi\Api4\Service\Spec\SpecFormatter::arrayToField($field, $this->getEntity());
      }
    }
    return $this->entityFields;
  }

  /**
   * @inheritDoc
   */
  public function getField($fieldName) {
    foreach ($this->getEntityFields() as $field) {
      $name = $field->getName();
      if ($name === $fieldName || strrpos($name, '.' . $fieldName) === strlen($name) - strlen($fieldName) - 1) {
        return $field;
      }
    }
    return NULL;
  }

  /**
   * @return string
   */
  public function getSqlColumn($fieldName) {
    return $this->columns[$fieldName];
  }

}
