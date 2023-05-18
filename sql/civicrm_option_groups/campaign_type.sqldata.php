<?php
return CRM_Core_CodeGen_OptionGroup::create('campaign_type', 'a/0051')
  ->addMetadata([
    'title' => ts('Campaign Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Direct Mail'), 'Direct Mail', 1],
    [ts('Referral Program'), 'Referral Program', 2],
    [ts('Constituent Engagement'), 'Constituent Engagement', 3],
  ])
  ->addDefaults([
    'weight' => 1,
  ]);
