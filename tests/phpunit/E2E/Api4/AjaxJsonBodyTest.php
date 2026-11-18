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

namespace E2E\Api4;

use Civi\Api4\Contact;
use Civi\Test\HttpTestTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * AJAX API requests with an `application/json` body, over HTTP.
 *
 * @group e2e
 */
class AjaxJsonBodyTest extends \CiviEndToEndTestCase {

  use HttpTestTrait;

  private int $contactId;

  private string $firstName;

  /**
   * Whether debug_enabled was set explicitly before a test changed it, and to what.
   */
  private ?array $originalDebugSetting = NULL;

  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();
    \Civi\Test::e2e()->install(['authx'])->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->firstName = uniqid('AjaxJsonBody');
    $this->contactId = Contact::create(FALSE)
      ->addValue('first_name', $this->firstName)
      ->execute()->single()['id'];
  }

  public function tearDown(): void {
    if ($this->originalDebugSetting) {
      [$wasExplicit, $value] = $this->originalDebugSetting;
      $wasExplicit ? \Civi::settings()->set('debug_enabled', $value) : \Civi::settings()->revert('debug_enabled');
    }
    Contact::delete(FALSE)->addWhere('id', '=', $this->contactId)->setUseTrash(FALSE)->execute();
    parent::tearDown();
  }

  public static function jsonContentTypes(): array {
    return [
      ['application/json'],
      ['application/json; charset=UTF-8'],
    ];
  }

  /**
   * @dataProvider jsonContentTypes
   */
  public function testApi4ParamsObject(string $contentType): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => $this->getParams()], $contentType);
    $this->assertSame([$this->contactId], array_column($this->decode(200, $response)['values'], 'id'));
  }

  public function testApi4ParamsString(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => json_encode($this->getParams())]);
    $this->assertSame([$this->contactId], array_column($this->decode(200, $response)['values'], 'id'));
  }

  public function testApi4Index(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => $this->getParams(), 'index' => 0]);
    $this->assertSame($this->contactId, $this->decode(200, $response)['values']['id']);
  }

  public function testApi4MultipleCalls(): void {
    $response = $this->postJson('civicrm/ajax/api4', ['calls' => ['first' => ['Contact', 'get', $this->getParams()]]]);
    $this->assertSame([$this->contactId], array_column($this->decode(200, $response)['first']['values'], 'id'));
  }

  /**
   * A body without the expected keys must not run the call with no params.
   */
  public function testApi4RejectsUnknownKeys(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', $this->getParams());
    $this->assertStringContainsString('where', $this->decode(400, $response)['error_message']);
  }

  public function testApi4RejectsMalformedBody(): void {
    $response = $this->post('civicrm/ajax/api4/Contact/get', '{"params": {"where": ', 'application/json');
    $this->assertStringContainsString('request body', $this->decode(400, $response)['error_message']);
  }

  public function testApi4RejectsScalarParams(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => 5]);
    $this->assertStringContainsString("'params'", $this->decode(400, $response)['error_message']);
  }

  public function testApi4RejectsNullParams(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => NULL]);
    $this->assertStringContainsString("'params'", $this->decode(400, $response)['error_message']);
  }

  public function testApi4RejectsNonScalarIndex(): void {
    $response = $this->postJson('civicrm/ajax/api4/Contact/get', ['params' => $this->getParams(), 'index' => ['a' => 1]]);
    $this->assertStringContainsString("'index'", $this->decode(400, $response)['error_message']);
  }

  public function testApi4RejectsJsonWithoutAjaxHeader(): void {
    // Debug mode deliberately lifts the check.
    $this->originalDebugSetting = [\Civi::settings()->hasExplicit('debug_enabled'), \Civi::settings()->getExplicit('debug_enabled')];
    \Civi::settings()->set('debug_enabled', FALSE);
    $response = $this->createGuzzle(['http_errors' => FALSE])->post('civicrm/ajax/api4/Contact/get', [
      'headers' => ['Content-Type' => 'application/json'],
      'body' => json_encode(['params' => $this->getParams()]),
    ]);
    $this->assertStringContainsString('SECURITY ALERT', $this->decode(400, $response)['error_message']);
  }

  /**
   * @dataProvider jsonContentTypes
   */
  public function testApi3JsonBody(string $contentType): void {
    $response = $this->postJson('civicrm/ajax/rest', [
      'entity' => 'Contact',
      'action' => 'get',
      'json' => ['first_name' => $this->firstName, 'return' => 'id'],
    ], $contentType);
    $result = $this->decode(200, $response);
    $this->assertSame(0, $result['is_error'], $result['error_message'] ?? '');
    $this->assertSame([$this->contactId], array_map('intval', array_keys($result['values'])));
  }

  public function testApi3AjaxJsonBody(): void {
    $response = $this->postJson('civicrm/api/json', [
      'entity' => 'Contact',
      'action' => 'get',
      'json' => ['first_name' => $this->firstName, 'return' => 'id'],
    ]);
    $result = $this->decode(200, $response);
    $this->assertSame(0, $result['is_error'], $result['error_message'] ?? '');
    // This endpoint always answers sequentially.
    $this->assertSame([$this->contactId], array_map('intval', array_column($result['values'], 'id')));
  }

  public static function api3Paths(): array {
    return [
      ['civicrm/ajax/rest'],
      ['civicrm/api/json'],
    ];
  }

  /**
   * @dataProvider api3Paths
   */
  public function testApi3ReportsMalformedJson(string $path): void {
    $response = $this->postJson($path, ['entity' => 'Contact', 'action' => 'get', 'json' => '{"first_name": ']);
    $result = $this->decode(200, $response);
    $this->assertSame(1, $result['is_error']);
    $this->assertSame("Malformed JSON in 'json'.", $result['error_message']);
  }

  /**
   * @dataProvider api3Paths
   */
  public function testApi3EmptyJsonParamsAnswerInJson(string $path): void {
    $response = $this->postJson($path, ['entity' => 'Domain', 'action' => 'get', 'json' => new \stdClass()]);
    $this->assertSame(0, $this->decode(200, $response)['is_error']);
  }

  public function testApi3NumericJsonFlag(): void {
    $response = $this->postJson('civicrm/ajax/rest', [
      'entity' => 'Contact',
      'action' => 'get',
      'json' => 1,
      'first_name' => $this->firstName,
      'return' => 'id',
    ]);
    $result = $this->decode(200, $response);
    $this->assertSame([$this->contactId], array_map('intval', array_keys($result['values'])));
  }

  public function testApi3JsonParamsWithLeadingWhitespace(): void {
    $response = $this->post('civicrm/ajax/rest', http_build_query([
      'entity' => 'Contact',
      'action' => 'get',
      'json' => ' ' . json_encode(['first_name' => $this->firstName, 'return' => 'id']),
    ]), 'application/x-www-form-urlencoded');
    $result = $this->decode(200, $response);
    $this->assertSame([$this->contactId], array_map('intval', array_keys($result['values'])));
  }

  public function testApi3MultipleCallsJsonBody(): void {
    $response = $this->postJson('civicrm/ajax/rest', [
      'entity' => 'api3',
      'action' => 'call',
      'json' => ['first' => ['Contact', 'get', ['first_name' => $this->firstName, 'return' => 'id']]],
    ]);
    $this->assertSame([$this->contactId], array_map('intval', array_keys($this->decode(200, $response)['first']['values'])));
  }

  private function getParams(): array {
    return [
      'select' => ['id'],
      'where' => [['first_name', '=', $this->firstName]],
    ];
  }

  private function postJson(string $path, array $body, string $contentType = 'application/json'): ResponseInterface {
    return $this->post($path, json_encode($body), $contentType);
  }

  private function post(string $path, string $body, string $contentType): ResponseInterface {
    return $this->createGuzzle(['http_errors' => FALSE])->post($path, [
      'authx_user' => $GLOBALS['_CV']['ADMIN_USER'],
      'authx_flow' => 'xheader',
      'headers' => ['Content-Type' => $contentType, 'X-Requested-With' => 'XMLHttpRequest'],
      'body' => $body,
    ]);
  }

  private function decode(int $expectedStatus, ResponseInterface $response): array {
    $this->assertStatusCode($expectedStatus, $response);
    $result = json_decode((string) $response->getBody(), TRUE);
    $this->assertIsArray($result, $this->formatFailure($response));
    return $result;
  }

}
