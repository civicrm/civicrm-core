<?php

namespace Civi\OAuth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use League\OAuth2\Client\Token\AccessToken;

/**
 * OpenID Connect authorization code provider using signed ID tokens. */
class CiviOpenIdProvider extends CiviGenericProvider {

  private array $metadata;

  public function __construct(array $options = [], array $collaborators = []) {
    $http = $collaborators['httpClient'] ?? new Client();
    $collaborators['httpClient'] = $http;
    if (isset($options['discoveryUrl']) === isset($options['discovery'])) {
      throw new OAuthException('Configure exactly one OpenID discovery source.');
    }
    if (isset($options['discoveryUrl'])) {
      $url = str_replace('{tenant}', rawurlencode(($options['tenant'] ?? '') ?: 'common'), $options['discoveryUrl']);
      OpenIdMetadata::httpsUrl($url);
      $document = self::readJson($http, $url);
      $issuer = $options['issuer'] ?? preg_replace('~/\.well-known/openid-configuration$~D', '', $url);
    }
    else {
      $document = $options['discovery'];
      $issuer = $options['issuer'] ?? $document['issuer'] ?? '';
    }
    $this->metadata = OpenIdMetadata::fromArray($document, $issuer);
    parent::__construct([
      'urlAuthorize' => $this->metadata['authorization_endpoint'],
      'urlAccessToken' => $this->metadata['token_endpoint'],
      'urlResourceOwnerDetails' => '',
      'scopeSeparator' => ' ',
    ] + $options + ['scopes' => ['openid']], $collaborators);
  }

  protected function fetchResourceOwnerDetails(AccessToken $token) {
    throw new OAuthException('OpenID claims require the authorization transaction nonce.');
  }

  private static function readJson($http, string $url): array {
    $body = $http->request('GET', $url, ['allow_redirects' => FALSE, 'timeout' => 10, 'stream' => TRUE]);
    if ($body->getStatusCode() !== 200) {
      throw new OAuthException('OpenID endpoint returned an unexpected HTTP status.');
    }
    $json = \GuzzleHttp\Psr7\Utils::copyToString($body->getBody(), 1048577);
    if (strlen($json) > 1048576 || !is_array($result = json_decode($json, TRUE))) {
      throw new OAuthException('Invalid OpenID endpoint response.');
    }
    return $result;
  }

  public function verifiedClaims(AccessToken $token, string $nonce): array {
    $jwt = $token->getValues()['id_token'] ?? NULL;
    if ($nonce === '' || !is_string($jwt)) {
      throw new OAuthException('Missing OpenID token or transaction nonce.');
    }
    $jwks = self::readJson($this->getHttpClient(), $this->metadata['jwks_uri']);
    if (!isset($jwks['keys']) || !is_array($jwks['keys'])) {
      throw new OAuthException('Invalid OpenID signing keys.');
    }
    $keys = array_filter($jwks['keys'], static fn($k) =>
      is_array($k) &&
      ($k['kty'] ?? '') === 'RSA' && ($k['alg'] ?? 'RS256') === 'RS256'
      && ($k['use'] ?? 'sig') === 'sig' && in_array('verify', $k['key_ops'] ?? ['verify'], TRUE)
    );
    try {
      $claims = (array) JWT::decode($jwt, JWK::parseKeySet(['keys' => array_values($keys)], 'RS256'));
    }
    catch (\Throwable $e) {
      throw new OAuthException('OpenID token signature or validity check failed.');
    }
    $aud = $claims['aud'] ?? NULL;
    $aud = is_string($aud) ? [$aud] : $aud;
    if (($claims['iss'] ?? NULL) !== $this->metadata['issuer']
      || !is_array($aud) || !array_is_list($aud) || !in_array($this->clientId, $aud, TRUE)
      || ((count($aud) > 1 || array_key_exists('azp', $claims)) && ($claims['azp'] ?? NULL) !== $this->clientId)
      || !is_int($claims['exp'] ?? NULL) || !is_int($claims['iat'] ?? NULL) || $claims['iat'] > time()
      || !is_string($claims['sub'] ?? NULL) || $claims['sub'] === ''
      || !is_string($claims['nonce'] ?? NULL) || !hash_equals($nonce, $claims['nonce'])) {
      throw new OAuthException('Invalid OpenID token claims.');
    }
    return json_decode(json_encode($claims, JSON_THROW_ON_ERROR), TRUE, 512, JSON_THROW_ON_ERROR);
  }

}
