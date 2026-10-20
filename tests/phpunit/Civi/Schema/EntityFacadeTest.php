<?php

namespace Civi\Schema;

use Civi\Api4\Entity;
use Civi\Api4\TestThing;
use Civi\Api4\TestWidget;
use Civi\Api4\TestGizmo;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;

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
    TestThing::save(FALSE)
      ->setRecords([
        ['name' => 'Widget1', 'type' => 'widget', 'description' => 'I am a widget'],
        ['name' => 'Gizmo1', 'type' => 'gizmo', 'description' => 'Gizmos are beyond description'],
        ['name' => 'Misc1', 'type' => 'misc', 'description' => 'I am not a widget or a gizmo'],
      ])
      ->execute();
  }

  /**
   * Event listeners added by a test, removed in tearDown.
   *
   * @var array
   */
  private array $listeners = [];

  public function tearDown(): void {
    $this->removeTestListeners();
    TestThing::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    $this->cleanupCustomGroups();
    parent::tearDown();
  }

  private function addTestListener(string $eventName, callable $listener): void {
    \Civi::dispatcher()->addListener($eventName, $listener);
    $this->listeners[] = [$eventName, $listener];
  }

  private function removeTestListeners(): void {
    foreach ($this->listeners as [$eventName, $listener]) {
      \Civi::dispatcher()->removeListener($eventName, $listener);
    }
    $this->listeners = [];
    EntityRepository::flush();
  }

  /**
   * Access and validation rules for the parent entity also apply to its facades.
   */
  public function testParentAccessAndValidationApplyToFacades(): void {
    $widgetID = TestWidget::get(FALSE)->execute()->single()['id'];
    $update = TestWidget::update(FALSE);
    $this->assertNotFalse(CoreUtil::checkAccessRecord($update, ['id' => $widgetID, 'name' => 'Renamed'], 0));

    $this->addTestListener('civi.api4.authorizeRecord::TestThing', function(\Civi\Api4\Event\AuthorizeRecordEvent $e) {
      $e->setAuthorized(FALSE);
    });
    $this->assertFalse(CoreUtil::checkAccessRecord($update, ['id' => $widgetID, 'name' => 'Renamed'], 0));

    $this->addTestListener('civi.api4.validate::TestThing', function(\Civi\Api4\Event\ValidateValuesEvent $e) {
      foreach ($e->records as $key => $record) {
        if (($record['name'] ?? NULL) === 'Invalid') {
          $e->addError($key, 'name', 'invalid_name', 'Invalid name');
        }
      }
    });
    try {
      TestWidget::create(FALSE)->addValue('name', 'Invalid')->execute();
      $this->fail('Validation for TestThing should apply to TestWidget');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString('Invalid name', $e->getMessage());
    }
  }

  public function testInvalidFacadeDeclarations(): void {
    $this->addTestListener('hook_civicrm_entityTypes', function($e) {
      $e->entityTypes['TestBadFields'] = [
        'name' => 'TestBadFields',
        'facade_of' => 'TestThing',
        'where' => ['type' => 'bad'],
        'alterFields' => fn(array $fields) => 'not fields',
      ];
    });
    EntityRepository::flush();
    try {
      \Civi::entity('TestBadFields')->getFields();
      $this->fail('alterFields must return an array');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString('TestBadFields', $e->getMessage());
    }
    $this->removeTestListeners();

    $this->addTestListener('hook_civicrm_entityTypes', function($e) {
      $e->entityTypes['TestNoWhere'] = ['name' => 'TestNoWhere', 'facade_of' => 'TestThing'];
    });
    EntityRepository::flush();
    try {
      EntityRepository::getFacades();
      $this->fail('A facade must declare where conditions');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertSame("Facade TestNoWhere must declare 'where' conditions", $e->getMessage());
    }
    $this->removeTestListeners();
  }

  public function testFacadesAreNotInEntityList(): void {
    $this->assertArrayHasKey('TestWidget', EntityRepository::getFacades());
    $this->assertArrayNotHasKey('TestWidget', EntityRepository::getEntities());
    $this->assertSame('TestThing', EntityRepository::getTableIndex()['civicrm_test_thing']);
    $this->assertTrue(EntityRepository::entityExists('TestWidget'));
    $this->assertSame('TestThing', EntityRepository::getFacadeParent('TestWidget'));
    $this->assertNull(EntityRepository::getFacadeParent('TestThing'));
    $this->assertSame('TestThing', CoreUtil::resolveFacade('TestGizmo'));
    $this->assertSame('TestThing', CoreUtil::resolveFacade('TestThing'));
  }

  public function testFacadeInfo(): void {
    $info = Entity::get(FALSE)
      ->addWhere('name', 'IN', ['TestThing', 'TestWidget', 'TestGizmo'])
      ->execute()->indexBy('name');

    $this->assertSame('Test Widget', $info['TestWidget']['title']);
    $this->assertSame('Test Widgets', $info['TestWidget']['title_plural']);
    $this->assertSame('Widgets (facade of TestThing)', $info['TestWidget']['description']);
    $this->assertSame('TestThing', $info['TestWidget']['facade_of']);
    $this->assertSame(['type' => 'widget'], $info['TestWidget']['where']);
    $this->assertContains('EntityFacade', $info['TestWidget']['type']);
    $this->assertContains('DAOEntity', $info['TestWidget']['type']);
    $this->assertSame('CRM_TestFacades_DAO_TestThing', $info['TestWidget']['dao']);
    $this->assertSame('civicrm_test_thing', $info['TestWidget']['table_name']);
    // Icon can be overridden or inherited
    $this->assertSame('fa-cog', $info['TestWidget']['icon']);
    $this->assertSame('fa-cube', $info['TestGizmo']['icon']);
    // As can other info items
    $this->assertSame('name', $info['TestWidget']['label_field']);
    $this->assertSame('primary', $info['TestWidget']['searchable']);
    $this->assertEmpty($info['TestThing']['label_field']);
    $this->assertSame($info['TestThing']['searchable'], $info['TestGizmo']['searchable']);

    $this->assertEmpty($info['TestThing']['facade_of'] ?? NULL);
    $this->assertNotContains('EntityFacade', $info['TestThing']['type']);
  }

  public function testFacadeGet() : void {
    $this->assertCount(3, TestThing::get(FALSE)->execute());

    $widgets = TestWidget::get(FALSE)
      ->execute();

    $this->assertCount(1, $widgets);
    $this->assertSame('widget', $widgets[0]['type']);
    $this->assertSame('Widget1', $widgets[0]['name']);
  }

  public function testFacadeJoin(): void {
    $things = TestThing::get(FALSE)
      ->addSelect('name', 'widget.name')
      ->addJoin('TestWidget AS widget', 'LEFT', ['id', '=', 'widget.id'])
      ->addOrderBy('name')
      ->execute()->column('widget.name', 'name');
    $this->assertSame(['Gizmo1' => NULL, 'Misc1' => NULL, 'Widget1' => 'Widget1'], $things);
  }

  public function testFacadeCreate() : void {
    // NB type is not specified
    TestWidget::create(FALSE)
      ->addValue('name', 'Widget2')
      ->execute();
    // Type is forced to the facade's value
    TestWidget::create(FALSE)
      ->addValue('name', 'Widget3')
      ->addValue('type', 'gizmo')
      ->execute();
    TestWidget::save(FALSE)
      ->addRecord(['name' => 'Widget4'])
      ->execute();

    // Check results from the facade get match those of the base
    $widgets = TestWidget::get(FALSE)
      ->execute();
    $this->assertCount(4, $widgets);

    $thingWidgets = TestThing::get(FALSE)
      ->addWhere('type', '=', 'widget')
      ->execute();
    $this->assertArrayValuesEqual((array) $widgets, (array) $thingWidgets);
  }

  public function testFacadeUpdate(): void {
    $updated = TestGizmo::update(FALSE)
      ->addWhere('id', '>', 0)
      ->addValue('name', 'Updated')
      ->execute();
    $this->assertCount(1, $updated);

    $names = TestThing::get(FALSE)->addOrderBy('type')->execute()->column('name', 'type');
    $this->assertSame(['gizmo' => 'Updated', 'misc' => 'Misc1', 'widget' => 'Widget1'], $names);
  }

  public function testFacadeDelete() : void {
    // Deleting a thing of another type by id does nothing
    $widgetID = TestWidget::get(FALSE)->execute()->single()['id'];
    $this->assertCount(0, TestGizmo::delete(FALSE)->addWhere('id', '=', $widgetID)->execute());

    // Should only delete the Gizmos, not all the other Things
    $deleted = TestGizmo::delete(FALSE)
      ->addWhere('id', '>', 0)
      ->execute();
    $this->assertCount(1, $deleted);

    $this->assertCount(0, TestGizmo::get(FALSE)->execute());
    $this->assertSame(['Misc1', 'Widget1'], TestThing::get(FALSE)->addOrderBy('name')->execute()->column('name'));
  }

  public function testFacadeFields() : void {
    $thingFields = TestThing::getFields(FALSE)->execute()->column('name');
    $widgetFields = TestWidget::getFields(FALSE)->execute()->column('name');
    $gizmoFields = TestGizmo::getFields(FALSE)->execute()->column('name');

    // Widgets have the same fields as Things, except 'type' which is fixed
    $this->assertSame(array_values(array_diff($thingFields, ['type'])), $widgetFields);

    // Gizmos also don't have 'description', which TestGizmo's alterFields removes
    $this->assertArrayValuesEqual(['type', 'description'], array_diff($thingFields, $gizmoFields));

    // Given a facade's values, the parent has that facade's fields
    $getThingFields = fn(string $type) => TestThing::getFields(FALSE)->addValue('type', $type)->execute()->column('name');
    $this->assertNotContains('description', $getThingFields('gizmo'));
    $this->assertContains('description', $getThingFields('widget'));

    $gizmo = TestGizmo::get(FALSE)->execute()->single();
    $thingGizmo = TestThing::get(FALSE)->addWhere('type', '=', 'gizmo')->execute()->single();

    // Gizmo should not have description field
    $this->assertArrayNotHasKey('description', $gizmo);

    // but other than description, fields and values should be the same
    unset($thingGizmo['description']);
    $this->assertSame($thingGizmo, $gizmo);
  }

  /**
   * Custom groups can extend the parent entity, or one facade of it (as they can extend Contact or Individual).
   */
  public function testFacadeCustomFields(): void {
    // test_facades registers both TestThing and TestWidget in cg_extend_objects
    $thingGroup = $this->createTestEntity('CustomGroup', ['name' => 'Thing_Fields', 'title' => 'Thing Fields', 'extends' => 'TestThing'], 'thing');
    $this->createTestEntity('CustomField', ['custom_group_id' => $thingGroup['id'], 'name' => 'Colour', 'label' => 'Colour', 'html_type' => 'Text', 'data_type' => 'String'], 'colour');
    $widgetGroup = $this->createTestEntity('CustomGroup', ['name' => 'Widget_Fields', 'title' => 'Widget Fields', 'extends' => 'TestWidget'], 'widget');
    $this->createTestEntity('CustomField', ['custom_group_id' => $widgetGroup['id'], 'name' => 'Teeth', 'label' => 'Teeth', 'html_type' => 'Text', 'data_type' => 'Int'], 'teeth');

    $getCustomFields = function(string $entityName, array $values = []): array {
      $fields = civicrm_api4($entityName, 'getFields', ['checkPermissions' => FALSE, 'values' => $values, 'where' => [['type', '=', 'Custom']]])->column('name');
      sort($fields);
      return $fields;
    };
    // The parent has all custom fields, a facade has its parent's and its own
    $this->assertSame(['Thing_Fields.Colour', 'Widget_Fields.Teeth'], $getCustomFields('TestThing'));
    $this->assertSame(['Thing_Fields.Colour', 'Widget_Fields.Teeth'], $getCustomFields('TestWidget'));
    $this->assertSame(['Thing_Fields.Colour'], $getCustomFields('TestGizmo'));
    // Given values for a facade, the parent has that facade's custom fields
    $this->assertSame(['Thing_Fields.Colour'], $getCustomFields('TestThing', ['type' => 'gizmo']));
    $this->assertSame(['Thing_Fields.Colour', 'Widget_Fields.Teeth'], $getCustomFields('TestThing', ['type' => 'widget']));

    // Values are saved through the facade, and stored against the parent record
    $widgetID = TestWidget::create(FALSE)
      ->addValue('name', 'Widget2')
      ->addValue('Thing_Fields.Colour', 'red')
      ->addValue('Widget_Fields.Teeth', 12)
      ->execute()->single()['id'];
    $widget = TestWidget::get(FALSE)
      ->addSelect('Thing_Fields.Colour', 'Widget_Fields.Teeth')
      ->addWhere('id', '=', $widgetID)
      ->execute()->single();
    $this->assertSame('red', $widget['Thing_Fields.Colour']);
    $this->assertSame(12, $widget['Widget_Fields.Teeth']);
    $thing = TestThing::get(FALSE)
      ->addSelect('Widget_Fields.Teeth')
      ->addWhere('id', '=', $widgetID)
      ->execute()->single();
    $this->assertSame(12, $thing['Widget_Fields.Teeth']);
  }

  public function testParentSpecProvidersApplyToFacades(): void {
    // TestThingSpecProvider only applies to 'TestThing'
    $this->assertSame('WIDGET1', TestWidget::get(FALSE)->addSelect('name_upper')->execute()->single()['name_upper']);
    $this->assertSame('GIZMO1', TestGizmo::get(FALSE)->addSelect('name_upper')->execute()->single()['name_upper']);
    $this->assertContains('name_upper', TestWidget::getFields(FALSE)->execute()->column('name'));
  }

  /**
   * A facade's fields come from its parent, after the parent's civi.entity.fields listeners,
   * then civi.entity.fields is dispatched for the facade itself.
   */
  public function testFieldsEventsForFacade(): void {
    $genericEntities = [];
    $this->addTestListener('civi.entity.fields', function(GenericHookEvent $e) use (&$genericEntities) {
      $genericEntities[] = $e->entity;
    });
    $this->addTestListener('civi.entity.fields::TestThing', function(GenericHookEvent $e) {
      $e->fields['name']['title'] = 'Altered Name';
      $e->fields['new_field'] = ['title' => 'New Field', 'sql_type' => 'int', 'input_type' => 'Number'];
    });
    $this->addTestListener('civi.entity.fields::TestWidget', function(GenericHookEvent $e) {
      $e->fields['name']['title'] = 'Widget Name';
    });
    // Fields were cached when setUp() created records
    unset(\Civi::$statics['civi.entity.fields']);

    $widgetFields = \Civi::entity('TestWidget')->getFields();
    $gizmoFields = \Civi::entity('TestGizmo')->getFields();
    $this->assertSame(['TestThing', 'TestWidget', 'TestGizmo'], $genericEntities);

    // Changes to the parent's fields reach its facades, including new fields unless removed by alterFields
    $this->assertSame('Altered Name', $gizmoFields['name']['title']);
    $this->assertArrayHasKey('new_field', $widgetFields);
    $this->assertArrayHasKey('new_field', $gizmoFields);
    $this->assertArrayNotHasKey('description', $gizmoFields);

    // Changes to a facade's fields come after its parent's, and only affect that facade
    $this->assertSame('Widget Name', $widgetFields['name']['title']);
    $this->assertSame('Altered Name', \Civi::entity('TestThing')->getField('name')['title']);
  }

}
