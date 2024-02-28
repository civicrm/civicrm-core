<?php

// Define default URL to haveibeenpwned service. Set this empty in settings to disable.
if (!defined('CIVICRM_HIBP_URL')) {
  define('CIVICRM_HIBP_URL', 'https://api.pwnedpasswords.com/range/');
}

require_once 'standaloneusers.civix.php';
// phpcs:disable
use CRM_Standaloneusers_ExtensionUtil as E;
// phpcs:enable


function standaloneusers_civicrm_alterBundle(CRM_Core_Resources_Bundle $bundle) {
  if ($bundle->name !== 'coreResources') {
    return;
  }
  // This adds a few styles that only need apply to standalone, mainly
  // providing a default style for login/password reset type pages.
  $bundle->addStyleFile('standaloneusers', 'css/standalone.css');
}

/**
 * Hide the inherit CMS language on the Settings - Localization form.
 *
 * Implements hook_civicrm_buildForm().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_buildForm/
 */
function standaloneusers_civicrm_buildForm($formName, CRM_Core_Form $form) {
  // Administer / Localization / Languages, Currency, Locations
  if ($formName == 'CRM_Admin_Form_Setting_Localization') {
    if ($inheritLocaleElement = $form->getElement('inheritLocale')) {
      $inheritLocaleElement->freeze();
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function standaloneusers_civicrm_config(&$config) {
  _standaloneusers_civix_civicrm_config($config);
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
    'label' => E::ts('Allow users to access the reset password system'),
  ];
  // Concrete implementations of synthetic cms: permissions.
  $permissions['administer users'] = [
    'label' => E::ts('Administer user accounts'),
  ];
  $permissions['view user account'] = [
    'label' => E::ts('View user accounts'),
  ];
}
