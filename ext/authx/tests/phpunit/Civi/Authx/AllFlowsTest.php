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

  public static function setUpBeforeClass(): void {
    \Civi\Test::e2e()
      ->installMe(__DIR__)
      ->callback(
        function() {
          \CRM_Utils_System::synchronizeUsers();
        },
        'synchronizeUsers'
      )
      ->apply();
  }

  public function setUp(): void {
    $quirks = [
      'Joomla' => ['sendsExcessCookies', 'authErrorShowsForm'],
      'WordPress' => ['sendsExcessCookies'],
    ];
    $this->quirks = $quirks[CIVICRM_UF] ?? [];

    parent::setUp();
    $this->settingsBackup = [];
    foreach (\Civi\Authx\Meta::getFlowTypes() as $flowType) {
      foreach (["authx_{$flowType}_cred", "authx_{$flowType}_user", "authx_guards"] as $setting) {
        $this->settingsBackup[$setting] = \Civi::settings()->get($setting);
      }
    }

    \Civi::settings()->set('authx_guards', []);
  }

  public function tearDown(): void {
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

  public function testAnonymous(): void {
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->requestMyContact();
    $response = $http->send($request);
    $this->assertAnonymousContact($response);
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
  public function testStatelessContactOnly($credType, $flowType): void {
    if ($credType === 'pass') {
      $this->assertTrue(TRUE, 'No need to test password credentials with non-user contacts');
      return;
    }
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getLebowskiCID());

    // Phase 1: Request fails if this credential type is not enabled
    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->send($request);
    $this->assertFailedDueToProhibition($response);

    // Phase 2: Request succeeds if this credential type is enabled
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertMyContact($this->getLebowskiCID(), NULL, $credType, $flowType, $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
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
  public function testStatelessUserContact($credType, $flowType): void {
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
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
  }

  /**
   * The setting "authx_guard" may be used to require (or not require) the site_key.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testStatelessGuardSiteKey() {
    if (!defined('CIVICRM_SITE_KEY')) {
      $this->markTestIncomplete("Cannot run test without CIVICRM_SITE_KEY");
    }

    $addParam = function($request, $key, $value) {
      $query = $request->getUri()->getQuery();
      return $request->withUri(
        $request->getUri()->withQuery($query . '&' . urlencode($key) . '=' . urlencode($value))
      );
    };

    [$credType, $flowType] = ['pass', 'header'];
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getDemoCID());

    // Request OK. Policy requires site_key, and we have one.
    \Civi::settings()->set("authx_guards", ['site_key']);
    $response = $http->send($request->withHeader('X-Civi-Key', CIVICRM_SITE_KEY));
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);

    // Request OK. Policy does not require site_key, and we do not have one
    \Civi::settings()->set("authx_guards", []);
    $response = $http->send($request);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);

    // Request fails. Policy requires site_key, but we don't have the wrong value.
    \Civi::settings()->set("authx_guards", ['site_key']);
    $response = $http->send($request->withHeader('X-Civi-Key', 'not-the-site-key'));
    $this->assertFailedDueToProhibition($response);

    // Request fails. Policy requires site_key, but we don't have one.
    \Civi::settings()->set("authx_guards", ['site_key']);
    $response = $http->send($request);
    $this->assertFailedDueToProhibition($response);
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
  public function testStatefulLoginAllowed($credType): void {
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
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    $this->assertHasCookies($response);

    // Phase 3: We can use cookies to request other pages
    $response = $http->get('civicrm/authx/id');
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
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
  public function testStatefulLoginProhibited($credType): void {
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
  public function testStatefulAutoAllowed($credType): void {
    $flowType = 'auto';
    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), $credType, $flowType, $this->getDemoCID());

    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $this->assertEquals(0, $cookieJar->count());
    $response = $http->send($request);
    $this->assertTrue($cookieJar->count() >= 1);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);

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
  public function testStatefulAutoProhibited($credType): void {
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
   * Create a session for $demoCID. Within the session, make a single
   * stateless request as $lebowskiCID.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testStatefulStatelessOverlap(): void {
    \Civi::settings()->set("authx_login_cred", ['api_key']);
    \Civi::settings()->set("authx_header_cred", ['api_key']);

    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    // Phase 1: Login, create a session.
    $response = $http->post('civicrm/authx/login', [
      'form_params' => ['_authx' => $this->credApikey($this->getDemoCID())],
    ]);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'api_key', 'login', $response);
    $this->assertHasCookies($response);
    $response = $http->get('civicrm/authx/id');
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'api_key', 'login', $response);

    // Phase 2: Make a single, stateless request with different creds
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->applyAuth($this->requestMyContact(), 'api_key', 'header', $this->getLebowskiCID());
    $response = $http->send($request);
    $this->assertFailedDueToProhibition($response);
    // The following assertion merely identifies current behavior. If you can get it working generally, then huzza.
    $this->assertBodyRegexp(';Session already active;', $response);
    // $this->assertMyContact($this->getLebowskiCID(), NULL, $response);
    // $this->assertNoCookies($response);

    // Phase 3: Original session is still valid
    $response = $http->get('civicrm/authx/id');
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'api_key', 'login', $response);
  }

  /**
   * This consumer intends to make stateless requests with a handful of different identities,
   * but their browser happens to be cookie-enabled. Ensure that identities do not leak between requests.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testMultipleStateless(): void {
    \Civi::settings()->set("authx_header_cred", ['api_key']);
    $cookieJar = new CookieJar();
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    /** @var \Psr\Http\Message\RequestInterface $request */

    // Alternate calls among (A)nonymous, (D)emo, and (L)ebowski
    $planSteps = 'LADA LDLD DDLLAA';
    $actualSteps = '';

    for ($i = 0; $i < strlen($planSteps); $i++) {
      switch ($planSteps[$i]) {
        case 'L':
          $request = $this->applyAuth($this->requestMyContact(), 'api_key', 'header', $this->getLebowskiCID());
          $response = $http->send($request);
          $this->assertMyContact($this->getLebowskiCID(), NULL, 'api_key', 'header', $response, 'Expected Lebowski in step #' . $i);
          $actualSteps .= 'L';
          break;

        case 'A':
          $request = $this->requestMyContact();
          $response = $http->send($request);
          $this->assertAnonymousContact($response);
          $actualSteps .= 'A';
          break;

        case 'D':
          $request = $this->applyAuth($this->requestMyContact(), 'api_key', 'header', $this->getDemoCID());
          $response = $http->send($request);
          $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'api_key', 'header', $response, 'Expected demo in step #' . $i);
          $actualSteps .= 'D';
          break;

        case ' ':
          $actualSteps .= ' ';
          break;

        default:
          $this->fail('Unrecognized step #' . $i);
      }
    }

    $this->assertEquals($actualSteps, $planSteps);
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
   *   The expected contact ID
   * @param int|null $uid
   *   The expected user ID
   * @param string $credType
   * @param string $flow
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertMyContact($cid, $uid, $credType, $flow, ResponseInterface $response): void {
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    $formattedFailure = $this->formatFailure($response);
    $this->assertEquals($cid, $j['contact_id'], "Response did not give expected contact ID\n" . $formattedFailure);
    $this->assertEquals($uid, $j['user_id'], "Response did not give expected user ID\n" . $formattedFailure);
    if ($flow !== NULL) {
      $this->assertEquals($flow, $j['flow'], "Response did not give expected flow type\n" . $formattedFailure);
    }
    if ($credType !== NULL) {
      $this->assertEquals($credType, $j['cred'], "Response did not give expected cred type\n" . $formattedFailure);
    }
  }

  /**
   * Assert the AJAX request provided empty contact information
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertAnonymousContact(ResponseInterface $response): void {
    $formattedFailure = $this->formatFailure($response);
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    if (json_last_error() !== JSON_ERROR_NONE || empty($j)) {
      $this->fail('Malformed JSON' . $formattedFailure);
    }
    $this->assertTrue(array_key_exists('contact_id', $j) && $j['contact_id'] === NULL, 'contact_id should be null' . $formattedFailure);
    $this->assertTrue(array_key_exists('user_id', $j) && $j['user_id'] === NULL, 'user_id should be null' . $formattedFailure);
  }

  /**
   * Assert that the $response indicates the user cannot view the dashboard.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertDashboardUnauthorized($response = NULL): void {
    $response = $this->resolveResponse($response);
    if (!in_array('authErrorShowsForm', $this->quirks)) {
      $this->assertStatusCode(403, $response);
    }
    $this->assertFalse(
      (bool) preg_match(';crm-dashboard-groups;', (string) $response->getBody()),
      'Response should not contain a dashboard' . $this->formatFailure($response)
    );
  }

  public function assertDashboardOk($response = NULL): void {
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

  /**
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  private function assertFailedDueToProhibition($response): void {
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

  private function getDemoUID(): int {
    return \CRM_Core_Config::singleton()->userSystem->getUfId($GLOBALS['_CV']['DEMO_USER']);
  }

  public function getLebowskiCID() {
    if (!isset(\Civi::$statics[__CLASS__]['lebowskiCID'])) {
      $contact = \civicrm_api3('Contact', 'create', [
        'contact_type' => 'Individual',
        'first_name' => 'Jeffrey',
        'last_name' => 'Lebowski',
        'external_identifier' => __CLASS__,
        'options' => [
          'match' => 'external_identifier',
        ],
      ]);
      \Civi::$statics[__CLASS__]['lebowskiCID'] = $contact['id'];
    }
    return \Civi::$statics[__CLASS__]['lebowskiCID'];
  }

}
