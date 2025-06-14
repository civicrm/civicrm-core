<?php

namespace E2E\AfformMock;

use Civi\Authx\AuthxRequestBuilder;
use CRM_Core_DAO;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

/**
 * Perform some tests against `mockPublicForm.aff.html`.
 *
 * This test uses Guzzle and checks more low-level behaviors. For more comprehensive
 * tests that also cover browser/Chrome/JS behaviors, see MockPublicFormBrowserTest.
 *
 * @group e2e
 * @group ang
 */
class MockPublicFormTest extends \Civi\AfformMock\FormTestCase {

  protected $formName = 'mockPublicForm';

  /**
   * These patterns are hints to indicate whether the page-view is authenticated in the CMS.
   * @var string[]
   */
  private $sessionCues = [
    'Backdrop' => '/<body.* class=".* logged-in[ "]/',
    'Drupal' => '/<body.* class=".* logged-in[ "]/',
    'Drupal8' => '/<body.* class=".* user-logged-in[ "]/',
  ];

  protected ?AuthxRequestBuilder $authxRequest;

  protected function setUp(): void {
    parent::setUp();
    $this->authxRequest = (new AuthxRequestBuilder())
      ->addFlow('afformpage', [$this, 'authAfformPage'])
      ->addCred('afformjwt', [$this, 'credAfformJwt']);
  }

  public function testGetPage() {
    $r = $this->createGuzzle()->get('civicrm/mock-public-form');
    $this->assertContentType('text/html', $r);
    $this->assertStatusCode(200, $r);
    $body = (string) $r->getBody();
    $this->assertStringContainsString('mockPublicForm', $body);
  }

  public function testPublicCreateAllowed() {
    $initialMaxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');

    $r = bin2hex(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $this->submit(['args' => [], 'values' => ['me' => $me]]);

    // Contact was created...
    $contact = \Civi\Api4\Contact::get(FALSE)->addWhere('first_name', '=', 'Firsty' . $r)->execute()->single();
    $this->assertEquals('Firsty' . $r, $contact['first_name']);
    $this->assertEquals('Lasty' . $r, $contact['last_name']);
    $this->assertTrue($contact['id'] > $initialMaxId);
  }

  public function testPublicEditDisallowed() {
    $contact = \Civi\Api4\Contact::create(FALSE)
      ->setValues([
        'first_name' => 'FirstBegin',
        'last_name' => 'LastBegin',
        'contact_type' => 'Individual',
      ])
      ->execute()
      ->first();

    $r = bin2hex(random_bytes(16));

    $me = [0 => ['fields' => []]];
    $me[0]['fields']['id'] = $contact['id'];
    $me[0]['fields']['first_name'] = 'Firsty' . $r;
    $me[0]['fields']['last_name'] = 'Lasty' . $r;

    $this->submit(['args' => [], 'values' => ['me' => $me]]);

    // Original contact hasn't changed
    $get = \Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $contact['id'])->execute()->single();
    $this->assertEquals('FirstBegin', $get['first_name']);
    $this->assertEquals('LastBegin', $get['last_name']);

    // Instead of updating original, a new contact was created.
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE first_name=%1', [1 => ["Firsty{$r}", 'String']]));
  }

  /**
   * There are two tokens ({form.mockPublicFormUrl} and {form.mockPublicFormLink})
   * which are rendered in two contexts (text and HTML).
   *
   * Make sure that the resulting URLs point to the same place, regardless of which
   * variant or environment is used.
   *
   * @return void
   */
  public function testWellFormedTokens() {
    $lebowski = $this->getLebowskiCID();
    $messages = \CRM_Core_TokenSmarty::render([
      'text' => 'url=({form.mockPublicFormUrl}) link=({form.mockPublicFormLink})',
      'html' => '<p>url=({form.mockPublicFormUrl}) link=({form.mockPublicFormLink})</p>',
    ], ['contactId' => $lebowski]);

    $httpTextUrl = '(https?:[a-zA-Z0-9%_/\.\?\-\+:=#&]+)';
    $httpHtmlUrl = '(https?:[a-zA-Z0-9%_/\.\?\-\+:=#&\;]+)';
    $textPattern = ";url=\($httpTextUrl\) link=\(\[My public form\]\($httpTextUrl\)\); ";
    $htmlPattern = ";\<p\>url=\($httpHtmlUrl\) link=\(<a href=\"$httpHtmlUrl\">My public form</a>\)\</p\>;";

    $this->assertMatchesRegularExpression($textPattern, $messages['text']);
    $this->assertMatchesRegularExpression($htmlPattern, $messages['html']);

    preg_match($textPattern, $messages['text'], $textMatches);
    preg_match($htmlPattern, $messages['html'], $htmlMatches);

    $this->assertEquals($textMatches[1], html_entity_decode($htmlMatches[1]), 'Text and HTML values of {form.mockPublicFormUrl} should point to same place');
    $this->assertEquals($textMatches[2], html_entity_decode($htmlMatches[2]), 'Text and HTML values of {form.mockPublicFormLink} should point to same place');

    $this->assertMatchesRegularExpression(';^https?:.*civicrm(/|%2F)mock-public-form.*;', $textMatches[1], "URL should look plausible");
    $this->assertMatchesRegularExpression(';^https?:.*civicrm(/|%2F)mock-public-form.*;', $textMatches[2], "URL should look plausible");
  }

