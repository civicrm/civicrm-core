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
    // get DynamicCss asset URL
    $riverUrl = \Civi::service('asset_builder')->getUrl(
      \Civi\Riverlea\DynamicCss::CSS_FILE,
      \Civi::service('riverlea.dynamic_css')->getCssParams()
    );

    $bundle->addStyleUrl($riverUrl);

    // TODO: add a non-admin permission for using Previewer
    if (\CRM_Core_Permission::check('administer CiviCRM')) {
      \Civi::resources()->addScriptFile('riverlea', 'js/previewer.js');

      // pass the river url and dark mode setting to the clientside
      // so the previewer can easily work with them
      $darkModeSetting = \CRM_Utils_System::isFrontendPage() ? 'riverlea_dark_mode_frontend' : 'riverlea_dark_mode_backend';
      $darkModeSettingValue = \Civi::settings()->get($darkModeSetting);
      \Civi::resources()->addVars('riverlea', [
        'river_url' => $riverUrl,
        'dark_mode' => $darkModeSettingValue,
      ]);
    }
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

/**
 * Implements search tasks hook to add the `activate` action
 *
 * @param array $tasks
 * @param bool $checkPermissions
 * @param int|null $userId
 */
function riverlea_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userId) {
  if ($checkPermissions && !CRM_Core_Permission::check('administer CiviCRM', $userId)) {
    return;
  }
  $tasks['RiverleaStream']['activate_backend'] = [
    'title' => E::ts('Activate for Backend'),
    'icon' => 'fa-briefcase',
    'number' => '=== 1',
    'apiBatch' => [
      'action' => 'activate',
      'params' => ['backOrFront' => 'backend'],
      'confirmMsg' => E::ts('Activate stream for backend pages?'),
      'runMsg' => E::ts('Activating stream...'),
      'successMsg' => E::ts('Stream activated. You may need to refresh the page or clear your browser cache to see the full effect.'),
      'errorMsg' => E::ts('An error occurred while attempting to activate the stream.'),
    ],
  ];
  $tasks['RiverleaStream']['activate_frontend'] = [
    'title' => E::ts('Activate for Frontend'),
    'icon' => 'fa-shop',
    'number' => '=== 1',
    'apiBatch' => [
      'action' => 'activate',
      'params' => ['backOrFront' => 'frontend'],
      'confirmMsg' => E::ts('Activate stream for frontend pages?'),
      'runMsg' => E::ts('Activating stream...'),
      'successMsg' => E::ts('Stream activated. You may need to refresh the page or clear your browser cache to see the full effect.'),
      'errorMsg' => E::ts('An error occurred while attempting to activate the stream.'),
    ],
  ];
  $tasks['RiverleaStream']['preview'] = [
    'title' => E::ts('Preview'),
    'icon' => 'fa-eye',
    'number' => '=== 1',
    'crmPopup' => [
      'path' => "/civicrm",
      'data' => "{name: name.join(',')",
    ],
  ];
}
