<?php
namespace api\v4\Afform;

use Civi\Api4\Afform;

/**
 * Ensure that the routes created by Afform are working.
 * @group e2e
 */
class AfformRoutingTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\EndToEndInterface {

  protected $formName = 'mockPage';

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->install(['org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
  }

  public function tearDown(): void {
    parent::tearDown();
    Afform::revert()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->execute();
  }

  public function testChangingPermissions(): void {
    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return \CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    $result = $http->get($url('civicrm/mock-page'));
    $this->assertNotAuthorized($result, 'mock-page');

    Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('permission', \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION)
      ->execute();

    $result = $http->get($url('civicrm/mock-page'));
    $this->assertOpensPage($result, 'mock-page');
  }

  public function testChangingPath(): void {
    $http = new \GuzzleHttp\Client(['http_errors' => FALSE]);
    $url = function ($path, $query = NULL) {
      return \CRM_Utils_System::url($path, $query, TRUE, NULL, FALSE);
    };

    Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('permission', \CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION)
      ->execute();

    $this->assertOpensPage($http->get($url('civicrm/mock-page')), 'mock-page');
    $this->assertNotAuthorized($http->get($url('civicrm/mock-page-renamed')), 'mock-page');

    Afform::update()
      ->setCheckPermissions(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->addValue('server_route', 'civicrm/mock-page-renamed')
      ->execute();

    $this->assertNotAuthorized($http->get($url('civicrm/mock-page')), 'mock-page');
    $this->assertOpensPage($http->get($url('civicrm/mock-page-renamed')), 'mock-page');
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface $result
   * @param string $directive
   */
  private function assertNotAuthorized(\Psr\Http\Message\ResponseInterface $result, string $directive) {
    $contents = $result->getBody()->getContents();
    $this->assertEquals(403, $result->getStatusCode());
    $this->assertMatchesRegularExpression(';(You are not authorized to access|You do not have permission to access|You need to be logged in to access this page);', $contents);
    $this->assertDoesNotMatchRegularExpression(';' . preg_quote("<$directive>", ';') . ';', $contents);
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface $result
   * @param string $directive
   *   The name of the directive which auto-opens.
   */
  private function assertOpensPage(\Psr\Http\Message\ResponseInterface $result, string $directive) {
    $contents = $result->getBody()->getContents();
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertDoesNotMatchRegularExpression(';(You are not authorized to access|You do not have permission to access|You need to be logged in to access this page);', $contents);
    $this->assertMatchesRegularExpression(';' . preg_quote("<$directive>", ';') . ';', $contents);
  }

}
