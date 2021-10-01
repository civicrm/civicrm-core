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

namespace api\v4\Entity;

use api\v4\UnitTestCase;
use Civi\Test\DbTestTrait;
use Civi\Test\GenericAssertionsTrait;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class MessageTemplateTest extends UnitTestCase implements TransactionalInterface {

  use GenericAssertionsTrait;
  use DbTestTrait;

  private $baseTpl = [
    'msg_title' => 'My Template',
    'msg_subject' => 'My Subject',
    'msg_text' => 'My body as text',
    'msg_html' => '<p>My body as HTML</p>',
    'is_reserved' => TRUE,
    'is_default' => FALSE,
  ];

  /**
   * Create/update a MessageTemplate with workflow_name and no corresponding workflow_id.
   */
  public function testWorkflowName_clean() {
    $create = civicrm_api4('MessageTemplate', 'create', [
      'values' => $this->baseTpl + ['workflow_name' => 'first', 'workflow_id' => NULL],
    ])->single();
    $this->assertDBQuery('first', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery(NULL, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);

    civicrm_api4('MessageTemplate', 'update', [
      'where' => [['id', '=', $create['id']]],
      'values' => ['workflow_name' => 'second', 'workflow_id' => NULL],
    ])->single();
    $this->assertDBQuery('second', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery(NULL, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
  }

  /**
   * Create/update a MessageTemplate with workflow_name - a name which happens to have an older/corresponding workflow_id.
   */
  public function testWorkflowName_legacyMatch() {
    [$firstId, $secondId] = $this->createFirstSecond();

    $create = civicrm_api4('MessageTemplate', 'create', [
      'values' => $this->baseTpl + ['workflow_name' => 'first', 'workflow_id' => NULL],
    ])->single();
    $this->assertDBQuery('first', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery($firstId, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);

    civicrm_api4('MessageTemplate', 'update', [
      'where' => [['id', '=', $create['id']]],
      'values' => ['workflow_name' => 'second', 'workflow_id' => NULL],
    ])->single();
    $this->assertDBQuery('second', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery($secondId, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
  }

  /**
   * Create/update a MessageTempalte with workflow_id. Ensure the newer workflow_name is set.
   */
  public function testWorkflowId_legacyMatch() {
    [$firstId, $secondId] = $this->createFirstSecond();

    $create = civicrm_api4('MessageTemplate', 'create', [
      'values' => $this->baseTpl + ['workflow_id' => $firstId, 'workflow_name' => NULL],
    ])->single();
    $this->assertDBQuery('first', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery($firstId, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);

    civicrm_api4('MessageTemplate', 'update', [
      'where' => [['id', '=', $create['id']]],
      'values' => ['workflow_id' => $secondId, 'workflow_name' => NULL],
    ])->single();
    $this->assertDBQuery('second', 'SELECT workflow_name FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
    $this->assertDBQuery($secondId, 'SELECT workflow_id FROM civicrm_msg_template WHERE id = %1', [1 => [$create['id'], 'Int']]);
  }

  protected function createFirstSecond() {
    $first = civicrm_api4('OptionValue', 'create', [
      'values' => [
        'option_group_id:name' => 'msg_tpl_workflow_meta',
        'label' => 'First',
        'name' => 'first',
      ],
    ]);
    $second = civicrm_api4('OptionValue', 'create', [
      'values' => [
        'option_group_id:name' => 'msg_tpl_workflow_meta',
        'label' => 'Second',
        'name' => 'second',
      ],
    ]);
    return [$first->single()['id'], $second->single()['id']];
  }

}
