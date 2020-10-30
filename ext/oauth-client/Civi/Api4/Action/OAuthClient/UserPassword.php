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
 * $result = civicrm_api4('OAuthClient', 'userPassword', [
 *   'where' => [['id', '=', 123],
 *   'username' => 'johndoe',
 *   'password' => 'abcd1234',
 *   'storage' => 'OAuthSysToken',
 * ]);
 * ```
 *
 * If successful, the result will be a (redacted) token.
 *
 * @method $this setUsername(string $username)
 * @method string getUsername()
 * @method $this setPassword(string $password)
 * @method string getPassword()
 *
 * @link https://tools.ietf.org/html/rfc6749#section-4.3
 */
class UserPassword extends AbstractGrantAction {

  /**
   * @var string
   */
  protected $username;

  /**
   * @var string
   */
  protected $password;

  public function _run(Result $result) {
    $this->validate();

    $tokenRecord = \Civi::service('oauth2.token')->init([
      'client' => $this->getClientDef(),
      'scope' => $this->getScopes(),
      'storage' => $this->getStorage(),
      'tag' => $this->getTag(),
      'grant_type' => 'password',
      'cred' => [
        'username' => $this->getUsername(),
        'password' => $this->getPassword(),
      ],
    ]);

    $result[] = \CRM_OAuth_BAO_OAuthSysToken::redact($tokenRecord);
  }

}
