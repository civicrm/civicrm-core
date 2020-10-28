<?php

namespace Civi\Api4\Action\OAuthClient;

use Civi\Api4\Generic\Result;

/**
 * Class AuthorizationCode
 * @package Civi\Api4\Action\OAuthClient
 *
 * In this workflow, we seek permission from the browser-user to access
 * resources on their behalf. The result will be stored as a token.
 *
 * This API call merely *initiates* the workflow. It returns a fully-formed `url` for the
 * authorization service. You should redirect the user to this URL.
 *
 * ```
 * $result = civicrm_api4('OAuthClient', 'authorizationCode', [
 *   'where' => [['id', '=', 123],
 * ]);
 * $startUrl = $result->first()['url'];
 * CRM_Utils_System::redirect($startUrl);
 * ```
 *
 * @method $this setLandingUrl(string $landingUrl)
 * @method string getLandingUrl()
 *
 * @link https://tools.ietf.org/html/rfc6749#section-4.1
 */
class AuthorizationCode extends AbstractGrantAction {

  /**
   * If a user successfully completes the authentication, where should they go?
   *
   * This value will be stored in a way that is bound to the user session and
   * OAuth-request.
   *
   * @var string|null
   */
  protected $landingUrl = NULL;

  /**
   * Tee-up the authorization request.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->validate();

    /** @var \League\OAuth2\Client\Provider\GenericProvider $provider */
    $provider = $this->createLeagueProvider();

    // NOTE: If we don't set scopes, then getAuthorizationUrl() would implicitly use getDefaultScopes().
    // We aim to store the effective list, but the protocol doesn't guarantee a notification of
    // effective list.
    $scopes = $this->getScopes() ?: $this->callProtected($provider, 'getDefaultScopes');

    $stateId = \CRM_OAuth_Page_Return::storeState([
      'time' => \CRM_Utils_Time::getTimeRaw(),
      'clientId' => $this->getClientDef()['id'],
      'landingUrl' => $this->getLandingUrl(),
      'storage' => $this->getStorage(),
      'scopes' => $scopes,
    ]);
    $result[] = [
      'url' => $provider->getAuthorizationUrl([
        'state' => $stateId,
        'scope' => $scopes,
      ]),
    ];
  }

  /**
   * Call a protected method.
   *
   * @param mixed $obj
   * @param string $method
   * @param array $args
   * @return mixed
   */
  protected function callProtected($obj, $method, $args = []) {
    $r = new \ReflectionMethod(get_class($obj), $method);
    $r->setAccessible(TRUE);
    return $r->invokeArgs($obj, $args);
  }

}
