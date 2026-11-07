<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use GuzzleHttp\Psr7\ServerRequest;

/**
 * @group headless
 */
class CRM_Utils_RequestTest extends CiviUnitTestCase {

  private array $originalGlobals;

  public function setUp(): void {
    parent::setUp();
    $this->originalGlobals = [$_GET, $_POST, $_REQUEST, $_SERVER];
  }

  public function tearDown(): void {
    [$_GET, $_POST, $_REQUEST, $_SERVER] = $this->originalGlobals;
    unset(\Civi::$statics['CRM_Utils_Request']);
    parent::tearDown();
  }

  public static function jsonContentTypes(): array {
    return [
      ['application/json'],
      ['application/json; charset=UTF-8'],
      ['Application/JSON'],
      [' application/json ;charset=utf-8'],
    ];
  }

  /**
   * @dataProvider jsonContentTypes
   */
  public function testGetJsonBodyAcceptsJsonMediaType(string $contentType): void {
    $request = $this->jsonRequest('{"params": {"limit": 1}}', $contentType);
    $this->assertSame(['params' => ['limit' => 1]], CRM_Utils_Request::getJsonBody($request));
  }

  public function testGetJsonBodyIgnoresOtherRequests(): void {
    $form = new ServerRequest('POST', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => 'application/x-www-form-urlencoded'], 'params=%7B%7D');
    $this->assertSame([], CRM_Utils_Request::getJsonBody($form));

    // Cross-site forms can send text/plain without a CORS preflight.
    $plain = new ServerRequest('POST', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => 'text/plain'], '{"params": {}}');
    $this->assertSame([], CRM_Utils_Request::getJsonBody($plain));

    $get = new ServerRequest('GET', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => 'application/json'], '{"params": {}}');
    $this->assertSame([], CRM_Utils_Request::getJsonBody($get));

    $this->assertSame([], CRM_Utils_Request::getJsonBody($this->jsonRequest('')));
    $this->assertSame([], CRM_Utils_Request::getJsonBody($this->jsonRequest(' {} ')));
  }

  public static function malformedJsonBodies(): array {
    return [
      'truncated' => ['{"params": {"limit": 1'],
      'list' => ['[["Contact", "get"]]'],
      'empty list' => ['[]'],
      'scalar' => ['"params"'],
      'null' => ['null'],
    ];
  }

  /**
   * @dataProvider malformedJsonBodies
   */
  public function testGetJsonBodyRejectsMalformedBody(string $body): void {
    $this->expectException(CRM_Core_Exception::class);
    CRM_Utils_Request::getJsonBody($this->jsonRequest($body));
  }

  public function testRetrieveJsonFromJsonBody(): void {
    $nested = $this->jsonRequest('{"params": {"select": ["id"]}}');
    $this->assertSame(['select' => ['id']], CRM_Utils_Request::retrieveJson('params', 'REQUEST', $nested));

    $encoded = $this->jsonRequest(json_encode(['params' => json_encode(['select' => ['id']])]));
    $this->assertSame(['select' => ['id']], CRM_Utils_Request::retrieveJson('params', 'POST', $encoded));

    $this->assertNull(CRM_Utils_Request::retrieveJson('params', 'GET', $nested));
    $this->assertNull(CRM_Utils_Request::retrieveJson('calls', 'REQUEST', $nested));
  }

  public function testRetrieveJsonFromFormData(): void {
    $_GET = [];
    $_POST = ['params' => '{"select": ["id"]}', 'empty' => ''];
    $_REQUEST = $_POST;
    $form = new ServerRequest('POST', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => 'application/x-www-form-urlencoded']);
    $this->assertSame(['select' => ['id']], CRM_Utils_Request::retrieveJson('params', 'REQUEST', $form));
    $this->assertNull(CRM_Utils_Request::retrieveJson('empty', 'REQUEST', $form));
    $this->assertNull(CRM_Utils_Request::retrieveJson('missing', 'REQUEST', $form));
  }

  public function testRetrieveJsonRejectsMalformedValue(): void {
    $_GET = [];
    $_POST = ['params' => '{"select": ['];
    $_REQUEST = $_POST;
    $form = new ServerRequest('POST', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => 'application/x-www-form-urlencoded']);
    $this->expectException(CRM_Core_Exception::class);
    CRM_Utils_Request::retrieveJson('params', 'REQUEST', $form);
  }

  public function testRetrieveJsonRejectsScalarInJsonBody(): void {
    $this->expectException(CRM_Core_Exception::class);
    CRM_Utils_Request::retrieveJson('params', 'REQUEST', $this->jsonRequest('{"params": 5}'));
  }

  /**
   * Webhook callers read JSON list bodies through exportValues().
   */
  public function testExportValuesMergesAnyJsonArray(): void {
    $_GET = ['q' => 'civicrm/webhook'];
    $_POST = [];
    $this->setCurrentJsonBody('[{"event": "delivered"}]');
    $this->assertSame(['q' => 'civicrm/webhook', ['event' => 'delivered']], CRM_Utils_Request::exportValues());
  }

  public function testExportValuesIgnoresMalformedJson(): void {
    $_GET = ['q' => 'civicrm/webhook'];
    $_POST = [];
    $this->setCurrentJsonBody('{"event": ');
    $this->assertSame(['q' => 'civicrm/webhook'], CRM_Utils_Request::exportValues());
  }

  /**
   * The current request's body comes from php://input, which a test cannot write; seed the cached copy instead.
   */
  private function setCurrentJsonBody(string $body): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    \Civi::$statics['CRM_Utils_Request']['rawJsonBody'] = $body;
  }

  private function jsonRequest(string $body, string $contentType = 'application/json'): ServerRequest {
    return new ServerRequest('POST', '/civicrm/ajax/api4/Contact/get', ['Content-Type' => $contentType], $body);
  }

}
