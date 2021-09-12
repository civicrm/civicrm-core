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
   * @var String[]
   */
  protected $modules;

  protected $fixtures;

  public function setUp(): void {
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

    $this->fixtures['com.example.one-Job'] = [
      'module' => 'com.example.one',
      'name' => 'Job',
      'entity' => 'Job',
      'params' => [
        'version' => 3,
        'name' => 'test_job',
        'run_frequency' => 'Daily',
        'api_entity' => 'Job',
        'api_action' => 'Get',
        'parameters' => '',
      ],
    ];
    $this->fixtures['com.example.one-Contact'] = [
      'module' => 'com.example.one',
      'name' => 'Contact',
      'entity' => 'Contact',
      'params' => [
        'version' => 3,
        'first_name' => 'Daffy',
        'last_name' => 'Duck',
        'contact_type' => 'Individual',
        'update' => 'never',
      ],
    ];

    $this->apiKernel = \Civi::service('civi_api_kernel');
    $this->adhocProvider = new \Civi\API\Provider\AdhocProvider(3, 'CustomSearch');
    $this->apiKernel->registerApiProvider($this->adhocProvider);
    $this->hookClass->setHook('civicrm_managed', [$this, 'hookManaged']);
  }

  public function tearDown(): void {
    parent::tearDown();
    \Civi::reset();
  }

  /**
   * @var array
   */
  protected $managedEntities = [];

  /**
   * Implements hook managed.
   *
   * @param array $entities
   */
  public function hookManaged(array &$entities): void {
    $entities = $this->managedEntities;
  }

  /**
   * Set up an active module and, over time, the hook implementation changes
   * to (1) create 'foo' entity, (2) create 'bar' entity', (3) remove 'foo'
   * entity
   */
  public function testAddRemoveEntitiesModule_UpdateAlways_DeleteAlways() {
    // create first managed entity ('foo')
    $this->managedEntities = [$this->fixtures['com.example.one-foo']];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook returns an extra managed entity ('bar')
    $this->managedEntities[] = $this->fixtures['com.example.one-bar'];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertEquals('CRM_Example_One_Bar', $bar['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');

    // and then hook changes its mind, removing 'foo' (first of two entities)
    unset($this->managedEntities[0]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertTrue($foo === NULL);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertEquals('CRM_Example_One_Bar', $bar['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');

    // and then hook changes its mind, removing 'bar' (the last remaining entity)
    unset($this->managedEntities[1]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertTrue($foo === NULL);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $bar = $me->get('com.example.one', 'bar');
    $this->assertNull($bar);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Bar"');
  }

  /**
   * Set up an active module with one managed-entity and, over
   * time, the content of the entity changes
   *
   * @throws \CRM_Core_Exception
   */
  public function testModifyDeclaration_UpdateAlways(): void {
    // create first managed entity ('foo')
    $this->managedEntities = [$this->fixtures['com.example.one-foo']];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook specification changes
    $this->managedEntities[0]['params']['class_name'] = 'CRM_Example_One_Foobar';
    $me = new CRM_Core_ManagedEntities($this->modules);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testModifyDeclaration_UpdateNever(): void {
    // create first managed entity ('foo')
    $this->managedEntities[] = array_merge($this->fixtures['com.example.one-foo'], [
      // Policy is to never update after initial creation
      'update' => 'never',
    ]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, hook specification changes
    $this->managedEntities[0]['params']['class_name'] = 'CRM_Example_One_Foobar';
    $me = new CRM_Core_ManagedEntities($this->modules);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testRemoveDeclaration_CleanupNever(): void {
    // create first managed entity ('foo')
    $this->managedEntities = [
      array_merge($this->fixtures['com.example.one-foo'], ['cleanup' => 'never']),
    ];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // later on, entity definition disappears; but we decide not to do any cleanup (per policy)
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testRemoveDeclaration_CleanupUnused(): void {
    // create first managed entity ('foo')
    $this->managedEntities = [array_merge($this->fixtures['com.example.one-foo'], ['cleanup' => 'unused'])];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo3 = $me->get('com.example.one', 'foo');
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
    $this->assertNull($foo3);
  }

  /**
   * Setup an active module with a malformed entity declaration.
   */
  public function testInvalidDeclarationModule(): void {
    // create first managed entity ('foo')
    $this->managedEntities = [
      [
        // erroneous
        'module' => 'com.example.unknown',
        'name' => 'foo',
        'entity' => 'CustomSearch',
        'params' => [
          'version' => 3,
          'class_name' => 'CRM_Example_One_Foo',
          'is_reserved' => 1,
        ],
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
  public function testMissingName(): void {
    $this->managedEntities = [
      [
        'module' => 'com.example.unknown',
        // erroneous
        'name' => NULL,
        'entity' => 'CustomSearch',
        'params' => [
          'version' => 3,
          'class_name' => 'CRM_Example_One_Foo',
          'is_reserved' => 1,
        ],
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
  public function testMissingEntity(): void {
    $this->managedEntities = [
      [
        'module' => 'com.example.unknown',
        'name' => 'foo',
        // erroneous
        'entity' => NULL,
        'params' => [
          'version' => 3,
          'class_name' => 'CRM_Example_One_Foo',
          'is_reserved' => 1,
        ],
      ],
    ];
    $me = new CRM_Core_ManagedEntities($this->modules);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeactivateReactivateModule(): void {
    $manager = CRM_Extension_System::singleton()->getManager();
    // Register the hook so we can check there is no effort to de-activate contact.
    $this->hookClass->setHook('civicrm_pre', [$this, 'preHook']);
    // create first managed entities ('foo' & Contact)
    $this->managedEntities = [$this->fixtures['com.example.one-foo'], $this->fixtures['com.example.one-Contact']];
    // Mock the contextual process info that would be added by CRM_Extension_Manager::install
    $manager->setProcessesForTesting(['com.example.one' => ['install']]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(1, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // now deactivate module, which has no declarations and which cascades to managed object
    $this->modules['one']->is_active = FALSE;
    // Mock the contextual process info that would be added by CRM_Extension_Manager::disable
    $manager->setProcessesForTesting(['com.example.one' => ['disable']]);
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(0, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // and reactivate module, which again provides decls and which cascades to managed object
    $this->modules['one']->is_active = TRUE;
    // Mock the contextual process info that would be added by CRM_Extension_Manager::enable
    $manager->setProcessesForTesting(['com.example.one' => ['enable']]);
    $this->managedEntities = [$this->fixtures['com.example.one-foo'], $this->fixtures['com.example.one-Contact']];

    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals(1, $foo['is_active']);
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // Special case: Job entities.
    //
    // First we repeat the above steps, but adding the context that
    // CRM_Extension_Manager adds when installing/enabling extensions.
    //
    // The behaviour should be as above.
    $this->managedEntities = [$this->fixtures['com.example.one-Job']];
    // Mock the contextual process info that would be added by CRM_Extension_Manager::install
    $manager->setProcessesForTesting(['com.example.one' => ['install']]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $job = $me->get('com.example.one', 'Job');
    $this->assertEquals(1, $job['is_active']);
    $this->assertEquals('test_job', $job['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_job WHERE name = "test_job"');
    // Reset context.
    $manager->setProcessesForTesting([]);

    // now deactivate module
    $this->modules['one']->is_active = FALSE;
    // Mock the contextual process info that would be added by CRM_Extension_Manager::disable
    $manager->setProcessesForTesting(['com.example.one' => ['disable']]);
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $job = $me->get('com.example.one', 'Job');
    $this->assertEquals(0, $job['is_active']);
    $this->assertEquals('test_job', $job['name']);
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_job WHERE name = "test_job"');

    // and reactivate module
    $this->modules['one']->is_active = TRUE;
    $this->managedEntities = [$this->fixtures['com.example.one-Job']];
    $me = new CRM_Core_ManagedEntities($this->modules);
    // Mock the contextual process info that would be added by CRM_Extension_Manager::enable
    $manager->setProcessesForTesting(['com.example.one' => ['enable']]);
    $me->reconcile();
    $job = $me->get('com.example.one', 'Job');
    $this->assertEquals(1, $job['is_active']);
    $this->assertEquals('test_job', $job['name']);
    $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_job WHERE name = "test_job"');

    // Currently: module enabled, job enabled.
    // Test that if we now manually disable the job, calling reconcile in a
    // normal flush situation does NOT re-enable it.
    // ... manually disable job.
    $this->callAPISuccess('Job', 'create', ['id' => $job['id'], 'is_active' => 0]);

    // ... now call reconcile in the context of a normal flush operation.
    // Mock the contextual process info - there would not be any
    $manager->setProcessesForTesting([]);
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $job = $me->get('com.example.one', 'Job');
    $this->assertEquals(0, $job['is_active'], "Job that was manually set inactive should not have been set active again, but it was.");
    $this->assertDBQuery(0, 'SELECT is_active FROM civicrm_job WHERE name = "test_job"');

    // Now call reconcile again, but in the context of the job's extension being installed/enabled. This should re-enable the job.
    foreach (['enable', 'install'] as $process) {
      // Manually disable the job
      $this->callAPISuccess('Job', 'create', ['id' => $job['id'], 'is_active' => 0]);
      // Mock the contextual process info that would be added by CRM_Extension_Manager::enable
      $manager->setProcessesForTesting(['com.example.one' => [$process]]);
      $me = new CRM_Core_ManagedEntities($this->modules);
      $me->reconcile();
      $job = $me->get('com.example.one', 'Job');
      $this->assertEquals(1, $job['is_active']);
      $this->assertEquals('test_job', $job['name']);
      $this->assertDBQuery(1, 'SELECT is_active FROM civicrm_job WHERE name = "test_job"');
    }

    // Reset context.
    $manager->setProcessesForTesting([]);
  }

  /**
   * Pre hook to test contact is not called on disable.
   *
   * @param string $op
   * @param string $objectName
   * @param int|null $id
   * @param array $params
   */
  public function preHook($op, $objectName, $id, $params): void {
    if ($op === 'edit' && $objectName === 'Individual') {
      $this->assertArrayNotHasKey('is_active', $params);
    }
  }

  /**
   * Setup an active module with an entity -- then entirely uninstall the
   * module
   *
   * @throws \CRM_Core_Exception
   */
  public function testUninstallModule(): void {
    $this->managedEntities = [$this->fixtures['com.example.one-foo']];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $foo = $me->get('com.example.one', 'foo');
    $this->assertEquals('CRM_Example_One_Foo', $foo['name']);
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');

    // then destroy module
    unset($this->modules['one']);
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();
    $fooNew = $me->get('com.example.one', 'foo');
    $this->assertNull($fooNew);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_option_value WHERE name = "CRM_Example_One_Foo"');
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testDependentEntitiesUninstallCleanly(): void {

    // Install a module with two dependent managed entities
    $this->managedEntities = [$this->fixtures['com.example.one-CustomGroup']];
    $this->managedEntities[] = $this->fixtures['com.example.one-CustomField'];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();

    // Uninstall the module
    unset($this->modules['one']);
    $this->managedEntities = [];
    $me = new CRM_Core_ManagedEntities($this->modules);
    $me->reconcile();

    // Ensure that no managed entities remain in the civicrm_managed
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_managed');

    // Ensure that com.example.one-CustomGroup is deleted
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_group WHERE name = "test_custom_group"');

    // Ensure that com.example.one-CustomField is deleted
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_custom_field WHERE name = "test_custom_field"');

  }

}
