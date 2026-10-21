<?php

require_once 'civi_report.civix.php';

use CRM_Report_ExtensionUtil as E;

/**
 * Implements hook_civicrm_permission_check().
 *
 * Checks access for virtual permissions named `@report:<id>`.
 *
 * @see CRM_Utils_Hook::permission_check()
 */
function civi_report_civicrm_permission_check($permission, &$granted, $contactId) {
  if (str_starts_with($permission, '@report:') && strlen($permission) > 8) {
    $instanceId = (int) substr($permission, 8);
    $granted = CRM_Report_Utils_Report::checkInstancePermission($instanceId, $contactId);
  }
}

/**
 * Implements hook_civicrm_permissionList().
 *
 * @see CRM_Utils_Hook::permissionList()
 */
function civi_report_civicrm_permissionList(&$permissions) {
  $reports = \Civi\Api4\ReportInstance::get(FALSE)
    ->addSelect('id', 'title', 'is_active')
    ->addOrderBy('title', 'DESC')
    ->execute();
  foreach ($reports as $report) {
    $permissions['@report:' . $report['id']] = [
      'group' => 'report',
      'title' => E::ts('CiviReport: %1', [
        1 => $report['title'],
      ]),
      'description' => ts('Permission from report instance %1', [1 => $report['id']]),
      'is_active' => $report['is_active'],
    ];
  }
}
