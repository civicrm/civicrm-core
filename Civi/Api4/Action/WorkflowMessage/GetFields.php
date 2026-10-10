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

namespace Civi\Api4\Action\WorkflowMessage;

/**
 * Fields of the WorkflowMessage entity.
 *
 * Carries `workflow` and `format` so that an action whose records describe a
 * particular workflow's message model — rather than a workflow — can declare
 * the columns it actually returns. Those two are forwarded from the calling
 * action by `AbstractAction::entityFields()`.
 *
 * @method $this setWorkflow(string $workflow)
 * @method string|null getWorkflow()
 * @method $this setFormat(string $format)
 * @method string|null getFormat()
 */
class GetFields extends \Civi\Api4\Generic\BasicGetFieldsAction {

  /**
   * @var string|null
   */
  protected $workflow;

  /**
   * @var string|null
   */
  protected $format;

  /**
   * Fields of a workflow, used unless the requested action declares its own.
   *
   * @return array
   */
  public function getRecords(): array {
    return [
      [
        'name' => 'name',
        'title' => ts('Name'),
        'data_type' => 'String',
      ],
      [
        'name' => 'group',
        'title' => ts('Group'),
        'data_type' => 'String',
      ],
      [
        'name' => 'class',
        'title' => ts('Class'),
        'data_type' => 'String',
      ],
      [
        'name' => 'description',
        'title' => ts('Description'),
        'data_type' => 'String',
      ],
      [
        'name' => 'support',
        'title' => ts('Support Level'),
        'options' => [
          'experimental' => ts('Experimental: Message may change substantively with no special communication or facilitation.'),
          'template-only' => ts('Template Support: Changes affecting the content of the message-template will get active support/facilitation.'),
          'full' => ts('Full Support: All changes affecting message-templates or message-senders will get active support/facilitation.'),
        ],
        'data_type' => 'String',
      ],
    ];
  }

}
