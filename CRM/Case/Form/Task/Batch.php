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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */

/**
 * This class provides the functionality for batch profile update for cases
 */
class CRM_Case_Form_Task_Batch extends CRM_Core_Form_Task_Batch {

  // Must be set to entity table name (eg. civicrm_participant) by child class
  static $tableName = 'civicrm_case';
  // Must be set to entity shortname (eg. event)
  static $entityShortname = 'case';

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @return void
   */
  public function postProcess() {
    $params = $this->exportValues();

    if (!isset($params['field'])) {
      CRM_Core_Session::setStatus(ts('No updates have been saved.'), ts('Not Saved'), 'alert');
      return;
    }

    $customFields = [];
    $dateFields = [
      'case_created_date',
      'case_start_date',
      'case_end_date',
      'case_modified_date',
    ];
    foreach ($params['field'] as $key => $value) {
      $value['id'] = $key;

      if (!empty($value['case_type'])) {
        $caseTypeId = $value['case_type_id'] = $value['case_type'][1];
      }
      unset($value['case_type']);

      // Get the case status
      $daoClass = 'CRM_Case_DAO_Case';
      $caseStatus = CRM_Utils_Array::value('case_status', $value);
      if (!$caseStatus) {
        // default to existing status ID
        $caseStatus = CRM_Core_DAO::getFieldValue($daoClass, $key, 'status_id');
      }
      $value['status_id'] = $caseStatus;
      unset($value['case_status']);

      foreach ($dateFields as $val) {
        if (isset($value[$val])) {
          $value[$val] = CRM_Utils_Date::processDate($value[$val]);
        }
      }
      if (empty($customFields)) {
        if (empty($value['case_type_id'])) {
          $caseTypeId = CRM_Core_DAO::getFieldValue('CRM_Case_DAO_Case', $key, 'case_type_id');
        }

        // case type custom data
        $customFields = CRM_Core_BAO_CustomField::getFields('Case', FALSE, FALSE, $caseTypeId);

        $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
          CRM_Core_BAO_CustomField::getFields('Case',
            FALSE, FALSE, NULL, NULL, TRUE
          )
        );
      }
      //check for custom data
      // @todo extract submit functions &
      // extend CRM_Event_Form_Task_BatchTest::testSubmit with a data provider to test
      // handling of custom data, specifically checkbox fields.
      $value['custom'] = CRM_Core_BAO_CustomField::postProcess($params['field'][$key],
        $key,
        'Case',
        $caseTypeId
      );

      $case = CRM_Case_BAO_Case::add($value);

      // add custom field values
      if (!empty($value['custom']) && is_array($value['custom'])) {
        CRM_Core_BAO_CustomValueTable::store($value['custom'], 'civicrm_case', $case->id);
      }
    }

    CRM_Core_Session::setStatus(ts('Your updates have been saved.'), ts('Saved'), 'success');
  }

}
