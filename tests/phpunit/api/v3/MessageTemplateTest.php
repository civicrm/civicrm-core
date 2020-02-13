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
 */
class api_v3_MessageTemplateTest extends CiviUnitTestCase {

  protected $entity = 'MessageTemplate';
  protected $params;

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->useTransaction(TRUE);
    $template = CRM_Core_DAO::createTestObject('CRM_Core_DAO_MessageTemplate')->toArray();
    $this->params = [
      'msg_title' => $template['msg_title'],
      'msg_subject' => $template['msg_subject'],
      'msg_text' => $template['msg_text'],
      'msg_html' => $template['msg_html'],
      'workflow_id' => $template['workflow_id'],
      'is_default' => $template['is_default'],
      'is_reserved' => $template['is_reserved'],
    ];
  }

  public function tearDown() {
    parent::tearDown();
    unset(CRM_Core_Config::singleton()->userPermissionClass->permissions);
  }

  /**
   * Test create function succeeds.
   */
  public function testCreate() {
    $result = $this->callAPIAndDocument('MessageTemplate', 'create', $this->params, __FUNCTION__, __FILE__);
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
  public function testGet() {
    $result = $this->callAPIAndDocument('MessageTemplate', 'get', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Test cloning of message templates.
   */
  public function testClone() {
    $main = $this->createTestEntity();
    $clone = $this->callAPIAndDocument('MessageTemplate', 'clone', ['id' => $main['id']], __FUNCTION__, __FILE__);

    $mainTemplate = $main['values'][$main['id']];
    $cloneTemplate = $clone['values'][$clone['id']];

    //Verify if values are equal.
    $this->assertEquals($mainTemplate['msg_title'] . " - Copy", $cloneTemplate['msg_title']);
    $this->assertEquals($mainTemplate['msg_subject'], $cloneTemplate['msg_subject']);
    $this->assertEquals($mainTemplate['msg_text'], $cloneTemplate['msg_text']);
    $this->assertEquals($mainTemplate['msg_html'], $cloneTemplate['msg_html']);
    $this->assertEquals($mainTemplate['is_active'], $cloneTemplate['is_active']);
    $this->assertEquals(0, $cloneTemplate['is_default']);
    $this->assertEquals(0, $cloneTemplate['is_reserved']);
  }

  /**
   * Check the delete function succeeds.
   */
  public function testDelete() {
    $entity = $this->createTestEntity();
    $result = $this->callAPIAndDocument('MessageTemplate', 'delete', ['id' => $entity['id']], __FUNCTION__, __FILE__);
    $checkDeleted = $this->callAPISuccess($this->entity, 'get', [
      'id' => $entity['id'],
    ]);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  public function testPermissionChecks() {
    $entity = $this->createTestEntity();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit user-driven message templates'];
    // Ensure that it cannot create a system message or update a system message tempalte given current permissions.
    $this->callAPIFailure('MessageTemplate', 'create', [
      'id' => $entity['id'],
      'msg_subject' => 'test msg permission subject',
      'check_permissions' => TRUE,
    ]);
    $testUserEntity = $entity['values'][$entity['id']];
    unset($testUserEntity['id']);
    $testUserEntity['msg_subject'] = 'Test user message template';
    unset($testUserEntity['workflow_id']);
    $testuserEntity['check_permissions'] = TRUE;
    // ensure that it can create user templates;
    $userEntity = $this->callAPISuccess('MessageTemplate', 'create', $testUserEntity);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit system workflow message templates'];
    // Now check that when its swapped around permissions that the correct reponses are detected.
    $this->callAPIFailure('MessageTemplate', 'create', [
      'id' => $userEntity['id'],
      'msg_subject' => 'User template updated by system message permission',
      'check_permissions' => TRUE,
    ]);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $entity['id'],
      'msg_subject' => 'test msg permission subject',
      'check_permissions' => TRUE,
    ]);
    // verify with all 3 permissions someone can do everything.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'edit system workflow message templates',
      'edit user-driven message templates',
    ];
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $userEntity['id'],
      'msg_subject' => 'User template updated by system message permission',
      'check_permissions' => TRUE,
    ]);
    $this->callAPISuccess('MessageTemplate', 'create', [
      'id' => $entity['id'],
      'msg_subject' => 'test msg permission subject',
      'check_permissions' => TRUE,
    ]);
    // Verify that the backwards compatabiltiy still works i.e. having edit message templates allows for editing of both kinds of message templates
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['edit message templates'];
    $this->callAPISuccess('MessageTemplate', 'create', ['id' => $userEntity['id'], 'msg_subject' => 'User template updated by edit message permission', 'check_permissions' => TRUE]);
    $this->callAPISuccess('MessageTemplate', 'create', ['id' => $entity['id'], 'msg_subject' => 'test msg permission subject backwards compatabilty', 'check_permissions' => TRUE]);
  }

}
