<?php

namespace Civi\Authx;

use GuzzleHttp\Cookie\CookieJar;

/**
 * In the MixedFlowsTest, we assume that the basic flows work -- then step out to another level.
 * What happens when different authentication behaviors get mixed-up/criss-crossed?
 * For example:
 *
 * - What happens if you send several stateless requests as different users -- without realizing
 *   that your HTTP client is actually tracking cookies? Are they truly stateless?
 * - What happens if you send a mix of stateless and stateful requests for different users?
 * - What happens if you mix `Authorization:` headers for authx with `Authorization:`
 *   headers for another layer (HTTPD/CMS/proxy)?
 *
 * @group e2e
 */
class MixedFlowsTest extends AbstractFlowsTest {

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

}
