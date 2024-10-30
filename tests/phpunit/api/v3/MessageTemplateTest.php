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
 * Test class for Template API - civicrm_msg_template*
 *
 * @package CiviCRM_APIv3
 * @group headless
 * @group msgtpl
 */
class api_v3_MessageTemplateTest extends CiviUnitTestCase {

  protected $entity = 'MessageTemplate';
  protected $params;

  public function setUp(): void {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction();
    $this->params = [
      'msg_title' => 'title',
      'msg_subject' => 'subject',
      'msg_text' => 'text',
      'msg_html' => 'html',
      'workflow_name' => 'friend',
    ];
  }

  /**
   * Test create function succeeds.
   */
  public function testCreate(): void {
    $result = $this->callAPISuccess('MessageTemplate', 'create', $this->params);
    $this->getAndCheck($this->params, $result['id'], $this->entity);
  }

  /**
   * Test get function succeeds.
   *
   * This is actually largely tested in the get action on create.
   *
   * Add extra checks for any 'special' return values or
   * behaviours
   */
  public function testGet(): void {
    $result = $this->callAPISuccess('MessageTemplate', 'get', [
      'workflow_name' => 'contribution_invoice_receipt',
      'is_default' => 1,
    ]);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDelete(): void {
    $entity = $this->createTestEntity('MessageTemplate', $this->params);
    $this->callAPISuccess('MessageTemplate', 'delete', ['id' => $entity['id']]);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', [
      'id' => $entity['id'],
    ]);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * If you give workflow_id, then workflow_name should also be set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testWorkflowIDToName(): void {
    $workflowName = 'uf_notify';
    $workflowID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_value WHERE name = %1', [
      1 => [$workflowName, 'String'],
    ]);

    $created = $this->callAPISuccess('MessageTemplate', 'create', [
      'msg_title' => __FUNCTION__,
      'msg_subject' => __FUNCTION__,
      'msg_text' => __FUNCTION__,
      'msg_html' => __FUNCTION__,
      'workflow_id' => $workflowID,
    ]);
    $this->assertEquals($workflowName, $created['values'][$created['id']]['workflow_name']);
    $this->assertEquals($workflowID, $created['values'][$created['id']]['workflow_id']);
    $get = $this->callAPISuccess('MessageTemplate', 'getsingle', ['id' => $created['id']]);
    $this->assertEquals($workflowName, $get['workflow_name']);
    $this->assertEquals($workflowID, $get['workflow_id']);
  }

  /**
   * If you give workflow_name, then workflow_id should also be set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testWorkflowNameToID(): void {
    $workflowName = 'petition_sign';
    $workflowID = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_value WHERE name = %1', [
      1 => ['petition_sign', 'String'],
    ]);

    $created = $this->callAPISuccess('MessageTemplate', 'create', [
      'msg_title' => __FUNCTION__,
      'msg_subject' => __FUNCTION__,
      'msg_text' => __FUNCTION__,
      'msg_html' => __FUNCTION__,
      'workflow_name' => $workflowName,
    ]);
    $this->assertEquals($workflowName, $created['values'][$created['id']]['workflow_name']);
    $this->assertEquals($workflowID, $created['values'][$created['id']]['workflow_id']);
    $get = $this->callAPISuccess('MessageTemplate', 'getsingle', ['id' => $created['id']]);
    $this->assertEquals($workflowName, $get['workflow_name']);
    $this->assertEquals($workflowID, $get['workflow_id']);
  }

  /**
   * Test workflow permissions.
   *
   * edit message templates allows editing all templates, otherwise:
   * - edit user-driven message templates is required when workflow_name is not set.
   * - edit system workflow message templates is required when workflow_name is set.
   */
  public function testPermissionChecks(): void {
    $this->createTestEntity('MessageTemplate', [
      'msg_title' => 'title',
      'msg_subject' => 'subject',
      'msg_html' => 'html',
      'workflow_name' => 'friend',
    ], 'workflow');

    $this->createTestEntity('MessageTemplate', [
      'msg_title' => 'title',
      'msg_subject' => 'subject',
      'msg_html' => 'html',
    ], 'user');

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit user-driven message templates'];
    // Attempting to update the workflow template should fail with only user permissions.
    $this->callAPIFailure('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['workflow'],
      'msg_subject' => 'test msg permission subject',
      'check_permissions' => TRUE,
    ]);

    // The user message should be possible to update.
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['user'],
      'msg_subject' => 'Test user message template',
      'check_permissions' => TRUE,
    ]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit system workflow message templates'];
    // Now check that when its swapped around permissions that the correct responses are detected.
    $this->callAPIFailure('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['user'],
      'msg_subject' => 'User template updated by system message permission',
      'check_permissions' => TRUE,
    ]);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['workflow'],
      'msg_subject' => 'test msg permission subject',
      'check_permissions' => TRUE,
    ]);

    // With both permissions the user can update both template types.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'edit system workflow message templates',
      'edit user-driven message templates',
    ];
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['workflow'],
      'msg_subject' => 'Workflow template updated',
      'check_permissions' => TRUE,
    ]);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $this->ids['MessageTemplate']['user'],
      'msg_subject' => 'User template updated',
      'check_permissions' => TRUE,
    ]);

    // Verify that the backwards compatibility still works i.e. having edit message templates allows for editing of both kinds of message templates
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit message templates'];
    $this->callAPISuccess('MessageTemplate', 'create', ['id' => $this->ids['MessageTemplate']['workflow'], 'msg_subject' => 'User template updated by edit message permission', 'check_permissions' => TRUE]);
    $this->callAPISuccess('MessageTemplate', 'create', ['id' => $this->ids['MessageTemplate']['user'], 'msg_subject' => 'test msg permission subject backwards compatibility', 'check_permissions' => TRUE]);
  }

}
