<?php

namespace Civi\OAuth;

use Civi\Api4\OAuthClient;
use Civi\Api4\OAuthSysToken;
use Civi\Test\HeadlessInterface;
use Firebase\JWT\JWT;
use Symfony\Component\Process\Process;

/**
 * @group headless
 */
class ParallelCallbackTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

  private array $workers = [];

  private ?string $directory = NULL;

  private ?int $clientId = NULL;

  private ?\Closure $providerListener = NULL;

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  public function tearDown(): void {
    foreach ($this->workers as $worker) {
      $worker->stop();
    }
    if ($this->clientId !== NULL) {
      OAuthSysToken::delete(FALSE)->addWhere('client_id', '=', $this->clientId)->execute();
      OAuthClient::delete(FALSE)->addWhere('id', '=', $this->clientId)->execute();
    }
    if ($this->directory !== NULL) {
      foreach (glob($this->directory . '/*') as $file) {
        unlink($file);
      }
      rmdir($this->directory);
    }
    if ($this->providerListener !== NULL) {
      \Civi::dispatcher()->removeListener('hook_civicrm_oauthProviders', $this->providerListener);
    }
    \Civi::cache('long')->delete('OAuthProvider_list');
    parent::tearDown();
  }

  /**
   * @dataProvider schedules
   */
  public function testOnlyOneProcessCompletesCallback(array $delays): void {
    require_once __DIR__ . '/../../../fixtures/CallbackProvider.php';
    $this->providerListener = CallbackProvider::register();
    $client = OAuthClient::create(FALSE)
      ->setValues(['provider' => 'callback-race', 'guid' => 'parallel-client', 'secret' => 'mock-secret'])
      ->execute()->single();
    $this->clientId = $client['id'];
    $flow = OAuthClient::authorizationCode(FALSE)
      ->addWhere('id', '=', $this->clientId)
      ->setStorage('OAuthSysToken')
      ->setTag('Login')
      ->setLandingUrl(NULL)
      ->execute()->first();
    parse_str(parse_url((string) $flow['url'], PHP_URL_QUERY), $query);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $public = openssl_pkey_get_details($key)['rsa'];
    $claims = [
      'iss' => 'https://id.example.org',
      'sub' => 'alice',
      'aud' => 'parallel-client',
      'nonce' => $query['nonce'],
      'iat' => time(),
      'exp' => time() + 120,
    ];
    $this->directory = sys_get_temp_dir() . '/oauth-race-' . bin2hex(random_bytes(8));
    mkdir($this->directory, 0700);
    $configFile = $this->directory . '/config.json';
    file_put_contents($configFile, json_encode([
      'state' => $query['state'],
      'directory' => $this->directory,
      'token' => [
        'access_token' => 'mock-access',
        'token_type' => 'Bearer',
        'id_token' => JWT::encode($claims, $key, 'RS256', 'test'),
      ],
      'jwks' => [
        'keys' => [[
          'kid' => 'test',
          'kty' => 'RSA',
          'alg' => 'RS256',
          'n' => JWT::urlsafeB64Encode($public['n']),
          'e' => JWT::urlsafeB64Encode($public['e']),
        ],
        ],
      ],
    ], JSON_THROW_ON_ERROR));
    for ($i = 0; $i < 2; $i++) {
      $worker = new Process([
        PHP_BINARY,
        __DIR__ . '/../../../fixtures/callback-worker.php',
        $configFile,
        (string) $delays[$i],
      ], getcwd(), ['CIVICRM_UF' => 'UnitTests']);
      $worker->setTimeout(45);
      $worker->start();
      $this->workers[] = $worker;
    }
    $deadline = microtime(TRUE) + 30;
    while (count(glob($this->directory . '/ready-*')) !== 2) {
      foreach ($this->workers as $worker) {
        if (!$worker->isRunning()) {
          $this->fail($worker->getErrorOutput() . $worker->getOutput());
        }
      }
      if (microtime(TRUE) > $deadline) {
        $this->fail('Workers did not reach the barrier.');
      }
      usleep(10000);
    }
    touch($this->directory . '/go');
    $results = [];
    foreach ($this->workers as $worker) {
      $worker->wait();
      $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput() . $worker->getOutput());
      $results[] = json_decode($worker->getOutput(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    $this->assertNotSame($results[0]['pid'], $results[1]['pid']);
    $statuses = array_column($results, 'status');
    sort($statuses);
    $this->assertSame(['accepted', 'rejected'], $statuses);
    $this->assertSame(1, array_sum(array_column($results, 'tokenHooks')));
    $this->assertSame(1, array_sum(array_column($results, 'returnHooks')));
    $this->assertSame(2, array_sum(array_column($results, 'requests')));
    $tokens = OAuthSysToken::get(FALSE)->addWhere('client_id', '=', $this->clientId)->execute();
    $this->assertCount(1, $tokens);
    $this->assertSame($claims, $tokens->single()['resource_owner']);
  }

  public static function schedules(): array {
    return [
      'simultaneous' => [[0, 0]],
      'first-delayed' => [[10000, 0]],
      'second-delayed' => [[0, 10000]],
    ];
  }

}
