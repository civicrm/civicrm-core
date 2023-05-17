<?php
return CRM_Core_CodeGen_OptionGroup::create('contribution_status', 'a/0011')
  ->addMetadata([
    'title' => ts('Contribution Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value', 'weight'], [
    [ts('Completed'), 'Completed', 1, 1, 'is_reserved' => 1],
    [ts('Pending'), 'Pending', 2, 2, 'is_reserved' => 1],
    [ts('Cancelled'), 'Cancelled', 3, 3, 'is_reserved' => 1],
    [ts('Failed'), 'Failed', 4, 4, 'is_reserved' => 1],
    [ts('Refunded'), 'Refunded', 7, 7, 'is_reserved' => 1],
    [ts('Partially paid'), 'Partially paid', 8, 8, 'is_reserved' => 1],
    [ts('Pending refund'), 'Pending refund', 9, 9, 'is_reserved' => 1],
    [ts('Chargeback'), 'Chargeback', 10, 10, 'is_reserved' => 1],
    [ts('Template'), 'Template', 11, 11, 'description' => ts('Status for contribution records which represent a template for a recurring contribution rather than an actual contribution. This status is transitional, to ensure that said contributions don\\\'t appear in reports. The is_template field is the preferred way to find and filter these contributions.'), 'is_reserved' => 1],
  ]);
