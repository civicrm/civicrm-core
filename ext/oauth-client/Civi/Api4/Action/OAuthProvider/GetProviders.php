<?php

namespace Civi\Api4\Action\OAuthProvider;

use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\OAuthProvider;
use Civi\OAuth\CiviGenericProvider;

class GetProviders extends BasicGetAction {

  protected function getRecords() {
    $cache = \Civi::cache('long');
    if (!$cache->has('OAuthProvider_list')) {
      $providers = [];
      \CRM_OAuth_Hook::oauthProviders($providers);

      foreach ($providers as $name => &$provider) {
        if ($provider['name'] !== $name) {
          throw new \CRM_Core_Exception(sprintf("Mismatched OAuth provider names: \"%s\" vs \"%s\"",
            $provider['name'], $name));
        }
        if (!isset($provider['class'])) {
          $provider['class'] = CiviGenericProvider::class;
        }
      }

      $cache->set('OAuthProvider_list', $providers, OAuthProvider::TTL);
    }
    return $cache->get('OAuthProvider_list');
  }

  protected function formatRawValues(&$records) {
    foreach ($records as &$record) {
      $record['permissions'] = $this->normalizePermissions($record);
    }
    parent::formatRawValues($records);
  }

  protected function filterArray($records) {
    if ($this->getCheckPermissions()) {
      $records = array_filter($records, function ($record) {
        return \CRM_Core_Permission::check($record['permissions']['meta']);
      });
    }
    return parent::filterArray($records);
  }

  protected function normalizePermissions(array $provider): array {
    // Nov 2025: These defaults chosen to match traditional specs from OAuthClient::permissions().

    $defaults = [
      // Meta: Can you see metadata (e.g. "OAuthProvider" records)?
      'meta' => ['access CiviCRM'],

      // Get: Can you view information about the enabled OAuthClients?
      'get' => [
        [
          'manage OAuth client',
          'manage my OAuth contact tokens',
          'manage all OAuth contact tokens',
        ],
      ],

      // Grants: Can you execute the various grant-flows?
      'default' => ['manage OAuth client'],
    ];
    return array_merge($defaults, $provider['permissions'] ?? []);
  }

}
