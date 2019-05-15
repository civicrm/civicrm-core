<?php
namespace Civi\API\Subscriber;

use Civi\API\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 */
class DynamicFKAuthorizationTest extends \CiviUnitTestCase {
  const FILE_WIDGET_ID = 10;

  const FILE_FORBIDDEN_ID = 11;

  const FILE_UNDELEGATED_ENTITY = 12;

  const WIDGET_ID = 20;

  const FORBIDDEN_ID = 30;

  /**
   * @var \Symfony\Component\EventDispatcher\EventDispatcher
   */
  public $dispatcher;

  /**
   * @var \Civi\API\Kernel
   */
  public $kernel;

  protected function setUp() {
    parent::setUp();
    \CRM_Core_DAO_AllCoreTables::init(TRUE);

    \CRM_Core_DAO_AllCoreTables::registerEntityType('FakeFile', 'CRM_Fake_DAO_FakeFile', 'fake_file');
    $fileProvider = new \Civi\API\Provider\StaticProvider(
      3,
      'FakeFile',
      array('id', 'entity_table', 'entity_id'),
      array(),
      array(
        array('id' => self::FILE_WIDGET_ID, 'entity_table' => 'fake_widget', 'entity_id' => self::WIDGET_ID),
        array('id' => self::FILE_FORBIDDEN_ID, 'entity_table' => 'fake_forbidden', 'entity_id' => self::FORBIDDEN_ID),
      )
    );

    \CRM_Core_DAO_AllCoreTables::registerEntityType('Widget', 'CRM_Fake_DAO_Widget', 'fake_widget');
    $widgetProvider = new \Civi\API\Provider\StaticProvider(3, 'Widget',
      array('id', 'title'),
      array(),
      array(
        array('id' => self::WIDGET_ID, 'title' => 'my widget'),
      )
    );

    \CRM_Core_DAO_AllCoreTables::registerEntityType('Forbidden', 'CRM_Fake_DAO_Forbidden', 'fake_forbidden');
    $forbiddenProvider = new \Civi\API\Provider\StaticProvider(
      3,
      'Forbidden',
      array('id', 'label'),
      array(
        'create' => \CRM_Core_Permission::ALWAYS_DENY_PERMISSION,
        'get' => \CRM_Core_Permission::ALWAYS_DENY_PERMISSION,
        'delete' => \CRM_Core_Permission::ALWAYS_DENY_PERMISSION,
      ),
      array(
        array('id' => self::FORBIDDEN_ID, 'label' => 'my forbidden'),
      )
    );

    $this->dispatcher = new EventDispatcher();
    $this->kernel = new Kernel($this->dispatcher);
    $this->kernel
      ->registerApiProvider($fileProvider)
      ->registerApiProvider($widgetProvider)
      ->registerApiProvider($forbiddenProvider);
    $this->dispatcher->addSubscriber(new DynamicFKAuthorization(
      $this->kernel,
      'FakeFile',
      array('create', 'get'),
      // Given a file ID, determine the entity+table it's attached to.
      "select
      case %1
        when " . self::FILE_WIDGET_ID . " then 1
        when " . self::FILE_FORBIDDEN_ID . " then 1
        else 0
      end as is_valid,
      case %1
        when " . self::FILE_WIDGET_ID . " then 'fake_widget'
        when " . self::FILE_FORBIDDEN_ID . " then 'fake_forbidden'
        else null
      end as entity_table,
      case %1
        when " . self::FILE_WIDGET_ID . " then " . self::WIDGET_ID . "
        when " . self::FILE_FORBIDDEN_ID . " then " . self::FORBIDDEN_ID . "
        else null
      end as entity_id
      ",
      // Get a list of custom fields (field_name,table_name,extends)
      "select",
      array('fake_widget', 'fake_forbidden')
    ));
  }

  protected function tearDown() {
    parent::tearDown();
    \CRM_Core_DAO_AllCoreTables::init(TRUE);
  }

  /**
   * @return array
   */
  public function okDataProvider() {
    $cases = array();

    $cases[] = array('Widget', 'create', array('id' => self::WIDGET_ID));
    $cases[] = array('Widget', 'get', array('id' => self::WIDGET_ID));

    $cases[] = array('FakeFile', 'create', array('id' => self::FILE_WIDGET_ID));
    $cases[] = array('FakeFile', 'get', array('id' => self::FILE_WIDGET_ID));
    $cases[] = array(
      'FakeFile',
      'create',
      array('entity_table' => 'fake_widget', 'entity_id' => self::WIDGET_ID),
    );

    return $cases;
  }

