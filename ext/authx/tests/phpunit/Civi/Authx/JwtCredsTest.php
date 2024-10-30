<?php

namespace Civi\Authx;

/**
 * Check that JWT credentials work in the expected ways.
 *
 * @group e2e
 */
class JwtCredsTest extends AbstractFlowsTest {

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

}
