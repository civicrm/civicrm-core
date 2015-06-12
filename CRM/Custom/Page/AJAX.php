<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 *
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_Custom_Page_AJAX {
  /**
   * Get list of options.
   *
   */
  public static function getOptionList() {
    $params = $_REQUEST;

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $options = CRM_Core_BAO_CustomOption::getOptionListSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = array(
      'label',
      'value',
      'is_default',
      'is_active',
      'links',
      'class',
    );

    header('Content-Type: application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($options, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Fix Ordering of options
   *
   */
  public static function fixOrdering() {
    $params = $_REQUEST;

    $queryParams = array(
      1 => array($params['start'], 'Integer'),
      2 => array($params['end'], 'Integer'),
      3 => array($params['gid'], 'Integer'),
    );
    $dao = "SELECT id FROM civicrm_option_value WHERE weight = %1 AND option_group_id = %3";
    $startid = CRM_Core_DAO::singleValueQuery($dao, $queryParams);

    $dao2 = "SELECT id FROM civicrm_option_value WHERE weight = %2 AND option_group_id = %3";
    $endid = CRM_Core_DAO::singleValueQuery($dao2, $queryParams);

    $query = "UPDATE civicrm_option_value SET weight = %2 WHERE id = $startid";
    CRM_Core_DAO::executeQuery($query, $queryParams);

    // increment or decrement the rest by one
    if ($params['start'] < $params['end']) {
      $updateRows = "UPDATE civicrm_option_value
                  SET weight = weight - 1
                  WHERE weight > %1 AND weight < %2 AND option_group_id = %3
                  OR id = $endid";
    }
    else {
      $updateRows = "UPDATE civicrm_option_value
                  SET weight = weight + 1
                  WHERE weight < %1 AND weight > %2 AND option_group_id = %3
                  OR id = $endid";
    }
    CRM_Core_DAO::executeQuery($updateRows, $queryParams);
    CRM_Utils_JSON::output(TRUE);
  }

}
