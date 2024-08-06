<?php

require_once 'shimmy.civix.php';
// phpcs:disable
use CRM_Shimmy_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function shimmy_civicrm_config(&$config) {
  _shimmy_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function shimmy_civicrm_install() {
  _shimmy_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function shimmy_civicrm_postInstall() {
  _shimmy_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function shimmy_civicrm_uninstall() {
  _shimmy_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function shimmy_civicrm_enable() {
  _shimmy_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function shimmy_civicrm_disable() {
  _shimmy_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function shimmy_civicrm_upgrade($op, ?CRM_Queue_Queue $queue = NULL) {
  return _shimmy_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function shimmy_civicrm_entityTypes(&$entityTypes) {
  _shimmy_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Assert that there is a service with a given name+type.
 *
 * @param string $class
 * @param string $expectServiceName
 * @param string $notServiceName
 * @throws \Exception
 */
function _shimmy_assert_service_object(string $class, string $expectServiceName, string $notServiceName) {
  if (Civi::container()->has($expectServiceName) && Civi::container()->has($notServiceName)) {
    throw new \Exception("Oops! Found both names ($expectServiceName and $notServiceName)!");
  }
  elseif (!Civi::container()->has($expectServiceName) && Civi::container()->has($notServiceName)) {
    throw new \Exception("Oops! Found ($notServiceName) and missing expected ($expectServiceName)!");
  }

  if (!(Civi::container()->get($expectServiceName) instanceof $class)) {
    $actual = Civi::container()->get($expectServiceName);
    $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
    throw new \Exception("Oops! The service ($expectServiceName) should be an instance the class ($class). But found ($actualType)!");
  }

}
