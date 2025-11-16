<?php

use Civi\Core\Event\GenericHookEvent;

class CRM_OAuth_Hook {

  /**
   * Generate a list of available OAuth providers.
   *
   * @param array $providers
   *   Alterable list of providers, keyed by symbolic name. Each has these properties:
   *     - name: string (MUST match the key in the array)
   *     - class: string, the controller class which generates and sends token requests (default: CiviGenericProvider)
   *     - options: array, constructor arguments for the driver class. Typical properties are:
   *         - urlAuthorize: string (e.g. "https://api.example.com/auth")
   *         - urlAccessToken: string (e.g. "https://api.example.com/token")
   *         - urlResourceOwnerDetails: string (e.g. "https://api.example.com/owner")
   *         - scopes: array (e.g. "read_profile", "frobnicate_widgets")
   *         - responseModes: array (e.g. "query", "web_message")
   *     - tags: string[], list of free-tags describing the purpose/behavior of this provider
   */
  public static function oauthProviders(array &$providers): void {
    $event = GenericHookEvent::create([
      'providers' => &$providers,
    ]);
    \Civi::dispatcher()->dispatch('hook_civicrm_oauthProviders', $event);
  }

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
