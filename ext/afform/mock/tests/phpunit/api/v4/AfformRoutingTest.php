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

}
