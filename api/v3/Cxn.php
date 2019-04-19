<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * The Cxn API allows a Civi site to initiate a connection to a
 * remote application. There are three primary actions:
 *
 *  - register: Establish a new connection.
 *  - unregister: Destroy an existing connection.
 *  - get: Get a list of existing connections.
 */

/**
 * Adjust metadata for "register" action.
 *
 * @param array $spec
 *   List of fields.
 */
function _civicrm_api3_cxn_register_spec(&$spec) {
  $daoFields = CRM_Cxn_DAO_Cxn::fields();
  $spec['app_guid'] = $daoFields['app_guid'];
  $spec['app_meta_url'] = [
    'name' => 'app_meta_url',
    'type' => CRM_Utils_Type::T_STRING,
    'title' => ts('Application Metadata URL'),
    'description' => 'Application Metadata URL',
    'maxlength' => 255,
    'size' => CRM_Utils_Type::HUGE,
  ];
}

/**
 * Register with a remote application and create a new connection.
 *
 * One should generally identify an application using the app_guid.
 * However, if you need to test a new/experimental application, then
 * disable CIVICRM_CXN_CA and specify app_meta_url.
 *
 * @param array $params
 *   Array with keys:
 *   - app_guid: The unique identifer of the target application.
 *   - app_meta_url: The URL for the application's metadata.
 * @return array
 * @throws Exception
 */
function civicrm_api3_cxn_register($params) {
  if (!empty($params['app_meta_url'])) {
    list ($status, $json) = CRM_Utils_HttpClient::singleton()->get($params['app_meta_url']);
    if (CRM_Utils_HttpClient::STATUS_OK != $status) {
      throw new API_Exception("Failed to download appMeta. (Bad HTTP response)");
    }
    $appMeta = json_decode($json, TRUE);
    if (empty($appMeta)) {
      throw new API_Exception("Failed to download appMeta. (Malformed)");
    }
  }
  elseif (!empty($params['app_guid'])) {
    $appMeta = civicrm_api3('CxnApp', 'getsingle', [
      'appId' => $params['app_guid'],
    ]);
  }

  if (empty($appMeta) || !is_array($appMeta)) {
    throw new API_Exception("Missing expected parameter: app_guid");
  }
  \Civi\Cxn\Rpc\AppMeta::validate($appMeta);

  try {
    /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
    $client = \Civi::service('cxn_reg_client');
    list($cxnId, $result) = $client->register($appMeta);
    CRM_Cxn_BAO_Cxn::updateAppMeta($appMeta);
  }
  catch (Exception $e) {
    CRM_Cxn_BAO_Cxn::updateAppMeta($appMeta);
    throw $e;
  }

  return $result;
}

/**
 * Adjust metadata for cxn unregister.
 *
 * @param array $spec
 */
function _civicrm_api3_cxn_unregister_spec(&$spec) {
  $daoFields = CRM_Cxn_DAO_Cxn::fields();
  $spec['cxn_guid'] = $daoFields['cxn_guid'];
  $spec['app_guid'] = $daoFields['app_guid'];
  $spec['force'] = [
    'name' => 'force',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'title' => ts('Force'),
    'description' => 'Destroy connection even if the remote application is non-responsive.',
    'default' => '0',
  ];
}

/**
 * Unregister with a remote application; destroy an existing connection.
 *
 * Specify app_guid XOR cxn_guid.
 *
 * @param array $params
 *   Array with keys:
 *   - cxn_guid: string
 *   - app_guid: string
 *   - force: bool
 * @return array
 */
function civicrm_api3_cxn_unregister($params) {
  $cxnId = _civicrm_api3_cxn_parseCxnId($params);
  $appMeta = CRM_Cxn_BAO_Cxn::getAppMeta($cxnId);

  /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
  $client = \Civi::service('cxn_reg_client');
  list($cxnId, $result) = $client->unregister($appMeta, CRM_Utils_Array::value('force', $params, FALSE));

  return $result;
}

/**
 * @param array $params
 *   An array with cxn_guid and/or app_guid.
 * @return string
 *   The CxnId. (If not available, then an exception is thrown.)
 *
 * @throws API_Exception
 */
function _civicrm_api3_cxn_parseCxnId($params) {
  $cxnId = NULL;

  if (!empty($params['cxn_guid'])) {
    $cxnId = $params['cxn_guid'];
  }
  elseif (!empty($params['app_guid'])) {
    $cxnId = CRM_Core_DAO::singleValueQuery('SELECT cxn_guid FROM civicrm_cxn WHERE app_guid = %1', [
      1 => [$params['app_guid'], 'String'],
    ]);
    if (!$cxnId) {
      throw new API_Exception("The app_guid does not correspond to an active connection.");
    }
  }
  if (!$cxnId) {
    throw new API_Exception('Missing required parameter: cxn_guid');
  }
  return $cxnId;
}

