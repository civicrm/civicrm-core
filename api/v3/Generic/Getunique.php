<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @package CiviCRM_APIv3
 */

/**
 * Generic api wrapper used to get all unique fields for a given entity.
 *
 * @param array $apiRequest
 *
 * @return mixed
 */
function civicrm_api3_generic_getUnique($apiRequest) {
  $entity = _civicrm_api_get_entity_name_from_camel($apiRequest['entity']);
  $uniqueFields = array();

  $baoName = _civicrm_api3_get_BAO($entity);
  $bao = new $baoName();
  $_entityTable = $bao->tableName();

  $sql = 'SHOW INDEX FROM '.$_entityTable.' WHERE Non_unique = 0';
  $uFields = CRM_Core_DAO::executeQuery($sql)->fetchAll();
  foreach($uFields as $field) {
    // group by Key_name to handle combination indexes
    $uniqueFields[$field['Key_name']][] = $field['Column_name'];
  }

  return civicrm_api3_create_success($uniqueFields);
}

