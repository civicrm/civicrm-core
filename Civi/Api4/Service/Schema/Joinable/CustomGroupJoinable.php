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
    if (!$this->entityFields) {
      $fields = CustomField::get(FALSE)
        ->setSelect(['custom_group.name', 'custom_group.extends', 'custom_group.table_name', '*'])
        ->addWhere('custom_group.table_name', '=', $this->getTargetTable())
        ->execute();
      foreach ($fields as $field) {
        $this->entityFields[] = \Civi\Api4\Service\Spec\SpecFormatter::arrayToField($field, $this->getEntityFromExtends($field['custom_group.extends']));
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
    if (strpos($fieldName, '.') !== FALSE) {
      $fieldName = substr($fieldName, 1 + strrpos($fieldName, '.'));
    }
    return $this->columns[$fieldName];
  }

  /**
   * Translate custom_group.extends to entity name.
   *
   * Custom_group.extends pretty much maps 1-1 with entity names, except for a couple oddballs.
   * @see \CRM_Core_SelectValues::customGroupExtends
   *
   * @param $extends
   * @return string
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function getEntityFromExtends($extends) {
    if (strpos($extends, 'Participant') === 0) {
      return 'Participant';
    }
    if ($extends === 'Contact' || in_array($extends, \CRM_Contact_BAO_ContactType::basicTypes(TRUE))) {
      return 'Contact';
    }
    return $extends;
  }

}