/**
 * Adjust metadata for cxn get action.
 *
 * @param array $spec
 */
function _civicrm_api3_cxn_get_spec(&$spec) {
  // Don't trust AJAX callers or other external code to modify, filter, or return the secret.
  unset($spec['secret']);
}

/**
 * Returns an array of Cxn records.
 *
 * @param array $params
 *   Array of one or more valid property_name=>value pairs.
 *
 * @return array
 *   API result array.
 */
function civicrm_api3_cxn_get($params) {
  // Don't trust AJAX callers or other external code to modify, filter, or return the secret.
  unset($params['secret']);

  $result = _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
  if (is_array($result['values'])) {
    foreach (array_keys($result['values']) as $i) {
      if (!empty($result['values'][$i]['app_meta'])) {
        $result['values'][$i]['app_meta'] = json_decode($result['values'][$i]['app_meta'], TRUE);
      }
      if (!empty($result['values'][$i]['perm'])) {
        $result['values'][$i]['perm'] = json_decode($result['values'][$i]['perm'], TRUE);
      }
      // Don't trust AJAX callers or other external code to modify, filter, or return the secret.
      unset($result['values'][$i]['secret']);
    }
  }

  return $result;
}

/**
 * Adjust metadata for "getlink" action.
 *
 * @param array $spec
 *   List of fields.
 */
function _civicrm_api3_cxn_getlink_spec(&$spec) {
  $daoFields = CRM_Cxn_DAO_Cxn::fields();
  $spec['app_guid'] = $daoFields['app_guid'];
  $spec['cxn_guid'] = $daoFields['cxn_guid'];
  $spec['page_name'] = [
    'name' => 'page_name',
    'type' => CRM_Utils_Type::T_STRING,
    'title' => ts('Page Type'),
    'description' => 'The type of page (eg "settings")',
    'maxlength' => 63,
    'size' => CRM_Utils_Type::HUGE,
    'api.aliases' => ['page'],
  ];
}

/**
 *
 * @param array $params
 *   Array with keys:
 *   - cxn_guid OR app_guid: string.
 *   - page: string.
 * @return array
 * @throws Exception
 */
function civicrm_api3_cxn_getlink($params) {
  $cxnId = _civicrm_api3_cxn_parseCxnId($params);
  $appMeta = CRM_Cxn_BAO_Cxn::getAppMeta($cxnId);

  if (empty($params['page_name']) || !is_string($params['page_name'])) {
    throw new API_Exception("Invalid page");
  }

  /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
  $client = \Civi::service('cxn_reg_client');
  return $client->call($appMeta, 'Cxn', 'getlink', [
    'page' => $params['page_name'],
  ]);
}

/**
 *
 * @param array $params
 * @return array
 * @throws Exception
 */
function civicrm_api3_cxn_getcfg($params) {
  $result = [
    'CIVICRM_CXN_CA' => defined('CIVICRM_CXN_CA') ? CIVICRM_CXN_CA : NULL,
    'CIVICRM_CXN_VIA' => defined('CIVICRM_CXN_VIA') ? CIVICRM_CXN_VIA : NULL,
    'CIVICRM_CXN_APPS_URL' => defined('CIVICRM_CXN_APPS_URL') ? CIVICRM_CXN_APPS_URL : NULL,
    'siteCallbackUrl' => CRM_Cxn_BAO_Cxn::getSiteCallbackUrl(),
  ];
  return civicrm_api3_create_success($result);
}

/**
 * Creates or modifies a Cxn row.
 *
 * @param array $params
 *   Array with keys:
 *   - id, cxn_guid OR app_guid: string.
 *   - is_active: boolean.
 *   - options: JSON
 * @return page
 * @throws Exception
 */
function civicrm_api3_cxn_create($params) {
  $result = "";

  try {
    // get the ID
    if (!empty($params['id'])) {
      $cxnId = $params['id'];
    }
    else {
      $cxnId = _civicrm_api3_cxn_parseCxnId($params);
    }

    // see if it's sth to update
    if (isset($params['options']) || isset($params['is_active'])) {

      $dao = new CRM_Cxn_DAO_Cxn();
      $dao->id = $cxnId;

      if ($dao->find()) {
        if (isset($params['is_active'])) {
          $dao->is_active = (int) $params['is_active'];
        }
        if (isset($params['options'])) {
          $dao->options = $params['options'];
        }

        $result = $dao->save();
      }

    }
    return civicrm_api3_create_success($result, $params, 'Cxn', 'create');

  }
  catch (Exception $ex) {
    throw $ex;
  }
}
