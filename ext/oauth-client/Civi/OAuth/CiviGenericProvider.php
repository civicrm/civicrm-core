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
 *   and proprietary scopes+URLs. To use this, set the the option:
 *
 *    "urlResourceOwnerDetails": "{{use_id_token}}",
 */
class CiviGenericProvider extends \League\OAuth2\Client\Provider\GenericProvider {

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
