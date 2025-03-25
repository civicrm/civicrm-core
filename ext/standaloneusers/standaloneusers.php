<?php

// Define default URL to haveibeenpwned service. Set this empty in settings to disable.
if (!defined('CIVICRM_HIBP_URL')) {
  define('CIVICRM_HIBP_URL', 'https://api.pwnedpasswords.com/range/');
}

require_once 'standaloneusers.civix.php';
use CRM_Standaloneusers_ExtensionUtil as E;

function standaloneusers_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  if ($bundle->name !== 'coreResources') {
    return;
  }
  // This adds a few styles that only need apply to standalone, mainly
  // providing a default style for login/password reset type pages.
  $bundle->addStyleFile('standaloneusers', 'css/standalone.css');
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function standaloneusers_civicrm_config(&$config) {
  _standaloneusers_civix_civicrm_config($config);

  // set system timezone based on logged in user
  \CRM_Utils_System::setTimeZone();

  $sess = CRM_Core_Session::singleton();

  if (!empty($sess->get('ufID'))) {
    // Logged in user is making a request.
    if (empty($sess->get('lastAccess')) || (time() - $sess->get('lastAccess')) >= 60) {
      // Once a minute, update the when_last_accessed field
      CRM_Standaloneusers_BAO_User::updateLastAccessed();
    }
  }
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function standaloneusers_civicrm_install() {
  _standaloneusers_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function standaloneusers_civicrm_enable() {
  _standaloneusers_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_permission().
 */
function standaloneusers_civicrm_permission(&$permissions) {
  $permissions['access password resets'] = [
    'label' => E::ts('CiviCRM Standalone Users: Allow users to access the reset password system'),
  ];
  // provide expected cms: permissions.
  //
  // This duplicates the list from CRM_Core_Permission_Base::getAvailablePermissions.
  // It may be cleaner to extend via CRM_Core_Permission_Standalone::getAvailablePermissions (call parent and flip is_synthetic).
  $permissions['cms:administer users'] = [
    'label' => E::ts('CiviCRM Standalone Users: Administer user accounts'),
    'implies' => ['cms:view user account'],
  ];
  $permissions['cms:view user account'] = [
    'label' => E::ts('CiviCRM Standalone Users: View user accounts'),
  ];
  $permissions['cms:bypass maintenance mode'] = [
    'label' => ts('CiviCRM Standalone Users: Bypass maintenance mode'),
    'description' => ts('Allow to bypass maintenance mode checks - e.g. when using AJAX API'),
  ];
}

function standaloneusers_civicrm_navigationMenu(&$menu) {
  _standaloneusers_civix_insert_navigation_menu($menu, 'Administer/Users and Permissions', [
    'label' => E::ts('Login settings'),
    'name' => 'standaloneusers_mfa',
    'url' => 'civicrm/admin/setting/standaloneusers?reset=1',
    'permission' => 'cms:administer users',
  ]);
}

/**
 * Implements search tasks hook to add the `sendPasswordReset` action
 *
 * @param array $tasks
 * @param bool $checkPermissions
 * @param int|null $userId
 */
function standaloneusers_civicrm_searchKitTasks(array &$tasks, bool $checkPermissions, ?int $userId) {
  if ($checkPermissions && !CRM_Core_Permission::check('cms:administer users', $userId)) {
    return;
  }
  $tasks['User']['send_password_reset'] = [
    'title' => E::ts('Send Password Reset'),
    'icon' => 'fa-lock',
    'apiBatch' => [
      'action' => 'sendPasswordResetEmail',
      'params' => NULL,
      'confirmMsg' => E::ts('Send password reset email to %1 user(s)?'),
      'runMsg' => E::ts('Sending password reset email(s) to %1 user(s)...'),
      'successMsg' => E::ts('Password reset emails sent to %1 user(s). Note that reset links are valid for 1 hour.'),
      'errorMsg' => E::ts('An error occurred while attempting to send password reset email(s).'),
    ],
  ];
}

/**
 * Alter settings meta where the Standalone meaning is different from CMS meaning
 *
 * @todo more settings that could use this. Also some settings that might be best removed?
 *
 * Implements hook_civicrm_alterSettingsMetaData.
 */
function standaloneusers_civicrm_alterSettingsMetaData(&$settings) {
  $settings['inheritLocale']['title'] = E::ts('Use User Language');
  $settings['inheritLocale']['description'] = E::ts('If Yes, the system will use the Language set on the logged-in user\'s record. This can be changed later if using the CiviCRM language switcher.');
}
