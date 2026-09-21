<?php

namespace Civi\Schema;

use Civi\Api4\TestThing;
use Civi\Api4\TestWidget;
use Civi\Api4\TestGizmo;
use Override;

/**
 * Test Entity Facades
 *
 * The test_facades extension creates a base (normal) entity of TestThing
 * along with two facades TestWidget and TestGizmo based on the 'type' field.
 */
class EntityFacadeTest extends \CiviUnitTestCase {

  public function setUp() : void {
    parent::setUp();
    \Civi\Test::headless()
      ->install('test_facades')
      ->apply();
    TestThing::create(FALSE)
      ->addValue('name', 'Widget1')
      ->addValue('type', 'widget')
      ->execute();
    TestThing::create(FALSE)
      ->addValue('name', 'Gizmo1')
      ->addValue('type', 'gizmo')
      ->execute();
    TestThing::create(FALSE)
      ->addValue('name', 'Misc1')
      ->addValue('type', 'misc')
      ->execute();

  }

  #[Override]
  public function tearDown(): void {
    parent::tearDown();
    TestThing::delete(FALSE)
      ->addWhere('id', '>=', 1)
      ->execute();
  }

  public function testBasicGet() : void {
    $count = TestThing::get(FALSE)
      ->execute()
      ->countFetched();
    $this->assertEquals(3, $count);

    $widgets = TestWidget::get(FALSE)
      ->execute();

    $this->assertEquals(1, $widgets->countFetched());
    $this->assertEquals('widget', $widgets[0]['type']);
    $this->assertEquals('Widget1', $widgets[0]['name']);
  }

  public function testFacadeCreate() : void {
    // NB type is not specified
    TestGizmo::create(FALSE)
      ->addValue('name', 'Gizmo2')
      ->execute();

    // Check results from the facade get match those of the base
    $gizmos = TestGizmo::get(FALSE)
      ->execute();
    $this->assertEquals(2, $gizmos->countFetched());

    $thingGizmos = TestThing::get(FALSE)
      ->addWhere('type', '=', 'gizmo')
      ->execute();
    $this->assertArrayValuesEqual((array) $gizmos, (array) $thingGizmos);
  }

  public function testFacadeDelete() : void {
    $thingCountBeforeDelete = TestThing::get(FALSE)->execute()->countFetched();
    $gizmoCountBeforeDelete = TestGizmo::get(FALSE)->execute()->countFetched();

    $this->assertNotEquals(0, $thingCountBeforeDelete);
    $this->assertNotEquals(0, $gizmoCountBeforeDelete);

    // Should only delete the Gizmos, not all the other Things
    TestGizmo::delete(FALSE)
      ->addWhere('id', '>=', 1)
      ->execute();

    $gizmoCountAfterDelete = TestGizmo::get(FALSE)->execute()->countFetched();
    $this->assertEquals(0, $gizmoCountAfterDelete);

    $thingCountAfterDelete = TestThing::get(FALSE)->execute()->countFetched();
    $this->assertNotEquals(0, $thingCountAfterDelete);

    $this->assertEquals($thingCountBeforeDelete - $gizmoCountBeforeDelete, $thingCountAfterDelete);
  }

}
