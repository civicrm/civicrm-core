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
 * A WorkflowMessage describes the inputs to an automated email messages, and it
 * allows you to render or preview the content fo automated email messages.
 *
 * For example, when a constituent donates online, CiviContribute uses the
 * `contribution_online_receipt` workflow message. This expects certain inputs
 * (eg `contactId` and `contributionId`) and supports certain tokens
 * (eg `{contribution.total_amount}`).
 *
 * WorkflowMessages are related to MessageTemplates (by way of
 * `WorkflowMessage.name`<=>`MessageTemplate.workflow_name`).
 * The WorkflowMessage defines the _contract_ or _processing_ of the
 * message, and the MessageTemplate defines the _literal prose_.  The prose
 * would change frequently (eg for different deployments, locales, timeframes,
 * and other whims), but contract would change conservatively (eg with a
 * code-update and with some attention to backward-compatibility/change-management).
 *
 * @searchable none
 * @since 5.43
 * @package Civi\Api4
 */
class WorkflowMessage extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new Generic\BasicGetAction(__CLASS__, __FUNCTION__, function ($get) {
      return \Civi\WorkflowMessage\WorkflowMessage::getWorkflowSpecs();
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
  public static function getTemplateFields($checkPermissions = TRUE) {
    return (new Action\WorkflowMessage\GetTemplateFields(__CLASS__, __FUNCTION__))
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
        [
          'name' => 'group',
          'title' => 'Group',
          'data_type' => 'String',
        ],
        [
          'name' => 'class',
          'title' => 'Class',
          'data_type' => 'String',
        ],
        [
          'name' => 'description',
          'title' => 'Description',
          'data_type' => 'String',
        ],
        [
          'name' => 'support',
          'title' => 'Support Level',
          'options'  => [
            'experimental' => ts('Experimental: Message may change substantively with no special communication or facilitation.'),
            'template-only' => ts('Template Support: Changes affecting the content of the message-template will get active support/facilitation.'),
            'full' => ts('Full Support: All changes affecting message-templates or message-senders will get active support/facilitation.'),
          ],
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
