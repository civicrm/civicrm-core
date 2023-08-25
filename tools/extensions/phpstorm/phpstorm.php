<?php

// require_once 'phpstorm.civix.php';
// phpcs:disable
// use CRM_Phpstorm_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
// function phpstorm_civicrm_config(&$config): void {
//  _phpstorm_civix_civicrm_config($config);
// }

/**
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 * @return void
 * @see \CRM_Utils_Hook::container()
 */
function phpstorm_civicrm_container($container): void {
  // Delegate pattern. There aren't many other ways to listen to this ehook.
  \Civi\PhpStorm\Generator::generate($container);
}
