<?php

namespace Civi\Authx;

use Civi\Pipe\BasicPipeClient;
use Civi\Pipe\JsonRpcMethodException;
use GuzzleHttp\Cookie\CookieJar;

/**
 * This is a matrix-style test which assesses all supported permutations of
 *
 * @group e2e
 */
class AllFlowsTest extends AbstractFlowsTest {

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
   * @throws \CRM_Core_Exception
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
    $this->assertNotAuthenticated($flowType === 'header' ? 'anon' : 'prohibit', $response);

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
   * @throws \CRM_Core_Exception
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
    $this->assertNotAuthenticated($flowType === 'header' ? 'anon' : 'prohibit', $response);

    // Phase 2: Request succeeds if this credential type is enabled
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http->send($request);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
  }

  /**
   * Send a request using a jwt that can't be decoded at all. Assert that it fails
   *
   * @param string $flowType
   *   The "flow" determines how the credential is added on top of the base-request (e.g. adding a parameter or header).
   *
   * @dataProvider getFlowTypes
   */
  public function testInvalidJwt($flowType): void {
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    $cred = $this->credJwt('Bearer thisisnotavalidjwt');

    $flowFunc = 'auth' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $flowType));
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->$flowFunc($this->requestMyContact(), $cred);

    \Civi::settings()->set("authx_{$flowType}_cred", ['jwt']);
    $response = $http->send($request);
    $this->assertNotAuthenticated('prohibit', $response);
  }

  /**
   * Send a request using a jwt that has expired. Assert that it fails
   *
   * @param string $flowType
   *   The "flow" determines how the credential is added on top of the base-request (e.g. adding a parameter or header).
   *
   * @dataProvider getFlowTypes
   */
  public function testExpiredJwt($flowType): void {
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    $cred = $this->credJwt($this->getDemoCID(), TRUE);
    $flowFunc = 'auth' . ucfirst(preg_replace(';[^a-zA-Z0-9];', '', $flowType));
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $this->$flowFunc($this->requestMyContact(), $cred);

    \Civi::settings()->set("authx_{$flowType}_cred", ['jwt']);
    $response = $http->send($request);
    $this->assertNotAuthenticated('prohibit', $response);
  }

  /**
   * The setting "authx_guard" may be used to require (or not require) the site_key.
   *
   * @throws \CRM_Core_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testStatelessGuardSiteKey() {
    if (!defined('CIVICRM_SITE_KEY')) {
      $this->markTestIncomplete("Cannot run test without CIVICRM_SITE_KEY");
    }

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
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
   * @throws \CRM_Core_Exception
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
   * Suppose a deployment has two layers of authorization:
   *
   * (1) a generic/site-wide HTTP restriction (perhaps enforced by a reverse proxy)
   * (2) anything/everything else (CMS/login-form/parameter/X-Civi-Auth stuff).
   *
   * Layer (1) has an `Authorization:` header that should be ignored by `authx`.
   *
   * This test submits both layer (1) and layer (2) credentials and ensures that authx respects
   * the layer (2).
   */
  public function testIgnoredHeaderAuthorization() {
    // We may submit some other credential - it will be used.
    $flowType = 'param';
    $credType = 'api_key';

    \Civi::settings()->set("authx_header_cred", []);
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);

    $http = $this->createGuzzle(['http_errors' => FALSE]);

    // We submit both the irrelevant `Authorization:` and the relevant `?_authx=...` (DemoCID).
    $request = $this->applyAuth($this->requestMyContact(), 'api_key', 'header', $this->getLebowskiCID());
    $request = $this->applyAuth($request, $credType, $flowType, $this->getDemoCID());
    // $request = $request->withAddedHeader('Authorization', $irrelevantAuthorization);
    $response = $http->send($request);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    if (!in_array('sendsExcessCookies', $this->quirks)) {
      $this->assertNoCookies($response);
    }
  }

  /**
   * Similar to testIgnoredHeaderAuthorization(), but the Civi/CMS user is anonymous.
   */
  public function testIgnoredHeaderAuthorization_anon() {
    $http = $this->createGuzzle(['http_errors' => FALSE]);

    /** @var \Psr\Http\Message\RequestInterface $request */

    // Variant 1: The `Authorization:` header is ignored (even if the content is totally fake/inauthentic).
    \Civi::settings()->set("authx_header_cred", []);
    $request = $this->requestMyContact()->withAddedHeader('Authorization', 'Basic ' . base64_encode("not:real"));
    $response = $http->send($request);
    $this->assertAnonymousContact($response);

    // Variant 2: The `Authorization:` header is ignored (even if the content is sorta-real-ish for LebowskiCID).
    \Civi::settings()->set("authx_header_cred", []);
    $request = $this->applyAuth($this->requestMyContact(), 'api_key', 'header', $this->getLebowskiCID());
    $response = $http->send($request);
    $this->assertAnonymousContact($response);
  }

  /**
   * This consumer intends to make stateless requests with a handful of different identities,
   * but their browser happens to be cookie-enabled. Ensure that identities do not leak between requests.
   *
   * @throws \CRM_Core_Exception
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
          $this->assertAnonymousContact($response, 'Expected Anonymous Contact in step #' . $i);
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
   * Civi's test suite includes middleware that will add JWT tokens to outgoing requests.
   *
   * This test tries a few permutations with different principals ("demo", "Lebowski"),
   * different identifier fields (authx_user, authx_contact_id), and different
   * flows (param/header/xheader).
   *
   * @throws \CRM_Core_Exception
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function testJwtMiddleware() {
    \Civi::settings()->revert("authx_param_cred");

    // HTTP GET with a specific user. Choose flow automatically.
    $response = $this->createGuzzle()->get('civicrm/authx/id', [
      'authx_user' => $GLOBALS['_CV']['DEMO_USER'],
    ]);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'jwt', 'param', $response);

    // HTTP GET with a specific contact. Choose flow automatically.
    $response = $this->createGuzzle()->get('civicrm/authx/id', [
      'authx_contact_id' => $this->getDemoCID(),
    ]);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'jwt', 'param', $response);

    // HTTP POST with a specific contact. Per-client default.
    $response = $this->createGuzzle([
      'authx_contact_id' => $this->getLebowskiCID(),
    ])->post('civicrm/authx/id');
    $this->assertMyContact($this->getLebowskiCID(), NULL, 'jwt', 'param', $response);

    // Using explicit flow options...
    foreach (['param', 'xheader', 'header'] as $flowType) {
      \Civi::settings()->set("authx_{$flowType}_cred", ['jwt']);
      $response = $this->createGuzzle()->get('civicrm/authx/id', [
        'authx_contact_id' => $this->getDemoCID(),
        'authx_flow' => $flowType,
      ]);
      $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), 'jwt', $flowType, $response);
    }
  }

  /**
   * The internal API `authx_login()` should be used by background services to set the active user.
   *
   * To test this, we call `cv ev 'authx_login(...);'` and check the resulting identity.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCliServiceLogin() {
    $withCv = function($phpStmt) {
      $cmd = strtr('cv ev -v @PHP', ['@PHP' => escapeshellarg($phpStmt)]);
      exec($cmd, $output, $val);
      $fullOutput = implode("\n", $output);
      $this->assertEquals(0, $val, "Command returned error ($cmd) ($val):\n\"$fullOutput\"");
      return json_decode($fullOutput, TRUE);
    };

    $principals = [
      'contactId' => $this->getDemoCID(),
      'userId' => $this->getDemoUID(),
      'user' => $GLOBALS['_CV']['DEMO_USER'],
    ];
    foreach ($principals as $principalField => $principalValue) {
      $msg = "Logged in with $principalField=$principalValue. We should see this user as authenticated.";

      $loginArgs = ['principal' => [$principalField => $principalValue]];
      $report = $withCv(sprintf('return authx_login(%s);', var_export($loginArgs, 1)));
      $this->assertEquals($this->getDemoCID(), $report['contactId'], $msg);
      $this->assertEquals($this->getDemoUID(), $report['userId'], $msg);
      $this->assertEquals('script', $report['flow'], $msg);
      $this->assertEquals('assigned', $report['credType'], $msg);
      $this->assertEquals(FALSE, $report['useSession'], $msg);
    }

    $invalidPrincipals = [
      ['contactId', 999999, AuthxException::CLASS, ';Contact ID 999999 is invalid;'],
      ['userId', 999999, AuthxException::CLASS, ';Cannot login. Failed to determine contact ID.;'],
      ['user', 'randuser' . mt_rand(0, 32767), AuthxException::CLASS, ';Must specify principal with valid user, userId, or contactId;'],
    ];
    foreach ($invalidPrincipals as $invalidPrincipal) {
      [$principalField, $principalValue, $expectExceptionClass, $expectExceptionMessage] = $invalidPrincipal;

      $loginArgs = ['principal' => [$principalField => $principalValue]];
      $report = $withCv(sprintf('try { return authx_login(%s); } catch (Exception $e) { return [get_class($e), $e->getMessage()]; }', var_export($loginArgs, 1)));
      $this->assertTrue(isset($report[0], $report[1]), "authx_login() should fail with invalid credentials ($principalField=>$principalValue). Received array: " . json_encode($report));
      $this->assertMatchesRegularExpression($expectExceptionMessage, $report[1], "Invalid principal ($principalField=>$principalValue) should generate exception.");
      $this->assertEquals($expectExceptionClass, $report[0], "Invalid principal ($principalField=>$principalValue) should generate exception.");
    }
  }

  public function testCliPipeTrustedLogin() {
    $rpc = new BasicPipeClient('cv ev \'Civi::pipe("tl");\'');
    $this->assertEquals('trusted', $rpc->getWelcome()['t']);
    $this->assertEquals(['login'], $rpc->getWelcome()['l']);

    $login = $rpc->call('login', ['userId' => $this->getDemoUID()]);
    $this->assertEquals($this->getDemoCID(), $login['contactId']);
    $this->assertEquals($this->getDemoUID(), $login['userId']);

    $me = $rpc->call('api3', ['Contact', 'get', ['id' => 'user_contact_id', 'sequential' => TRUE]]);
    $this->assertEquals($this->getDemoCID(), $me['values'][0]['contact_id']);
  }

  public function testCliPipeUntrustedLogin() {
    $rpc = new BasicPipeClient('cv ev \'Civi::pipe("ul");\'');
    $this->assertEquals('untrusted', $rpc->getWelcome()['u']);
    $this->assertEquals(['login'], $rpc->getWelcome()['l']);

    try {
      $rpc->call('login', ['userId' => $this->getDemoUID()]);
      $this->fail('Untrusted sessions should require authentication credentials');
    }
    catch (JsonRpcMethodException $e) {
      $this->assertMatchesRegularExpression(';not trusted;', $e->getMessage());
    }

    $login = $rpc->call('login', ['cred' => $this->credJwt($this->getDemoCID())]);
    $this->assertEquals($this->getDemoCID(), $login['contactId']);
    $this->assertEquals($this->getDemoUID(), $login['userId']);

    $me = $rpc->call('api3', ['Contact', 'get', ['id' => 'user_contact_id', 'sequential' => TRUE]]);
    $this->assertEquals($this->getDemoCID(), $me['values'][0]['contact_id']);
  }

}
