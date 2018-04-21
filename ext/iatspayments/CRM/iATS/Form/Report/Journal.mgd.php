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
    'name' => 'CRM_iATS_Form_Report_Journal',
    'entity' => 'ReportTemplate',
    'params' =>
    array(
      'version' => 3,
      'label' => 'iATS Payments - Journal',
      'description' => 'iATS Payments - Journal Report',
      'class_name' => 'CRM_iATS_Form_Report_Journal',
      'report_url' => 'com.iatspayments.com/journal',
      'component' => 'CiviContribute',
    ),
  ),
);
