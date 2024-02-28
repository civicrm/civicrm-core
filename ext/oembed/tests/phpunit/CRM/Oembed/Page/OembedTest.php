<?php

use CRM_Oembed_ExtensionUtil as E;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use Civi\Test\LocalHttpClient;
use GuzzleHttp\Psr7\Request;

/**
 * @group headless
 */
class CRM_Oembed_Page_OembedTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  protected LocalHttpClient $client;

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['iframe', 'oembed'])
      ->apply();
  }

  public function setUp():void {
    parent::setUp();
    $this->client = new LocalHttpClient();
  }

  public function testUnknownFormat() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=zzz')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(501, $response->getStatusCode());
    $this->assertEquals('Unsupported format', (string) $response->getBody());
  }

  public function testWrongDomain() {
    $pageUrl = Civi::url('https://othersite.com/civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=zzz')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(501, $response->getStatusCode());
    $this->assertStringContainsString('Unrecognized host', (string) $response->getBody());
  }

  public function testShare_default() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=share')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $doc = \phpQuery::newDocument($responseBody, 'text/html');

    $link = $doc->find('link[type=application/json+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fcontribute%2Ftransact%26reset%3D1%26id%3D1&format=json;', $link->attr('href'));

    $link = $doc->find('link[type=text/xml+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fcontribute%2Ftransact%26reset%3D1%26id%3D1&format=xml;', $link->attr('href'));
  }

  public function testShare_1024() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=share&maxwidth=1024&maxheight=768')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $doc = \phpQuery::newDocument($responseBody, 'text/html');

    $link = $doc->find('link[type=application/json+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fcontribute%2Ftransact%26reset%3D1%26id%3D1&maxwidth=1024&maxheight=768&format=json;', $link->attr('href'));

    $link = $doc->find('link[type=text/xml+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fcontribute%2Ftransact%26reset%3D1%26id%3D1&maxwidth=1024&maxheight=768&format=xml;', $link->attr('href'));
  }

  public function testJson() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=json')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $data = json_decode($responseBody, TRUE);
    $this->assertEquals('rich', $data['type']);
    $this->assertMatchesRegularExpression(';iframe.*src=.*contribute;', $data['html']);
    $this->assertEquals(600, $data['width']);
    $this->assertEquals(400, $data['height']);
    $this->assertTrue(is_int($data['width']));
  }

  public function testJson_1024() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=json&maxwidth=1024&maxheight=768')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $data = json_decode($responseBody, TRUE);
    $this->assertEquals('rich', $data['type']);
    $this->assertMatchesRegularExpression(';iframe.*src=.*contribute;', $data['html']);
    $this->assertEquals(1024, $data['width']);
    $this->assertEquals(768, $data['height']);
  }

  public function testXml() {
    $pageUrl = Civi::url('frontend://civicrm/contribute/transact?reset=1&id=1');
    $oembedUrl = Civi::url('frontend://civicrm/oembed?format=xml')->addQuery([
      'url' => $pageUrl,
    ]);
    $response = $this->client->sendRequest(new Request('GET', (string) $oembedUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $xml = simplexml_load_string($responseBody);

    $this->assertEquals('rich', $xml->type);
    $this->assertMatchesRegularExpression(';iframe.*src=.*contribute;', $xml->html);
    $this->assertEquals('600', $xml->width);
    $this->assertEquals('400', $xml->height);
  }

}
