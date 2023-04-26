<?php

namespace E2E\Core;

use Civi\Test\HttpTestTrait;

/**
 * Class ErrorTest
 * @package E2E\Core
 * @group e2e
 *
 * Check that errors are reported in a sensible way. In this context, we speak of a few common error types, eg
 *
 * - `fatal` -- ie `CRM_Core_Error::fatal("Some message")'
 * - `exception` -- ie `throw new \Exception("Some message")'
 * - `permission` -- ie `CRM_Utils_System::permissionDenied()`
 */
class ErrorTest extends \CiviEndToEndTestCase {

  use HttpTestTrait;

  /**
   * FIXME: These represent pre-existing bugs.
   *
   * By default, these test scenarios do not run in CI.
   * However, you can run them manually by setting env-var `FORCE_ALL=1`.
   *
   * @var string[]
   */
  protected $nonCompliant = [
    // Format: "{$uf}_{$testFunc}_{$errorType}"
    '/WordPress_testErrorStatus_(fatal|exception)/',
    '/Drupal_testErrorChrome_(fatal|exception)/',
    '/Drupal8_testErrorChrome_(fatal|exception)/',
    '/Backdrop_testErrorChrome_(fatal|exception)/',
  ];

  public function getErrorTypes() {
    return [
      'frontend_fatal' => ['frontend://civicrm/dev/fake-error', 'fatal'],
      'frontend_exception' => ['frontend://civicrm/dev/fake-error', 'exception'],
      'frontend_permission' => ['frontend://civicrm/dev/fake-error', 'permission'],
      'backend_fatal' => ['backend://civicrm/dev/fake-error', 'fatal'],
      'backend_exception' => ['backend://civicrm/dev/fake-error', 'exception'],
      'backend_permission' => ['backend://civicrm/dev/fake-error', 'permission'],
    ];
  }

  /**
   * When showing an error screen, does the basic message come through?
   *
   * @param string $url
   *   Ex: 'frontend://civicrm/dev/fake-error'
   * @param string $errorType
   *   Ex: 'fatal' or 'exception'
   * @dataProvider getErrorTypes
   */
  public function testErrorMessage(string $url, string $errorType) {
    $this->skipIfNonCompliant(__FUNCTION__, $errorType);
    $messages = [
      'fatal' => '/This is a fake problem \(fatal\)/',
      'exception' => '/This is a fake problem \(exception\)/',
      'permission' => '/(You do not have permission|You are not authorized to access)/',
    ];
    $response = $this->provokeError($url, $errorType);
    $this->assertBodyRegexp($messages[$errorType] ?? 'Test error: Invalid error type', $response);
  }

  /**
   * When showing an error screen, does the HTTP status indicate an error?
   *
   * @param string $url
   *   Ex: 'frontend://civicrm/dev/fake-error'
   * @param string $errorType
   *   Ex: 'fatal' or 'exception'
   * @dataProvider getErrorTypes
   */
  public function testErrorStatus(string $url, string $errorType) {
    $this->skipIfNonCompliant(__FUNCTION__, $errorType);
    $httpCodes = [
      'fatal' => 500,
      'exception' => 500,
      'permission' => 403,
    ];
    $response = $this->provokeError($url, $errorType);
    $this->assertStatusCode($httpCodes[$errorType] ?? 'Test error: Invalid error type', $response);
  }

  /**
   * @param string $url
   *   Ex: 'frontend://civicrm/dev/fake-error'
   * @param string $errorType
   *   Ex: 'fatal' or 'exception'
   * @dataProvider getErrorTypes
   */
  public function testErrorChrome(string $url, string $errorType) {
    $this->skipIfNonCompliant(__FUNCTION__, $errorType);
    $patterns = [
      'Backdrop' => '/href=.*user\/(login|register)/',
      'Drupal' => '/href=.*user\/register/',
      'Drupal8' => '/href=.*user\/(login|register)/',
      'WordPress' => '/( role=.navigation.| class=.site-header.| class=.page-template-default.)/',
    ];
    if (!isset($patterns[CIVICRM_UF])) {
      $this->markTestIncomplete('testErrorChrome() cannot check for chrome on ' . CIVICRM_UF);
    }

    $response = $this->provokeError($url, $errorType);
    $this->assertContentType('text/html', $response);
    $this->assertBodyRegexp($patterns[CIVICRM_UF], $response, 'Body should have some chrome/decoration');
  }

  /**
   * @param string $url
   * @param string $errorType
   * @return \Psr\Http\Message\ResponseInterface
   */
  protected function provokeError(string $url, string $errorType) {
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    $jwt = \Civi::service('crypto.jwt')->encode([
      'exp' => \CRM_Utils_Time::time() + 3600,
      'civi.fake-error' => $errorType,
    ]);
    return $http->get("$url?token=$jwt");
  }

  protected function skipIfNonCompliant($func, $errorType) {
    if (getenv('FORCE_ALL')) {
      return;
    }
    $sig = implode('_', [CIVICRM_UF, $func, $errorType]);
    foreach ($this->nonCompliant as $nonCompliant) {
      if (preg_match($nonCompliant, $sig)) {
        $this->markTestIncomplete("Skipping non-compliant scenario ($sig matches $nonCompliant)");
      }
    }
  }

}
