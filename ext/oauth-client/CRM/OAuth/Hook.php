<?php

use Civi\Core\Event\GenericHookEvent;

class CRM_OAuth_Hook {

  /**
   * Fires a hook whenever we receive an updated OAuth token.
   *
   * @param string $flow
   *   Ex: 'init' or 'refresh'
   * @param string $type
   *   Ex: 'OAuthSysToken', 'OAuthContactToken', 'OAuthSessionToken'
   * @param array $token
   *   Ex: ['tag' => 'foo', 'client_id' => 123]
   * @return void
   */
  public static function hook_civicrm_oauthToken(string $flow, string $type, array &$token) {
    $event = GenericHookEvent::create([
      'flow' => $flow,
      'type' => $type,
      'token' => &$token,
    ]);
    \Civi::dispatcher()->dispatch('hook_civicrm_oauthToken', $event);
  }

}
