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
    'name' => 'CRM_iATS_Form_Report_ContributeDetail',
    'entity' => 'ReportTemplate',
    'params' =>
    array(
      'version' => 3,
      'label' => 'iATS Payments - Contribution Reconciliation',
      'description' => 'Donor Report (Detail) Report with extra iATS Reconciliation fields.',
      'class_name' => 'CRM_iATS_Form_Report_ContributeDetail',
      'report_url' => 'com.iatspayments.com/contributedetail',
      'component' => 'CiviContribute',
    ),
  ),
);
