<?php
namespace Civi\OAuth;

use League\OAuth2\Client\Token\AccessToken;

/**
 * Class CiviGenericProvider
 * @package Civi\OAuth
 *
 * This is a variant of "GenericProvider" which tries to support some newer
 * conventions out-of-the-box.
 *
 * - By default, do not send "approval_prompt" for auth-code requests. Providers
 *   may prefer "prompt" nowadays.
 * - Allow one to fetch claims about the resource-owner from the `id_token`
 *   supported by OpenID Connect. This reduces the need for extra round-trips
 *   and proprietary scopes+URLs. To use this, set the option:
 *     "urlResourceOwnerDetails": "{{use_id_token}}",
 * - Allow support for {{tenant}} token in provider URLs, if the provider has
 *   the 'tenancy' option set to TRUE (eg: ms-exchange).
 */
class CiviGenericProvider extends \League\OAuth2\Client\Provider\GenericProvider {

  /**
   * @var string
   */
  protected $tenant;

  protected function getAuthorizationParameters(array $options) {
    $newOptions = parent::getAuthorizationParameters($options);
    if (!isset($options['approval_prompt'])) {
      // GenericProvider insists on filling in "approval_prompt", but this seems
      // to be disfavored nowadays b/c OpenID Connect defines "prompt".
      unset($newOptions['approval_prompt']);
    }
    return $newOptions;
  }

  /**
   * Returns the base URL for authorizing a client.
   *
   * Eg. https://oauth.service.com/authorize
   *
   * @return string
   */
  public function getBaseAuthorizationUrl() {
    $url = parent::getBaseAuthorizationUrl();
    return $this->replaceTenantToken($url);
  }

  /**
   * Returns the base URL for requesting an access token.
   *
   * Eg. https://oauth.service.com/token
   *
   * @param array $params
   * @return string
   */
  public function getBaseAccessTokenUrl(array $params) {
    $url = parent::getBaseAccessTokenUrl($params);
    return $this->replaceTenantToken($url);
  }

  /**
   * Replace {{tenant}} in the endpoint URLs with 'common' for consumer accounts
   * or the tenancy ID for dedicated services.
   *
   * @param string $str URL to replace
   * @return string
   */
  private function replaceTenantToken($str) {
    if (str_contains($str, '{{tenant}}')) {
      $tenant = !empty($this->tenant) ? $this->tenant : 'common';
      $str = str_replace('{{tenant}}', $tenant, $str);
    }
    return $str;
  }

  /**
   * Requests resource owner details.
   *
   * @param \League\OAuth2\Client\Token\AccessToken $token
   * @return mixed
   */
  protected function fetchResourceOwnerDetails(AccessToken $token) {
    $url = $this->getResourceOwnerDetailsUrl($token);

    // If there is no resource-owner URL, and if there is an id_token, use it.
    if ($url === '{{use_id_token}}') {
      $tokenData = $token->jsonSerialize();
      if (isset($tokenData['id_token'])) {
        $idToken = $this->decodeUnauthenticatedJwt($tokenData['id_token']);

        // As long as id_token comes from a secure source, we can skip signature check.
        // Which is fortunate... because we don't what key to check against...
        if (!preg_match(';^https:;', $this->getBaseAccessTokenUrl([]))) {
          throw new \RuntimeException("Cannot decode ID token from insecure source.");
        }

        return $idToken['payload'];
      }
    }

    return parent::fetchResourceOwnerDetails($token);
  }

  private function decodeUnauthenticatedJwt($t) {
    list ($header, $payload) = explode('.', $t);

    return [
      'header' => json_decode(base64_decode($header), 1),
      'payload' => json_decode(base64_decode($payload), 1),
    ];
  }

}
