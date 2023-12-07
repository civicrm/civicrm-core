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
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function recaptcha_civicrm_install() {
  _recaptcha_civix_civicrm_install();
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

  CRM_Utils_ReCAPTCHA::checkAndAddCaptchaToForm($formName, $form);
}
