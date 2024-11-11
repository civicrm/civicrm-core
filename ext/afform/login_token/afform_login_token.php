<?php

require_once 'afform_login_token.civix.php';

use CRM_AfformLoginToken_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function afform_login_token_civicrm_config(&$config): void {
  _afform_login_token_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function afform_login_token_civicrm_install(): void {
  _afform_login_token_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function afform_login_token_civicrm_enable(): void {
  _afform_login_token_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_check().
 *
 * Afform Login Token requires JWT is an acceptable cred for Auto Login in Authx
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_check
 */
function afform_login_token_civicrm_check(&$messages, $statusNames, $includeDisabled) {
  if (!in_array('jwt', \Civi::settings()->get('authx_auto_cred'))) {
    $messages[] = new \CRM_Utils_Check_Message(
      'afform_login_token_authx',
      E::ts('Afform Login-Tokens requires that JSON Web Tokens are included as an acceptable credential for Auto Login in your AuthX configuration. Please <a href="%1">review your configuration here</a>.', [
        1 => \Civi::url('backend://civicrm/admin/setting/authx'),
      ]),
      E::ts('AuthX Configuration'),
      \Psr\Log\LogLevel::ERROR,
      'fa-chain-broken'
    );
  }
}