  /**
   * @return array
   */
  public function badDataProvider() {
    $cases = array();

    $cases[] = array('Forbidden', 'create', array('id' => self::FORBIDDEN_ID), '/Authorization failed/');
    $cases[] = array('Forbidden', 'get', array('id' => self::FORBIDDEN_ID), '/Authorization failed/');

    $cases[] = array('FakeFile', 'create', array('id' => self::FILE_FORBIDDEN_ID), '/Authorization failed/');
    $cases[] = array('FakeFile', 'get', array('id' => self::FILE_FORBIDDEN_ID), '/Authorization failed/');

    $cases[] = array('FakeFile', 'create', array('entity_table' => 'fake_forbidden'), '/Authorization failed/');
    $cases[] = array('FakeFile', 'get', array('entity_table' => 'fake_forbidden'), '/Authorization failed/');

    $cases[] = array(
      'FakeFile',
      'create',
      array('entity_table' => 'fake_forbidden', 'entity_id' => self::FORBIDDEN_ID),
      '/Authorization failed/',
    );
    $cases[] = array(
      'FakeFile',
      'get',
      array('entity_table' => 'fake_forbidden', 'entity_id' => self::FORBIDDEN_ID),
      '/Authorization failed/',
    );

    $cases[] = array(
      'FakeFile',
      'create',
      array(),
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'entity_table/",
    );
    $cases[] = array(
      'FakeFile',
      'get',
      array(),
      "/Mandatory key\\(s\\) missing from params array: 'id' or 'entity_table/",
    );

    $cases[] = array('FakeFile', 'create', array('entity_table' => 'unknown'), '/Unrecognized target entity/');
    $cases[] = array('FakeFile', 'get', array('entity_table' => 'unknown'), '/Unrecognized target entity/');

    // We should be allowed to lookup files for fake_widgets, but we need an ID.
    $cases[] = array('FakeFile', 'get', array('entity_table' => 'fake_widget'), '/Missing entity_id/');

    return $cases;
  }

  /**
   * @param $entity
   * @param $action
   * @param array $params
   * @dataProvider okDataProvider
   */
  public function testOk($entity, $action, $params) {
    $params['version'] = 3;
    $params['debug'] = 1;
    $params['check_permissions'] = 1;
    $result = $this->kernel->run($entity, $action, $params);
    $this->assertFalse((bool) $result['is_error'], print_r(array(
      '$entity' => $entity,
      '$action' => $action,
      '$params' => $params,
      '$result' => $result,
    ), TRUE));
  }

  /**
   * @param $entity
   * @param $action
   * @param array $params
   * @param $expectedError
   * @dataProvider badDataProvider
   */
  public function testBad($entity, $action, $params, $expectedError) {
    $params['version'] = 3;
    $params['debug'] = 1;
    $params['check_permissions'] = 1;
    $result = $this->kernel->run($entity, $action, $params);
    $this->assertTrue((bool) $result['is_error'], print_r(array(
      '$entity' => $entity,
      '$action' => $action,
      '$params' => $params,
      '$result' => $result,
    ), TRUE));
    $this->assertRegExp($expectedError, $result['error_message']);
  }

  /**
   * Test whether trusted API calls bypass the permission check
   *
   */
  public function testNotDelegated() {
    $entity = 'FakeFile';
    $action = 'create';
    $params = [
      'entity_id' => self::FILE_UNDELEGATED_ENTITY,
      'entity_table' => 'civicrm_membership',
      'version' => 3,
      'debug' => 1,
      'check_permissions' => 1,
    ];
    // run with permission check
    $result = $this->kernel->run('FakeFile', 'create', $params);
    $this->assertTrue((bool) $result['is_error'], 'Undelegated entity with check_permissions = 1 should fail');
    $this->assertRegExp('/Unrecognized target entity table \(civicrm_membership\)/', $result['error_message']);
    // repeat without permission check
    $params['check_permissions'] = 0;
    $result = $this->kernel->run('FakeFile', 'create', $params);
    $this->assertFalse((bool) $result['is_error'], 'Undelegated entity with check_permissions = 0 should succeed');
  }

}
