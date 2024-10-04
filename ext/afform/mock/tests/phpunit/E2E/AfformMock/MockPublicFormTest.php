<?php

namespace E2E\AfformMock;

use CRM_Core_DAO;

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

  public function testGetPage() {
    $r = $this->createGuzzle()->get('civicrm/mock-public-form');
    $this->assertContentType('text/html', $r);
    $this->assertStatusCode(200, $r);
    $body = (string) $r->getBody();
    $this->assertStringContainsString('mockPublicForm', $body);
  }

  public function testPublicCreateAllowed() {
    $initialMaxId = CRM_Core_DAO::singleValueQuery('SELECT max(id) FROM civicrm_contact');

    $r = md5(random_bytes(16));

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

    $r = md5(random_bytes(16));

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
   * There are two tokens ({afform.mockPublicFormUrl} and {afform.mockPublicFormLink})
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
      'text' => 'url=({afform.mockPublicFormUrl}) link=({afform.mockPublicFormLink})',
      'html' => '<p>url=({afform.mockPublicFormUrl}) link=({afform.mockPublicFormLink})</p>',
    ], ['contactId' => $lebowski]);

    $httpTextUrl = '(https?:[a-zA-Z0-9_/\.\?\-\+:=#&]+)';
    $httpHtmlUrl = '(https?:[a-zA-Z0-9_/\.\?\-\+:=#&\;]+)';
    $textPattern = ";url=\($httpTextUrl\) link=\(\[My public form\]\($httpTextUrl\)\); ";
    $htmlPattern = ";\<p\>url=\($httpHtmlUrl\) link=\(<a href=\"$httpHtmlUrl\">My public form</a>\)\</p\>;";

    $this->assertMatchesRegularExpression($textPattern, $messages['text']);
    $this->assertMatchesRegularExpression($htmlPattern, $messages['html']);

    preg_match($textPattern, $messages['text'], $textMatches);
    preg_match($htmlPattern, $messages['html'], $htmlMatches);

    $this->assertEquals($textMatches[1], html_entity_decode($htmlMatches[1]), 'Text and HTML values of {afform.mockPublicFormUrl} should point to same place');
    $this->assertEquals($textMatches[2], html_entity_decode($htmlMatches[2]), 'Text and HTML values of {afform.mockPublicFormLink} should point to same place');

    $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $textMatches[1], "URL should look plausible");
    $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $textMatches[2], "URL should look plausible");
  }

  /**
   * Evaluate the email token `{afform.mockPublicFormUrl}`. The output should be a page-level auth token.
   */
  public function testAuthenticatedUrlToken_Page() {
    $this->assertTrue(function_exists('authx_civicrm_config'), 'Cannot test without authx');

    $lebowski = $this->getLebowskiCID();
    $url = $this->renderTokens($lebowski, '{afform.mockPublicFormUrl}', 'text/plain');
    $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $url, "URL should look plausible");

    // This URL doesn't specifically log you in to a durable sesion.
    // $this->assertUrlStartsSession($url, NULL);

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

}
