<?php

require_once 'riverlea.civix.php';
use CRM_riverlea_ExtensionUtil as E;

/**
 * Supports multiple theme variations/streams.
 */
function riverlea_civicrm_themes(&$themes) {
  $themes['minetta'] = array(
    'ext' => 'riverlea',
    'title' => 'Minetta (RiverLea ~Greenwich)',
    'prefix' => 'streams/minetta/',
    'search_order' => array('minetta', '_riverlea_core_', '_fallback_'),
  );
  $themes['walbrook'] = array(
    'ext' => 'riverlea',
    'title' => 'Walbrook (RiverLea ~Shoreditch/Island)',
    'prefix' => 'streams/walbrook/',
    'search_order' => array('walbrook', '_riverlea_core_', '_fallback_'),
  );
  $themes['hackneybrook'] = array(
    'ext' => 'riverlea',
    'title' => 'Hackney Brook (RiverLea ~Finsbury Park)',
    'prefix' => 'streams/hackneybrook/',
    'search_order' => array('hackneybrook', '_riverlea_core_', '_fallback_'),
  );
  $themes['thames'] = array(
    'ext' => 'riverlea',
    'title' => 'Thames (RiverLea ~Aah)',
    'prefix' => 'streams/thames/',
    'search_order' => array('thames', '_riverlea_core_', '_fallback_'),
  );
  $themes['_riverlea_core_'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: base theme',
    'prefix' => 'core/',
    'search_order' => array('_riverlea_core_', '_fallback_'),
  );
}

/**
 * Check if current active theme is a Riverlea theme
 * @return bool
 */
function _riverlea_is_active() {
  $themeKey = Civi::service('themes')->getActiveThemeKey();
  $themeExt = Civi::service('themes')->get($themeKey)['ext'];
  return ($themeExt === 'riverlea');
}

function riverlea_civicrm_config(&$config) {
  _riverlea_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_alterBundle().
 *
 * Add Bootstrap JS.
 */
function riverlea_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  if (!_riverlea_is_active()) {
    return;
  }

  if ($bundle->name === 'bootstrap3') {
    $bundle->clear();
    $bundle->addStyleFile('riverlea', 'core/css/_bootstrap.css');
    $bundle->addScriptFile('greenwich', 'extern/bootstrap3/assets/javascripts/bootstrap.min.js', [
      'translate' => FALSE,
    ]);
    $bundle->addScriptFile('greenwich', 'js/noConflict.js', [
      'translate' => FALSE,
    ]);
  }
  if ($bundle->name === 'coreResources') {
    // get DynamicCss asset
    $bundle->addStyleUrl(\Civi::service('asset_builder')->getUrl(
      \Civi\riverlea\DynamicCss::CSS_FILE,
      \Civi\riverlea\DynamicCss::getCssParams()
    ));
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function riverlea_civicrm_install() {
  _riverlea_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function riverlea_civicrm_enable() {
  _riverlea_civix_civicrm_enable();
}
