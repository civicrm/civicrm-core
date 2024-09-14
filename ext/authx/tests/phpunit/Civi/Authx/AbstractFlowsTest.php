<?php

namespace Civi\Authx;

use Civi\Test\EndToEndInterface;
use Civi\Test\HttpTestTrait;
use GuzzleHttp\Psr7\AppendStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Psr7\Utils;

class AbstractFlowsTest extends \PHPUnit\Framework\TestCase implements EndToEndInterface {

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
          \CRM_Utils_System::synchronizeUsersIfAllowed();
        },
        'synchronizeUsers'
      )
      ->apply();
  }

  public function setUp(): void {
    $this->quirks = $this->findQuirks();

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

  public function getCredTypes() {
    $exs = [];
    $exs[] = ['pass'];
    $exs[] = ['api_key'];
    $exs[] = ['jwt'];
    return $exs;
  }

  public function getFlowTypes() {
    $exs = [];
    $exs[] = ['param'];
    $exs[] = ['header'];
    $exs[] = ['xheader'];
    return $exs;
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
   * Assert the AJAX response provided the expected contact.
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
   * @param string $additionalMessage
   */
  public function assertAnonymousContact(ResponseInterface $response, $additionalMessage = ''): void {
    $formattedFailure = $this->formatFailure($response);
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    if (json_last_error() !== JSON_ERROR_NONE || empty($j)) {
      $this->fail('Malformed JSON' . $formattedFailure);
    }
    $this->assertTrue(array_key_exists('contact_id', $j) && $j['contact_id'] === NULL, 'contact_id should be null' . $formattedFailure . ' ' . $additionalMessage);
    $this->assertTrue(array_key_exists('user_id', $j) && $j['user_id'] === NULL, 'user_id should be null' . $formattedFailure . ' ' . $additionalMessage);
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
        Utils::streamFor('_authx=' . urlencode($cred) . '&'),
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

  public function credNone($cid) {
    return NULL;
  }

  /**
   * Assert that a request was not authenticated.
   *
   * @param string $mode
   *   Expect that the  'prohibited' or 'anon'
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  protected function assertNotAuthenticated(string $mode, $response) {
    switch ($mode) {
      case 'anon':
        $this->assertAnonymousContact($response);
        break;

      case 'prohibit':
        $this->assertFailedDueToProhibition($response);
        break;

      default:
        throw new \RuntimeException("Invalid option: mode=$mode");
    }
  }

  /**
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  protected function assertFailedDueToProhibition($response): void {
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
  protected function assertNoCookies($response = NULL) {
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
  protected function assertHasCookies($response = NULL) {
    $response = $this->resolveResponse($response);
    $this->assertNotEmpty(
      preg_grep('/Set-Cookie/i', array_keys($response->getHeaders())),
      'Response should have cookies' . $this->formatFailure($response)
    );
    return $this;
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

  protected function getDemoUID(): int {
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

  /**
   * @return array|string[]
   */
  protected function findQuirks(): array {
    $quirks = [
      'Joomla' => ['sendsExcessCookies', 'authErrorShowsForm'],
      'WordPress' => ['sendsExcessCookies'],
      'Standalone' => ['sendsExcessCookies'],
    ];
    return $quirks[CIVICRM_UF] ?? [];
  }

}
