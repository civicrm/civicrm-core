<?php

/**
 * Class CRM_Core_ManagedEntitiesTest
 * @group headless
 */
class CRM_Core_ManagedEntitiesTest extends CiviUnitTestCase {
  /**
   * @var \Civi\API\Kernel
   */
  protected $apiKernel;

  /**
   * @var \Civi\API\Provider\AdhocProvider
   */
  protected $adhocProvider;

  /**
   * @var array(string
   */
  protected $modules;

  protected $fixtures;

  public function setUp() {
    $this->useTransaction(TRUE);
    parent::setUp();
    $this->modules = [
      'one' => new CRM_Core_Module('com.example.one', TRUE),
      'two' => new CRM_Core_Module('com.example.two', TRUE),
    ];

    // Testing on drupal-demo fails because some extensions have mgd ents.
    CRM_Core_DAO::singleValueQuery('DELETE FROM civicrm_managed');

    $this->fixtures['com.example.one-foo'] = [
      'module' => 'com.example.one',
      'name' => 'foo',
      'entity' => 'CustomSearch',
      'params' => [
        'version' => 3,
        'class_name' => 'CRM_Example_One_Foo',
        'is_reserved' => 1,
      ],
    ];
    $this->fixtures['com.example.one-bar'] = [
      'module' => 'com.example.one',
      'name' => 'bar',
      'entity' => 'CustomSearch',
      'params' => [
        'version' => 3,
        'class_name' => 'CRM_Example_One_Bar',
        'is_reserved' => 1,
      ],
    ];
    $this->fixtures['com.example.one-CustomGroup'] = [
      'module' => 'com.example.one',
      'name' => 'CustomGroup',
      'entity' => 'CustomGroup',
      'params' => [
        'version' => 3,
        'name' => 'test_custom_group',
        'title' => 'Test custom group',
        'extends' => 'Individual',
      ],
    ];
    $this->fixtures['com.example.one-CustomField'] = [
      'module' => 'com.example.one',
      'name' => 'CustomField',
      'entity' => 'CustomField',
      'params' => [
        'version' => 3,
        'name' => 'test_custom_field',
        'label' => 'Test custom field',
        'custom_group_id' => 'test_custom_group',
        'data_type' => 'String',
        'html_type' => 'Text',
      ],
    ];

    $this->apiKernel = \Civi::service('civi_api_kernel');
    $this->adhocProvider = new \Civi\API\Provider\AdhocProvider(3, 'CustomSearch');
    $this->apiKernel->registerApiProvider($this->adhocProvider);
  }

  public function tearDown() {
    parent::tearDown();
    \Civi::reset();
  }

