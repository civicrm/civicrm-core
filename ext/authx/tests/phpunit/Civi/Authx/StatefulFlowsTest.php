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

}
