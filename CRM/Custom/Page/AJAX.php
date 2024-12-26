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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class contains the functions that are called using AJAX (jQuery)
 */
class CRM_Custom_Page_AJAX {

  /**
   * This function uses the deprecated v1 datatable api and needs updating. See CRM-16353.
   * @deprecated
   */
  public static function getOptionList() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $params = $_REQUEST;

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $options = CRM_Core_BAO_CustomOption::getOptionListSelector($params);

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = [
      'label',
      'value',
      'description',
      'is_default',
      'is_active',
      'links',
      'class',
    ];

    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($options, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Fix Ordering of options
   *
   */
  public static function fixOrdering() {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();
    $params = $_REQUEST;

    $queryParams = [
      1 => [$params['start'], 'Integer'],
      2 => [$params['end'], 'Integer'],
      3 => [$params['gid'], 'Integer'],
    ];
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

  /**
   * Get list of Multi Record Fields.
   */
  public static function getMultiRecordFieldList(): void {
    CRM_Core_Page_AJAX::validateAjaxRequestMethod();

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams(0, 10);
    $params['cid'] = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $params['cgid'] = CRM_Utils_Type::escape($_GET['cgid'], 'Integer');

    if (!CRM_Core_BAO_CustomGroup::checkGroupAccess($params['cgid'], CRM_Core_Permission::VIEW) ||
      !CRM_Contact_BAO_Contact_Permission::allow($params['cid'], CRM_Core_Permission::VIEW)
    ) {
      CRM_Utils_System::permissionDenied();
    }

    $contactType = CRM_Contact_BAO_Contact::getContactType($params['cid']);

    $obj = new CRM_Profile_Page_MultipleRecordFieldsListing();
    $obj->_pageViewType = 'customDataView';
    $obj->_contactId = $params['cid'];
    $obj->_customGroupId = $params['cgid'];
    $obj->_contactType = $contactType;
    $obj->_DTparams['offset'] = ($params['page'] - 1) * $params['rp'];
    $obj->_DTparams['rowCount'] = $params['rp'];
    if (!empty($params['sortBy'])) {
      $obj->_DTparams['sort'] = $params['sortBy'];
    }

    [$fields, $attributes] = $obj->browse();

    // format params and add class attributes
    $fieldList = [];
    foreach ($fields as $id => $value) {
      foreach ($value as $fieldId => &$fieldName) {
        if (!empty($attributes[$fieldId][$id]['class'])) {
          $fieldName = ['data' => $fieldName, 'cellClass' => $attributes[$fieldId][$id]['class']];
        }
        if (is_numeric($fieldId)) {
          $fName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldId, 'column_name');
          CRM_Utils_Array::crmReplaceKey($value, $fieldId, $fName);
        }
      }
      array_push($fieldList, $value);
    }
    $totalRecords = !empty($obj->_total) ? $obj->_total : 0;

    $multiRecordFields = [];
    $multiRecordFields['data'] = $fieldList;
    $multiRecordFields['recordsTotal'] = $totalRecords;
    $multiRecordFields['recordsFiltered'] = $totalRecords;

    CRM_Utils_JSON::output($multiRecordFields);
  }

}
