<?php

namespace Civi\Api4\Action\OAuthClient;

use Civi\Api4\Generic\Result;
use Civi\OAuth\OAuthException;

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
 * @method $this setPrompt(string $prompt)
 * @method string getPrompt()
 * @method $this setResponseMode(string $responseMode)
 * @method string getResponseMode()
 * @method $this setTtl(int $ttl)
 * @method int getTtl()
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
   * @var string
   *   Ex: 'none', 'consent', 'select_account'
   *
   * @see https://docs.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow
   * @see https://developers.google.com/identity/protocols/oauth2/web-server
   */
  protected $prompt = NULL;

  /**
   * How long we will wait for the user return. After this time, the stored "state" is lost.
   *
   * @var int
   *  Duration, in seconds
   */
  protected $ttl = 3600;

  /**
   * How do we expect the OAuth server to send its response data?
   *
   * Note: The OAuthProvider should declare a list of supported responseModes (default: `["query"]`).
   * If the requested mode is not supported, then the AuthorizationCode request will fail.
   *
   * @var string|null
   */
  protected $responseMode;

  /**
   * Tee-up the authorization request.
   *
   * @param \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    $this->validate();
    $output = [];

    /** @var \League\OAuth2\Client\Provider\GenericProvider|\Civi\OAuth\CiviGenericProvider $provider */
    $provider = $this->createLeagueProvider();

    // NOTE: If we don't set scopes, then getAuthorizationUrl() would implicitly use getDefaultScopes().
    // We aim to store the effective list, but the protocol doesn't guarantee a notification of
    // effective list.
    $scopes = $this->getScopes() ?: $this->callProtected($provider, 'getDefaultScopes');

    $stateId = \Civi::service('oauth2.state')->store([
      'time' => \CRM_Utils_Time::time(),
      'ttl' => $this->getTtl(),
      'clientId' => $this->getClientDef()['id'],
      'grant_type' => 'authorization_code',
      'landingUrl' => $this->getLandingUrl(),
      'storage' => $this->getStorage(),
      'scopes' => $scopes,
      'tag' => $this->getTag(),
    ]);
    $authOptions = [
      'state' => $stateId,
      'scope' => $scopes,
    ];
    if ($this->prompt !== NULL) {
      $authOptions['prompt'] = $this->prompt;
    }

    $allowResponseModes = is_callable([$provider, 'getResponseModes']) ? $provider->getResponseModes() : ['query'];
    $output['response_mode'] = $this->responseMode ?: 'query';
    if (!in_array($output['response_mode'], $allowResponseModes)) {
      throw new \CRM_Core_Exception('Unsupported response mode: ' . $output['response_mode']);
    }
    switch ($output['response_mode']) {
      case 'query':
        // This is standard/default. No need to pass literal `?response_mode=query`.
        break;

      case 'web_message':
        $authOptions['response_mode'] = $this->getResponseMode();
        $output['response_origin'] = \CRM_Utils_Url::toOrigin($provider->getBaseAuthorizationUrl());
        $output['continue_url'] = \CRM_OAuth_BAO_OAuthClient::getRedirectUri();
        break;

      default:
        throw new \CRM_Core_Exception('Unsupported response mode: ' . $output['response_mode']);
    }

    $result[] = $output + [
      'url' => $provider->getAuthorizationUrl($authOptions),
    ];
  }

  protected function validate() {
    parent::validate();
    if ($this->landingUrl) {
      $landingUrlParsed = parse_url($this->landingUrl);
      $landingUrlIp = gethostbyname($landingUrlParsed['host'] . '.');
      $allowedBases = [
        \Civi::paths()->getVariable('cms.root', 'url'),
        \Civi::paths()->getVariable('civicrm.root', 'url'),
      ];
      foreach ($allowedBases as $allowed) {
        $allowedParsed = parse_url($allowed);
        $allowedIp = gethostbyname($allowedParsed['host'] . '.');
        if ($landingUrlIp === $allowedIp && $landingUrlParsed['scheme'] == $allowedParsed['scheme']) {
          return;
        }
      }
      throw new OAuthException("Cannot initiate OAuth. Unsupported landing URL.");
    }
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
    return $r->invokeArgs($obj, $args);
  }

}