  /**
   * Set up an active module and, over time, the hook implementation changes
   * to (1) create 'foo' entity, (2) create 'bar' entity', (3) remove 'foo'
   * entity
   */
  public function testAddRemoveEntitiesModule_UpdateAlways_DeleteAlways() {
    $decls = [];

    // create first managed entity ('foo')
    $decls[] = $this->fixtures['com.example.one-foo'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook returns an extra managed entity ('bar')
    $decls[] = $this->fixtures['com.example.one-bar'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertEquals('CRM_Example_One_Bar', $bar['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');

    // and then hook changes its mind, removing 'foo' (first of two entities)
    unset($decls[0]);
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertTrue($foo === NULL);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertEquals('CRM_Example_One_Bar', $bar['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');

    // and then hook changes its mind, removing 'bar' (the last remaining entity)
    unset($decls[1]);
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertTrue($foo === NULL);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertTrue($bar === NULL);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');
  }

  /**
   * Set up an active module with one managed-entity and, over
   * time, the content of the entity changes
   */
  public function testModifyDeclaration_UpdateAlways() {
    $decls = [];

    // create first managed entity ('foo')
    $decls[] = $this->fixtures['com.example.one-foo'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook specification changes
    $decls[0]['params']['class_name'] = 'CRM_Example_One_Foobar';
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo2 = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foobar', $foo2['name']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_FooBar"');
    $this->assertEquals($foo['id'], $foo2['id']);
  }

  /**
   * Set up an active module with one managed-entity and, over
   * time, the content of the entity changes
   */
  public function testModifyDeclaration_UpdateNever() {
    $decls = [];

    // create first managed entity ('foo')
    $decls[] = array_merge($this->fixtures['com.example.one-foo'], [
      // Policy is to never update after initial creation
      'update' => 'never',
    ]);
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook specification changes
    $decls[0]['params']['class_name'] = 'CRM_Example_One_Foobar';
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo2 = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo2['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_FooBar"');
    $this->assertEquals($foo['id'], $foo2['id']);
  }

  /**
   * Set up an active module with one managed-entity using the
   * policy "cleanup=>never". When the managed-entity goes away,
   * ensure that the policy is followed (ie the entity is not
   * deleted).
   */
  public function testRemoveDeclaration_CleanupNever() {
    $decls = [];

    // create first managed entity ('foo')
    $decls[] = array_merge($this->fixtures['com.example.one-foo'], [
      'cleanup' => 'never',
    ]);
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, entity definition disappears; but we decide not to do any cleanup (per policy)
    $decls = [];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo2 = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo2['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertEquals($foo['id'], $foo2['id']);
  }

  /**
   * Set up an active module with one managed-entity using the
   * policy "cleanup=>never". When the managed-entity goes away,
   * ensure that the policy is followed (ie the entity is not
   * deleted).
   */
  public function testRemoveDeclaration_CleanupUnused() {
    $decls = [];

    // create first managed entity ('foo')
    $decls[] = array_merge($this->fixtures['com.example.one-foo'], [
      'cleanup' => 'unused',
    ]);
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // Override 'getrefcount' ==> The refcount is 1
    $this->adhocProvider->addAction('getrefcount', 'access CiviCRM', function ($apiRequest) {
      return civicrm_api3_create_success([
        [
          'name' => 'mock',
          'type' => 'mock',
          'count' => 1,
        ],
      ]);
    });

    // Later on, entity definition disappears; but we decide not to do any cleanup (per policy)
    $decls = [];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo2 = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo2['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertEquals($foo['id'], $foo2['id']);

    // Override 'getrefcount' ==> The refcount is 0
    $this->adhocProvider->addAction('getrefcount', 'access CiviCRM', function ($apiRequest) {
      return civicrm_api3_create_success([]);
    });

    // The entity definition disappeared and there's no reference; we decide to cleanup (per policy)
    $decls = [];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo3 = $me->get('com.example.one', 'foo');
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertTrue($foo3 === NULL);
  }

  /**
   * Setup an active module with a malformed entity declaration.
   */
  public function testInvalidDeclarationModule() {
    // create first managed entity ('foo')
    $decls = [];
    $decls[] = [
      // erroneous
      'module' => 'com.example.unknown',
      'name' => 'foo',
      'entity' => 'CustomSearch',
      'params' => [
        'version' => 3,
        'class_name' => 'CRM_Example_One_Foo',
        'is_reserved' => 1,
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    try {
      $me->reconcile();
      $this->fail('Expected exception when using invalid declaration');
    }
    catch (Exception $e) {
      // good
    }
  }

  /**
   * Setup an active module with a malformed entity declaration.
   */
  public function testMissingName() {
    // create first managed entity ('foo')
    $decls = [];
    $decls[] = [
      'module' => 'com.example.unknown',
      // erroneous
      'name' => NULL,
      'entity' => 'CustomSearch',
      'params' => [
        'version' => 3,
        'class_name' => 'CRM_Example_One_Foo',
        'is_reserved' => 1,
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    try {
      $me->reconcile();
      $this->fail('Expected exception when using invalid declaration');
    }
    catch (Exception $e) {
      // good
    }
  }

  /**
   * Setup an active module with a malformed entity declaration.
   */
  public function testMissingEntity() {
    // create first managed entity ('foo')
    $decls = [];
    $decls[] = [
      'module' => 'com.example.unknown',
      'name' => 'foo',
      // erroneous
      'entity' => NULL,
      'params' => [
        'version' => 3,
        'class_name' => 'CRM_Example_One_Foo',
        'is_reserved' => 1,
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    try {
      $me->reconcile();
      $this->fail('Expected exception when using invalid declaration');
    }
    catch (Exception $e) {
      // good
    }
  }

  /**
   * Setup an active module with an entity -- then disable and re-enable the
   * module
   */
  public function testDeactivateReactivateModule() {
    // create first managed entity ('foo')
    $decls = [];
    $decls[] = $this->fixtures['com.example.one-foo'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(1, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // now deactivate module, which has empty decls and which cascades to managed object
    $this->modules['one']->is_active = FALSE;
    $me = new CRM_Core_ManagedEntities($this->modules, []);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(0, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // and reactivate module, which again provides decls and which cascades to managed object
    $this->modules['one']->is_active = TRUE;
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(1, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
  }

  /**
   * Setup an active module with an entity -- then entirely uninstall the
   * module
   */
  public function testUninstallModule() {
    // create first managed entity ('foo')
    $decls = [];
    $decls[] = $this->fixtures['com.example.one-foo'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // then destroy module; note that decls go away
    unset($this->modules['one']);
    $me = new CRM_Core_ManagedEntities($this->modules, []);
    $me->reconcile();
    $fooNew = $me->get('com.example.one', 'foo');
    $this->assertTrue(NULL === $fooNew);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
  }

  public function testDependentEntitiesUninstallCleanly() {

    // Install a module with two dependent managed entities
    $decls = [];
    $decls[] = $this->fixtures['com.example.one-CustomGroup'];
    $decls[] = $this->fixtures['com.example.one-CustomField'];
    $me = new CRM_Core_ManagedEntities($this->modules, $decls);
    $me->reconcile();

    // Uninstall the module
    unset($this->modules['one']);
    $me = new CRM_Core_ManagedEntities($this->modules, []);
    $me->reconcile();

    // Ensure that no managed entities remain in the civicrm_managed
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_managed');

    // Ensure that com.example.one-CustomGroup is deleted
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_group WHERE name = "test_custom_group"');

    // Ensure that com.example.one-CustomField is deleted
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_field WHERE name = "test_custom_field"');

  }

}
