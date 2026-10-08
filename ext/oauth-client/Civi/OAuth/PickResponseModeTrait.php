<?php
namespace Civi\OAuth;

/**
 * Helper for a UI which starts an authorization flow and must choose a response-mode
 * that both the UI and the provider support.
 *
 * @see ResponseModeTrait for the provider-side declaration.
 */
trait PickResponseModeTrait {

  /**
   * @param \League\OAuth2\Client\Provider\AbstractProvider|\Civi\OAuth\CiviGenericProvider $providerObj
   * @param array $preferResponseModes
   *   List of response-modes supported by the calling UI, in order of preference.
   *   Ex: ['web_message', 'query', 'fragment']
   * @return string|null
   */
  protected function pickResponseMode($providerObj, array $preferResponseModes): ?string {
    $allowResponseModes = is_callable([$providerObj, 'getResponseModes']) ? $providerObj->getResponseModes() : ['query'];
    foreach ($preferResponseModes as $preferResponseMode) {
      if (in_array($preferResponseMode, $allowResponseModes)) {
        return $preferResponseMode;
      }
    }
    return NULL;
  }

}
