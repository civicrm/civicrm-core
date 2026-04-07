<?php

namespace Civi\Authx;

use GuzzleHttp\Cookie\CookieJar;

/**
 * Send requests using stateful authentication mechanisms (such as `login`).
 *
 * @group e2e
 */
class StatefulFlowsTest extends AbstractFlowsTest {

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

    // Create an HTTP client the given cookie jar
    $http = fn (CookieJar $cookieJar) => $this->createGuzzle(['http_errors' => FALSE, 'cookies' => $cookieJar]);

    // Phase 1: Some pages are not accessible.
    $anonCookieJar = new CookieJar();
    $http($anonCookieJar)->get('civicrm/user');
    $this->assertDashboardUnauthorized();

    $this->assertAnonymousContact($http($anonCookieJar)->get('civicrm/authx/id'));

    // Phase 2: Request succeeds if this credential type is enabled
    $loginCookieJar = clone $anonCookieJar;
    \Civi::settings()->set("authx_{$flowType}_cred", [$credType]);
    $response = $http($loginCookieJar)->post('civicrm/authx/login', [
      'form_params' => ['_authx' => (new AuthxRequestBuilder())->createCred($credType, $this->getDemoCID())],
    ]);
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    $this->assertNotEquals($anonCookieJar->toArray(), $loginCookieJar->toArray(), 'Cookie should change after login');
    $this->assertHasCookies();

    // Phase 3: We can use new cookie to request other pages. (But not the old cookie.)
    $response = $http($loginCookieJar)->get('civicrm/authx/id');
    $this->assertMyContact($this->getDemoCID(), $this->getDemoUID(), $credType, $flowType, $response);
    $this->assertDashboardOk($http($loginCookieJar)->get('civicrm/user'));
    $this->assertDashboardUnauthorized($http($anonCookieJar)->get('civicrm/user'));

    // Phase 4: After logout, requests should fail (with all cookie revisions).
    $logoutCookieJar = clone $loginCookieJar;
    $http($logoutCookieJar)->get('civicrm/authx/logout');
    $this->assertStatusCode(200);
    $this->assertNotEquals($loginCookieJar->toArray(), $logoutCookieJar->toArray(), 'Cookie should change after logout');
    $this->assertDashboardUnauthorized($http($logoutCookieJar)->get('civicrm/user'));
    $this->assertDashboardUnauthorized($http($loginCookieJar)->get('civicrm/user'));
    $this->assertDashboardUnauthorized($http($anonCookieJar)->get('civicrm/user'));
    $this->assertAnonymousContact($http($logoutCookieJar)->get('civicrm/authx/id'));
    $this->assertAnonymousContact($http($loginCookieJar)->get('civicrm/authx/id'));
    $this->assertAnonymousContact($http($anonCookieJar)->get('civicrm/authx/id'));
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

    \Civi::settings()->set("authx_{$flowType}_cred", []);
    $response = $http->post('civicrm/authx/login', [
      'form_params' => ['_authx' => (new AuthxRequestBuilder())->createCred($credType, $this->getDemoCID())],

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

}
