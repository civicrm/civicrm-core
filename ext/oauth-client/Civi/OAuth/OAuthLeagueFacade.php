<?php

namespace Civi\OAuth;

class OAuthLeagueFacade {

  /**
   * @param array $clientDef
   *   The OAuthClient record. This may be a full record, or it may be
   *   brief stub with 'id' or 'provider'. (In which case, it will look for
   *   exactly one matching client.)
   * @return \League\OAuth2\Client\Provider\AbstractProvider
   */
  public function createProvider($clientDef) {
    list ($class, $options) = $this->createProviderOptions($clientDef);
    return new $class($options);
  }

  /**
   * @param array $clientDef
   *   The OAuthClient record. This may be a full record, or it may be
   *   brief stub with 'id' or 'provider'. (In which case, it will look for
   *   exactly one matching client.)
   * @return array
   */
  public function createProviderOptions($clientDef) {
    $clientDef = $this->resolveSingleRef('OAuthClient', $clientDef, ['id', 'provider'], ['secret', 'guid']);
    $providerDef = \Civi\Api4\OAuthProvider::get(FALSE)
      ->addWhere('name', '=', $clientDef['provider'])
      ->execute()
      ->single();

    $class = $providerDef['class'];

    $localOptions = [];
    $localOptions['clientId'] = $clientDef['guid'];
    $localOptions['tenant'] = !empty($clientDef['tenant']) ? $clientDef['tenant'] : '';
    $localOptions['clientSecret'] = $clientDef['secret'];
    $options = array_merge(
      $providerDef['options'] ?? [],
      $clientDef['options'] ?? [],
      $localOptions
    );
    $options['redirectUri'] ??= \CRM_OAuth_BAO_OAuthClient::getRedirectUri();

    return [$class, $options];
  }

  /**
   * Create an instance of the PHP League's OAuth2 client for interacting with
   * a given token.
   *
   * @param array $tokenRecord
   * @return array
   *   An array with properties:
   *   - provider: League\OAuth2\Client\Provider\AbstractProvider
   *   - token: League\OAuth2\Client\Token\AccessTokenInterface
   * @throws \Civi\OAuth\OAuthException
   */
  public function create($tokenRecord) {
    $tokenRecord = $this->resolveSingleRef('OAuthSysToken', $tokenRecord, ['id'], ['client_id', 'raw']);
    $provider = $this->createProvider(['id' => $tokenRecord['client_id']]);
    $token = new \League\OAuth2\Client\Token\AccessToken($tokenRecord['raw']);
    return [
      'provider' => $provider,
      'token' => $token,
    ];
  }

  /**
   * Given a $record, determine if it is complete enough for usage. If not,
   * attempt to load the full record. Throw an exception if we don't find it.
   *
   * @param string $entity
   *   The of record that we want to load. (APIv4 entity)
   * @param array $record
   *   A complete or partial API record
   * @param array $lookupFields
   *   A list of key fields that can be used to lookup records.
   * @param array $requireFields
   *   A list of data fields that we need to have.
   * @return array
   * @throws \Civi\OAuth\OAuthException
   */
  protected function resolveSingleRef($entity, $record, $lookupFields, $requireFields) {
    $requireFields = array_unique(array_merge($lookupFields, $requireFields));
    $hasReqs = TRUE;
    foreach ($requireFields as $field) {
      $hasReqs = $hasReqs && isset($record[$field]);
    }

    if ($hasReqs) {
      return $record;
    }

    $where = [];
    foreach ($lookupFields as $field) {
      if (isset($record[$field])) {
        $where[] = [$field, '=', $record[$field]];

      }
    }

    if (empty($where)) {
      throw new OAuthException("Incomplete reference to $entity. Must have at least one of these fields: " . implode(',', $lookupFields));
    }

    return civicrm_api4($entity, 'get', [
      'where' => $where,
      'checkPermissions' => FALSE,
    ])->single();
  }

}
