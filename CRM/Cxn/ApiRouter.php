<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Cxn_ApiRouter
 *
 * The ApiRouter receives an incoming API request from CiviConnect,
 * validates it, configures permissions, and sends it to the API layer.
 */
class CRM_Cxn_ApiRouter {

  /**
   * @param array $cxn
   * @param string $entity
   * @param string $action
   * @param array $params
   * @return mixed
   */
  public static function route($cxn, $entity, $action, $params) {
    $SUPER_PERM = ['administer CiviCRM'];

    require_once 'api/v3/utils.php';

    // FIXME: Shouldn't the X-Forwarded-Proto check be part of CRM_Utils_System::isSSL()?
    if (Civi::settings()->get('enableSSL') &&
      !CRM_Utils_System::isSSL() &&
      strtolower(CRM_Utils_System::getRequestHeaders()['X_FORWARDED_PROTO'] ?? '') != 'https'
    ) {
      return civicrm_api3_create_error('System policy requires HTTPS.');
    }

    // Note: $cxn and cxnId are authenticated before router is called.
    $dao = new CRM_Cxn_DAO_Cxn();
    $dao->cxn_id = $cxn['cxnId'];
    if (empty($cxn['cxnId']) || !$dao->find(TRUE) || !$dao->cxn_id) {
      return civicrm_api3_create_error('Failed to lookup connection authorizations.');
    }
    if (!$dao->is_active) {
      return civicrm_api3_create_error('Connection is inactive.');
    }
    if (!is_string($entity) || !is_string($action) || !is_array($params)) {
      return civicrm_api3_create_error('API parameters are malformed.');
    }
    if (
      empty($cxn['perm']['api'])
      || !is_array($cxn['perm']['api'])
      || empty($cxn['perm']['grant'])
      || !(is_array($cxn['perm']['grant']) || is_string($cxn['perm']['grant']))
    ) {
      return civicrm_api3_create_error('Connection has no permissions.');
    }

    $whitelist = \Civi\API\WhitelistRule::createAll($cxn['perm']['api']);
    \Civi::dispatcher()
      ->addSubscriber(new \Civi\API\Subscriber\WhitelistSubscriber($whitelist));
    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();
    if ($cxn['perm']['grant'] === '*') {
      CRM_Core_Config::singleton()->userPermissionTemp->grant($SUPER_PERM);
    }
    else {
      CRM_Core_Config::singleton()->userPermissionTemp->grant($cxn['perm']['grant']);
    }

    $params['check_permissions'] = 'whitelist';
    return civicrm_api($entity, $action, $params);
  }

}