  /**
   * Evaluate the email token `{form.mockPublicFormUrl}`. The output should be a page-level auth token.
   */
  public function testAuthenticatedUrlToken_Page() {
    $this->assertTrue(function_exists('authx_civicrm_config'), 'Cannot test without authx');

    $lebowski = $this->getLebowskiCID();
    $url = $this->renderTokens($lebowski, '{form.mockPublicFormUrl}', 'text/plain');
    $this->assertMatchesRegularExpression(';^https?:.*civicrm(/|%2F)mock-public-form.*;', $url, "URL should look plausible");

    // This URL doesn't specifically log you in to a durable session.
    $this->assertUrlSessionContact($url, NULL);

    // However, there is an auth token.
    $query = parse_url($url, PHP_URL_QUERY);
    parse_str($query, $queryParams);
    $token = $queryParams['_aff'];
    $this->assertNotEmpty($token);
    $auth = ['_authx' => $token];

    // This token cannot be used for any random API...
    $body = $this->callApi4AuthTokenFailure($auth, 'Contact', 'get', ['limit' => 5]);
    $this->assertMatchesRegularExpression('/JWT specifies a different form or route/', $body, 'Response should have error message');

    // The token can be used for Afform.prefill and Afform.submit...
    $response = $this->callApi4AuthTokenSuccess($auth, 'Afform', 'prefill', [
      'name' => $this->getFormName(),
    ]);
    $this->assertEquals('me', $response['values'][0]['name']);
    $this->assertEquals($lebowski, $response['values'][0]['values'][0]['fields']['id'], 'Afform.prefill should return id');
    $this->assertEquals('Lebowski', $response['values'][0]['values'][0]['fields']['last_name'], 'Afform.prefill should return last_name');

    // But the token cannot be used for Afform calls with sneaky params...
    $body = $this->callApi4AuthTokenFailure($auth, 'Afform', 'prefill', [
      'name' => $this->getFormName(),
      'chain' => ['name_me_0' => ['Contact', 'get', []]],
    ]);
    $this->assertMatchesRegularExpression('/JWT specifies a different form or route/', $body, 'Response should have error message');
  }

  /**
   * The prior test checks that Afform Message Tokens are working.
   *
   * There are other ways to generate a token - e.g. a custom or future script which produces a JWT.
   * We do a sniff test to see if a few other exampleswork.
   */
  public function testAuthenticatedUrl_CustomJwt(): void {
    if (!isset($this->sessionCues[CIVICRM_UF])) {
      $this->markTestIncomplete(sprintf('Cannot run test for this environment (%s). Need session-cues to identify logged-in page-views.', CIVICRM_UF));
    }

    // Internal helper - Send HTTP request to GET the form with custom JWT.
    $sendRequest = function(array $claims) {
      $basicClaims = [
        'exp' => \CRM_Utils_Time::time() + (60 * 60),
        'sub' => "cid:" . $this->getDemoCID(),
        'scope' => 'afform',
        'afform' => 'mockPublicForm',
      ];

      $token = \Civi::service('crypto.jwt')->encode(array_merge($basicClaims, $claims));
      $url = \Civi::url('frontend://civicrm/mock-public-form', 'a')
        ->addQuery(['_aff' => 'Bearer ' . $token]);
      $http = $this->createGuzzle(['http_errors' => FALSE]);
      return $http->get((string) $url);
    };

    // This might be nicer as 4 separate tests.
    $this->assertBodyRegexp('/Invalid credential/', $sendRequest(['scope' => 'wrong-scope']));
    $this->assertNotBodyRegexp($this->sessionCues[CIVICRM_UF], $sendRequest(['userMode' => 'ignore']));
    $this->assertBodyRegexp($this->sessionCues[CIVICRM_UF], $sendRequest(['userMode' => 'optional']));
    $this->assertBodyRegexp($this->sessionCues[CIVICRM_UF], $sendRequest(['userMode' => 'require']));
  }

