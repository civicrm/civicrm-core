<?php

namespace Civi\OAuth;

use Civi\Api4\OAuthClient;
use Civi\Core\HookInterface;
use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * The page a user lands on when they come back from the provider's sign-in screen.
 *
 * @group headless
 */
class ReturnPageTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  private array $providers = [];

  private array $returnErrors = [];

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../fixtures/DummyProvider.php';
    \Civi::cache('long')->delete('OAuthProvider_list');
  }

  public function tearDown(): void {
    $_GET = $_REQUEST = [];
    parent::tearDown();
  }

  public function hook_civicrm_oauthProviders(&$providers) {
    $providers = array_merge($providers, $this->providers);
  }

  public function hook_civicrm_oauthReturnError($error, $description, $uri, $state) {
    $this->returnErrors[] = [$error, $description, $state['tag'] ?? NULL];
  }

  /**
   * Start a flow against a provider whose token endpoint gives $response.
   *
   * @return string
   *   The state ID the provider would hand back.
   */
  private function startFlow(array $response): string {
    $this->providers['dummy'] = [
      'name' => 'dummy',
      'title' => 'Dummy Provider',
      'class' => 'Civi\OAuth\DummyProvider',
      'options' => [
        'urlAuthorize' => 'https://dummy/authorize',
        'urlAccessToken' => 'https://dummy/token',
        'urlResourceOwnerDetails' => 'https://dummy/owner',
        'scopes' => ['mail'],
        'cannedResponses' => [
          [
            'status' => $response['status'],
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($response['body']),
          ],
        ],
      ],
    ];
    \Civi::cache('long')->delete('OAuthProvider_list');

    $client = OAuthClient::create(FALSE)
      ->setValues(['provider' => 'dummy', 'guid' => 'guid-' . uniqid(), 'secret' => 'shh'])
      ->execute()
      ->single();
    return \Civi::service('oauth2.state')->store([
      'clientId' => $client['id'],
      'storage' => 'OAuthSysToken',
      'scopes' => ['mail'],
      'tag' => 'Example:1',
      'landingUrl' => NULL,
    ]);
  }

  private function runPage(array $params): string {
    $_GET = $_REQUEST = $params;
    ob_start();
    try {
      (new \CRM_OAuth_Page_Return())->run();
    }
    finally {
      $output = ob_get_clean();
    }
    return $output;
  }

  public function testRefusedTokenShowsTheProviderReason(): void {
    $state = $this->startFlow([
      'status' => 401,
      'body' => ['error' => 'invalid_client', 'error_description' => 'The client secret has expired.'],
    ]);

    $output = $this->runPage(['state' => $state, 'code' => 'abc']);

    $this->assertStringContainsString('invalid_client', $output);
    $this->assertStringContainsString('The client secret has expired.', $output);
    $this->assertSame([['invalid_client', 'The client secret has expired.', 'Example:1']], $this->returnErrors);
  }

  public function testProviderErrorShowsTheReason(): void {
    $state = $this->startFlow(['status' => 500, 'body' => []]);

    $output = $this->runPage([
      'state' => $state,
      'error' => 'access_denied',
      'error_description' => 'The user declined.',
    ]);

    $this->assertStringContainsString('The user declined.', $output);
    $this->assertSame([['access_denied', 'The user declined.', 'Example:1']], $this->returnErrors);
  }

}
