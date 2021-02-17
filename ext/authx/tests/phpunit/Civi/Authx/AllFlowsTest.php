<?php

namespace Civi\Authx;

use Civi\Test\HttpTestTrait;
use CRM_Authx_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use function GuzzleHttp\Psr7\stream_for;

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
      'Joomla' => ['sendsExcessCookies', 'authErrorShowsForm'],
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
    $exs[] = ['jwt', 'param'];
    $exs[] = ['jwt', 'header'];
    $exs[] = ['jwt', 'xheader'];
    return $exs;
  }

  public function getCredTypes() {
    $exs = [];
    $exs[] = ['pass'];
    $exs[] = ['api_key'];
    $exs[] = ['jwt'];
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
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getDemoCID());

    // Phase 1: Request fails if this credential type is not enabled
    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->send($request);
    $this->assertFailedDueToProhibition($response);

    // Phase 2: Request succeeds if this credential type is enabled
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertMyContact($this->getDemoCID(), $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
  }

  /**
   * The login flow allows you use 'civicrm/authx/login' and 'civicrm/authx/logout'
   * to setup/teardown a session.
   *
   * @param string $credType
   *   The type of credential to put in the login request.
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getCredTypes
   */
  public function testStatefulLoginAllowed($credType) {
    $flowType = 'login';
    $credFunc = 'cred' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $credType));

    // Phase 1: Some pages are not accessible.
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    $http->get('civicrm/user');
    $this->assertDashboardUnauthorized();

    // Phase 2: Request succeeds if this credential type is enabled
    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->post('civicrm/authx/login', [
      'form_params' => ['_authx' => $this->$credFunc($this->getDemoCID())],
    ]);
    $this->assertMyContact($this->getDemoCID(), $response);
    $this->assertHasCookies($response);

    // Phase 3: We can use cookies to request other pages
    $response = $http->get('civicrm/authx/id');
    $this->assertMyContact($this->getDemoCID(), $response);
    $response = $http->get('civicrm/user');
    $this->assertDashboardOk();

    // Phase 4: After logout, requests should fail.
    $oldCookies = clone $cookieJar;
    $http->get('civicrm/authx/logout');
    $this->assertStatusCode(200);
    $http->get('civicrm/user');
    $this->assertDashboardUnauthorized();

    $httpHaxor = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $oldCookies]);
    $httpHaxor->get('civicrm/user');
    $this->assertDashboardUnauthorized();
  }

  /**
   * The login flow 'civicrm/authx/login' may be prohibited by policy.
   *
   * @param string $credType
   *   The type of credential to put in the login request.
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getCredTypes
   */
  public function testStatefulLoginProhibited($credType) {
    $flowType = 'login';
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    $credFunc = 'cred' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $credType));

    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->post('civicrm/authx/login', [
      'form_params' => ['_authx' => $this->$credFunc($this->getDemoCID())],
    ]);
    $this->assertFailedDueToProhibition($response);
  }

  /**
   * The auto-login flow allows you to request a specific page with specific
   * credentials. The new session is setup, and the page is displayed.
   *
   * @param string $credType
   *   The type of credential to put in the login request.
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getCredTypes
   */
  public function testStatefulAutoAllowed($credType) {
    $flowType = 'auto';
    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getDemoCID());

    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertHasCookies($response);
    $this->assertMyContact($this->getDemoCID(), $response);

    // FIXME: Assert that re-using cookies yields correct result.
  }

  /**
   * The auto-login flow allows you to request a specific page with specific
   * credentials. The new session is setup, and the page is displayed.
   *
   * @param string $credType
   *   The type of credential to put in the login request.
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @dataProvider getCredTypes
   */
  public function testStatefulAutoProhibited($credType) {
    $flowType = 'auto';
    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getDemoCID());

    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->send($request);
    $this->assertFailedDueToProhibition($response);
  }

  /**
   * Filter a request, applying the given authentication options
   *
   * @param \Psr\Http\Message\RequestInterface $request
   * @param string $credType
   *   Ex: 'pass', 'jwt', 'api_key'
   * @param string $flowType
   *   Ex: 'param', 'header', 'xheader'
   * @param int $cid
   * @return \Psr\Http\Message\RequestInterface
   */
  protected function applyAuth($request, $credType, $flowType, $cid) {
    $credFunc = 'cred' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $credType));
    $flowFunc = 'auth' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $flowType));
    return $this->$flowFunc($request, $this->$credFunc($cid));
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
    if (json_last_error() !== JSON_ERROR_NONE || empty($j)) {
      $this->fail('Malformed JSON' . $this->formatFailure());
    }
    $this->assertTrue(array_key_exists('contact_id', $j) && $j['contact_id'] === NULL);
    $this->assertTrue(array_key_exists('user_id', $j) && $j['user_id'] === NULL);
  }

  /**
   * Assert that the $response indicates the user cannot view the dashboard.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertDashboardUnauthorized($response = NULL) {
    $response = $this->resolveResponse($response);
    if (!in_array('authErrorShowsForm', $this->quirks)) {
      $this->assertStatusCode(403, $response);
    }
    $this->assertFalse(
      (bool) preg_match(';crm-dashboard-groups;', (string) $response->getBody()),
      'Response should not contain a dashboard' . $this->formatFailure($response)
    );
  }

  public function assertDashboardOk($response = NULL) {
    $response = $this->resolveResponse($response);
    $this->assertStatusCode(200, $response);
    $this->assertContentType('text/html', $response);
    // If the first two assertions pass but the next fails, then... perhaps the
    // local site permissions are wrong?
    $this->assertTrue(
      (bool) preg_match(';crm-dashboard-groups;', (string) $response->getBody()),
      'Response should contain a dashboard' . $this->formatFailure($response)
    );
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

  public function authLogin(Request $request, $cred) {
    return $request->withMethod('POST')
      ->withBody(new AppendStream([
        stream_for('_authx=' . urlencode($cred) . '&'),
        $request->getBody(),
      ]));
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
    if ($cid === $this->getDemoCID()) {
      return 'Basic ' . base64_encode($GLOBALS['_CV']['DEMO_USER'] . ':' . $GLOBALS['_CV']['DEMO_PASS']);
    }
    else {
      $this->fail("This test does have the password the requested contact.");
    }
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
    if (empty(\Civi::service('crypto.registry')->findKeysByTag('SIGN'))) {
      $this->markTestIncomplete('Cannot test JWT. No CIVICRM_SIGN_KEYS are defined.');
    }
    $token = \Civi::service('crypto.jwt')->encode([
      'exp' => time() + 60 * 60,
      'sub' => "cid:$cid",
      'scope' => 'authx',
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
  private function assertFailedDueToProhibition($response) {
    $this->assertBodyRegexp(';HTTP 401;', $response);
    $this->assertContentType('text/plain', $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
    $this->assertStatusCode(401, $response);

  }

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
    $this->assertRegexp($regexp, (string) $response->getBody(),
      'Response body does not match pattern' . $this->formatFailure($response));
    return $this;
  }

  /**
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private function getDemoCID(): int {
    if (!isset(\Civi::$statics[__CLASS__]['demoId'])) {
      \Civi::$statics[__CLASS__]['demoId'] = (int) \civicrm_api3('Contact', 'getvalue', [
        'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
        'return' => 'id',
      ]);
    }
    return \Civi::$statics[__CLASS__]['demoId'];
  }

}
