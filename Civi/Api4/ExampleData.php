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
class ExampleData extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\AbstractGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Action\ExampleData\Get(__CLASS__, __FILE__))
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
          'data_type' => 'Array',
          'options'  => [
            'preview' => ts('Preview: Display as an example in the "Preview" dialog'),
            'phpunit' => ts('PHPUnit: Run basic sniff tests in PHPUnit using this example'),
          ],
        ],
        [
          'type' => 'Extra',
          'name' => 'data',
          'title' => 'Example data',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        ],
        [
          'type' => 'Extra',
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

  /**
   * @inheritDoc
   */
  public static function getInfo() {
    $info = parent::getInfo();
    $info['primary_key'] = ['name'];
    return $info;
  }

}
