<?php

require_once 'recaptcha.civix.php';
// phpcs:disable
use CRM_Recaptcha_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function recaptcha_civicrm_config(&$config) {
  _recaptcha_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function recaptcha_civicrm_xmlMenu(&$files) {
  _recaptcha_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function recaptcha_civicrm_install() {
  _recaptcha_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function recaptcha_civicrm_postInstall() {
  _recaptcha_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function recaptcha_civicrm_uninstall() {
  _recaptcha_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function recaptcha_civicrm_enable() {
  _recaptcha_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function recaptcha_civicrm_disable() {
  _recaptcha_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function recaptcha_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _recaptcha_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function recaptcha_civicrm_managed(&$entities) {
  _recaptcha_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function recaptcha_civicrm_angularModules(&$angularModules) {
  _recaptcha_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function recaptcha_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _recaptcha_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function recaptcha_civicrm_entityTypes(&$entityTypes) {
  _recaptcha_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function recaptcha_civicrm_navigationMenu(&$menu) {
  _recaptcha_civix_insert_navigation_menu($menu, 'Administer/System Settings', [
    'label' => E::ts('reCAPTCHA Settings'),
    'name' => 'recaptcha_settings',
    'url' => 'civicrm/admin/setting/recaptcha',
    'permission' => 'administer CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ]);
  _recaptcha_civix_navigationMenu($menu);
}

/**
 * Intercept form functions
 */
function recaptcha_civicrm_buildForm($formName, &$form) {
  switch ($formName) {
    case 'CRM_Admin_Form_Generic':
      if ($form->getSettingPageFilter() !== 'recaptcha') {
        return;
      }

      $helpText = E::ts(
          'reCAPTCHA is a free service that helps prevent automated abuse of your site. To use it on public-facing CiviCRM forms: sign up at <a href="%1" target="_blank">Google\'s reCaptcha site</a>; enter the provided public and private keys here; then enable reCAPTCHA under Advanced Settings in any Profile.',
          [
            1 => 'https://www.google.com/recaptcha',
          ]
        )
        . '<br/><strong>' . E::ts('Only the reCAPTCHA v2 checkbox type is supported.') . '</strong>';
      \Civi::resources()
        ->addMarkup('<div class="help">' . $helpText . '</div>', [
          'weight' => -1,
          'region' => 'page-body',
        ]);
  }
}