  public static function getAuthUrlPermissionExamples(): array {
    // What kind of users might request the form?
    $asDemo = ['xheader', 'jwt', 'getDemoCID'];
    $asLebowski = ['xheader', 'jwt', 'getLebowskiCID'];
    $asLebowskiPageToken = ['afformpage', 'afformjwt', 'getLebowskiCID'];
    $asAnon = ['none', 'none', NULL];

    $cases = [];
    // Array(array $formSpec, string $flowType, string $credType, string $cidProvider, bool $expectRencdered)

    $permAlways = ['permission' => '*always allow*'];
    $cases['always-demo'] = [$permAlways, ...$asDemo, TRUE];
    $cases['always-lebowski-xhj'] = [$permAlways, ...$asLebowski, TRUE];
    $cases['always-lebowski-aff'] = [$permAlways, ...$asLebowskiPageToken, TRUE];
    $cases['always-anon'] = [$permAlways, ...$asAnon, TRUE];

    $permAuthenticated = ['permission' => '*authenticated*'];
    $cases['auth-demo'] = [$permAuthenticated, ...$asDemo, TRUE];
    $cases['auth-lebowski-xhj'] = [$permAuthenticated, ...$asLebowski, TRUE];
    $cases['auth-lebowski-aff'] = [$permAuthenticated, ...$asLebowskiPageToken, TRUE];
    $cases['auth-anon'] = [$permAuthenticated, ...$asAnon, FALSE];

    $permAdmin = ['permission' => 'administer CiviCRM'];
    $cases['admin-demo'] = [$permAdmin, ...$asDemo, TRUE];
    $cases['admin-lebowski-xhj'] = [$permAdmin, ...$asLebowski, FALSE];
    $cases['admin-lebowski-aff'] = [$permAdmin, ...$asLebowskiPageToken, FALSE];
    $cases['admin-anon'] = [$permAdmin, ...$asAnon, FALSE];

    $permSecretLink = ['permission' => '@afformPageToken'];
    $cases['secret-demo'] = [$permSecretLink, ...$asDemo, TRUE];
    $cases['secret-lebowski-xhj'] = [$permSecretLink, ...$asLebowski, FALSE];
    $cases['secret-lebowski-aff'] = [$permSecretLink, ...$asLebowskiPageToken, TRUE];
    $cases['secret-anon'] = [$permSecretLink, ...$asAnon, FALSE];

    $permAccessCiviOrSecretLink = ['permission' => ['access CiviCRM', '@afformPageToken'], 'permission_operator' => 'OR'];
    $cases['or-demo'] = [$permAccessCiviOrSecretLink, ...$asDemo, TRUE];
    $cases['or-lebowski-xhj'] = [$permAccessCiviOrSecretLink, ...$asLebowski, FALSE];
    $cases['or-lebowski-aff'] = [$permAccessCiviOrSecretLink, ...$asLebowskiPageToken, TRUE];

    $permAccessCiviAndSecretLink = ['permission' => ['access CiviCRM', '@afformPageToken'], 'permission_operator' => 'AND'];
    $cases['and-demo'] = [$permAccessCiviAndSecretLink, ...$asDemo, TRUE];
    $cases['and-lebowski-xhj'] = [$permAccessCiviAndSecretLink, ...$asLebowski, FALSE];
    $cases['and-lebowski-aff'] = [$permAccessCiviAndSecretLink, ...$asLebowskiPageToken, FALSE];

    return $cases;
  }

  /**
   * The general purpose of the test is to see how special permissions -- like '*always allow*',
   * '*authenticated*', or '@afformPageToken' behave.
   *
   * @param array $formSpec
   *   Configuration options to apply the form.
   *   Ex: ['permission' => 'administer CiviCRM']
   * @param string $flowType
   *   How to transmit authentication info for this HTTP request.
   *   Ex: 'param', 'header', 'xheader'
   * @param string $credType
   *   How to encode the credential.
   *   Ex: 'pass', 'jwt', 'afformjwt'
   * @param string|null $contactMethod
   *   For an authenticated page-view, which contact should we use?
   *   Ex: 'getLebowskiCID', 'getDemoCID'
   * @param bool $expectRendered
   *   Should the form be displayed?
   * @dataProvider getAuthUrlPermissionExamples
   */
  public function testSpecialPermissions(array $formSpec, string $flowType, string $credType, ?string $contactMethod, bool $expectRendered): void {
    \Civi\Api4\Afform::update(FALSE)
      ->addWhere('name', '=', $this->formName)
      ->setValues($formSpec)
      ->execute();

    $contactId = $contactMethod ? $this->$contactMethod() : NULL;
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    $formUrl = \Civi::url('frontend://civicrm/mock-public-form', 'a');
    $request = $this->authxRequest->applyAuth(new Request('GET', (new Uri($formUrl))), $credType, $flowType, $contactId);
    $response = $http->send($request);

    if ($expectRendered) {
      $this->assertStatusCode(200, $response);
      $this->assertContentType('text/html', $response);
    }
    else {
      $this->assertPageNotShown($response);
    }
  }

