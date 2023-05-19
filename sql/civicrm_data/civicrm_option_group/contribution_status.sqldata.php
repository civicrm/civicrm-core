<?php
return CRM_Core_CodeGen_OptionGroup::create('contribution_status', 'a/0011')
  ->addMetadata([
    'title' => ts('Contribution Status'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Completed'), 'Completed', 1],
    [ts('Pending'), 'Pending', 2],
    [ts('Cancelled'), 'Cancelled', 3],
    [ts('Failed'), 'Failed', 4],
    // 5 and 6 went fishing.
    [ts('Refunded'), 'Refunded', 7],
    [ts('Partially paid'), 'Partially paid', 8],
    [ts('Pending refund'), 'Pending refund', 9],
    [ts('Chargeback'), 'Chargeback', 10],
    [ts('Template'), 'Template', 11, 'description' => ts('Status for contribution records which represent a template for a recurring contribution rather than an actual contribution. This status is transitional, to ensure that said contributions don\\\'t appear in reports. The is_template field is the preferred way to find and filter these contributions.')],
  ])
  ->addDefaults([
    'is_reserved' => 1,
  ])
  ->syncColumns('fill', ['value' => 'weight']);
