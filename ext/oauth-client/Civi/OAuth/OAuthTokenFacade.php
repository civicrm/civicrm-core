<?php

namespace Civi\OAuth;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class OAuthTokenFacade {

  const STORAGE_TYPES = ';^OAuth(Sys|Contact|Session)Token$;';

  /**
   * Request and store a token.
   *
   * @param array $options
   *   With some mix of the following:
   *   - client: array, the OAuthClient record
   *   - scope: array|string|null, list of scopes to request. if omitted,
   *   inherit default from client/provider
   *   - storage: string, default: "OAuthSysToken"
   *   - tag: string|null, a symbolic/freeform identifier for looking-up tokens
   *   - grant_type: string, ex "authorization_code", "client_credentials",
   *   "password"
   *   - cred: array, extra credentialing options to pass to the "token" URL
   *   (via getAccessToken($tokenOptions)), eg "username", "password", "code"
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @see \League\OAuth2\Client\Provider\AbstractProvider::getAccessToken()
   */
  public function init($options): array {
    $options['storage'] ??= 'OAuthSysToken';
    if (!preg_match(self::STORAGE_TYPES, $options['storage'])) {
      throw new \CRM_Core_Exception("Invalid token storage ({$options['storage']})");
    }

    /** @var \League\OAuth2\Client\Provider\GenericProvider $provider */
    $provider = \Civi::service('oauth2.league')
      ->createProvider($options['client']);
    $scopeSeparator = $this->callProtected($provider, 'getScopeSeparator');

    $sendOptions = $options['cred'] ?? [];
    if (isset($options['scope']) && $options['scope'] !== NULL) {
      switch ($options['grant_type']) {
        case 'authorization_code':
          // already sent.
          break;

        default:
          $sendOptions['scope'] = $this->implodeScopes($scopeSeparator, $options['scope']);
      }
    }

    /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
    $accessToken = $provider->getAccessToken($options['grant_type'], $sendOptions);
    $values = $accessToken->getValues();

    $tokenRecord = [
      'client_id' => $options['client']['id'],
      'grant_type' => $options['grant_type'],
      'tag' => $options['tag'] ?? NULL,
      'scopes' => $this->splitScopes($scopeSeparator, $values['scope'] ?? $options['scope'] ?? NULL),
      'token_type' => $values['token_type'] ?? NULL,
      'access_token' => $accessToken->getToken(),
      'refresh_token' => $accessToken->getRefreshToken(),
      'expires' => $accessToken->getExpires(),
      'raw' => $accessToken->jsonSerialize(),
      'storage' => $options['storage'],
    ];

    try {
      $owner = $provider->getResourceOwner($accessToken);
      $tokenRecord['resource_owner_name'] = $this->findName($owner);
      $tokenRecord['resource_owner'] = $owner->toArray();
    }
    catch (\Throwable $e) {
      \Civi::log()->warning("Failed to resolve resource_owner");
    }

    return civicrm_api4($options['storage'], 'create', [
      'checkPermissions' => FALSE,
      'values' => $tokenRecord,
    ])->single();
  }

  /**
   * Call a protected method.
   *
   * @param mixed $obj
   * @param string $method
   * @param array $args
   *
   * @return mixed
   */
  protected function callProtected($obj, string $method, $args = []) {
    $r = new \ReflectionMethod(get_class($obj), $method);
    $r->setAccessible(TRUE);
    return $r->invokeArgs($obj, $args);
  }

  /**
   * @param string $delim
   * @param string|array|null $scopes
   *
   * @return array|null
   */
  protected function splitScopes(string $delim, $scopes) {
    if ($scopes === NULL || is_array($scopes)) {
      return $scopes;
    }
    if ($scopes === '') {
      return [];
    }
    if (is_string($scopes)) {
      return explode($delim, $scopes);
    }
    \Civi::log()->warning("Failed to explode scopes", [
      'scopes' => $scopes,
    ]);
    return NULL;
  }

  protected function implodeScopes($delim, $scopes): ?string {
    if ($scopes === NULL || is_string($scopes)) {
      return $scopes;
    }
    if (is_array($scopes)) {
      return implode($delim, $scopes);
    }
    \Civi::log()->warning("Failed to implode scopes", [
      'scopes' => $scopes,
    ]);
    return NULL;
  }

  protected function findName(ResourceOwnerInterface $owner) {
    if (method_exists($owner, 'getName')) {
      return $owner->getName();
    }
    $values = $owner->toArray();
    $fields = ['upn', 'userPrincipalName', 'mail', 'email', 'id'];
    foreach ($fields as $field) {
      if (isset($values[$field])) {
        return $values[$field];
      }
    }
    return $owner->getId();
  }

}
