<?php

namespace Civi\Authx;

use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;

/**
 * (TEST HELPER/EXPERIMENTAL)
 * This class helps with building test-scenarios where we use different authx flows/credentials.
 * It is based on refactoring test code. It still includes some test-specific bits (like knowledge of the "demo" user).
 */
class AuthxRequestBuilder {

  protected $flows;

  protected $creds;

  public function __construct() {
    $this->flows = [
      'param' => [$this, 'authParam'],
      'auto' => [$this, 'authAuto'],
      'login' => [$this, 'authLogin'],
      'header' => fn(Request $request, $cred) => $request->withHeader('Authorization', $cred),
      'xheader' => fn(Request $request, $cred) => $request->withHeader('X-Civi-Auth', $cred),
      'none' => fn(Request $request, $cred) => $request,
    ];
    $this->creds = [
      'pass' => [$this, 'credPass'],
      'api_key' => [$this, 'credApikey'],
      'jwt' => [$this, 'credJwt'],
      'none' => fn($cid) => NULL,
    ];
  }

  /**
   * Apply authentication options to a prepared HTTP request.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The original HTTP request (without any authentication options).
   * @param string $credType
   *   Ex: 'pass', 'jwt', 'api_key'
   * @param string $flowType
   *   Ex: 'param', 'header', 'xheader'
   * @param int $cid
   *   Authenticate as a specific contact (contact ID#).
   * @return \Psr\Http\Message\RequestInterface
   *   The new HTTP request (with authentication options).
   */
  public function applyAuth($request, $credType, $flowType, $cid) {
    $cred = $this->createCred($credType, $cid);
    return $this->applyFlow($request, $flowType, $cred);
  }

  /**
   * Apply authentication options to a prepared HTTP request.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   The original HTTP request (without any authentication options).
   * @param string $flowType
   *   Ex: 'param', 'header', 'xheader'
   * @param string $credValue
   *   Ex: 'Bearer ABCD1234'
   * @return \Psr\Http\Message\RequestInterface
   *   The new HTTP request (with authentication options).
   */
  public function applyFlow($request, $flowType, $credValue) {
    $flowFunc = $this->flows[$flowType] ?? NULL;
    if (!$flowFunc) {
      throw new \LogicException("Invalid flow type: $flowType");
    }
    return $flowFunc($request, $credValue);
  }

  /**
   * Create a credential of the given type on behalf of the given contact
   * @param string $credType
   * @param int $cid
   * @return string
   */
  public function createCred($credType, $cid) {
    $credFunc = $this->creds[$credType] ?? NULL;
    if (!$credFunc) {
      throw new \LogicException("Invalid credential type: $credType");
    }
    return $credFunc($cid);
  }

  public function addFlow(string $flowType, callable $flowFunc) {
    $this->flows[$flowType] = $flowFunc;
    return $this;
  }

  public function addCred(string $credType, callable $credFunc) {
    $this->creds[$credType] = $credFunc;
    return $this;
  }

  // ------------------------------------------------
  // Library: Flow functions

  /**
   * Add query parameter ("&_authx=<CRED>").
   *
   * @param \GuzzleHttp\Psr7\Request $request
   * @param string $cred
   *   The credential add to the request (e.g. "Basic ASDF==" or "Bearer FDSA").
   * @return \GuzzleHttp\Psr7\Request
   */
  protected function authParam(Request $request, $cred) {
    $query = $request->getUri()->getQuery();
    return $request->withUri(
      $request->getUri()->withQuery($query . '&_authx=' . urlencode($cred))
    );
  }

  /**
   * Add query parameter ("&_authx=<CRED>&_authxSes=1").
   *
   * @param \GuzzleHttp\Psr7\Request $request
   * @param string $cred
   *   The credential add to the request (e.g. "Basic ASDF==" or "Bearer FDSA").
   * @return \GuzzleHttp\Psr7\Request
   */
  protected function authAuto(Request $request, $cred) {
    $query = $request->getUri()->getQuery();
    return $request->withUri(
      $request->getUri()->withQuery($query . '&_authx=' . urlencode($cred) . '&_authxSes=1')
    );
  }

  protected function authLogin(Request $request, $cred) {
    return $request->withMethod('POST')
      ->withBody(new AppendStream([
        Utils::streamFor('_authx=' . urlencode($cred) . '&'),
        $request->getBody(),
      ]));
  }

  // ------------------------------------------------
  // Library: Credential functions

  /**
   * @param int $cid
   * @return string
   *   The credential add to the request (e.g. "Basic ASDF==" or "Bearer FDSA").
   */
  protected function credPass($cid) {
    if ($cid === $this->getDemoCID()) {
      return 'Basic ' . base64_encode($GLOBALS['_CV']['DEMO_USER'] . ':' . $GLOBALS['_CV']['DEMO_PASS']);
    }
    else {
      $this->fail("This test does not have the password for the requested contact.");
    }
  }

  public function credApikey($cid) {
    $api_key = bin2hex(\random_bytes(16));
    \civicrm_api3('Contact', 'create', [
      'id' => $cid,
      'api_key' => $api_key,
    ]);
    return 'Bearer ' . $api_key;
  }

  public function credJwt($cid, $expired = FALSE) {
    if (empty(\Civi::service('crypto.registry')->findKeysByTag('SIGN'))) {
      $this->markTestIncomplete('Cannot test JWT. No CIVICRM_SIGN_KEYS are defined.');
    }
    $token = \Civi::service('crypto.jwt')->encode([
      'exp' => $expired ? time() - 60 * 60 : time() + 60 * 60,
      'sub' => "cid:$cid",
      'scope' => 'authx',
    ]);
    return 'Bearer ' . $token;
  }

  /**
   * @return int
   * @throws \CRM_Core_Exception
   */
  protected function getDemoCID(): int {
    if (!isset(\Civi::$statics[__CLASS__]['demoId'])) {
      \Civi::$statics[__CLASS__]['demoId'] = (int) \civicrm_api3('Contact', 'getvalue', [
        'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
        'return' => 'id',
      ]);
    }
    return \Civi::$statics[__CLASS__]['demoId'];
  }

}
