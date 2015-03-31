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

  if (!CRM_Cxn_BAO_Cxn::isAppMetaVerified()) {
    $spec['app_meta_url'] = array(
      'name' => 'app_meta_url',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => ts('Application Metadata URL'),
      'description' => 'Application Metadata URL',
      'maxlength' => 255,
      'size' => CRM_Utils_Type::HUGE,
    );
  }
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
    if (!CRM_Cxn_BAO_Cxn::isAppMetaVerified()) {
      list ($status, $json) = CRM_Utils_HttpClient::singleton()->get($params['app_meta_url']);
      if (CRM_Utils_HttpClient::STATUS_OK != $status) {
        throw new API_Exception("Failed to download appMeta.");
      }
      $appMeta = json_decode($json, TRUE);
    }
    else {
      // Note: The metadata includes a cert, but the details aren't signed.
      // This is very useful in testing/development. In ordinary usage, we
      // rely on civicrm.org to sign the metadata for all apps en masse.
      throw new API_Exception('This site is configured to only connect to applications with verified metadata.');
    }
  }
  elseif (!empty($params['app_guid'])) {
    $appMeta = civicrm_api3('CxnApp', 'getsingle', array(
      'appId' => $params['app_guid'],
    ));
  }

  if (empty($appMeta) || !is_array($appMeta)) {
    throw new API_Exception("Missing expected parameter: app_guid");
  }
  \Civi\Cxn\Rpc\AppMeta::validate($appMeta);

  try {
    /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
    $client = \Civi\Core\Container::singleton()->get('cxn_reg_client');
    list($cxnId, $result) = $client->register($appMeta);
    CRM_Cxn_BAO_Cxn::updateAppMeta($appMeta);
  }
  catch (Exception $e) {
    CRM_Cxn_BAO_Cxn::updateAppMeta($appMeta);
    throw $e;
  }

  return $result;
}

function _civicrm_api3_cxn_unregister_spec(&$spec) {
  $daoFields = CRM_Cxn_DAO_Cxn::fields();
  $spec['cxn_guid'] = $daoFields['cxn_guid'];
  $spec['app_guid'] = $daoFields['app_guid'];
  $spec['force'] = array(
    'name' => 'force',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'title' => ts('Force'),
    'description' => 'Destroy connection even if the remote application is non-responsive.',
    'default' => '0',
  );
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
  $cxnId = NULL;

  if (!empty($params['cxn_guid'])) {
    $cxnId = $params['cxn_guid'];
  }
  elseif (!empty($params['app_guid'])) {
    $cxnId = CRM_Core_DAO::singleValueQuery('SELECT cxn_guid FROM civicrm_cxn WHERE app_guid = %1', array(
      1 => array($params['app_guid'], 'String'),
    ));
    if (!$cxnId) {
      throw new API_Exception("The app_guid does not correspond to an active connection.");
    }
  }
  if (!$cxnId) {
    throw new API_Exception('Missing required parameter: cxn_guid');
  }

  $appMeta = CRM_Cxn_BAO_Cxn::getAppMeta($cxnId);

  /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
  $client = \Civi\Core\Container::singleton()->get('cxn_reg_client');
  list($cxnId, $result) = $client->unregister($appMeta, CRM_Utils_Array::value('force', $params, FALSE));

  return $result;
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
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
