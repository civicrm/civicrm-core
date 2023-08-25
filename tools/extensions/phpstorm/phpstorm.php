<?php

// require_once 'phpstorm.civix.php';
// phpcs:disable
// use CRM_Phpstorm_ExtensionUtil as E;
// phpcs:enable
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
// function phpstorm_civicrm_config(&$config): void {
//  _phpstorm_civix_civicrm_config($config);
// }

function phpstorm_civicrm_container(\Symfony\Component\DependencyInjection\ContainerBuilder $container) {
  $container->addCompilerPass(new \Civi\PhpStorm\PhpStormCompilePass(), PassConfig::TYPE_AFTER_REMOVING, 2000);
}
