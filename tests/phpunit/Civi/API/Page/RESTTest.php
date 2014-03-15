<?php
namespace Civi\API\Page;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * This class ensures that the Doctrine-REST bindings support their major features, such
 * as CRUD and JSON/XML-encoding. They do not perform detailed verification of each entity's API.
 *
 * As an example, the tests manipulate the Worldregion entity.
 */
class RESTTest extends \CiviUnitTestCase {

  /**
   * @var \Civi\API\Security
   */
  private $apiSecurity;

  /**
   * @var array (string $perm) [Mocked] List of permissions granted to the current user
   */
  private $apiSecurityGrantedPermissions;

  /**
   * @var array(string $table => int $id)
   */
  private $maxIds;

  /**
   * @var array (string $name => mixed $value);
   */
  private $fixtures;

  function setUp() {
    $this->maxIds['civicrm_worldregion'] = \CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_worldregion');
    $this->maxIds['civicrm_location_type'] = \CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_location_type');
    $this->fixtures['Middle East'] = array(
      'id' => 3,
      'name' => 'Middle East and North Africa',
      '_links' => array(
        'self' => array(
          'href' => $this->url('civicrm/rest/world-region/3'),
        ),
      ),
    );
    $test = $this;
    $this->apiSecurityGrantedPermissions = array('access AJAX API', 'access CiviCRM', 'administer CiviCRM');
    $this->apiSecurity = new \Civi\API\Security(
      \Civi\Core\Container::singleton()->get('annotation_reader'),
      function ($perm) use ($test) {
        return FALSE !== array_search($perm, $test->apiSecurityGrantedPermissions);
      }
    );
  }

  function tearDown() {
    foreach ($this->maxIds as $table => $maxId) {
      \CRM_Core_DAO::executeQuery("DELETE FROM $table WHERE id > %1", array(
        1 => array($maxId, 'Positive')
      ));
    }
    parent::tearDown();
  }

  function testGetItem_DefaultJson() {
    $response = $this->request('GET', 'civicrm/rest/world-region/3');
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $expected = array($this->fixtures['Middle East']);
    $this->assertEquals($expected, $actual);
  }

  function testGetItem_Xml() {
    $response = $this->request('GET', 'civicrm/rest/world-region/3', array(
      'Accept' => 'application/xml'
    ));
    $this->assertResponse($response, 200, 'application/xml');

    /** @var $xml \SimpleXMLElement */
    $xml = simplexml_load_string($response->getContent());
    $this->assertEquals(1, count($xml->children()));
    $this->assertEquals(3, (string) $xml->entry->id);
    $this->assertEquals('Middle East and North Africa', (string) $xml->entry->name);
    $this->assertEquals('self', (string) $xml->entry->link['rel']);
    $this->assertEquals($this->url('civicrm/rest/world-region/3'), (string) $xml->entry->link['href']);
  }

  function testGetItem_InsufficientPermission_DefaultJson() {
    $this->apiSecurityGrantedPermissions = array('access AJAX API'); // missing: access CiviCRM
    $response = $this->request('GET', 'civicrm/rest/world-region/3');
    $this->assertErrorResponse($response, 403, 'application/json');
  }

  function testGetCollection_DefaultJson() {
    $response = $this->request('GET', 'civicrm/rest/world-region');
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(6, count($actual));
    foreach ($actual as $country) {
      $this->assertTrue(isset($country['id']) && is_numeric($country['id']));
      $this->assertTrue(isset($country['name']));
      $this->assertTrue(isset($country['_links']));
    }
    $this->assertContains($this->fixtures['Middle East'], $actual);
  }

  function testGetCollection_InsufficientPermission_DefaultJson() {
    $this->apiSecurityGrantedPermissions = array('access AJAX API'); // missing: access CiviCRM
    $response = $this->request('GET', 'civicrm/rest/world-region');
    $this->assertErrorResponse($response, 403, 'application/json');
  }

