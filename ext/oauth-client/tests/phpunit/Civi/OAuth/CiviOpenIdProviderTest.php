<?php

namespace Civi\OAuth;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;

class CiviOpenIdProviderTest extends TestCase {

  /**
   * @dataProvider verificationCases */
  public function testTransaction(string $case): void {
    $issuer = 'https://id.example.org/realm';
    $metadata = [
      'issuer' => $issuer,
      'authorization_endpoint' => "$issuer/authorize",
      'token_endpoint' => "$issuer/token",
      'jwks_uri' => "$issuer/keys",
    ];
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    $public = openssl_pkey_get_details($key)['rsa'];
    $jwks = [
      'keys' => [[
        'kid' => 'test',
        'kty' => 'RSA',
        'alg' => 'RS256',
        'n' => JWT::urlsafeB64Encode($public['n']),
        'e' => JWT::urlsafeB64Encode($public['e']),
      ],
      ],
    ];
    $responses = [new Response(200, [], json_encode($jwks))];
    $options = ['discovery' => $metadata];
    if ($case !== 'inline') {
      array_unshift($responses, new Response(200, [], json_encode($metadata)));
      $options = ['discoveryUrl' => "$issuer/.well-known/openid-configuration"];
    }
    $http = new Client(['handler' => HandlerStack::create(new MockHandler($responses))]);
    $provider = new CiviOpenIdProvider(['clientId' => 'test'] + $options, ['httpClient' => $http]);
    $claims = [
      'iss' => $issuer,
      'sub' => 'alice',
      'aud' => 'test',
      'nonce' => 'transaction',
      'iat' => time(),
      'exp' => time() + 60,
    ];
    switch ($case) {
      case 'wrong-issuer':
        $claims['iss'] = 'https://other.example.org';
        break;

      case 'wrong-audience':
        $claims['aud'] = 'other-client';
        break;

      case 'expired':
        $claims['exp'] = time() - 60;
        break;

      case 'missing-subject':
        unset($claims['sub']);
        break;

      case 'wrong-authorized-party':
        $claims['aud'] = ['test', 'other-client'];
        $claims['azp'] = 'other-client';
        break;
    }
    $signer = $case === 'wrong-key' ? openssl_pkey_new(['private_key_bits' => 2048]) : $key;
    $token = new AccessToken(['access_token' => 'test', 'id_token' => JWT::encode($claims, $signer, 'RS256', 'test')]);
    if (!in_array($case, ['valid', 'inline'], TRUE)) {
      $this->expectException(OAuthException::class);
    }
    $this->assertSame($claims, $provider->verifiedClaims($token, $case === 'wrong-nonce' ? 'other' : 'transaction'));
  }

  public static function verificationCases(): array {
    return [['valid'], ['inline'], ['wrong-key'], ['wrong-nonce'], ['wrong-issuer'], ['wrong-audience'], ['expired'], ['missing-subject'], ['wrong-authorized-party']];
  }

}
