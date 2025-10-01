<?php

use CRM_OAuth_ExtensionUtil as E;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;
use GuzzleHttp\Psr7\Request;

/**
 * Test the "grant" methods (authorizationCode, clientCredential, etc).
 *
 * @group headless
 */
class api_v4_OAuthClientGrantTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    // Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
    // See: https://docs.civicrm.org/dev/en/latest/testing/phpunit/#civitest
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->assertEquals(0, CRM_Core_DAO::singleValueQuery('SELECT count(*) FROM civicrm_oauth_client WHERE guid <> "{civi_connect}"'));
  }

  public function tearDown(): void {
    parent::tearDown();
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   * @param ?string $scopeToUse
   * @dataProvider scopeProvider
   */
  public function testAuthorizationCode(?string $scopeToUse): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    $client = $this->createClient('test_example_1');

    $usePerms(['manage OAuth client']);
    // This `if` block could be optimized a little but then it wouldn't be
    // checking that setScopes is chainable. Is that important? :shrug: But it's
    // what led to this addition.
    if ($scopeToUse) {
      $result = Civi\Api4\OAuthClient::authorizationCode()->addWhere('id', '=', $client['id'])->setScopes($scopeToUse)->execute();
    }
    else {
      $result = Civi\Api4\OAuthClient::authorizationCode()->addWhere('id', '=', $client['id'])->execute();
    }
    $this->assertEquals(1, $result->count());
    foreach ($result as $ac) {
      $this->assertEquals('query', $ac['response_mode']);
      $url = parse_url($ac['url']);
      $this->assertEquals('example.com', $url['host']);
      $this->assertEquals('/one/auth', $url['path']);
      \parse_str($url['query'], $actualQuery);
      $this->assertEquals('code', $actualQuery['response_type']);
      $this->assertMatchesRegularExpression(';^CC_[a-zA-Z0-9]+$;', $actualQuery['state']);
      $this->assertEquals($scopeToUse ?: 'scope-1-foo,scope-1-bar', $actualQuery['scope']);
      // ? // $this->assertEquals('auto', $actualQuery['approval_prompt']);
      $this->assertEquals('example-id', $actualQuery['client_id']);
      $this->assertTrue(empty($actualQuery['response_mode']), 'response_mode should be empty');
      $this->assertMatchesRegularExpression(';civicrm/oauth-client/return;', $actualQuery['redirect_uri']);
    }

    try {
      Civi\Api4\OAuthClient::authorizationCode()
        ->addWhere('id', '=', $client['id'])
        ->setResponseMode('web_message')
        ->execute();
      $this->fail('test_example_1 should not support response_mode=web_message');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertMatchesRegularExpression(';Unsupported response mode: web_message;', $e->getMessage());
    }
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   */
  public function testAuthorizationCode_webMessage(): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $client = $this->createClient('test_example_3');

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $result = Civi\Api4\OAuthClient::authorizationCode()
      ->addWhere('id', '=', $client['id'])
      ->setResponseMode('web_message')
      ->execute();
    $this->assertEquals(1, $result->count());
    foreach ($result as $ac) {
      $this->assertEquals('web_message', $ac['response_mode']);

      $url = parse_url($ac['url']);
      $this->assertEquals('example.com', $url['host']);
      $this->assertEquals('/three/auth', $url['path']);
      \parse_str($url['query'], $actualQuery);
      $this->assertEquals('code', $actualQuery['response_type']);
      $this->assertMatchesRegularExpression(';^CC_[a-zA-Z0-9]+$;', $actualQuery['state']);
      $this->assertEquals('scope-3-foo,scope-3-bar', $actualQuery['scope']);
      // ? // $this->assertEquals('auto', $actualQuery['approval_prompt']);
      $this->assertEquals('example-id', $actualQuery['client_id']);
      $this->assertEquals('web_message', $actualQuery['response_mode']);
      $this->assertMatchesRegularExpression(';civicrm/oauth-client/return;', $actualQuery['redirect_uri']);
      $this->assertEquals($actualQuery['redirect_uri'], $ac['redirect_uri']);
    }
  }

  public static function getStartPageExamples(): array {
    $exs = [];
    // $ex['scenario name'] = [provider, startPagePolicy, userPermissions, activeSettings, expectStartPageContent)
    // For "expectStartPageContent", mix of the following: 'Not Shown', 'T&C', 'No T&C', 'Allow Skip', 'Prohibit Skip', 'Need Approval'

    $basicOauth = 'test_example_3';
    $civiConnect = 'test_example_4_sandbox';
    $notYetApproved = ['oauth_civi_connect_approved' => FALSE, 'oauth_auto_confirm' => FALSE];
    $allApproved = ['oauth_civi_connect_approved' => TRUE, 'oauth_auto_confirm' => TRUE];
    $fullAdmin = ['manage OAuth client', 'administer payment processors'];
    $partialAdmin = ['administer payment processors'];

    $exs['always for full admin, basic'] = [$basicOauth, $fullAdmin, $notYetApproved, 'always', ['Allow Skip', 'No T&C']];
    $exs['always for full admin, CiviConnect'] = [$civiConnect, $fullAdmin, $notYetApproved, 'always', ['Allow Skip', 'T&C']];

    $exs['never for full admin, basic'] = [$basicOauth, $fullAdmin, $notYetApproved, 'never', ['Not Shown']];
    $exs['never for full admin, CiviConnect'] = [$civiConnect, $fullAdmin, $notYetApproved, 'never', ['Not Shown']];

    $exs['unconfigured site, full admin, basic'] = [$basicOauth, $fullAdmin, $notYetApproved, 'auto', ['No T&C', 'Allow Skip']];
    $exs['unconfigured site, full admin, CiviConnect'] = [$civiConnect, $fullAdmin, $notYetApproved, 'auto', ['T&C', 'Allow Skip']];

    $exs['configured site, full admin, basic'] = [$basicOauth, $fullAdmin, $allApproved, 'auto', ['Not Shown']];
    $exs['configured site, full admin, CiviConnect'] = [$civiConnect, $fullAdmin, $allApproved, 'auto', ['Not Shown']];

    // Support for hook_oauthGrant is in a parallel PR. Test coverage can be expanded later.
    // $exs['unconfigured site, partial admin, CiviConnect'] = [$civiConnect, $partialAdmin, $notYetApproved, 'auto', ['Need Approval']];
    // $exs['configured site, partial admin, CiviConnect'] = [$civiConnect, $partialAdmin, $allApproved, 'auto', ['Not Shown']];
    // $exs['unconfigured site, partial admin, basic'] = [$basicOauth, $partialAdmin, $notYetApproved, 'auto', ['Prohibit Skip']];

    return $exs;
  }

  /**
   * Generate the URL to start the auth-code flow, beginning with Civi's internal confirmation screen.
   *
   * @dataProvider getStartPageExamples
   * @param string $provider
   *   Name of the provider to use
   * @param array $userPermissions
   *   List of permissions that the current user has.
   * @param array $settings
   *   List of system-settings that are preconfigured
   * @param string $startPagePolicy
   *   The value of `AuthorizationCode::$startPage`, specifying whether we want a start-page.
   *   Ex: 'always', 'never', or 'auto'
   * @param array $expectStartPage
   *   A list of expectations for the content of the start page, such as:
   *   - 'Not Shown': The start page is not shown the user.
   *   - 'T&C': The start page includes the "Terms and Conditions" box
   *   - 'Allow Skip': The start page allows the user to skip this in the future.
   */
  public function testAuthorizationCode_startPage(string $provider, array $userPermissions, array $settings, string $startPagePolicy, array $expectStartPage): void {
    $cleanup = CRM_Utils_AutoClean::with(function() use ($settings) {
      foreach ($settings as $key => $value) {
        \Civi::settings()->revert($key);
      }
    });
    foreach ($settings as $key => $value) {
      \Civi::settings()->set($key, $value);
    }

    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client']);
    // For CiviConnect sandbox, the client already exists. Otherwise, make a new client.
    if (preg_match('/test_.*_sandbox/', $provider)) {
      $client = Civi\Api4\OAuthClient::get(FALSE)
        ->addWhere('provider', '=', $provider)
        ->execute()
        ->single();
    }
    else {
      $client = $this->createClient($provider);
    }

    $usePerms($userPermissions);
    $ac = Civi\Api4\OAuthClient::authorizationCode()
      ->addWhere('id', '=', $client['id'])
      ->setStartPage($startPagePolicy)
      ->setTag('PaymentProcessor:1234')
      ->execute()
      ->single();

    if (in_array('Not Shown', $expectStartPage)) {
      $this->assertMatchesRegularExpression(';^https://(example.com|sandbox.connect.civicrm.org)/(one|two|three|four)/;', $ac['url'], 'Expected URL for remote auth page');
      $this->assertStringContainsString('state=CC_', $ac['url']);
      return;
    }

    $this->assertEquals('civicrm/oauth-client/start', $this->extractRoute($ac['url']), "Expected URL for start page");
    $this->assertStringContainsString('state=CC_', $ac['url']);

    try {
      $client = new \Civi\Test\LocalHttpClient();
      $response = $client->sendRequest(new Request('GET', (string) $ac['url']));
      $responseBody = (string) $response->getBody();
    }
    catch (\Exception $e) {
      $expectException = in_array('Need Approval', $expectStartPage);
      if ($expectException) {
        $responseBody = $e->getMessage();
      }
      else {
        throw $e;
      }
    }

    $availableClaims = [
      'T&C' => fn() => $this->assertMatchesRegularExpression(';Terms and Conditions;i', $responseBody, 'Should have terms and conditions.'),
      'No T&C' => fn() => $this->assertDoesNotMatchRegularExpression(';Terms and Conditions;i', $responseBody, 'Should not have terms and conditions.'),
      'Allow Skip' => fn() => $this->assertMatchesRegularExpression(';Do not show this message again;i', $responseBody, 'Should allow option to skip in future.'),
      'Prohibit Skip' => fn() => $this->assertDoesNotMatchRegularExpression(';Do not show this message again;i', $responseBody, 'Should not allow option to skip in future.'),
      'Need Approval' => fn() => $this->assertStringContainsString('The system administrator must approve CiviConnect.', $responseBody, 'Should not be allowed. User needs admin approval.'),
    ];
    foreach ($expectStartPage as $claim) {
      call_user_func($availableClaims[$claim]);
    }
  }

  protected function extractRoute(string $actualUrl): string {
    $config = CRM_Core_Config::singleton();
    $parsedUrl = parse_url($actualUrl);
    \parse_str($parsedUrl['query'], $parsedQuery);
    return trim($parsedQuery[$config->userFrameworkURLVar] ?? $parsedUrl['path'], '/');
  }

  /**
   * Generate the URL to request an authorization code from a provider.
   */
  public function testAuthorizationCode_prohibited(): void {
    $usePerms = function($ps) {
      $base = ['access CiviCRM'];
      \CRM_Core_Config::singleton()->userPermissionClass->permissions = array_merge($base, $ps);
    };

    $usePerms(['manage OAuth client', 'administer payment processors']);
    $client = $this->createClient('test_example_3');
    $usePerms(['access CiviContribute']);

    // With dropped privileges, we can see the client...
    $get = Civi\Api4\OAuthClient::get()
      ->addWhere('id', '=', $client['id'])
      ->execute()
      ->single();
    $this->assertEquals('test_example_3', $get['provider']);

    // But we cannot add new tokens... with any of the standard grant-types...

    try {
      Civi\Api4\OAuthClient::authorizationCode()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*authorizationCode.*test_example_3;', $e->getMessage());
    }

    try {
      Civi\Api4\OAuthClient::clientCredential()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*clientCredential.*test_example_3;', $e->getMessage());
    }

    try {
      Civi\Api4\OAuthClient::userPassword()
        ->addWhere('id', '=', $client['id'])
        ->execute();
      $this->fail('test_example_3 should require a higher privilege (administer payment processors)');
    }
    catch (\Civi\API\Exception\UnauthorizedException $e) {
      $this->assertMatchesRegularExpression(';Insufficient.*userPassword.*test_example_3;', $e->getMessage());
    }
  }

  private function createClient(string $provider): array {
    $create = Civi\Api4\OAuthClient::create()->setValues([
      'provider' => $provider,
      'guid' => "example-id",
      'secret' => "example-secret",
    ])->execute();
    $this->assertEquals(1, $create->count());
    $client = $create->first();
    $this->assertTrue(!empty($client['id']));
    return $client;
  }

  public static function scopeProvider(): array {
    return [[NULL], ['scope-1-bar']];
  }

}
