<?php

namespace Civi\Authx;

/**
 * Send requests using stateless authentication mechanisms (such as `header`, `xheader`, and
 * `param`).
 *
 * @group e2e
 */
class StatelessFlowsTest extends AbstractFlowsTest {

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

}
