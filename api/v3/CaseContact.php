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
 * This api exposes CiviCRM CaseContact records.
 *
 * @package CiviCRM_APIv3
 */

/**
 * Save a CaseContact.
 *
 * @param array $params
 *
 * @return array
 */
function civicrm_api3_case_contact_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'CaseContact');
}

/**
 * @param array $fields
 */
function _civicrm_api3_case_contact_create_spec(&$fields) {
  $fields['contact_id']['api.required'] = $fields['case_id']['api.required'] = 1;
}

/**
 * Get a CaseContact.
 *
 * @param array $params
 *
 * @return array
 *   Array of retrieved case_contact property values.
 */
function civicrm_api3_case_contact_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Delete a CaseContact.
 *
 * @param array $params
 *
 * @return array
 *   Array of deleted values.
 */
function civicrm_api3_case_contact_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * Results formatting for Case entityRef lookups.
 *
 * @param array $result
 * @param array $request
 * @param string $entity
 * @param array $fields
 *
 * @return array
 */
function _civicrm_api3_case_contact_getlist_output($result, $request, $entity, $fields) {
  $output = [];
  if (!empty($result['values'])) {
    foreach ($result['values'] as $row) {
      $data = [
        'id' => $row[$request['id_field']],
        'label' => $row[$request['label_field']] . ' - ' . $row['case_id.case_type_id.title'],
      ];
      $status = CRM_Core_PseudoConstant::getLabel('CRM_Case_BAO_Case', 'status_id', $row['case_id.status_id']);
      $date = CRM_Utils_Date::customFormat($row['case_id.start_date']);
      $data['description'] = [
        "#{$row['case_id']}: $status " . ts('(opened %1)', [1 => $date]),
        $row['case_id.subject'],
      ];
      if (!empty($request['image_field'])) {
        $data['image'] = $row[$request['image_field']] ?? '';
      }
      $output[] = $data;
    }
  }
  return $output;
}
