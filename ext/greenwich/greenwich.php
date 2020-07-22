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
 * Implements hook_civicrm_thems().
 */
function greenwich_civicrm_themes(&$themes) {
  _greenwich_civix_civicrm_themes($themes);
  $themes['greenwich'] = [
    // FIXME: Move assets into this extension
    'ext' => 'civicrm',
    'title' => 'Greenwich (without Bootstrap)',
    'help' => ts('CiviCRM 4.x look-and-feel'),
    'url_callback' => '_greenwhich_resolver',
    'search_order' => ['greenwich', \Civi\Core\Themes::FALLBACK_THEME],
  ];
  $themes['greenwich-bs'] = [
    // FIXME: Move assets into this extension
    'ext' => 'civicrm',
    'title' => 'Greenwich (with Bootstrap)',
    'help' => ts('CiviCRM 4.x look-and-feel'),
    'url_callback' => '_greenwhich_resolver',
    'search_order' => ['greenwich-bs', \Civi\Core\Themes::FALLBACK_THEME],
  ];
}

/**
 * For certain files (eg `bootstrap.css`), we want to customize the resolver.
 * Otherwise, delegate to the normal "simple" resolver.
 *
 * @param \Civi\Core\Themes $themes
 *   The theming subsystem.
 * @param string $themeKey
 *   The active/desired theme key.
 * @param string $cssExt
 *   The extension for which we want a themed CSS file (e.g. "civicrm").
 * @param string $cssFile
 *   File name (e.g. "css/bootstrap.css").
 * @return array|string
 *   List of CSS URLs, or PASSTHRU.
 */
function _greenwhich_resolver($themes, $themeKey, $cssExt, $cssFile) {
  // FIXME: There isn't actually a canonical "$cssExt:$cssFile" for bootstrap.css...
  switch ("$themeKey:$cssExt:$cssFile") {
    case 'greenwich:civicrm:css/bootstrap.css':
      return [];

    case 'greenwich-bs:civicrm:css/bootstrap.css':
      return [Civi::service('asset_builder')->getUrl('greenwich-bootstrap.css')];

  }

  return \Civi\Core\Themes\Resolvers::simple($themes, $themeKey, $cssExt, $cssFile);
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
