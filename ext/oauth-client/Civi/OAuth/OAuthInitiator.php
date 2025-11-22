<?php

namespace Civi\OAuth;

use Civi\Core\Service\AutoService;
use CRM_OAuth_ExtensionUtil as E;

/**
 * Provide a helper to have an "initiator" trigger an AuthorizationCode request.
 *
 * Example:
 *   $initiator['render'] = function($region, ...) {
 *     Civi::service('oauth_client.initiator')->renderButton($region, 'Connect to Foo Bar', [...authCodeGrantOptions...]);
 *   };
 *
 * @service oauth_client.initiator
 */
class OAuthInitiator extends AutoService {

  /**
   * Display an authorization-code request to the user.
   *
   * @param \CRM_Core_Resources_CollectionAdderInterface $resources
   * @param string $label
   * @param array $authCodeOptions
   * @see \Civi\Api4\Action\OAuthClient\AuthorizationCode
   */
  public function renderButton(\CRM_Core_Resources_CollectionAdderInterface $resources, string $label, array $authCodeOptions): void {
    $resources->addScriptFile(E::LONG_NAME, 'js/oauth.initiator.js');
    $resources->addMarkup(sprintf(
      '<div><a class="btn btn-xs btn-primary" href="#" onclick="CRM.oauth.authorizationCode(%s)">%s</a></div>',
      htmlentities(\CRM_Utils_JSON::encodeScriptVar($authCodeOptions)),
      htmlentities($label)
    ));
  }

}
