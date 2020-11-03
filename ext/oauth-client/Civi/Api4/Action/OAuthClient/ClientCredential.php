<?php

namespace Civi\Api4\Action\OAuthClient;

use Civi\Api4\Generic\Result;

/**
 * Class AuthorizationCode
 * @package Civi\Api4\Action\OAuthClient
 *
 * In this workflow, we seek permission to access resources by relaying
 * a username and password.
 *
 * ```
 * $result = civicrm_api4('OAuthClient', 'clientCredential', [
 *   'where' => [['id', '=', 123],
 *   'storage' => 'OAuthSysToken',
 * ]);
 * ```
 *
 * If successful, the result will be a (redacted) token.
 *
 * @link https://tools.ietf.org/html/rfc6749#section-4.4
 */
class ClientCredential extends AbstractGrantAction {

  public function _run(Result $result) {
    $this->validate();

    $tokenRecord = \Civi::service('oauth2.token')->init([
      'client' => $this->getClientDef(),
      'scope' => $this->getScopes(),
      'storage' => $this->getStorage(),
      'tag' => $this->getTag(),
      'grant_type' => 'client_credentials',
    ]);

    $result[] = \CRM_OAuth_BAO_OAuthSysToken::redact($tokenRecord);
  }

}
