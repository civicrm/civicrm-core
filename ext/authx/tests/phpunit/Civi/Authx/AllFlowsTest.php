<?php

namespace Civi\Authx;

use Civi\Test\HttpTestTrait;
use CRM_Authx_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;

/**
 * This is a matrix-style test which assesses all supported permutations of
 *
 * @group e2e
 */
class AllFlowsTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

  use HttpTestTrait;

  /**
   * Backup copy of the original settings.
   *
   * @var array
   */
  protected $settingsBackup;

  /**
   * List of CMS-dependent quirks that should be ignored during testing.
   * @var array
   */
  protected $quirks = [];

  public static function setUpBeforeClass() {
    \Civi\Test::e2e()->installMe(__DIR__)->apply();
  }

  public function setUp() {
    $quirks = [
      'WordPress' => ['sendsExcessCookies'],
    ];
    $this->quirks = $quirks[CIVICRM_UF] ?? [];

    parent::setUp();
    $this->settingsBackup = [];
    foreach (\Civi\Authx\Meta::getFlowTypes() as $flowType) {
      foreach (["authx_{$flowType}_cred", "authx_{$flowType}_user"] as $setting) {
        $this->settingsBackup[$setting] = \Civi::settings()->get($setting);
      }
    }
  }

  public function tearDown() {
    foreach ($this->settingsBackup as $setting => $value) {
      \Civi::settings()->set($setting, $value);
    }
    parent::tearDown();
  }

  public function getStatelessExamples() {
    $exs = [];
    $exs[] = ['pass', 'param'];
    $exs[] = ['pass', 'header'];
    $exs[] = ['pass', 'xheader'];
    $exs[] = ['api_key', 'param'];
    $exs[] = ['api_key', 'header'];
    $exs[] = ['api_key', 'xheader'];
    // $exs[] = ['jwt', 'param'];
    // $exs[] = ['jwt', 'header'];
    // $exs[] = ['jwt', 'xheader'];
    return $exs;
  }

  public function getStatefulExamples() {
    $exs = [];
    $exs[] = ['pass', 'auto'];
    $exs[] = ['api_key', 'auto'];
    // $exs[] = ['jwt', 'auto'];
    return $exs;
  }

  public function testAnonymous() {
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->requestMyContact();
    $response = $http->send($request);
    $this->assertNoContact(NULL, $response);
  }

  /**
   * Send a request using a stateless protocol. Assert that identities are setup correctly.
   *
   * @param string $credType
   *   The type of credential to put in the `Authorization:` header.
   * @param string $flowType
   *   The "flow" determines how the credential is added on top of the base-request (e.g. adding a parameter or header).
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getStatelessExamples
   */
  public function testStateless($credType, $flowType) {
    $credFunc = 'cred' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $credType));
    $flowFunc = 'auth' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $flowType));

    $cid = \civicrm_api3('Contact', 'getvalue', [
      'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
      'return' => 'id',
    ]);

    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->$flowFunc($this->requestMyContact(), $this->$credFunc($cid));

    // Phase 1: Request fails if this credential type is not enabled
    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->send($request);
    $this->assertBodyRegexp(';HTTP 401;', $response);
    $this->assertContentType('text/plain', $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
    $this->assertStatusCode(401, $response);

    // Phase 2: Request succeeds if this credential type is enabled
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertStatusCode(200, $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
    $this->assertMyContact($cid, $response);
  }

  /**
   * Send a request using a stateful protocol. Assert that identities are setup correctly.
   *
   * @param string $credType
   *   The type of credential to put in the `Authorization:` header.
   * @param string $flowType
   *   The "flow" determines how the credential is added on top of the base-request (e.g. adding a parameter or header).
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getStatefulExamples
   */
  public function testStateful($credType, $flowType) {
    $credFunc = 'cred' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $credType));
    $flowFunc = 'auth' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $flowType));

    $cid = \civicrm_api3('Contact', 'getvalue', [
      'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
      'return' => 'id',
    ]);

    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->$flowFunc($this->requestMyContact(), $this->$credFunc($cid));

    // Phase 1: Request fails if this credential type is not enabled
    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->send($request);
    $this->assertBodyRegexp(';HTTP 401;', $response);
    $this->assertContentType('text/plain', $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
    $this->assertStatusCode(401, $response);

    // Phase 2: Request succeeds if this credential type is enabled
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertStatusCode(200, $response);
    $this->assertHasCookies($response);
    $this->assertMyContact($cid, $response);

    // FIXME: Assert that re-using cookies yields correct result.
  }

  // ------------------------------------------------
  // Library: Base requests

  /**
   * Make an AJAX request with info about the current contact.
   *
   * @return \GuzzleHttp\Psr7\Request
   */
  public function requestMyContact() {
    $p = (['where' => [['id', '=', 'user_contact_id']]]);
    $uri = (new Uri('civicrm/authx/id'))
      ->withQuery('params=' . urlencode(json_encode($p)));
    $req = new Request('GET', $uri);
    return $req;
  }

  /**
   * Assert the AJAX request provided the expected contact.
   *
   * @param int $cid
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertMyContact($cid, ResponseInterface $response) {
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    $this->assertEquals($cid, $j['contact_id'], "Response did not give expected contact ID\n" . $this->formatFailure($response));
  }

  /**
   * Assert the AJAX request provided empty contact information
   *
   * @param int $cid
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertNoContact($cid, ResponseInterface $response) {
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    $this->assertNull($j[0]['contact_id']);
    $this->assertNull($j[0]['user_id']);
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
  public function authParam(Request $request, $cred) {
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
  public function authAuto(Request $request, $cred) {
    $query = $request->getUri()->getQuery();
    return $request->withUri(
      $request->getUri()->withQuery($query . '&_authx=' . urlencode($cred) . '&_authxSes=1')
    );
  }

  public function authHeader(Request $request, $cred) {
    return $request->withHeader('Authorization', $cred);
  }

  public function authXHeader(Request $request, $cred) {
    return $request->withHeader('X-Civi-Auth', $cred);
  }

  public function authNone(Request $request, $cred) {
    return $request;
  }

  // ------------------------------------------------
  // Library: Credential functions

  /**
   * @param int $cid
   * @return string
   *   The credential add to the request (e.g. "Basic ASDF==" or "Bearer FDSA").
   */
  public function credPass($cid) {
    return 'Basic ' . base64_encode($GLOBALS['_CV']['DEMO_USER'] . ':' . $GLOBALS['_CV']['DEMO_PASS']);
  }

  public function credApikey($cid) {
    $api_key = md5(\random_bytes(16));
    \civicrm_api3('Contact', 'create', [
      'id' => $cid,
      'api_key' => $api_key,
    ]);
    return 'Bearer ' . $api_key;
  }

  public function credJwt($cid) {
    $token = \Civi::service('authx.jwt')->create([
      'contact_id' => $cid,
      'ttl' => 60 * 60,
    ]);
    return 'Bearer ' . $token;
  }

  public function credNone($cid) {
    return NULL;
  }

  //  public function createBareJwtCred() {
  //    $contact = \civicrm_api3('Contact', 'create', [
  //      'contact_type' => 'Individual',
  //      'first_name' => 'Jeffrey',
  //      'last_name' => 'Lebowski',
  //      'external_identifier' => __CLASS__,
  //      'options' => [
  //        'match' => 'external_identifier',
  //      ],
  //    ]);
  //  }

  /**
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  private function assertNoCookies($response = NULL) {
    $response = $this->resolveResponse($response);
    $this->assertEmpty(
      preg_grep('/Set-Cookie/i', array_keys($response->getHeaders())),
      'Response should not have cookies' . $this->formatFailure($response)
    );
    return $this;
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  private function assertHasCookies($response = NULL) {
    $response = $this->resolveResponse($response);
    $this->assertNotEmpty(
      preg_grep('/Set-Cookie/i', array_keys($response->getHeaders())),
      'Response should have cookies' . $this->formatFailure($response)
    );
    return $this;
  }

  /**
   * @param $regexp
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  private function assertBodyRegexp($regexp, $response = NULL) {
    $response = $this->resolveResponse($response);
    $this->assertRegexp($regexp, (string) $response->getBody());
    return $this;
  }

}
