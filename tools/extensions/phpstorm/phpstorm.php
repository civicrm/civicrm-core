<?php

use Symfony\Component\DependencyInjection\Compiler\PassConfig;

require_once 'phpstorm.civix.php';
// phpcs:disable
use CRM_PhpStorm_ExtensionUtil as E;
// phpcs:enable

/**
 * Determine the folder where we will store PhpStorm metadata files.
 *
 *  Not 100% sure which is best. These options trade-off in edge-cases of writability and readability:
 *  - '[civicrm.files]/.phpstorm.meta.php'
 *  - '[civicrm.compile]/.phpstorm.meta.php'
 *  - '[civicrm.root]/.phpstorm.meta.php'
 *
 * @return string
 */
function phpstorm_metadata_dir(): ?string {
  $candidates = _phpstorm_metadata_dirs();
  foreach ($candidates as $candidate) {
    if (file_exists($candidate) && is_writable($candidate)) {
      return $candidate;
    }
    if (!file_exists($candidate) && is_writable(dirname($candidate))) {
      return $candidate;
    }
  }
  \Civi::log()->error("Failed to find writeable folder for PhpStorm metadata. Candidates: " . implode(",", $candidates));
  return NULL;
}

function _phpstorm_metadata_dirs(): array {
  $dirs = [];
  if (CRM_Utils_Constant::value('CIVICRM_PHPSTORM_METADATA')) {
    $dirs[] = CRM_Utils_Constant::value('CIVICRM_PHPSTORM_METADATA');
  }
  $dirs[] = E::path('.phpstorm.meta.php');
  $dirs[] = \Civi::paths()->getPath('[civicrm.files]/.phpstorm.meta.php');
  return $dirs;
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function phpstorm_civicrm_config(&$config): void {
  _phpstorm_civix_civicrm_config($config);
}

function phpstorm_civicrm_container(\Symfony\Component\DependencyInjection\ContainerBuilder $container) {
  $container->addCompilerPass(new \Civi\PhpStorm\PhpStormCompilePass(), PassConfig::TYPE_AFTER_REMOVING, 2000);
}

function phpstorm_civicrm_managed(&$entities, $modules) {
  // We don't currently have an event for extensions to join the "system flush" operation. Apply Skullduggery method.
  // This gives a useful baseline event for most generators -- but it's _not_ for "services", and each generator may be supplemented
  // by other events.
  if ($modules === NULL && !defined('CIVICRM_TEST')) {
    Civi::dispatcher()->dispatch('civi.phpstorm.flush');
  }
}

function phpstorm_civicrm_enable(): void {
  // Remove any stale files from old versions.
  _phpstorm_cleanup();
}

function phpstorm_civicrm_uninstall(): void {
  _phpstorm_cleanup();
}

function _phpstorm_cleanup(): void {
  $dirs = _phpstorm_metadata_dirs();
  foreach ($dirs as $dir) {
    if (file_exists($dir)) {
      CRM_Utils_File::cleanDir($dir, TRUE);
    }
  }
}
