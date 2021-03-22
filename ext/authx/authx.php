<?php

require_once 'authx.civix.php';
// phpcs:disable
use CRM_Authx_ExtensionUtil as E;
// phpcs:enable

Civi::dispatcher()->addListener('civi.invoke.auth', function($e) {
  $params = ($_SERVER['REQUEST_METHOD'] === 'GET') ? $_GET : $_POST;
  $siteKey = $_SERVER['HTTP_X_CIVI_KEY'] ?? $params['_authxSiteKey'] ?? NULL;

  if (!empty($_SERVER['HTTP_X_CIVI_AUTH'])) {
    return (new \Civi\Authx\Authenticator())->auth($e, ['flow' => 'xheader', 'cred' => $_SERVER['HTTP_X_CIVI_AUTH'], 'siteKey' => $siteKey]);
  }

  if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
    return (new \Civi\Authx\Authenticator())->auth($e, ['flow' => 'header', 'cred' => $_SERVER['HTTP_AUTHORIZATION'], 'siteKey' => $siteKey]);
  }

  if (!empty($params['_authx'])) {
    if ((implode('/', $e->args) === 'civicrm/authx/login')) {
      (new \Civi\Authx\Authenticator())->auth($e, ['flow' => 'login', 'cred' => $params['_authx'], 'useSession' => TRUE, 'siteKey' => $siteKey]);
      _authx_redact(['_authx']);
    }
    elseif (!empty($params['_authxSes'])) {
      (new \Civi\Authx\Authenticator())->auth($e, ['flow' => 'auto', 'cred' => $params['_authx'], 'useSession' => TRUE, 'siteKey' => $siteKey]);
      if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        _authx_reload(implode('/', $e->args), $_SERVER['QUERY_STRING']);
      }
      else {
        _authx_redact(['_authx', '_authxSes']);
      }
    }
    else {
      (new \Civi\Authx\Authenticator())->auth($e, ['flow' => 'param', 'cred' => $params['_authx'], 'siteKey' => $siteKey]);
      _authx_redact(['_authx']);
    }
  }
});

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
    if (CRM_Utils_String::startsWith($key, '_authx')) {
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
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function authx_civicrm_xmlMenu(&$files) {
  _authx_civix_civicrm_xmlMenu($files);
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
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function authx_civicrm_postInstall() {
  _authx_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function authx_civicrm_uninstall() {
  _authx_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function authx_civicrm_enable() {
  _authx_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function authx_civicrm_disable() {
  _authx_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function authx_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _authx_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function authx_civicrm_managed(&$entities) {
  _authx_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function authx_civicrm_caseTypes(&$caseTypes) {
  _authx_civix_civicrm_caseTypes($caseTypes);
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
function authx_civicrm_angularModules(&$angularModules) {
  _authx_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function authx_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _authx_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function authx_civicrm_entityTypes(&$entityTypes) {
  _authx_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function authx_civicrm_themes(&$themes) {
  _authx_civix_civicrm_themes($themes);
}

/**
 * Implements hook_civicrm_permission().
 *
 * @see CRM_Utils_Hook::permission()
 */
function authx_civicrm_permission(&$permissions) {
  $permissions['authenticate with password'] = E::ts('AuthX: Authenticate to services with password');
  $permissions['authenticate with api key'] = E::ts('AuthX: Authenticate to services with API key');
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function authx_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function authx_civicrm_navigationMenu(&$menu) {
//  _authx_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _authx_civix_navigationMenu($menu);
//}
