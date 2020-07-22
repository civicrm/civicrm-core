<?php

// require_once 'csslib.civix.php';
// phpcs:disable
// use CRM_Csslib_ExtensionUtil as E;
// phpcs:enable

/**
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function csslib_civicrm_container($container) {
  $container->addResource(new \Symfony\Component\Config\Resource\FileResource(__FILE__));
  $container->setDefinition('csslib.scss_compiler', new \Symfony\Component\DependencyInjection\Definition(
    'Civi\Csslib\ScssCompiler',
    []
  ))->setPublic(TRUE);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function csslib_civicrm_config(&$config) {
  // CONSIDER: For core ext's, should we just shift the requirements into main `composer.json`?
  // It's certainly more correct from composer POV. OTOH, it's nice to keep them organized separately.
  // Maybe a merge plugin would work, but I suspect that's problematic for D8/D9-style builds.
  // It may be better to have a preprocess step (a la setup.sh/gencode) to merge?
  if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
  }
  // _csslib_civix_civicrm_config($config);
}

///**
// * Implements hook_civicrm_xmlMenu().
// *
// * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
// */
//function csslib_civicrm_xmlMenu(&$files) {
//  _csslib_civix_civicrm_xmlMenu($files);
//}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function csslib_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  // _csslib_civix_civicrm_alterSettingsFolders($metaDataFolders);
  $settingsDir = __DIR__ . DIRECTORY_SEPARATOR . 'settings';
  if (!in_array($settingsDir, $metaDataFolders) && is_dir($settingsDir)) {
    $metaDataFolders[] = $settingsDir;
  }
}
