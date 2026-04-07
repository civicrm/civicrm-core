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
   * Test CRM_Core_BAO_Dashboard::getContactDashlets
   */
  public function testGetContactDashlets(): void {
    // note: getter triggers reinitialise on first load for each contact
    // (or if all dashlets have been deleted)
    $dashlets = array_column(CRM_Core_BAO_Dashboard::getContactDashlets(), NULL, 'name');

    // check expected dashlets are available
    $this->assertArrayKeyExists('blog', $dashlets);
    $this->assertArrayKeyExists('activity', $dashlets);
    $this->assertArrayKeyExists('afformDashlet', $dashlets);

    // check a dashlet we dont have access to is not inclued
    $this->assertEquals(array_key_exists('myCases', $dashlets), FALSE);

    // blog should be placed (by the default initialisation)
    $this->assertEquals($dashlets['blog']['dashboard_contact.contact_id'], $this->contactId);

    // activities dashlet is available but not placed
    $this->assertEquals($dashlets['activity']['dashboard_contact.contact_id'], NULL);

    \Civi\Api4\DashboardContact::create(FALSE)
      ->addValue('contact_id', $this->contactId)
      ->addValue('column_no', 0)
      ->addValue('weight', -100)
      ->addValue('dashboard_id', $dashlets['activity']['id'])
      ->execute();

    $updatedDashlets = CRM_Core_BAO_Dashboard::getContactDashlets();

    // check the activity dashboard is now placed
    // and contact weight has been respected (activity comes in index 0, before blog)
    $this->assertEquals($updatedDashlets[0]['name'], 'activity');
    $this->assertEquals($updatedDashlets[0]['dashboard_contact.contact_id'], $this->contactId);
  }

  /**
   * Test that dashlet directives are required by crmDashboard
   */
  public function testDashletModuleRequirements(): void {
    $crmDashboard = \Civi::service('angular')->getModules()['crmDashboard'];

    $this->assertEquals(in_array($this->dashletModule, $crmDashboard['requires']), TRUE);
  }

}
