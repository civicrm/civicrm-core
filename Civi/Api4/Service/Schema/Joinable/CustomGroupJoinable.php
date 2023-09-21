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

namespace Civi\Api4\Service\Schema\Joinable;

use Civi\Api4\CustomField;
use Civi\Api4\Utils\CoreUtil;

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
   * @param string $columns
   */
  public function __construct($targetTable, $alias, $isMultiRecord, $columns) {
    $this->columns = $columns;
    parent::__construct($targetTable, 'entity_id', $alias);
    $this->joinType = $isMultiRecord ?
      self::JOIN_TYPE_ONE_TO_MANY : self::JOIN_TYPE_ONE_TO_ONE;
    // Only multi-record groups are considered an api "entity"
    if (!$isMultiRecord) {
      $this->entity = NULL;
    }
  }

  /**
   * @inheritDoc
   */
  public function getEntityFields() {
    $cacheKey = 'APIv4_CustomGroupJoinable-' . $this->getTargetTable();
    $entityFields = (array) \Civi::cache('metadata')->get($cacheKey);
    if (!$entityFields) {
      $baseEntity = CoreUtil::getApiNameFromTableName($this->getBaseTable());
      $fields = CustomField::get(FALSE)
        ->setSelect(['custom_group_id.name', 'custom_group_id.extends', 'custom_group_id.table_name', 'custom_group_id.title', '*'])
        ->addWhere('custom_group_id.table_name', '=', $this->getTargetTable())
        ->execute();
      foreach ($fields as $field) {
        $entityFields[] = \Civi\Api4\Service\Spec\SpecFormatter::arrayToField($field, $baseEntity);
      }
      \Civi::cache('metadata')->set($cacheKey, $entityFields);
    }
    return $entityFields;
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
    if (strpos($fieldName, '.') !== FALSE) {
      $fieldName = substr($fieldName, 1 + strrpos($fieldName, '.'));
    }
    return $this->columns[$fieldName];
  }

}
