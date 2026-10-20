<?php

namespace Civi\Core;

/**
 * Tests requests for civicrm/asset/builder with a missing or bad token.
 *
 * Such a request is a client error and should get a 4xx response, not a
 * fatal error.
 *
 * @package Civi\Core
 * @group headless
 */
class AssetBuilderPageRenderTest extends \CiviUnitTestCase {

  /**
   * Provides requests whose `aj` parameter is missing or invalid.
   *
   * @return array
   *   The _GET values of each request.
   */
  public static function getInvalidRequests(): array {
    $base64Url = fn(string $json): string => rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    return [
      'missing aj' => [[]],
      'aj is not a string' => [['aj' => ['x']]],
      'aj is not a valid token' => [['aj' => 'not-a-valid-token']],
      'aj header has a non-string alg' => [['aj' => $base64Url('{"alg":["HS256"]}') . '.' . $base64Url('{}') . '.eA']],
    ];
  }

  /**
   * Tests that a missing or invalid token gets an HTTP 400.
   *
   * @param array $get
   *   The _GET values of the request.
   *
   * @dataProvider getInvalidRequests
   */
  public function testInvalidRequestIsBadRequest(array $get): void {
    $response = AssetBuilder::pageRender($get);
    $this->assertEquals(400, $response['statusCode']);
    $this->assertEquals('text/plain', $response['mimeType']);
  }

  /**
   * Tests that a signed token without an asset claim gets an HTTP 400.
   */
  public function testSignedTokenWithoutAssetIsBadRequest(): void {
    $token = \Civi::service('crypto.jwt')->encode(['exp' => \CRM_Utils_Time::time() + 60, 'foo' => 'bar'], ['SIGN', 'WEAK_SIGN']);
    $response = AssetBuilder::pageRender(['aj' => $token]);
    $this->assertEquals(400, $response['statusCode']);
  }

}
