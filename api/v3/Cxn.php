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
 * @param array $params
 *   Array with keys:
 *   - appMeta: the application's metadata.
 * @return array
 */
function civicrm_api3_cxn_register($params) {
  if (empty($params['appMeta']) && !empty($params['appMetaUrl'])) {
    if (!CRM_Cxn_BAO_Cxn::isAppMetaVerified()) {
      list ($status, $json) = CRM_Utils_HttpClient::singleton()->get($params['appMetaUrl']);
      if (CRM_Utils_HttpClient::STATUS_OK != $status) {
        throw new API_Exception("Failed to download appMeta.");
      }
      $params['appMeta'] = json_decode($json, TRUE);
    }
    else {
      // Note: The metadata includes a cert, but the details aren't signed.
      // This is very useful in testing/development. In ordinary usage, we
      // rely on civicrm.org to sign the metadata for all apps en masse.
      throw new API_Exception('This site is configured to only connect to applications with verified metadata.');
    }
  }

  if (empty($params['appMeta']) || !is_array($params['appMeta'])) {
    throw new API_Exception("Missing expected parameter: appMeta (array)");
  }
  \Civi\Cxn\Rpc\AppMeta::validate($params['appMeta']);

  try {
    /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
    $client = \Civi\Core\Container::singleton()->get('cxn_reg_client');
    list($cxnId, $result) = $client->register($params['appMeta']);
    CRM_Cxn_BAO_Cxn::updateAppMeta($params['appMeta']);
  }
  catch (Exception $e) {
    CRM_Cxn_BAO_Cxn::updateAppMeta($params['appMeta']);
    throw $e;
  }

  return $result;
}

/**
 * @param array $params
 *   Array with keys:
 *   - cxnId: string
 * @return array
 */
function civicrm_api3_cxn_unregister($params) {
  if (empty($params['cxnId'])) {
    throw new API_Exception('Missing required parameter: cxnId');
  }

  $appMeta = CRM_Cxn_BAO_Cxn::getAppMeta($params['cxnId']);

  /** @var \Civi\Cxn\Rpc\RegistrationClient $client */
  $client = \Civi\Core\Container::singleton()->get('cxn_reg_client');
  list($cxnId, $result) = $client->unregister($appMeta, CRM_Utils_Array::value('force', $params, FALSE));

  return $result;
}
