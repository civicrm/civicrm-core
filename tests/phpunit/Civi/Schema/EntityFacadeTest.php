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
      ->addValue('description', 'I am a widget')
      ->execute();
    TestThing::create(FALSE)
      ->addValue('name', 'Gizmo1')
      ->addValue('type', 'gizmo')
      ->addValue('description', 'Gizmos are beyond description')
      ->execute();
    TestThing::create(FALSE)
      ->addValue('name', 'Misc1')
      ->addValue('type', 'misc')
      ->addValue('description', 'I am not a widget or a gizmo')
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
    TestWidget::create(FALSE)
      ->addValue('name', 'Widget2')
      ->execute();

    // Check results from the facade get match those of the base
    $widgets = TestWidget::get(FALSE)
      ->execute();
    $this->assertEquals(2, $widgets->countFetched());

    $thingWidgets = TestThing::get(FALSE)
      ->addWhere('type', '=', 'widget')
      ->execute();
    $this->assertArrayValuesEqual((array) $widgets, (array) $thingWidgets);
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

  public function testFacadeFields() : void {
    $thingFields = TestThing::getFields(FALSE)->execute()->column('name');
    $widgetFields = TestWidget::getFields(FALSE)->execute()->column('name');
    $gizmoFields = TestGizmo::getFields(FALSE)->execute()->column('name');

    // Widgets have the same fields as Things
    $this->assertEquals($widgetFields, $thingFields);

    // Gizmos don't have 'description'
    $this->assertArrayValuesEqual(array_diff($thingFields, $gizmoFields), ['description']);

    $gizmo = TestGizmo::get(FALSE)->execute()->first();
    $thingGizmo = TestThing::get(FALSE)->addWhere('type', '=', 'gizmo')->execute()->first();

    // Gizmo should not have description field
    $this->assertFalse(isset($gizmo['description']));

    // but other than description, fields and values should be the same
    unset($thingGizmo['description']);
    $this->assertEquals($gizmo, $thingGizmo);
  }

}
