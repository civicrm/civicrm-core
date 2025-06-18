<?php

require_once 'riverlea.civix.php';
use CRM_riverlea_ExtensionUtil as E;

/**
 * Supply available streams to the theme hook
 *
 * Note: if this looks labour intensive, don't worry - the output
 * is cached in \Civi\Core\Themes
 */
function riverlea_civicrm_themes(&$themes) {
  // always add (hidden) Riverlea base theme
  $themes['_riverlea_core_'] = [
    'ext' => 'riverlea',
    'title' => 'Riverlea: base theme',
    'prefix' => 'core/',
    'search_order' => ['_riverlea_core_', '_fallback_'],
  ];

  try {
    $streams = \Civi::service('riverlea.dynamic_css')->getAvailableStreamMeta();
  }
  catch (\CRM_Core_Exception $e) {
    // dont crash the whole hook if Riverlea is broken
    \CRM_Core_Session::setStatus('Error occured making Riverlea streams available to the theme engine: ' . $e->getMessage());
    return;
  }

  $streamsById = array_column($streams, NULL, 'id');

  foreach ($streams as $name => $stream) {
    $themeMeta = [
      'title' => $stream['label'],
      'search_order' => [],
    ];

    $extension = $stream['extension'];

    // we only add the stream itself to the search order if
    // it has an extension (which indicates it may have its own
    // file overrides)
    if ($extension) {
      $themeMeta['search_order'][] = $name;

      // used to resolve files from this stream
      $themeMeta['ext'] = $extension;
      $themeMeta['prefix'] = $stream['file_prefix'] ?? '';
    }

    $themeMeta['search_order'][] = '_riverlea_core_';
    $themeMeta['search_order'][] = '_fallback_';

    $themes[$name] = $themeMeta;
  }
}

/**
 * Check if current active theme is a Riverlea theme
 * @return bool
 */
function _riverlea_is_active() {
  $themeKey = \Civi::service('themes')->getActiveThemeKey();
  $themeSearchOrder = \Civi::service('themes')->get($themeKey)['search_order'] ?? [];
  return in_array('_riverlea_core_', $themeSearchOrder);
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
      \Civi\Riverlea\DynamicCss::CSS_FILE,
      \Civi::service('riverlea.dynamic_css')->getCssParams()
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