  function testGetCollection_Xml() {
    $response = $this->request('GET', 'civicrm/rest/world-region', array(
      'Accept' => 'application/xml'
    ));
    $this->assertResponse($response, 200, 'application/xml');
    $actual = json_decode($response->getContent(), TRUE);

    /** @var $xml \SimpleXMLElement */
    $xml = simplexml_load_string($response->getContent());
    $this->assertEquals(6, count($xml->children()));
    $mideastCount = 0;
    foreach ($xml->children() as $entry) {
      $this->assertTrue(\CRM_Utils_Rule::positiveInteger((string) $entry->id));
      $this->assertNotEmpty((string) $entry->name);
      if ('Middle East and North Africa' == (string) $entry->name) {
        $mideastCount++;
      }
      $this->assertEquals('self', (string) $entry->link['rel']);
      $this->assertNotEmpty((string) $entry->link['href']);
    }
    $this->assertEquals(1, $mideastCount);
  }

  function testGetCollectionFiltered_DefaultJson() {
    $response = $this->request('GET', 'civicrm/rest/world-region', array(), array('name' => 'Middle East and North Africa'));
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $expected = array($this->fixtures['Middle East']);
    $this->assertEquals($expected, $actual);
  }

  function testGetCollectionFilteredEmpty_DefaultJson() {
    $response = $this->request('GET', 'civicrm/rest/world-region', array(), array(
      'name' => 'Middle Earth',
    ));
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(0, count($actual));
  }

