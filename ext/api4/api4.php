<?php

require_once 'api4.civix.php';

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Procedural wrapper for the OO api version 4.
 *
 * @param $entity
 * @param $action
 * @param array $params
 *
 * @return \Civi\Api4\Generic\Result
 */
function civicrm_api4($entity, $action, $params = []) {
  $params['version'] = 4;
  // For custom pseudo-entities
  if (strpos($entity, 'Custom_') === 0) {
    $params['customGroup'] = substr($entity, 7);
    $entity = 'CustomValue';
  }
  $request = \Civi\API\Request::create($entity, $action, $params);
  return \Civi::service('civi_api_kernel')->runRequest($request);
}

/**
 * @param ContainerBuilder $container
 */
function api4_civicrm_container($container) {
  $loader = new XmlFileLoader($container, new FileLocator(__DIR__));
  $loader->load('services.xml');

  $container->getDefinition('civi_api_kernel')->addMethodCall(
    'registerApiProvider',
    [new Reference('action_object_provider')]
  );

  // add event subscribers$container->get(
  $dispatcher = $container->getDefinition('dispatcher');
  $subscribers = $container->findTaggedServiceIds('event_subscriber');

  foreach (array_keys($subscribers) as $subscriber) {
    $dispatcher->addMethodCall(
      'addSubscriber',
      [new Reference($subscriber)]
    );
  }

  // add spec providers
  $providers = $container->findTaggedServiceIds('spec_provider');
  $gatherer = $container->getDefinition('spec_gatherer');

  foreach (array_keys($providers) as $provider) {
    $gatherer->addMethodCall(
      'addSpecProvider',
      [new Reference($provider)]
    );
  }

  if (defined('CIVICRM_UF') && CIVICRM_UF === 'UnitTests') {
    $loader->load('tests/services.xml');
  }
}

/**
 * Implements hook_civicrm_coreResourceList().
 */
function api4_civicrm_coreResourceList(&$list, $region) {
  if ($region == 'html-header') {
    Civi::resources()->addScriptFile('org.civicrm.api4', 'js/api4.js', -9000, $region);
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function api4_civicrm_config(&$config) {
  _api4_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function api4_civicrm_xmlMenu(&$files) {
  _api4_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function api4_civicrm_install() {
  _api4_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function api4_civicrm_uninstall() {
  _api4_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function api4_civicrm_enable() {
  _api4_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function api4_civicrm_disable() {
  _api4_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function api4_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _api4_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function api4_civicrm_managed(&$entities) {
  _api4_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function api4_civicrm_angularModules(&$angularModules) {
  _api4_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function api4_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _api4_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
