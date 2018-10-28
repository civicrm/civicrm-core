<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
  0 =>
  array(
    'name' => 'Cron:Job.Iatsreport',
    'entity' => 'Job',
    'params' =>
    array(
      'version' => 3,
      'name' => 'iATS Payments Get Transaction Journal',
      'description' => 'Call into iATS to get transaction journals (for auditing and verifying).',
      'run_frequency' => 'Hourly',
      'api_entity' => 'Job',
      'api_action' => 'iatsreport',
      'parameters' => '',
    ),
    'update' => 'never',
  ),
);
