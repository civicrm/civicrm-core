<?php
namespace Civi\API\Provider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * This class ensures that the Doctrine<->API bindings support their major features. They do not
 *perform detailed verification of each entity's API.
 *
 * As an example, the tests manipulate the Worldregion entity.
 */
class DoctrineCrudProviderTest extends \CiviUnitTestCase {

  /**
   * @var array(string $table => int $id)
   */
  private $maxIds;

  /**
   * @var array (string $name => mixed $value);
   */
  private $fixtures;

  function setUp() {
    $this->_apiversion = 4;
    $this->quickCleanup(array('civicrm_contact'));
    $this->maxIds['civicrm_worldregion'] = \CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_worldregion');
    $this->maxIds['civicrm_location_type'] = \CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_location_type');
    $this->fixtures['Middle East'] = array(
      'id' => 3,
      'name' => 'Middle East and North Africa',
    );

    $this->setPermissions(array('access AJAX API', 'access CiviCRM', 'administer CiviCRM'));
  }

  function tearDown() {
    foreach ($this->maxIds as $table => $maxId) {
      \CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE id > %1", array(
        1 => array($maxId, 'Positive')
      ));
    }
    parent::tearDown();
  }

  function testGetItem_ById() {
    $result = $this->callAPISuccess('Worldregion', 'get', array('id' => 3));
    $this->assertEquals($this->fixtures['Middle East'], $result['values'][3]);
  }

  function testGetItem_ByName() {
    $result = $this->callAPISuccess('Worldregion', 'get', array('name' => 'Middle East and North Africa'));
    $this->assertEquals($this->fixtures['Middle East'], $result['values'][3]);
  }

  function testGetItem_ByRelationId() {
    $contacts = array();
    for ($i = 0; $i < 4; $i++) {
      $contacts[] = civicrm_api3('Contact', 'create', array(
        'contact_type' => 'Individual',
        'first_name' => "Example{$i}",
        'last_name' => "Example{$i}",
        'api.email.create' => array(
          array('location_type_id' => 'Home', 'email' => "home{$i}@example.com"),
          array('location_type_id' => 'Work', 'email' => "work{$i}@example.com"),
        ),
      ));
    }
    $result = $this->callAPISuccess('Email', 'get', array('contact' => $contacts[2]['id']));
    $emails = \CRM_Utils_Array::collect('email', array_values($result['values']));
    $this->assertEquals(array('home2@example.com', 'work2@example.com'), $emails);
  }

  function testGetItem_InsufficientPermission() {
    $this->setPermissions(array('access AJAX API')); // missing: access CiviCRM
    $result = $this->callAPIFailure('Worldregion', 'get', array('id' => 3));
    $this->assertEquals('unauthorized', $result['error_code']);
  }

  function testGetCollection() {
    $result = $this->callAPISuccess('Worldregion', 'get', array());
    $this->assertEquals(6, count($result['values']));
    foreach ($result['values'] as $country) {
      $this->assertTrue(isset($country['id']) && is_numeric($country['id']));
      $this->assertTrue(isset($country['name']) && is_string($country['name']));
    }
    $this->assertContains($this->fixtures['Middle East'], $result['values']);
  }

  function testGetCollection_InsufficientPermission() {
    $this->setPermissions(array('access AJAX API')); // missing: access CiviCRM
    $result = $this->callAPIFailure('Worldregion', 'get', array());
    $this->assertEquals('unauthorized', $result['error_code']);
  }

  function testCreateItem() {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $result = $this->callAPISuccess('Worldregion', 'create', array(
      'name' => 'Middle Earth',
    ));
    $this->assertEquals(1, count($result['values']));

    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testCreateItem_InsufficientPermission() {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $this->setPermissions(array('access AJAX API', 'access CiviCRM')); // missing: administer CiviCRM
    $result = $this->callAPIFailure('Worldregion', 'create', array(
      'name' => 'Middle Earth',
    ));
    $this->assertEquals('unauthorized', $result['error_code']);

    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testDeleteItem() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $result = $this->callAPISuccess('Worldregion', 'delete', array('id' => $region->getId()));
    $this->assertEquals(0, count($result['values']));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testDeleteItem_InsufficientPermission() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $this->setPermissions(array('access AJAX API', 'access CiviCRM')); // missing: administer CiviCRM
    $result = $this->callAPIFailure('Worldregion', 'delete', array(
      'id' => $region->getId()
    ));
    $this->assertEquals('unauthorized', $result['error_code']);

    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testUpdateItem() {
    $item = $this->createCampusLocationType();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $id = $item->getId();

    $result = $this->callAPISuccess('LocationType', 'create', array(
      'id' => $id,
      'displayName' => 'On Campus',
    ));

    $this->assertEquals(1, count($result['values']));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "On Campus"');
  }

  function testUpdateItem_InsufficientPermission() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "OhNoes"');

    $this->setPermissions(array('access AJAX API', 'access CiviCRM')); // missing: administer CiviCRM
    $result = $this->callAPIFailure('Worldregion', 'create', array(
      'id' => $region->getId(),
      'name' => 'OhNoes',
    ));
    $this->assertEquals('unauthorized', $result['error_code']);

    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "OhNoes"');
  }

  function testUpdateItem_InvalidID() {
    $result = $this->callAPIFailure('Worldregion', 'create', array(
      'id' => 123456789,
      'name' => 'OhNoes',
    ));
    $this->assertEquals('not-found', $result['error_code']);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "OhNoes"');
  }

  /**
   * @param array<string> $perms
   */
  function setPermissions($perms) {
    $config = \CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = $perms;
  }

  /**
   * @return \Civi\Core\LocationType
   */
  function createCampusLocationType() {
    $item = new \Civi\Core\LocationType();
    $item->setName("Campus");
    $item->setDescription("On-campus address");
    $item->setDisplayName("Campus");
    $item->setIsActive(TRUE);
    $item->setVcardName('CAMPUS');
    $em = \CRM_DB_EntityManager::singleton();
    $em->persist($item);
    $em->flush();
    return $item;
  }

  /**
   * @return \Civi\Core\Worldregion
   */
  function createMiddleEarth() {
    $region = new \Civi\Core\Worldregion();
    $region->setName('Middle Earth');
    $em = \CRM_DB_EntityManager::singleton();
    $em->persist($region);
    $em->flush();
    return $region;
  }

}