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

}
