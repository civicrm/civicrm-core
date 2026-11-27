<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

use Civi\Api4\OAuthProvider;
use Civi\Core\Attribute\CiviEventListener;
use CRM_OAuth_ExtensionUtil as E;

class CRM_OAuth_BAO_OAuthClient extends CRM_OAuth_DAO_OAuthClient {

  #[CiviEventListener(event: 'civi.api4.validate::OAuthClient')]
  public static function onValidateProvider(\Civi\Api4\Event\ValidateValuesEvent $e) {
    // Hrm, APIv4 doesn't validate <callback> PC's by default.
    $ps = NULL;
    foreach ($e->diffs as $r => $diff) {
      if (isset($diff['new']['provider'])) {
        $ps ??= \CRM_OAuth_BAO_OAuthClient::getProviders();
        if (!isset($ps[$diff['new']['provider']])) {
          $e->addError($r, 'provider', 'invalid_provider', "Invalid provider name: " . $diff['new']['provider']);
        }
      }
    }
  }

  #[CiviEventListener(event: 'civi.api4.validate::OAuthClient')]
  public static function onValidatePkce(\Civi\Api4\Event\ValidateValuesEvent $e) {
    foreach ($e->diffs as $r => $diff) {
      if (!array_key_exists('pkce_method', $diff['new']) && !array_key_exists('provider', $diff['new'])) {
        continue;
      }

      $pkceMethod = $diff['new']['pkce_method'] ?? $diff['old']['pkce_method'] ?? 'auto';
      $providerName = $diff['new']['provider'] ?? $diff['old']['provider'] ?? NULL;

      $provider = OAuthProvider::get(FALSE)->addWhere('name', '=', $providerName)->execute()->first();
      $allowedMethods = !empty($provider)
        ? ['auto', ...$provider['pkceMethods']]
        : array_keys(\CRM_OAuth_BAO_OAuthClient::getPkceMethods());

      if (!in_array($pkceMethod, $allowedMethods, TRUE)) {
        $e->addError($r, 'pkce_method', 'invalid_pkce_method', "Invalid pkce_method=$pkceMethod for provider=$providerName");
      }
    }
  }

  /**
   * @return array
   *   List of methods. "Auto" comes first. All other options are sorted by preference (best to worst).
   */
  public static function getPkceMethods(): array {
    return [
      'auto' => E::ts('Auto'),
      'S256' => E::ts('S256'),
      'plain' => E::ts('Plain'),
      'none' => E::ts('None'),
    ];
  }

  /**
   * @return array
   *   ~~Ex: ['my_provider' => 'My Provider']~~
   *   Ex: ['my_provider' => 'my_provider']
   */
  public static function getProviders() {
    if (!isset(Civi::$statics[__FUNCTION__])) {
      if (!class_exists('\Civi\Api4\OAuthProvider')) {
        return [];
      }
      $ps = Civi\Api4\OAuthProvider::get(FALSE)
        ->setSelect(['name', 'title'])
        ->execute();
      $titles = [];
      foreach ($ps as $p) {
        $titles[$p['name']] = $p['name'];
        // $titles[$p['name']] = $p['title'];
      }
      Civi::$statics[__FUNCTION__] = $titles;
    }
    return Civi::$statics[__FUNCTION__];
  }

  /**
   * Determine the "redirect_uri". When using authorization-code flow, the
   * OAuth2 provider will redirect back to our "redirect_uri".
   *
   * @return string
   */
  public static function getRedirectUri() {
    return \Civi::settings()->get('oauthClientRedirectUrl') ?:
      \CRM_Utils_System::url('civicrm/oauth-client/return', NULL, TRUE, NULL, FALSE, FALSE, TRUE);
  }

  /**
   * @param string|null $entityName
   * @param int|null $userId
   * @param array $conditions
   * @inheritDoc
   */
  public function addSelectWhereClause(?string $entityName = NULL, ?int $userId = NULL, array $conditions = []): array {
    $clauses = parent::addSelectWhereClause($entityName, $userId, $conditions);

    $allowProviders = _oauth_client_providers_by_perm('get');
    $none = [chr(0)];
    $clauses['provider'][] = 'IN (' . CRM_Core_DAO::escapeStrings(empty($allowProviders) ? $none : $allowProviders) . ')';

    return $clauses;
  }

}
