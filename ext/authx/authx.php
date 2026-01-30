<?php

require_once 'authx.civix.php';
// phpcs:disable
use CRM_Authx_ExtensionUtil as E;
// phpcs:enable

/**
 * Perform a system login.
 *
 * This is useful for backend scripts that need to switch to a specific user.
 *
 * As needed, this will update the Civi session and CMS data.
 *
 * @param array{flow: ?string, useSession: ?bool, principal: ?array, cred: ?string,} $details
 *   Describe the authentication process with these properties:
 *
 *   - string $flow (default 'script');
 *     The type of authentication flow being used
 *     Ex: 'param', 'header', 'auto'
 *   - bool $useSession (default FALSE)
 *     If TRUE, then the authentication should be persistent (in a session variable).
 *     If FALSE, then the authentication should be ephemeral (single page-request).
 *
 *   And then ONE of these properties to describe the user/principal:
 *
 *   - string $cred
 *     The credential, as formatted in the 'Authorization' header.
 *     Ex: 'Bearer 12345', 'Basic ASDFFDSA=='
 *   - array $principal
 *     Description of a validated principal.
 *     Must include 'contactId', 'userId', xor 'user'
 * @return array{contactId: int, userId: ?int, flow: string, credType: string, useSession: bool}
 *   An array describing the authenticated session.
 * @throws \Civi\Authx\AuthxException
 */
function authx_login(array $details): array {
  $defaults = ['flow' => 'script', 'useSession' => FALSE];
  $details = array_merge($defaults, $details);
  $auth = new \Civi\Authx\Authenticator();
  $auth->setRejectMode('exception');
  $auth->auth(NULL, array_merge($defaults, $details));
  return \CRM_Core_Session::singleton()->get("authx");
}

/**
 * @return \Civi\Authx\AuthxInterface
 */
function _authx_uf() {
  $class = 'Civi\\Authx\\' . CIVICRM_UF;
  return class_exists($class) ? new $class() : new \Civi\Authx\None();
}

/**
 * For parameter-based authentication, this option will hide parameters.
 * This is mostly a precaution, hedging against the possibility that some routes
 * make broad use of $_GET or $_PARAMS.
 *
 * @param array $keys
 */
function _authx_redact(array $keys) {
  foreach ($keys as $key) {
    unset($_POST[$key], $_GET[$key], $_REQUEST[$key]);
  }
}

/**
 * Reload the current page-view.
 *
 * @param string $route
 * @param string $queryString
 */
function _authx_reload($route, $queryString) {
  parse_str($queryString, $query);
  foreach (array_keys($query) as $key) {
    if (str_starts_with($key, '_authx')) {
      unset($query[$key]);
    }
  }
  $url = CRM_Utils_System::url($route, $query, TRUE, NULL, FALSE, CRM_Core_Config::singleton()->userSystem->isFrontEndPage());
  CRM_Utils_System::redirect($url);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function authx_civicrm_config(&$config) {
  _authx_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function authx_civicrm_install() {
  _authx_civix_civicrm_install();

}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function authx_civicrm_enable() {
  _authx_civix_civicrm_enable();
  // If the system is already using HTTP `Authorization:` headers before installation/re-activation, then
  // it's probably an extra/independent layer of security.
  // Only activate support for `Authorization:` if this looks like a clean/amenable environment.
  // @link https://github.com/civicrm/civicrm-core/pull/22837
  if (empty($_SERVER['HTTP_AUTHORIZATION']) && NULL === Civi::settings()->getExplicit('authx_header_cred')) {
    Civi::settings()->set('authx_header_cred', ['jwt', 'api_key']);
  }
}

/**
 * Implements hook_civicrm_permission().
 *
 * @see CRM_Utils_Hook::permission()
 */
function authx_civicrm_permission(&$permissions) {
  $permissions['authenticate with password'] = [
    'label' => E::ts('AuthX: Authenticate to services with password'),
    'description' => E::ts('AuthX: Authenticate to services with password'),
  ];
  $permissions['authenticate with api key'] = [
    'label' => E::ts('AuthX: Authenticate to services with API key'),
    'description' => E::ts('AuthX: Authenticate to services with API key'),
  ];
  $permissions['generate any authx credential'] = [
    'label' => E::ts('AuthX: Generate new JWT credentials for other users via the API'),
    'description' => E::ts('AuthX: Generate new JWT credentials for other users via the API'),
  ];
  $permissions['validate any authx credential'] = [
    'label' => E::ts('AuthX: Validate credentials for other users via the API'),
    'description' => E::ts('AuthX: Validate credentials for other users via the API'),
  ];
}
