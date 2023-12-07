<?php

/**
 * @group e2e
 * @group ang
 */
class MockPublicFormTest extends \Civi\AfformMock\FormTestCase {

  const FILE = __FILE__;

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
    $contact = Civi\Api4\Contact::get(FALSE)->addWhere('first_name', '=', 'Firsty' . $r)->execute()->single();
    $this->assertEquals('Firsty' . $r, $contact['first_name']);
    $this->assertEquals('Lasty' . $r, $contact['last_name']);
    $this->assertTrue($contact['id'] > $initialMaxId);
  }

  public function testPublicEditDisallowed() {
    $contact = Civi\Api4\Contact::create(FALSE)
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
    $get = Civi\Api4\Contact::get(FALSE)->addWhere('id', '=', $contact['id'])->execute()->single();
    $this->assertEquals('FirstBegin', $get['first_name']);
    $this->assertEquals('LastBegin', $get['last_name']);

    // Instead of updating original, a new contact was created.
    $this->assertEquals(1, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_contact WHERE first_name=%1', [1 => ["Firsty{$r}", 'String']]));
  }

  /**
   * The email token `{afform.mockPublicFormUrl}` should evaluate to an authenticated URL.
   */
  public function testAuthenticatedUrlToken_Plain() {
    if (!function_exists('authx_civicrm_config')) {
      $this->fail('Cannot test without authx');
    }

    $lebowski = $this->getLebowskiCID();
    $text = $this->renderTokens($lebowski, 'Please go to {afform.mockPublicFormUrl}', 'text/plain');
    if (!preg_match(';Please go to ([^\s]+);', $text, $m)) {
      $this->fail('Plain text message did not have URL in expected place: ' . $text);
    }
    $url = $m[1];
    $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $url, "URL should look plausible");

    // Going to this page will cause us to authenticate as the target contact
    $http = $this->createGuzzle(['http_errors' => FALSE, 'cookies' => new \GuzzleHttp\Cookie\CookieJar()]);
    $response = $http->get($url);
    $r = (string) $response->getBody();
    $this->assertStatusCode(200, $response);
    $response = $http->get('civicrm/authx/id');
    $this->assertContactJson($lebowski, $response);
  }

  /**
   * The email token `{afform.mockPublicFormUrl}` should evaluate to an authenticated URL.
   */
  public function testAuthenticatedUrlToken_Html() {
    if (!function_exists('authx_civicrm_config')) {
      $this->fail('Cannot test without authx');
    }

    $lebowski = $this->getLebowskiCID();
    $html = $this->renderTokens($lebowski, 'Please go to <a href="{afform.mockPublicFormUrl}">my form</a>', 'text/html');

    if (!preg_match(';a href="([^"]+)";', $html, $m)) {
      $this->fail('HTML message did not have URL in expected place: ' . $html);
    }
    $url = html_entity_decode($m[1]);
    $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $url, "URL should look plausible");

    // Going to this page will cause us to authenticate as the target contact
    $http = $this->createGuzzle(['cookies' => new \GuzzleHttp\Cookie\CookieJar()]);
    $response = $http->get($url);
    $this->assertStatusCode(200, $response);
    $response = $http->get('civicrm/authx/id');
    $this->assertContactJson($lebowski, $response);
  }

  /**
   * The email token `{afform.mockPublicFormLink}` should evaluate to an authenticated URL.
   */
  public function testAuthenticatedLinkToken_Html() {
    if (!function_exists('authx_civicrm_config')) {
      $this->fail('Cannot test without authx');
    }

    $lebowski = $this->getLebowskiCID();
    $html = $this->renderTokens($lebowski, 'Please go to {afform.mockPublicFormLink}', 'text/html');
    $doc = \phpQuery::newDocument($html, 'text/html');
    $this->assertEquals(1, $doc->find('a')->count(), 'Document should have hyperlink');
    foreach ($doc->find('a') as $item) {
      /** @var \DOMElement $item */
      $this->assertMatchesRegularExpression(';^https?:.*civicrm/mock-public-form.*;', $item->getAttribute('href'));
      $this->assertEquals('My public form', $item->firstChild->data);
      $url = $item->getAttribute('href');
    }

    // Going to this page will cause us to authenticate as the target contact
    $http = $this->createGuzzle(['cookies' => new \GuzzleHttp\Cookie\CookieJar()]);
    $response = $http->get($url);
    $this->assertStatusCode(200, $response);
    $response = $http->get('civicrm/authx/id');
    $this->assertContactJson($lebowski, $response);
  }

  protected function renderTokens($cid, $body, $format) {
    $tp = new \Civi\Token\TokenProcessor(\Civi::dispatcher(), []);
    $tp->addRow()->context('contactId', $cid);
    $tp->addMessage('example', $body, $format);
    $tp->evaluate();
    return $tp->getRow(0)->render('example');
  }

  protected function getLebowskiCID() {
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

}