  function testCreateItem_DefaultJson() {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $response = $this->request('POST', 'civicrm/rest/world-region', array(), array(), json_encode(array(
      'name' => 'Middle Earth',
    )));
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(1, count($actual));

    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testCreateItem_InsufficientPermission_DefaultJson() {
    $this->apiSecurityGrantedPermissions = array('access AJAX API', 'access CiviCRM'); // missing: administer CiviCRM
    $response = $this->request('POST', 'civicrm/rest/world-region', array(), array(), json_encode(array(
      'name' => 'Middle Earth',
    )));
    $this->assertErrorResponse($response, 403, 'application/json');
  }

  function testCreateItem_Xml() {
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $response = $this->request('POST', 'civicrm/rest/world-region',
      array(
        'Content-Type' => 'application/xml',
        'Accept' => 'application/xml',
      ),
      array(),
      '<item><name>Middle Earth</name></item>'
    );
    $this->assertResponse($response, 200, 'application/xml');

    /** @var $xml \SimpleXMLElement */
    $xml = simplexml_load_string($response->getContent());
    $this->assertEquals(1, count($xml->children()));
    $this->assertTrue(\CRM_Utils_Rule::positiveInteger((string) $xml->entry->id));
    $this->assertEquals('Middle Earth', (string) $xml->entry->name);
    $this->assertEquals('self', (string) $xml->entry->link['rel']);
    $this->assertEquals($this->url('civicrm/rest/world-region/' . $xml->entry->id), (string) $xml->entry->link['href']);

    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testUpdateItem_PATCH_DefaultJson() {
    $item = $this->createCampusLocationType();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $id = $item->getId();

    $response = $this->request('PATCH', 'civicrm/rest/location-type/' . $item->getId(), array(), array(),
      json_encode(array(
        'display_name' => 'On Campus',
      ))
    );
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(1, count($actual));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "On Campus" AND name = "Campus" AND id = %1', array(1 => array($id, 'Positive')));
  }

  function testUpdateItem_POST_DefaultJson() {
    $item = $this->createCampusLocationType();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $id = $item->getId();

    $response = $this->request('POST', 'civicrm/rest/location-type/' . $item->getId(), array(), array(),
      json_encode(array(
        'display_name' => 'On Campus',
      ))
    );
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(1, count($actual));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "On Campus" AND name = "Campus" AND id = %1', array(1 => array($id, 'Positive')));
  }

  function testUpdateItem_PATCH_OptionalId_DefaultJson() {
    $item = $this->createCampusLocationType();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $id = $item->getId();

    $response = $this->request('PATCH', 'civicrm/rest/location-type/' . $item->getId(), array(), array(),
      json_encode(array(
        'id' => $item->getId(),
        'display_name' => 'On Campus',
      ))
    );
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(1, count($actual));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "Campus"');
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_location_type WHERE display_name = "On Campus" AND name = "Campus" AND id = %1', array(1 => array($id, 'Positive')));
  }

  // TODO testUpdateItem_Xml

  function testUpdateItem_PATCH_InsufficientPermission_DefaultJson() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $this->apiSecurityGrantedPermissions = array('access AJAX API', 'access CiviCRM'); // missing: administer CiviCRM
    $response = $this->request('PATCH', 'civicrm/rest/world-region/' . $region->getId());
    $this->assertErrorResponse($response, 403, 'application/json');
  }

  function testUpdateItem_PATCH_MismatchedId_DefaultJson() {
    $firstLocation = \CRM_DB_EntityManager::singleton()->find('Civi\Core\LocationType', 1);
    $originalLocationName = $firstLocation->getDisplayName();
    $campusLocation = $this->createCampusLocationType();

    $this->assertDBQuery($originalLocationName, 'SELECT display_name FROM civicrm_location_type WHERE id = %1', array(1 => array($firstLocation->getId(), 'Positive'))); // unchanged
    $this->assertDBQuery('Campus', 'SELECT display_name FROM civicrm_location_type WHERE id = %1', array(1 => array($campusLocation->getId(), 'Positive')));

    $response = $this->request('PATCH', 'civicrm/rest/location-type/' . $campusLocation->getId(), array(), array(),
      json_encode(array(
        'id' => $firstLocation->getId(), // Mismatched
        'display_name' => 'On Campus',
      ))
    );
    $this->assertErrorResponse($response, 500, 'application/json');

    $this->assertDBQuery($originalLocationName, 'SELECT display_name FROM civicrm_location_type WHERE id = %1', array(1 => array($firstLocation->getId(), 'Positive'))); // unchanged
    $this->assertDBQuery('Campus', 'SELECT display_name FROM civicrm_location_type WHERE id = %1', array(1 => array($campusLocation->getId(), 'Positive'))); // unchanged
  }

  function testUpdateInvalidItem_PATCH_DefaultJson() {
    $response = $this->request('PATCH', 'civicrm/rest/location-type/123456789', array(), array(),
      json_encode(array(
        'display_name' => 'Does Not Exist',
      ))
    );
    $this->assertErrorResponse($response, 404, 'application/json');
  }

  function testDeleteItem_DefaultJson() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $response = $this->request('DELETE', 'civicrm/rest/world-region/' . $region->getId());
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(0, count($actual));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  function testDeleteItem_InsufficientPermission_DefaultJson() {
    $region = $this->createMiddleEarth();
    $this->assertDBQuery(1, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');

    $this->apiSecurityGrantedPermissions = array('access AJAX API', 'access CiviCRM'); // missing: administer CiviCRM
    $response = $this->request('DELETE', 'civicrm/rest/world-region/' . $region->getId());
    $this->assertErrorResponse($response, 403, 'application/json');
  }

  function testDeleteInvalidItem_DefaultJson() {
    $response = $this->request('DELETE', 'civicrm/rest/world-region/123456789012345');
    $this->assertResponse($response, 200, 'application/json');
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertEquals(0, count($actual));
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_worldregion WHERE name = "Middle Earth"');
  }

  /**
   * @param string $method
   * @param string $path
   * @return Response
   */
  function request($method, $path, $headers = array(), $query = array(), $content = NULL) {
    // array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null
    $request = new Request($query, array(), array(), array(), array(), array(), $content);
    $request->setMethod($method);
    foreach ($headers as $key => $value) {
      $request->headers->set($key, $value);
    }

    $page = new REST(NULL, NULL, NULL, $this->apiSecurity);
    $page->request = $request;
    $page->urlPath = explode('/', $path);
    $result = $page->run();

    // Need to simulate a flush because we're not doing a full request?
    \CRM_DB_EntityManager::singleton()->flush();

    return $result;
  }

  function url($path, $query = NULL, $absolute = TRUE) {
    $r = \CRM_Utils_System::url($path, $query, $absolute);
    $this->assertContains($path, $r);
    return $r;
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

  function assertResponse(Response $response, $expectedCode, $expectedMimeType) {
    $this->assertEquals($expectedCode, $response->getStatusCode());
    $this->assertEquals($expectedMimeType, $response->headers->get('Content-type'));
  }

  function assertErrorResponse(Response $response, $expectedCode, $expectedMimeType) {
    $this->assertEquals($expectedCode, $response->getStatusCode());
    $this->assertEquals($expectedMimeType, $response->headers->get('Content-type'));
    $actual = json_decode($response->getContent(), TRUE);
    $this->assertTrue(isset($actual['error']) && is_string($actual['error']));
  }
}