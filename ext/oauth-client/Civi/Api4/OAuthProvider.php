<?php

namespace Civi\Api4;

use Civi\Core\Event\GenericHookEvent;
use Civi\OAuth\CiviGenericProvider;

class OAuthProvider extends Generic\AbstractEntity {

  const TTL = 600;

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetAction
   */
  public static function get($checkPermissions = TRUE) {
    $action = new Generic\BasicGetAction('OAuthProvider', __FUNCTION__, function () {
      $cache = \Civi::cache('long');
      if (!$cache->has('OAuthProvider_list')) {
        $providers = [];
        $event = GenericHookEvent::create([
          'providers' => &$providers,
        ]);
        \Civi::dispatcher()->dispatch('hook_civicrm_oauthProviders', $event);

        foreach ($providers as $name => &$provider) {
          if ($provider['name'] !== $name) {
            throw new \CRM_Core_Exception(sprintf("Mismatched OAuth provider names: \"%s\" vs \"%s\"",
              $provider['name'], $name));
          }
          if (!isset($provider['class'])) {
            $provider['class'] = CiviGenericProvider::class;
          }
        }

        $cache->set('OAuthProvider_list', $providers, self::TTL);
      }
      return $cache->get('OAuthProvider_list');
    });
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    $action = new Generic\BasicGetFieldsAction('OAuthProvider', __FUNCTION__, function () {
      return [
        [
          'name' => 'name',
        ],
        [
          'name' => 'title',
        ],
        [
          'name' => 'class',
        ],
        [
          'name' => 'options',
        ],
        [
          'name' => 'contactTemplate',
        ],
        [
          'name' => 'mailSettingsTemplate',
        ],
      ];
    });
    return $action->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      "meta" => ["access CiviCRM"],
      "get" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}
