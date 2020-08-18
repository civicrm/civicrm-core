<?php

require_once 'greenwich.civix.php';
// phpcs:disable
use CRM_Greenwich_ExtensionUtil as E;
// phpcs:enable

// REVERT ME - need better activation protocol
//function greenwich_civicrm_coreResourceList(&$items, $region) {
//  if ($region == 'html-header') {
//    CRM_Core_Resources::singleton()->addStyleFile('civicrm', 'css/bootstrap.css', -50, 'html-header');
//  }
//}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function greenwich_civicrm_config(&$config) {
  _greenwich_civix_civicrm_config($config);
}

///**
// * Implements hook_civicrm_xmlMenu().
// *
// * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
// */
//function greenwich_civicrm_xmlMenu(&$files) {
//  _greenwich_civix_civicrm_xmlMenu($files);
//}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function greenwich_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _greenwich_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_themes().
 */
function greenwich_civicrm_themes(&$themes) {
  _greenwich_civix_civicrm_themes($themes);
  $themes['greenwich'] = [
    'ext' => 'civicrm',
    'title' => 'Greenwich',
    'help' => ts('CiviCRM 4.x look-and-feel'),
  ];
}

/**
 * Implements hook_civicrm_alterBundle().
 */
function greenwich_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  $theme = Civi::service('themes')->getActiveThemeKey();
  switch ($theme . ':' . $bundle->name) {
    case 'greenwich:bootstrap3':
      $bundle->clear();
      $bundle->addStyleUrl(Civi::service('asset_builder')->getUrl('greenwich-bootstrap.css'));
      $bundle->addScriptFile('greenwich', 'extern/bootstrap3/assets/javascripts/bootstrap.min.js', [
        'translate' => FALSE,
      ]);
      break;
  }
}

/**
 * @param $asset
 * @param $params
 * @param $mimeType
 * @param $content
 *
 * @see CRM_Utils_Hook::buildAsset()
 */
function greenwich_civicrm_buildAsset($asset, $params, &$mimeType, &$content) {
  if ($asset === 'greenwich-bootstrap.css') {
    $mimeType = 'text/css';
    $content = Civi::service('csslib.scss_compiler')->compile(
      // Apply prefix to all Bootstrap styles so that Greenwich plays nice with CMS themes.
      '#bootstrap-theme { @import "greenwich"; @import "bootstrap"; }',
      [E::path('extern/bootstrap3/assets/stylesheets'), E::path('scss')]
    );
  }
}
