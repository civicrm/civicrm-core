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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class provides the functionality for batch profile update for cases
 */
class CRM_Case_Form_Task_Batch extends CRM_Core_Form_Task_Batch {

  /**
   * Must be set to entity table name (eg. civicrm_participant) by child class
   * @var string
   */
  public static $tableName = 'civicrm_case';
  /**
   * Must be set to entity shortname (eg. event)
   * @var string
   */
  public static $entityShortname = 'case';

  /**
   * Get the name of the table for the relevant entity.
   *
   * @return string
   */
  public function getTableName() {
    return $this::$tableName;
  }

  /**
   * Get the query mode (eg. CRM_Core_BAO_Query::MODE_CASE)
   *
   * @return int
   */
  public function getQueryMode() {
    return CRM_Contact_BAO_Query::MODE_CASE;
  }

  /**
   * Get the group by clause for the component.
   *
   * @return string
   */
  public function getEntityAliasField() {
    return $this::$entityShortname . '_id';
  }

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
      $caseStatus = $value['case_status'] ?? NULL;
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
