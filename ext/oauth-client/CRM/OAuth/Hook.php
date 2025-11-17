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
   *
   *   The following fields are equally applicable to all storage-types:
   *
   *   - access_token
   *   - client_id
   *   - expires
   *   - grant_type
   *   - raw
   *   - refresh_token
   *   - resource_owner
   *   - resource_owner_name
   *   - scopes
   *   - tag
   *   - token_type
   *
   *   Some fields depend on the storage-type:
   *
   *    - id (OAuthSysToken, OAuthContactToken)
   *    - contact_id (OAuthContactToken)
   *    - cardinal (OAuthSessionToken)
   *    - created_date (extant records; OAuthSysToken, OAuthContactToken)
   *    - modified_date (extant records; OAuthSysToken, OAuthContactToken)
   *
   * @return void
   */
  public static function oauthToken(string $flow, string $type, array &$token) {
    $event = GenericHookEvent::create([
      'flow' => $flow,
      'type' => $type,
      'token' => &$token,
    ]);
    \Civi::dispatcher()->dispatch('hook_civicrm_oauthToken', $event);
  }

  /**
   * Fires whenever a user returns to our site (from a successful AuthCode flow).
   *
   * @param array $tokenRecord
   *   The newly created token record (e.g OAuthSysToken or OAuthContactToken)
   * @param string|null $nextUrl
   *   When the user returns from an OAuth provider, we can redirect them to an internal page.
   *   This alterable string identifies the next page.
   * @return void
   */
  public static function oauthReturn(array $tokenRecord, ?string &$nextUrl): void {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'token' => $tokenRecord,
      'nextUrl' => &$nextUrl,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_oauthReturn', $event);
  }

  /**
   * Fires whenever a user returns to our site (from an unsuccessful AuthCode flow).
   *
   * @param string|null $error
   * @param string|null $description
   * @param string|null $uri
   * @param string $state
   * @return void
   */
  public static function oauthReturnError(?string $error, ?string $description, ?string $uri, string $state): void {
    $event = \Civi\Core\Event\GenericHookEvent::create([
      'error' => $error,
      'description' => $description,
      'uri' => $uri,
      'state' => $state,
    ]);
    Civi::dispatcher()->dispatch('hook_civicrm_oauthReturnError', $event);
  }

}
