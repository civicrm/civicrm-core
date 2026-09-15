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

  public function implementHookUFGroupTypes(&$ufGroupTypes): void {
    $ufGroupTypes['Test Placement'] = 'Test Placement';
  }

  /**
   * Test that a uf_join module registered by an extension is offered on the profile form
   * and is created and deleted along with the types core declares itself.
   */
  public function testUFGroupTypesHook(): void {
    $this->hookClass->setHook('civicrm_ufGroupTypes', [$this, 'implementHookUFGroupTypes']);

    $this->assertArrayHasKey('Test Placement', CRM_Core_SelectValues::ufGroupTypes());

    $ufGroupID = $this->createUFGroup([
      'title' => 'testUFGroupTypesHook',
      'is_active' => 1,
    ])['id'];

    // A join for a module nobody declares should survive being edited around, the way
    // component-managed joins such as CiviEvent do.
    $componentJoin = ['uf_group_id' => $ufGroupID, 'module' => 'CiviEvent'];
    CRM_Core_BAO_UFGroup::addUFJoin($componentJoin);

    CRM_Core_BAO_UFGroup::createUFJoin(1, ['Profile' => 1, 'Test Placement' => 1], $ufGroupID);
    $this->assertEquals(['CiviEvent', 'Profile', 'Test Placement'], $this->getUFJoinModules($ufGroupID));

    // This is what pre-ticks the checkbox when the form is reopened.
    $this->assertContains('Test Placement', CRM_Core_BAO_UFGroup::getUFJoinRecord($ufGroupID));

    CRM_Core_BAO_UFGroup::createUFJoin(1, ['Profile' => 1], $ufGroupID);
    $this->assertEquals(['CiviEvent', 'Profile'], $this->getUFJoinModules($ufGroupID));
  }

  /**
   * @return array
   */
  protected function getUFJoinModules(int $ufGroupID): array {
    $modules = (array) \Civi\Api4\UFJoin::get(FALSE)
      ->addWhere('uf_group_id', '=', $ufGroupID)
      ->execute()->column('module');
    sort($modules);
    return $modules;
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
