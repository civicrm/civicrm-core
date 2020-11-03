<?php

require_once 'oauth_client.civix.php';
// phpcs:disable
use CRM_OauthClient_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function oauth_client_civicrm_config(&$config) {
  _oauth_client_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function oauth_client_civicrm_xmlMenu(&$files) {
  _oauth_client_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function oauth_client_civicrm_install() {
  _oauth_client_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function oauth_client_civicrm_postInstall() {
  _oauth_client_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function oauth_client_civicrm_uninstall() {
  _oauth_client_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function oauth_client_civicrm_enable() {
  _oauth_client_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function oauth_client_civicrm_disable() {
  _oauth_client_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function oauth_client_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _oauth_client_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function oauth_client_civicrm_managed(&$entities) {
  _oauth_client_civix_civicrm_managed($entities);
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
    $prefix . ts('manage OAuth client'),
    ts('Create and delete OAuth client connections'),
  ];
  $permissions['manage OAuth client secrets'] = [
    $prefix . ts('manage OAuth client secrets'),
    ts('Access OAuth secrets'),
  ];
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function oauth_client_civicrm_caseTypes(&$caseTypes) {
  _oauth_client_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function oauth_client_civicrm_angularModules(&$angularModules) {
  _oauth_client_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function oauth_client_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _oauth_client_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function oauth_client_civicrm_entityTypes(&$entityTypes) {
  _oauth_client_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function oauth_client_civicrm_themes(&$themes) {
  _oauth_client_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function oauth_client_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function oauth_client_civicrm_navigationMenu(&$menu) {
//  _oauth_client_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _oauth_client_civix_navigationMenu($menu);
//}

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