  protected function renderTokens($cid, $body, $format) {
    $tp = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), []);
    $tp->addRow()->context('contactId', $cid);
    $tp->addMessage('example', $body, $format);
    $tp->evaluate();
    return $tp->getRow(0)->render('example');
  }

  protected function getLebowskiCID(): int {
    $contact = \civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Jeffrey',
      'last_name' => 'Lebowski',
      'external_identifier' => __CLASS__,
      'options' => [
        'match' => 'external_identifier',
      ],
    ]);
    return $contact['id'];
  }

  protected function getDemoCID(): int {
    if (!isset(\Civi::$statics[__CLASS__]['demoId'])) {
      \Civi::$statics[__CLASS__]['demoId'] = (int) \civicrm_api3('Contact', 'getvalue', [
        'id' => '@user:' . $GLOBALS['_CV']['DEMO_USER'],
        'return' => 'id',
      ]);
    }
    return \Civi::$statics[__CLASS__]['demoId'];
  }

  /**
   * Assert the AJAX request provided the expected contact.
   *
   * @param int $cid
   *   The expected contact ID
   * @param \Psr\Http\Message\ResponseInterface $response
   */
  public function assertContactJson($cid, $response) {
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $j = json_decode((string) $response->getBody(), 1);
    $formattedFailure = $this->formatFailure($response);
    $this->assertEquals($cid, $j['contact_id'], "Response did not give expected contact ID\n" . $formattedFailure);
  }

  protected function callApi4AuthTokenSuccess(array $auth, string $entity, string $action, $params = []) {
    $response = $this->callApi4AuthToken($auth, $entity, $action, $params);
    $this->assertContentType('application/json', $response);
    $this->assertStatusCode(200, $response);
    $result = json_decode((string) $response->getBody(), 1);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->fail("Failed to decode APIv4 JSON.\n" . $this->formatFailure($response));
    }
    return $result;
  }

  protected function callApi4AuthTokenFailure(array $auth, string $entity, string $action, $params = []): string {
    $httpResponse = $this->callApi4AuthToken($auth, $entity, $action, $params);
    $this->assertEquals(401, $httpResponse->getStatusCode(), "HTTP status code should be 401");
    return (string) $httpResponse->getBody();
  }

  /**
   * @param array $auth
   * @param string $entity
   * @param string $action
   * @param array $params
   *
   * @return \Psr\Http\Message\ResponseInterface
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function callApi4AuthToken(array $auth, string $entity, string $action, array $params = []): \Psr\Http\Message\ResponseInterface {
    $http = $this->createGuzzle(['http_errors' => FALSE]);
    $method = str_starts_with($action, 'get') ? 'GET' : 'POST';

    $response = $http->request($method, "civicrm/ajax/api4/$entity/$action", [
      'headers' => ['X-Requested-With' => 'XMLHttpRequest'],
      // This should probably be 'form_params', but 'query' is more representative of frontend.
      ($method === 'GET' ? 'query' : 'form_params') => array_merge(['params' => json_encode($params)], $auth),
      'http_errors' => FALSE,
    ]);
    return $response;
  }

  /**
   * Opening $url may generate a session-cookie. Does that cookie authenticate you as $contactId?
   *
   * @param string $url
   * @param int|null $contactId
   * @return void
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function assertUrlSessionContact(string $url, ?int $contactId): void {
    $http = $this->createGuzzle([
      'http_errors' => FALSE,
      'cookies' => new \GuzzleHttp\Cookie\CookieJar(),
    ]);
    $response = $http->get($url);
    // $r = (string) $response->getBody();
    $this->assertStatusCode(200, $response);

    // We make another request in the same session. Is it the expected contact?
    $response = $http->get('civicrm/authx/id');
    $this->assertContactJson($contactId, $response);
  }

  public function authAfformPage(Request $request, $cred) {
    $query = $request->getUri()->getQuery();
    return $request->withUri(
      $request->getUri()->withQuery($query . '&_aff=' . urlencode($cred))
    );
  }

  public function credAfformJwt(int $cid, array $claims = []) {
    $defaults = [
      'exp' => \CRM_Utils_Time::time() + (60 * 60),
      'sub' => 'cid:' . $cid,
      'scope' => 'afform',
      'afform' => $this->formName,
    ];

    $token = \Civi::service('crypto.jwt')->encode(array_merge($defaults, $claims));
    return 'Bearer ' . $token;
  }

}
