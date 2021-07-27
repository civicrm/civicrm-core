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

namespace Civi\Api4;

/**
 * Search for example data.
 *
 * @searchable none
 * @since 5.43
 * @package Civi\Api4
 */
class WorkflowMessageExample extends \Civi\Api4\Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\AbstractGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\WorkflowMessageExample\Get(__CLASS__, __FILE__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function () {
      return [
        [
          'name' => 'name',
          'title' => 'Example Name',
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Example Title',
          'data_type' => 'String',
        ],
        [
          'name' => 'workflow',
          'title' => 'Workflow Name',
          'data_type' => 'String',
        ],
        [
          'name' => 'file',
          'title' => 'File Path',
          'data_type' => 'String',
          'description' => 'If the example is loaded from a file, this is the location.',
        ],
        [
          'name' => 'tags',
          'title' => 'Tags',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
        ],
        [
          'name' => 'data',
          'title' => 'Example data',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        ],
        [
          'name' => 'asserts',
          'title' => 'Test assertions',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      // FIXME: Perhaps use 'edit message templates' or similar?
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
