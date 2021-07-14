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

namespace Civi\Api4;

/**
 * Utility API for evaluating templated expressions
 *
 * @searchable none
 * @since 5.41
 * @package Civi\Api4
 */
class WorkflowMessage extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction(__CLASS__, __FUNCTION__, function ($get) {
      $result = [];
      foreach (\Civi\WorkflowMessage\WorkflowMessage::getWorkflowNameClassMap() as $name => $class) {
        $result[] = ['name' => $name];
      }
      return $result;
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   *
   * @return \Civi\Api4\Action\WorkflowMessage\Render
   */
  public static function render($checkPermissions = TRUE) {
    return (new Action\WorkflowMessage\Render(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      return [
        [
          'name' => 'name',
          'title' => 'Name',
          'data_type' => 'String',
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'render' => [
        // nested array = OR
        [
          'edit message templates',
          'edit user-driven message templates',
          'edit system workflow message templates',
          'render templates',
        ],
      ],
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
