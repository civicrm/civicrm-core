<?php

namespace Civi\OAuth;

trait ResponseModeTrait {

  /**
   * List of supported response-modes.
   *
   * @var array|string[]
   *
   *   Some mix of values. At time of writing, Civi supports:
   *
   *   - 'query': Values returned to the `$redirectUri` as query params. (Standard OAuth 2.0 behavior.)
   *   - 'web_message': Values returned via JS API: `window.opener.postMessage(params, civicrmInstanceUrl)`
   *     Requires the calling page to have a listener corresponding listener for `window.addEventListener` (where e.origin==findOrigin(urlAuthorize))
   *
   * @link https://www.ietf.org/archive/id/draft-meyerzuselha-oauth-web-message-response-mode-00.html
   */
  protected array $responseModes = ['query'];

  public function getResponseModes(): array {
    return $this->responseModes;
  }

  public function setResponseModes(array $responseModes): void {
    $this->responseModes = $responseModes;
  }

}
