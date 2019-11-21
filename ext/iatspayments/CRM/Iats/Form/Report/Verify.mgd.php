<?php

/**
 * @file
 * This file declares a managed database record of type "ReportTemplate".
 */

// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
  0 =>
  array(
    'name' => 'CRM_Iats_Form_Report_Verify',
    'entity' => 'ReportTemplate',
    'params' =>
    array(
      'version' => 3,
      'label' => 'iATS Payments - Verify',
      'description' => 'iATS Payments - Verify Report',
      'class_name' => 'CRM_Iats_Form_Report_Verify',
      'report_url' => 'com.iatspayments.com/verify',
      'component' => 'CiviContribute',
    ),
  ),
);
