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

use Civi\Api4\Service\Spec\SpecFormatter;
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
  public function getEntityFields(): array {
    $entityFields = [];
    $baseEntity = CoreUtil::getApiNameFromTableName($this->getBaseTable());
    $filters = [
      'is_active' => TRUE,
      'table_name' => $this->getTargetTable(),
    ];
    foreach (\CRM_Core_BAO_CustomGroup::getAll($filters) as $customGroup) {
      foreach ($customGroup['fields'] as $fieldArray) {
        $entityFields[] = SpecFormatter::arrayToField($fieldArray, $baseEntity, $customGroup);
      }
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
