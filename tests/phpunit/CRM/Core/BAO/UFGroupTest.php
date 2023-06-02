<?php

use Civi\Api4\UFGroup;

/**
 * Class CRM_Core_BAO_UFGroupTest.
 *
 * @group headless
 */
class CRM_Core_BAO_UFGroupTest extends CiviUnitTestCase {

  public function implementHookPre($op, $objectName, $id, &$params): void {
    if ($objectName === 'UFGroup') {
      if ($op === 'create') {
        $params['is_active'] = 0;
      }
      elseif ($op === 'delete') {
        $this->callAPISuccess('SystemLog', 'create', [
          'message' => "CRM_Core_BAO_UFGroupTest::implementHookPre $id",
          'level' => 'info',
        ]);
      }
    }
  }

  public function implementHookPost($op, $objectName, $objectId, $objectRef): void {
    if ($objectName === 'UFGroup') {
      if ($op === 'create') {
        $objectRef->is_active = 0;
      }
      elseif ($op === 'delete') {
        $this->callAPISuccess('SystemLog', 'create', [
          'message' => "CRM_Core_BAO_UFGroupTest::implementHookPost $objectId",
          'level' => 'info',
        ]);
      }
    }
  }

  /**
   * Test that when creating a UFGroup the registered pre-hook is called.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPreHookIsCalledForCreate(): void {
    // Specify pre hook implementation.
    $this->hookClass->setHook('civicrm_pre', [$this, 'implementHookPre']);

    $this->createUFGroup([
      'title' => 'testPreHookIsCalledForCreate',
      'is_active' => 1,
    ]);
    // Assert that pre hook implementation was called.
    $ufGroup = UFGroup::get()->addWhere('title', '=', 'testPreHookIsCalledForCreate')->execute()->first();
    $this->assertEquals(0, $ufGroup['is_active'], 'Is active should be 0');
  }

  /**
   * Test the hook is called during delete.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPreHookIsCalledForDelete(): void {
    $this->hookClass->setHook('civicrm_pre', [$this, 'implementHookPre']);

    $ufGroupID = $this->createUFGroup([
      'title' => 'testPreHookIsCalledForDelete',
      'is_active' => 1,
    ])['id'];

    UFGroup::delete()->addWhere('id', '=', $ufGroupID)->execute();

    // Assert that pre hook implementation was called for delete op.
    $systemLogCount = $this->callAPISuccess('SystemLog', 'getcount', [
      'message' => "CRM_Core_BAO_UFGroupTest::implementHookPre $ufGroupID",
      'level' => 'info',
    ]);

    $this->assertEquals(1, $systemLogCount, 'There should be one system log entry with message "CRM_Core_BAO_UFGroupTest::implementHookPre ' . $ufGroupID . '"');
  }

  /**
   * Test the hook is called when created a UF Group.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPostHookIsCalledForCreate(): void {
    $this->hookClass->setHook('civicrm_post', [$this, 'implementHookPost']);
    $ufGroup = $this->createUFGroup([
      'title' => 'testPostHookIsCalledForCreate',
      'is_active' => 1,
    ]);

    // Assert that pre hook implementation was called.
    $this->assertEquals('testPostHookIsCalledForCreate', $ufGroup['title']);
    $this->assertEquals(0, $ufGroup['is_active'], 'Is active should be 0');
  }

  /**
   * Test that the hook fires during UFGroup (profile) delete.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPostHookIsCalledForDelete(): void {
    $this->hookClass->setHook('civicrm_post', [$this, 'implementHookPost']);

    $ufGroupID = UFGroup::create()->setValues([
      'title' => 'testPostHookIsCalledForDelete',
      'is_active' => 1,
    ])->execute()->first()['id'];

    UFGroup::delete()->addWhere('id', '=', $ufGroupID)->execute();

    // Assert that pre hook implementation was called for delete op.
    $systemLogCount = $this->callAPISuccess('SystemLog', 'getcount', [
      'message' => "CRM_Core_BAO_UFGroupTest::implementHookPost $ufGroupID",
      'level' => 'info',
    ]);

    $this->assertEquals(1, $systemLogCount, 'There should be one system log entry with message "CRM_Core_BAO_UFGroupTest::implementHookPost ' . $ufGroupID . '"');
  }

  /**
   * Create a UF Group.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function createUFGroup($values): ?array {
    $ufGroup = UFGroup::create()->setValues($values)->execute()->first();
    $this->ids['UFGroup'][] = $ufGroup['id'];
    return $ufGroup;
  }

}
