<?php

class CRM_OAuth_Angular {

  public static function getSettings() {
    $s = [];

    $s['redirectUrl'] = \CRM_OAuth_BAO_OAuthClient::getRedirectUri();
    $s['providers'] = self::getProvidersWithClientCounts();

    return $s;
  }

  /**
   * Get the provider list, annotated with the number of clients configured for each.
   *
   * @return array
   *   Providers keyed by name, each with extra keys `client_count` and `active_client_count`.
   */
  private static function getProvidersWithClientCounts(): array {
    $providers = civicrm_api4('OAuthProvider', 'get', [])->indexBy('name')->getArrayCopy();

    $counts = [];
    // Permission-checked, so this only counts clients whose provider the user may see.
    $clients = \Civi\Api4\OAuthClient::get()->addSelect('provider', 'is_active')->execute();
    foreach ($clients as $client) {
      $counts[$client['provider']]['total'] = ($counts[$client['provider']]['total'] ?? 0) + 1;
      if ($client['is_active']) {
        $counts[$client['provider']]['active'] = ($counts[$client['provider']]['active'] ?? 0) + 1;
      }
    }

    foreach ($providers as $name => &$provider) {
      $provider['client_count'] = $counts[$name]['total'] ?? 0;
      $provider['active_client_count'] = $counts[$name]['active'] ?? 0;
    }

    return $providers;
  }

}
