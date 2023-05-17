<?php
return CRM_Core_CodeGen_OptionGroup::create('campaign_type', 'a/0051')
  ->addMetadata([
    'title' => ts('Campaign Type'),
  ])
  ->addValues(['label', 'name', 'value', 'weight'], [
    [ts('Direct Mail'), 'Direct Mail', 1, 1],
    [ts('Referral Program'), 'Referral Program', 2, 1],
    [ts('Constituent Engagement'), 'Constituent Engagement', 3, 1],
  ]);
