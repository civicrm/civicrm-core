<?php
declare(strict_types = 1);

use CRM_RouterTest_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * @group e2e
 */
class CRM_RouterTest_Page_StaticExamplesTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  use \Civi\Test\HttpTestTrait;

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  protected ?\GuzzleHttp\Client $client = NULL;

  protected function setUp(): void {
    parent::setUp();
    $this->client = $this->createGuzzle([
      'authx_user' => $GLOBALS['_CV']['ADMIN_USER'],
      'authx_ttl' => 30 * 60,
    ]);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::textAndExit()
   */
  public function testTextAndExit(): void {
    $result = $this->client->get('frontend://civicrm/route-test/static-text-and-exit');
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('text/plain', $result);
    $this->assertEquals('Text and Exit', (string) $result->getBody());
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::submitJson()
   */
  public function testSubmitJson(): void {
    $result = $this->client->post('frontend://civicrm/route-test/submit-json', [
      'authx_user' => NULL,
      \GuzzleHttp\RequestOptions::JSON => ['number' => 123.45],
    ]);
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('text/plain', $result);
    $this->assertEquals('OK 123.45', (string) $result->getBody());
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::ajaxReturnJsonResponse()
   */
  public function testAjaxReturnJsonResponse(): void {
    $result = $this->client->get('frontend://civicrm/route-test/ajax-return-json-response');
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertEquals('OK', $parsed['ajaxReturnJsonResponse']);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::systemSendJsonResponse()
   */
  public function testSystemSendJsonResponse(): void {
    $result = $this->client->get('frontend://civicrm/route-test/system-send-json-response', ['http_errors' => FALSE]);
    $this->assertEquals(499, $result->getStatusCode());
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertEquals('OK', $parsed['systemSendJsonResponse']);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::psr7()
   */
  public function testPsr7(): void {
    $result = $this->client->get('frontend://civicrm/route-test/psr7?whiz=bang', [
      'http_errors' => FALSE,
      \GuzzleHttp\RequestOptions::JSON => ['number' => 456.78],
    ]);
    $this->assertEquals(498, $result->getStatusCode());
    $this->assertEquals('Bar', $result->getHeader('X-Foo')[0]);
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertEquals('hello 456.78', $parsed['psr7']);
    $this->assertEquals('bang', $parsed['input']);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::psr7()
   */
  public function testPsr7OldInput(): void {
    $result = $this->client->get('frontend://civicrm/route-test/psr7-old-input?whiz=bang', [
      'http_errors' => FALSE,
      \GuzzleHttp\RequestOptions::JSON => ['number' => 111],
    ]);
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertEquals('Bar', $result->getHeader('X-Foo')[0]);
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertEquals('hello 111', $parsed['psr7OldInput']);
    $this->assertEquals('bang', $parsed['input']);
  }

}
