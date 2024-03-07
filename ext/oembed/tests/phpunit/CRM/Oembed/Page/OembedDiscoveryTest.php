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
class CRM_Oembed_Page_OembedDiscoveryTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\EntityTrait;

  protected ?LocalHttpClient $client;

  protected ?array $exampleRecord;

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['iframe', 'oembed'])
      ->apply();
  }

  public function setUp():void {
    parent::setUp();
    $this->client = new LocalHttpClient();
    $this->exampleRecord = $this->createTestEntity('Event', [
      'title' => 'Walla walla',
      'start_date' => '2024-01-05',
      'event_type_id' => 1,
      'participant_listing_id' => 1,
    ]);
    // $this->exampleRecord = $this->createTestEntity('ContributionPage', [
    //   'title' => 'Test Contribution Page',
    //   'financial_type_id:name' => 'Donation',
    //   'currency' => 'USD',
    //   'financial_account_id' => 1,
    //   'is_active' => 1,
    //   'is_allow_other_amount' => 1,
    //   'min_amount' => 10,
    //   'max_amount' => 1000,
    // ], 'test');
  }

  protected function getExampleUrl(): \Civi\Core\Url {
    return Civi::url('frontend://civicrm/event/info?reset=1&id=' . $this->exampleRecord['id']);
    // return Civi::url('frontend://civicrm/contribute/transact?reset=1&id=' . $this->exampleRecord['id']);
  }

  protected function getShareUrl(array $shareOpts = []): \Civi\Core\Url {
    $pageUrl = $this->getExampleUrl();
    $shareOpts['url'] = $pageUrl;
    return Civi::url('frontend://civicrm/share')->addQuery($shareOpts);
  }

  public function testStandard() {
    $cleanup = CRM_Utils_AutoClean::with(fn() => Civi::settings()->revert('oembed_standard'));
    Civi::settings()->set('oembed_standard', TRUE);

    $pageUrl = $this->getExampleUrl();

    $response = $this->client->sendRequest(new Request('GET', (string) $pageUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $doc = \phpQuery::newDocument($responseBody, 'text/html');

    $link = $doc->find('link[type=application/json+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.*url=http.*civicrm%2Fevent%2Finfo.*reset%3D1.*id%3D\d+&format=json;', $link->attr('href'));

    $link = $doc->find('link[type=text/xml+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.*url=http.*civicrm%2Fevent%2Finfo.*reset%3D1.*id%3D\d+&format=xml;', $link->attr('href'));
  }

  public function testShare_default() {
    $shareUrl = $this->getShareUrl();
    $response = $this->client->sendRequest(new Request('GET', (string) $shareUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $doc = \phpQuery::newDocument($responseBody, 'text/html');

    $link = $doc->find('link[type=application/json+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fshare.*civicrm.*event.*info.*reset%253D1.*id%253D\d+&format=json;', $link->attr('href'));

    $link = $doc->find('link[type=text/xml+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fshare.*civicrm.*event.*info.*reset%253D1.*id%253D\d+&format=xml;', $link->attr('href'));
  }

  public function testShare_1024() {
    $shareUrl = $this->getShareUrl(['maxwidth' => 1024, 'maxheight' => 768]);

    $response = $this->client->sendRequest(new Request('GET', (string) $shareUrl));
    $this->assertEquals(200, $response->getStatusCode());
    $responseBody = (string) $response->getBody();
    $doc = \phpQuery::newDocument($responseBody, 'text/html');

    $link = $doc->find('link[type=application/json+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fshare%26maxwidth%3D1024%26maxheight%3D768%26url.*civicrm.*event.*info.*reset%253D1.*id%253D\d+&format=json;', $link->attr('href'));

    $link = $doc->find('link[type=text/xml+oembed]');
    $this->assertMatchesRegularExpression(';civicrm/oembed.url=http.*civicrm%2Fshare%26maxwidth%3D1024%26maxheight%3D768%26url.*civicrm.*event.*info.*reset%253D1.*id%253D\d+&format=xml;', $link->attr('href'));
  }

}
