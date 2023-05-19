<?php
return CRM_Core_CodeGen_OptionGroup::create('mail_approval_status', 'a/0054')
  ->addMetadata([
    'title' => ts('CiviMail Approval Status'),
  ])
  ->addValues([
    [
      'label' => ts('Approved'),
      'value' => 1,
      'name' => 'Approved',
      'is_default' => 1,
      'is_reserved' => 1,
      'component_id' => 4,
      'domain_id' => 1,
    ],
    [
      'label' => ts('Rejected'),
      'value' => 2,
      'name' => 'Rejected',
      'is_reserved' => 1,
      'component_id' => 4,
      'domain_id' => 1,
    ],
    [
      'label' => ts('None'),
      'value' => 3,
      'name' => 'None',
      'is_reserved' => 1,
      'component_id' => 4,
      'domain_id' => 1,
    ],
  ]);
