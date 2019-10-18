<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
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
  protected function writeObjects($items) {
    $result = [];
    $fields = $this->entityFields();
    foreach ($items as $item) {
      FormattingUtil::formatWriteParams($item, $this->getEntityName(), $fields);

      // Convert field names to custom_xx format
      foreach ($fields as $name => $field) {
        if (!empty($field['custom_field_id']) && isset($item[$name])) {
          $item['custom_' . $field['custom_field_id']] = $item[$name];
          unset($item[$name]);
        }
      }

      $result[] = \CRM_Core_BAO_CustomValueTable::setValues($item);
    }
    return $result;
  }

  /**
   * @inheritDoc
   */
  protected function deleteObjects($items) {
    $customTable = CoreUtil::getCustomTableByName($this->getCustomGroup());
    $ids = [];
    foreach ($items as $item) {
      \CRM_Utils_Hook::pre('delete', $this->getEntityName(), $item['id'], \CRM_Core_DAO::$_nullArray);
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

}
