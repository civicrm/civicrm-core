<?php

require_once 'oauth_client.civix.php';

use CRM_OAuth_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function oauth_client_civicrm_config(&$config) {
  _oauth_client_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_permission().
 *
 * @see CRM_Utils_Hook::permission()
 * @see CRM_Core_Permission::getCorePermissions()
 */
function oauth_client_civicrm_permission(&$permissions) {
  $prefix = ts('CiviCRM') . ': ';
  $permissions['manage OAuth client'] = [
    'label' => $prefix . ts('manage OAuth client'),
    'description' => ts('Create and delete OAuth client connections'),
  ];
  $permissions['manage OAuth client secrets'] = [
    'label' => $prefix . ts('manage OAuth client secrets'),
    'description' => ts('Access OAuth secrets'),
  ];
  $permissions['create OAuth tokens via auth code flow'] = [
    'label' => $prefix . ts('create OAuth tokens via auth code flow'),
    'description' => ts('Create OAuth tokens via the authorization code flow'),
  ];
  $permissions['manage my OAuth contact tokens'] = [
    'label' => $prefix . ts('manage my OAuth contact tokens'),
    'description' => ts("Manage user's own OAuth tokens"),
  ];
  $permissions['manage all OAuth contact tokens'] = [
    'label' => $prefix . ts('manage all OAuth contact tokens'),
    'description' => ts("Manage OAuth tokens for all contacts"),
    'implies' => ['manage my OAuth contact tokens'],
  ];
}

/**
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function oauth_client_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->setDefinition('oauth2.league', new \Symfony\Component\DependencyInjection\Definition(
    \Civi\OAuth\OAuthLeagueFacade::class, []))->setPublic(TRUE);
  $container->setDefinition('oauth2.token', new \Symfony\Component\DependencyInjection\Definition(
    \Civi\OAuth\OAuthTokenFacade::class, []))->setPublic(TRUE);
}

/**
 * Implements hook_civicrm_oauthProviders().
 */
function oauth_client_civicrm_oauthProviders(&$providers) {
  $ingest = function($pat) use (&$providers) {
    $files = (array) glob($pat);
    foreach ($files as $file) {
      if (!defined('CIVICRM_TEST') && preg_match(';\.test\.json$;', $file)) {
        continue;
      }
      $name = preg_replace(';\.(dist\.|test\.|)json$;', '', basename($file));
      $provider = json_decode(file_get_contents($file), 1);
      $provider['name'] = $name;
      $providers[$name] = $provider;
    }
  };

  $ingest(__DIR__ . '/providers/*.json');
  $localDir = Civi::paths()->getPath('[civicrm.private]/oauth-providers');
  if (file_exists($localDir)) {
    $ingest($localDir . '/*.json');
  }
}

/**
 * Implements hook_civicrm_mailSetupActions().
 *
 * @see CRM_Utils_Hook::mailSetupActions()
 */
function oauth_client_civicrm_mailSetupActions(&$setupActions) {
  $setupActions = array_merge($setupActions, CRM_OAuth_MailSetup::buildSetupLinks());
}

/**
 * Implements hook_civicrm_oauthReturn().
 */
function oauth_client_civicrm_oauthReturn($token, &$nextUrl) {
  CRM_OAuth_MailSetup::onReturn($token, $nextUrl);
}

/**
 * Implements hook_civicrm_alterMailStore().
 *
 * @see CRM_Utils_Hook::alterMailStore()
 */
function oauth_client_civicrm_alterMailStore(&$mailSettings) {
  CRM_OAuth_MailSetup::alterMailStore($mailSettings);
}

/**
 * @see CRM_Utils_Hook::managed()
 */
function oauth_client_civicrm_managed(array &$entities, ?array $modules = NULL): void {
  if ($modules !== NULL && !in_array(E::LONG_NAME, $modules)) {
    return;
  }

  $providers = [];
  \CRM_OAuth_Hook::oauthProviders($providers);

  foreach ($providers as $provider) {
    if (array_intersect($provider['tags'] ?? [], ['CiviConnect', 'CiviConnectSandbox', 'CiviConnectLocal'])) {
      $entities[] = [
        'module' => E::LONG_NAME,
        'name' => 'CiviConnect_' . $provider['name'],
        'entity' => 'OAuthClient',
        'params' => [
          'version' => 4,
          'values' => [
            'provider' => $provider['name'],
            'guid' => '{civi_connect}',
            'secret' => '{civi_connect}',
          ],
        ],
        'update' => 'always',
        'cleanup' => 'unused',
      ];
    }
  }
}
