<?php

namespace Civi\OAuth;

use Civi\Api4\Action\OAuthClient\AuthorizationCode;
use Civi\Core\Service\AutoService;
use CRM_OAuth_ExtensionUtil as E;

/**
 * Provide a helper to have an "initiator" trigger an AuthorizationCode request.
 *
 * Example:
 *   $initiator['callback'] = function(..., $resources) {
 *     $authCode = OAuthClient::authorizationCode()->addWhere('id', '=', 1234);
 *     Civi::service('oauth_client.initiator')->render($authCode, $resources);
 *   };
 *
 * @service oauth_client.initiator
 */
class OAuthInitiator extends AutoService {

  /**
   * Display an authorization-code request to the user.
   *
   * @param \Civi\Api4\Action\OAuthClient\AuthorizationCode $authorizationCode
   * @param \CRM_Core_Resources_CollectionAdderInterface $resources
   */
  public function render(AuthorizationCode $authorizationCode, \CRM_Core_Resources_CollectionAdderInterface $resources): void {
    $data = $authorizationCode->execute()->first();
    $resources->addScriptFile(E::LONG_NAME, 'js/oauth.initiator.js');
    $resources->addScript(sprintf("window.CRM.initiateOauth(%s);", \CRM_Utils_JSON::encodeScriptVar($data)), ['weight' => 1000]);
  }

}
