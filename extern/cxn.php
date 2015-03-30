<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once '../civicrm.config.php';
$config = CRM_Core_Config::singleton();

CRM_Utils_System::loadBootStrap(array(), FALSE);

$apiServer = new \Civi\Cxn\Rpc\ApiServer(new CRM_Cxn_CiviCxnStore());
$apiServer->setLog(new CRM_Utils_SystemLogger());
$apiServer->setRouter(function ($cxn, $entity, $action, $params) {
  $SUPER_PERM = array('administer CiviCRM');

  require_once 'api/v3/utils.php';

  // Note: $cxn and cxnId are authenticated before router is called.
  $dao = new CRM_Cxn_DAO_Cxn();
  $dao->cxn_id = $cxn['cxnId'];
  if (empty($cxn['cxnId']) || !$dao->find(TRUE) || !$dao->cxn_id) {
    return civicrm_api3_create_error('Failed to lookup connection authorizations.');
  }
  if (!$dao->is_active) {
    return civicrm_api3_create_error('Connection is inactive');
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
  Civi\Core\Container::singleton()
    ->get('dispatcher')
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

});
$apiServer->handle(file_get_contents('php://input'))->send();
