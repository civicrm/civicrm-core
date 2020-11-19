<?php

/**
 * Class CRM_Core_BAO_UFGroupTest
 * @group headless
 */
class CRM_Core_BAO_UFGroupTest extends CiviUnitTestCase {

  public function implementHookPre($op, $objectName, $id, &$params) {
    if ($objectName == 'UFGroup') {
      if ($op == 'create') {
        $params['is_active'] = 0;
      }
      elseif ($op == 'delete') {
        $systemLog = $this->callAPISuccess('SystemLog', 'create', [
          'message' => "CRM_Core_BAO_UFGroupTest::implementHookPre $id",
          'level' => 'info',
        ]);
      }
    }
  }

  public function implementHookPost($op, $objectName, $objectId, &$objectRef) {
    if ($objectName == 'UFGroup') {
      if ($op == 'create') {
        $objectRef->is_active = 0;
      }
      elseif ($op == 'delete') {
        $systemLog = $this->callAPISuccess('SystemLog', 'create', [
          'message' => "CRM_Core_BAO_UFGroupTest::implementHookPost $objectId",
          'level' => 'info',
        ]);
      }
    }
  }

  public function testPreHookIsCalledForCreate() {
    // Specify pre hook implementation.
    $this->hookClass->setHook('civicrm_pre', array($this, 'implementHookPre'));

    // Create a ufgroup with BAO.
    $params = [
      'title' => 'testPreHookIsCalledForCreate',
      'is_active' => 1,
    ];
    $ufGroup = CRM_Core_BAO_UFGroup::add($params);

    // Assert that pre hook implemntation was called.
    $this->assertEquals('testPreHookIsCalledForCreate', $ufGroup->title);
    $this->assertEquals(0, $ufGroup->is_active, 'Is active should be 0');
  }

  public function testPreHookIsCalledForDelete() {
    // Specify pre hook implementation.
    $this->hookClass->setHook('civicrm_pre', array($this, 'implementHookPre'));

    // Create a ufgroup with BAO.
    $params = [
      'title' => 'testPreHookIsCalledForDelete',
      'is_active' => 1,
    ];
    $ufGroup = CRM_Core_BAO_UFGroup::add($params);
    $ufGroupID = $ufGroup->id;
    $ufGroup = CRM_Core_BAO_UFGroup::del($ufGroupID);

    // Assert that pre hook implemntation was called for delete op.
    $systemLogCount = $this->callAPISuccess('SystemLog', 'getcount', [
      'message' => "CRM_Core_BAO_UFGroupTest::implementHookPre $ufGroupID",
      'level' => 'info',
    ]);

    $this->assertEquals(1, $systemLogCount, 'There should be one system log entry with message "CRM_Core_BAO_UFGroupTest::implementHookPre ' . $ufGroupID . '"');
  }

  public function testPostHookIsCalledForCreate() {
    $this->hookClass->setHook('civicrm_post', array($this, 'implementHookPost'));

    $params = [
      'title' => 'testPostHookIsCalledForCreate',
      'is_active' => 1,
    ];
    $ufGroup = CRM_Core_BAO_UFGroup::add($params);

    // Assert that pre hook implemntation was called.
    $this->assertEquals('testPostHookIsCalledForCreate', $ufGroup->title);
    $this->assertEquals(0, $ufGroup->is_active, 'Is active should be 0');
  }

  public function testPostHookIsCalledForDelete() {
    $this->hookClass->setHook('civicrm_post', array($this, 'implementHookPost'));

    $params = [
      'title' => 'testPostHookIsCalledForDelete',
      'is_active' => 1,
    ];
    $ufGroup = CRM_Core_BAO_UFGroup::add($params);
    $ufGroupID = $ufGroup->id;
    $ufGroup = CRM_Core_BAO_UFGroup::del($ufGroupID);

    // Assert that pre hook implemntation was called for delete op.
    $systemLogCount = $this->callAPISuccess('SystemLog', 'getcount', [
      'message' => "CRM_Core_BAO_UFGroupTest::implementHookPost $ufGroupID",
      'level' => 'info',
    ]);

    $this->assertEquals(1, $systemLogCount, 'There should be one system log entry with message "CRM_Core_BAO_UFGroupTest::implementHookPost ' . $ufGroupID . '"');
  }

}
