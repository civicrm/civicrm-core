<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

/**
 * This class helps to manage connections to third-party apps.
 */
class CRM_Cxn_BAO_Cxn extends CRM_Cxn_DAO_Cxn {
  public static function getSiteCallbackUrl() {
    $config = CRM_Core_Config::singleton();
    if (preg_match('/^(http|https):/', $config->resourceBase)) {
      $civiUrl = $config->resourceBase;
    }
    else {
      $civiUrl = rtrim(CRM_Utils_System::baseURL(), '/') . '/' . ltrim($config->resourceBase, '/');
    }
    return rtrim($civiUrl, '/') . '/extern/cxn.php';
  }

  public static function updateAppMeta($appMeta) {
    \Civi\Cxn\Rpc\AppMeta::validate($appMeta);
    CRM_Core_DAO::executeQuery('UPDATE civicrm_cxn SET app_meta = %1 WHERE app_id = %2', array(
      1 => array(json_encode($appMeta), 'String'),
      2 => array($appMeta['appId'], 'String'),
    ));
  }

  public static function getAppMeta($cxnId) {
    $appMetaJson = CRM_Core_DAO::getFieldValue('CRM_Cxn_DAO_Cxn', $cxnId, 'app_meta', 'cxn_id', TRUE);
    $appMeta = json_decode($appMetaJson, TRUE);
    \Civi\Cxn\Rpc\AppMeta::validate($appMeta);
    return $appMeta;
  }

}
