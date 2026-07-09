<?php

use Civi\Test\ContactTestTrait;

/**
 * Class CRM_Core_BAO_DashboardTest
 *
 * @group headless
 */
class CRM_Core_BAO_DashboardTest extends CiviUnitTestCase {

  use ContactTestTrait;

  protected int $contactId;
  protected string $dashletModule;

  public function setUp(): void {
    parent::setUp();
    $this->contactId = $this->createLoggedInUser();

    $angularDashlet = \Civi\Api4\Afform::create(FALSE)
      ->addValue('name', 'afformDashlet')
      ->addValue('title', 'Test Dashlet')
      ->addValue('layout', '<af-form><crm-search-display-table saved-search="MySearch"></crm-search-display-table></af-form>')
      ->addValue('placement', ['dashboard_dashlet'])
      ->execute()
      ->single();

    $this->dashletModule = $angularDashlet['module_name'];
  }

  public function tearDown(): void {
    parent::tearDown();

    \Civi\Api4\Contact::delete(FALSE)->addWhere('id', '=', $this->contactId)->execute();
    // TODO: shouldn't we delete? this follows what AfformPlacementTest does
    \Civi\Api4\Afform::revert(FALSE)->addWhere('module_name', '=', $this->dashletModule)->execute();
  }

  /**
   * Test that dashlet directives are required by crmDashboard
   */
  public function testDashletModuleRequirements(): void {
    $crmDashboard = \Civi::service('angular')->getModules()['crmDashboard'];

    $this->assertEquals(in_array($this->dashletModule, $crmDashboard['requires']), TRUE);
  }

}
