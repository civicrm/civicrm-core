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
    $this->assertSame('Text and Exit', (string) $result->getBody());
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::pathWithData()
   */
  public function testPathWithData(): void {
    $result = $this->client->get('frontend://civicrm/route-test/path-with-data/foo%2Abar%25whiz%2Fbang');
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('text/plain', $result);
    $resultData = (string) $result->getBody();
    $this->assertTrue(str_starts_with($resultData, 'Path Data '), "Response should start with 'Path Data'. Received: $resultData");
    [, , $resultPath64] = explode(' ', $resultData);
    $resultPath = base64_decode($resultPath64);
    // CRM_Utils_System::currentPath() provides the decoded path, so %2A (etc) is replaced.
    $this->assertSame('civicrm/route-test/path-with-data/foo*bar%whiz/bang', $resultPath);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::psr7PathWithData()
   */
  public function testPsr7PathWithData(): void {
    $result = $this->client->get('frontend://civicrm/route-test/psr7-path-with-data/foo%2Abar%25whiz%2Fbang');
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('text/plain', $result);
    $resultData = (string) $result->getBody();
    $this->assertTrue(str_starts_with($resultData, 'Path Data '), "Response should start with 'Path Data'. Received: $resultData");
    [, , $resultPath64] = explode(' ', $resultData);
    $resultPath = base64_decode($resultPath64);

    // PSR7 getPath() provides the encoded-path, so we see %2A and %25. However, Civi normalizes %2F as /.
    // (Civi has clean+dirty URLs across multiple CMSs, and we can't guarantee %2F-vs-/ distinction across all configurations.)
    $this->assertSame('/civicrm/route-test/psr7-path-with-data/foo%2Abar%25whiz/bang', $resultPath);
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
    $this->assertSame('OK 123.45', (string) $result->getBody());
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::ajaxReturnJsonResponse()
   */
  public function testAjaxReturnJsonResponse(): void {
    $result = $this->client->get('frontend://civicrm/route-test/ajax-return-json-response');
    $this->assertEquals(200, $result->getStatusCode());
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertSame('OK', $parsed['ajaxReturnJsonResponse']);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::systemSendJsonResponse()
   */
  public function testSystemSendJsonResponse(): void {
    $result = $this->client->get('frontend://civicrm/route-test/system-send-json-response', ['http_errors' => FALSE]);
    $this->assertEquals(429, $result->getStatusCode());
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertSame('OK', $parsed['systemSendJsonResponse']);
  }

  /**
   * @see \CRM_RouterTest_Page_StaticExamples::psr7()
   */
  public function testPsr7(): void {
    $result = $this->client->get('frontend://civicrm/route-test/psr7?whiz=bang', [
      'http_errors' => FALSE,
      \GuzzleHttp\RequestOptions::JSON => ['number' => 456.78],
    ]);
    $this->assertEquals(428, $result->getStatusCode());
    $this->assertSame('Bar', $result->getHeader('X-Foo')[0]);
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertSame('hello 456.78', $parsed['psr7']);
    $this->assertSame('bang', $parsed['input']);
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
    $this->assertSame('Bar', $result->getHeader('X-Foo')[0]);
    $this->assertContentType('application/json', $result);
    $parsed = json_decode((string) $result->getBody(), TRUE);
    $this->assertSame('hello 111', $parsed['psr7OldInput']);
    $this->assertSame('bang', $parsed['input']);
  }

}
