<?php

require_once 'eventcart.civix.php';
// phpcs:disable
use CRM_Eventcart_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function eventcart_civicrm_config(&$config) {
  if (isset(Civi::$statics[__FUNCTION__])) {
    return;
  }
  Civi::$statics[__FUNCTION__] = 1;
  // Since as a hidden extension it's always enabled, until this is a "real" extension you can turn off we need to check the legacy setting.
  if ((bool) Civi::settings()->get('enable_cart')) {
    Civi::dispatcher()->addListener('hook_civicrm_pageRun', 'CRM_Event_Cart_PageCallback::run');
  }

  _eventcart_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function eventcart_civicrm_install() {
  _eventcart_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function eventcart_civicrm_postInstall() {
  _eventcart_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function eventcart_civicrm_uninstall() {
  _eventcart_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function eventcart_civicrm_enable() {
  _eventcart_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function eventcart_civicrm_disable() {
  _eventcart_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function eventcart_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _eventcart_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function eventcart_civicrm_entityTypes(&$entityTypes) {
  _eventcart_civix_civicrm_entityTypes($entityTypes);
}
