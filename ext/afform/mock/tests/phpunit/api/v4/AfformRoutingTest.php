<?php

/**
 * Ensure that the routes created by Afform are working.
 * @group e2e
 */
class api_v4_AfformRoutingTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\EndToEndInterface {

  protected $formName = 'mockPage';

  public static function setUpBeforeClass() {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  public function setUp() {
    parent::setUp();
    Civi\Api4\Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
  }

  public function tearDown() {
    parent::tearDown();
    Civi\Api4\Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
  }

  public function testChangingPermissions() {
    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    $result = $http->get($url('civicrm/mock-page'));
    $this->assertNotAuthorized($result);

    Civi\Api4\Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('permission', CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION)
      ->execute();

    $result = $http->get($url('civicrm/mock-page'));
    $this->assertOpensPage($result, 'mock-page');
  }

  public function testChangingPath() {
    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    Civi\Api4\Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('permission', CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION)
      ->execute();

    $this->assertOpensPage($http->get($url('civicrm/mock-page')), 'mock-page');
    $this->assertNotAuthorized($http->get($url('civicrm/mock-page-renamed')));

    Civi\Api4\Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('server_route', 'civicrm/mock-page-renamed')
      ->execute();

    $this->assertNotAuthorized($http->get($url('civicrm/mock-page')));
    $this->assertOpensPage($http->get($url('civicrm/mock-page-renamed')), 'mock-page');
  }

  /**
   * @param $result
   */
  private function assertNotAuthorized(Psr\Http\Message\ResponseInterface $result) {
    $contents = $result->getBody()->getContents();
    $this->assertEquals(403, $result->getStatusCode());
    $this->assertRegExp(';You are not authorized to access;', $contents);
    $this->assertNotRegExp(';afform":\{"open":".*"\};', $contents);
  }

  /**
   * @param $result
   * @param string $directive
   *   The name of the directive which auto-opens.
   */
  private function assertOpensPage(Psr\Http\Message\ResponseInterface $result, $directive) {
    $contents = $result->getBody()->getContents();
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertNotRegExp(';You are not authorized to access;', $contents);
    $this->assertRegExp(';afform":\{"open":"' . preg_quote($directive, ';') . '"\};', $contents);
  }

  public function testPublicCreateAllowed() {
    $initialMaxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');
    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    $this->createPublicForm();

    $r = md5(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $query = [
      'params' => json_encode(['name' => $this->formName, 'args' => [], 'values' => ['me' => $me]]),
    ];

    $response = $http->post($url('civicrm/ajax/api4/Afform/submit', $query), ['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
    $this->assertEquals(200, $response->getStatusCode());
    $contact = Civi\Api4\Contact::get(FALSE)->addWhere('first_name', '=', 'Firsty' . $r)->execute()->first();
    $this->assertEquals('Firsty' . $r, $contact['first_name']);
    $this->assertEquals('Lasty' . $r, $contact['last_name']);
    $this->assertTrue($contact['id'] > $initialMaxId);
  }

  public function testPublicEditDisallowed() {
    $contact = Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'first_name' => 'FirstBegin',
        'last_name' => 'LastBegin',
        'contact_type' => 'Individual',
      ])
      ->execute()
      ->first();

    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    $this->createPublicForm();

    $r = md5(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['id'] = $contact['id'];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $query = [
      'params' => json_encode(['name' => $this->formName, 'args' => [], 'values' => ['me' => $me]]),
    ];

    $response = $http->post($url('civicrm/ajax/api4/Afform/submit', $query), ['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);

    // FIXME: The current behavior is {status=500,body='[]'} ... but status=403 probably makes more sense.
    $this->assertEquals(500, $response->getStatusCode());
    $get = Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $contact['id'])->execute()->first();
    // Contact hasn't changed
    $this->assertEquals('FirstBegin', $get['first_name']);
    $this->assertEquals('LastBegin', $get['last_name']);
    // No other contacts were created or edited with the requested value.
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE first_name=%1', [1 => ["Firsty{$r}", 'String']]));
  }

  private function createPublicForm():void {
    $defaults = [
      'title' => 'My form',
      'name' => $this->formName,
      'layout' => '
<af-form ctrl="modelListCtrl">
  <af-entity type="Contact" data="{contact_type: \'Individual\'}" name="me" label="Myself" url-autofill="1" autofill="user" />
  <fieldset af-fieldset="me">
      <af-field name="first_name" />
      <af-field name="last_name" />
  </fieldset>
</af-form>',
      'permission' => '@afformGeneric:public',
    ];
    Civi\Api4\Afform::create()
      ->setCheckPermissions(FALSE)
      ->setLayoutFormat('html')
      ->setValues($defaults)
      ->execute();
  }

}
