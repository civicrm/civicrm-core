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
    $authxTs = fn($s, $p = []) => _ts($s, [...$p, 'domain' => ['authx', NULL]]);
    $messages[] = new \CRM_Utils_Check_Message(
      'afform_login_token_authx',
      E::ts('This extension uses JSON Web Tokens (JWT) for automatic login, but that feature is disabled. Please review the <a href="%1">Authentication settings</a> and update the "<em>%2</em>".', [
        1 => \Civi::url('backend://civicrm/admin/setting/authx'),
        2 => $authxTs('Acceptable credentials (%1)', [
          1 => $authxTs('Auto Login', []),
        ]),
      ]),
      E::ts('Form Core Login-Tokens'),
      \Psr\Log\LogLevel::ERROR,
      'fa-chain-broken'
    );
  }
}
