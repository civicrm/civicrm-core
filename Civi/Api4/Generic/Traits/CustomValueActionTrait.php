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


namespace Civi\Api4\Generic\Traits;

use Civi\Api4\Utils\FormattingUtil;
use Civi\Api4\Utils\CoreUtil;

/**
 * Helper functions for working with custom values
 *
 * @package Civi\Api4\Generic
 */
trait CustomValueActionTrait {

  public function __construct($customGroup, $actionName) {
    $this->customGroup = $customGroup;
    parent::__construct('CustomValue', $actionName, ['id', 'entity_id']);
  }

  /**
   * Custom Group name if this is a CustomValue pseudo-entity.
   *
   * @var string
   */
  private $customGroup;

  /**
   * @inheritDoc
   */
  public function getEntityName() {
    return 'Custom_' . $this->getCustomGroup();
  }

  /**
   * @inheritDoc
   */
  protected function writeObjects(&$items) {
    $fields = $this->entityFields();
    foreach ($items as $idx => $item) {
      FormattingUtil::formatWriteParams($item, $fields);

      // Convert field names to custom_xx format
      foreach ($fields as $name => $field) {
        if (!empty($field['custom_field_id']) && isset($item[$name])) {
          $item['custom_' . $field['custom_field_id']] = $item[$name];
          unset($item[$name]);
        }
      }

      \CRM_Core_BAO_CustomValueTable::setValues($item);

      // Darn setValues function doesn't return an id.
      if (empty($item['id'])) {
        $tableName = CoreUtil::getTableName($this->getEntityName());
        $items[$idx]['id'] = (int) \CRM_Core_DAO::singleValueQuery('SELECT MAX(id) FROM ' . $tableName);
      }
    }
    FormattingUtil::formatOutputValues($items, $this->entityFields(), $this->getEntityName(), 'create');
    return $items;
  }

  /**
   * @inheritDoc
   */
  protected function deleteObjects($items) {
    $customTable = CoreUtil::getTableName($this->getEntityName());
    $ids = [];
    foreach ($items as $item) {
      \CRM_Utils_Hook::pre('delete', $this->getEntityName(), $item['id']);
      \CRM_Utils_SQL_Delete::from($customTable)
        ->where('id = #value')
        ->param('#value', $item['id'])
        ->execute();
      \CRM_Utils_Hook::post('delete', $this->getEntityName(), $item['id'], \CRM_Core_DAO::$_nullArray);
      $ids[] = $item['id'];
    }
    return $ids;
  }

  /**
   * @inheritDoc
   */
  protected function fillDefaults(&$params) {
    foreach ($this->entityFields() as $name => $field) {
      if (!isset($params[$name]) && isset($field['default_value'])) {
        $params[$name] = $field['default_value'];
      }
    }
  }

  /**
   * @return string
   */
  public function getCustomGroup() {
    return $this->customGroup;
  }

  /**
   * @return \CRM_Core_DAO|string
   */
  protected function getBaoName() {
    return \CRM_Core_BAO_CustomValue::class;
  }

}
