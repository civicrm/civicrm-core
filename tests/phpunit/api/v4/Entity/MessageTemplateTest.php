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

use api\v4\Api4TestBase;
use Civi\Test\DbTestTrait;
use Civi\Test\GenericAssertionsTrait;
use Civi\Test\TransactionalInterface;
use Civi\Api4\MessageTemplate;

/**
 * @group headless
 * @group msgtpl
 */
class MessageTemplateTest extends Api4TestBase implements TransactionalInterface {

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
  public function testWorkflowName_clean(): void {
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
  public function testWorkflowName_legacyMatch(): void {
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
  public function testWorkflowId_legacyMatch(): void {
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

  /**
   * Test save with no id
   */
  public function testSaveNoId(): void {
    $saved = civicrm_api4('MessageTemplate', 'save', ['records' => [array_merge(['is_reserved' => 0], $this->baseTpl)]])->first();
    $this->assertDBQuery('My Template', 'SELECT msg_title FROM civicrm_msg_template WHERE id = %1', [1 => [$saved['id'], 'Int']]);
    $this->assertDBQuery('<p>My body as HTML</p>', 'SELECT msg_html FROM civicrm_msg_template WHERE id = %1', [1 => [$saved['id'], 'Int']]);
  }

  /**
   * Test save with an explicit null id
   */
  public function testSaveNullId(): void {
    $saved = civicrm_api4('MessageTemplate', 'save', ['records' => [array_merge(['id' => NULL, 'is_reserved' => 0], $this->baseTpl)]])->first();
    $this->assertDBQuery('My Template', 'SELECT msg_title FROM civicrm_msg_template WHERE id = %1', [1 => [$saved['id'], 'Int']]);
    $this->assertDBQuery('<p>My body as HTML</p>', 'SELECT msg_html FROM civicrm_msg_template WHERE id = %1', [1 => [$saved['id'], 'Int']]);
  }

  /**
   * Test APIv4 calculated field master_id
   */
  public function testMessageTemplateMasterID(): void {
    \CRM_Core_Transaction::create(TRUE)->run(function(\CRM_Core_Transaction $tx) {
      $tx->rollback();

      MessageTemplate::update(FALSE)
        ->addWhere('workflow_name', '=', 'contribution_offline_receipt')
        ->addValue('msg_subject', 'Subject test {if 1 > 2} smarty{/if}')
        ->addValue('msg_html', '<p>Body test {if 1 > 2} markup{/if}</p>')
        ->execute();

      $originalTemplate = MessageTemplate::get()
        ->addWhere('is_default', '=', 1)
        ->addWhere('workflow_name', '=', 'contribution_offline_receipt')
        ->addSelect('id', 'msg_subject', 'msg_html', 'master_id', 'master_id.msg_subject')
        ->execute()->first();
      $messageTemplateID = $originalTemplate['id'];
      $reservedTemplate = MessageTemplate::get()
        ->addWhere('is_reserved', '=', 1)
        ->addWhere('workflow_name', '=', 'contribution_offline_receipt')
        ->addSelect('id', 'msg_subject', 'msg_html')
        ->execute()->first();
      $messageTemplateIDReserved = $reservedTemplate['id'];

      $this->assertEquals('Subject test {if 1 > 2} smarty{/if}', $originalTemplate['msg_subject']);
      $this->assertEquals('<p>Body test {if 1 > 2} markup{/if}</p>', $originalTemplate['msg_html']);
      $this->assertNull($originalTemplate['master_id']);
      $this->assertNull($originalTemplate['master_id.msg_subject']);

      MessageTemplate::update()
        ->addWhere('id', '=', $messageTemplateID)
        ->setValues([
          'msg_subject' => 'Hello world',
          'msg_html' => '<p>Hello world</p>',
        ])
        ->execute();
      $msgTpl = MessageTemplate::get()
        ->addSelect('msg_subject', 'master_id', 'master_id.msg_subject')
        ->addWhere('id', '=', $messageTemplateID)
        ->execute()->first();
      // confirm subject is set
      $this->assertEquals('Hello world', $msgTpl['msg_subject']);
      // message is changed so both of these should be set
      $this->assertEquals($msgTpl['master_id'], $messageTemplateIDReserved);
      $this->assertNotNull($msgTpl['master_id.msg_subject']);
      // these should be different
      $this->assertNotEquals($msgTpl['msg_subject'], $msgTpl['master_id.msg_subject']);

      // Now revert it
      MessageTemplate::revert(FALSE)
        ->addWhere('id', '=', $messageTemplateID)
        ->execute();

      $msgTpl = MessageTemplate::get()
        ->addSelect('msg_subject', 'msg_html', 'master_id', 'master_id.msg_subject')
        ->addWhere('id', '=', $messageTemplateID)
        ->execute()->first();
      // confirm subject is reverted
      $this->assertEquals($originalTemplate['msg_subject'], $msgTpl['msg_subject']);
      $this->assertEquals($originalTemplate['msg_html'], $msgTpl['msg_html']);
      // message is unchanged from original so both of these should be null
      $this->assertNull($msgTpl['master_id']);
      $this->assertNull($msgTpl['master_id.msg_subject']);
    });
  }

}
