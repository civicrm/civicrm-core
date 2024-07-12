<?php

require_once 'riverlea.civix.php';
use CRM_riverlea_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function riverlea_civicrm_config(&$config) {
  _riverlea_civix_civicrm_config($config);

  $themeKey = Civi::service('themes')->getActiveThemeKey();
  $themeExt = Civi::service('themes')->get($themeKey)['ext'];
  if ($themeExt !== 'riverlea') {
    return;
  }

  // A riverlea theme is active
  if (Civi::settings()->get('riverlea_hide_cms_menubar')) {
    // If CMS is not WordPress/Joomla the setting won't exist so we won't get here
    // @todo: Uncomment the below line when we have one (see example in theisland theme)
    // Civi::resources()->addStyle(file_get_contents(E::path('css/' . mb_strtolower(CIVICRM_UF) . '-menubar.css')));
  }
}

/**
 * Supports multiple theme variations/streams.
 */

 function riverlea_civicrm_themes(&$themes) {
  $themes['minetta'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: Minetta (~Greenwich)',
    'prefix' => 'streams/minetta/',
    'search_order' => array('minetta', '_riverlea_core_', '_fallback_'),
  );
  $themes['walbrook'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: Walbrook (~Shoreditch/Island)',
    'prefix' => 'streams/walbrook/',
    'search_order' => array('walbrook', '_riverlea_core_', '_fallback_'),
  );
  $themes['hackneybrook'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: Hackney Brook (~Finsbury Park)',
    'prefix' => 'streams/hackneybrook/',
    'search_order' => array('hackneybrook', '_riverlea_core_', '_fallback_'),
  );
  $themes['_riverlea_core_'] = array(
    'ext' => 'riverlea',
    'title' => 'Riverlea: base theme',
    'prefix' => 'core/',
    'search_order' => array('_riverlea_core_', '_fallback_'),
  );
}

/**
 * Implements hook_civicrm_alterBundle(). Add Bootstrap JS.
 */

function riverlea_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  $themeKey = Civi::service('themes')->getActiveThemeKey();
  $themeExt = Civi::service('themes')->get($themeKey)['ext'];
  if ($themeExt !== 'riverlea') {
    return;
  }
  if ($bundle->name === 'bootstrap3') {
    $bundle->clear();
    $bundle->addStyleFile('riverlea', 'css/bootstrap3.css');
    $bundle->addScriptFile('riverlea', 'js/bootstrap.min.js', [
      'translate' => FALSE,
    ]);
    $bundle->addScriptFile('riverlea', 'js/noConflict.js', [
      'translate' => FALSE,
    ]);
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
