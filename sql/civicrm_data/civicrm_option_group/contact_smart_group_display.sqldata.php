<?php
return CRM_Core_CodeGen_OptionGroup::create('contact_smart_group_display', 'a/0017')
  ->addMetadata([
    'title' => ts('Contact Smart Group View Options'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Show Smart Groups on Demand'), 'showondemand', 1],
    [ts('Always Show Smart Groups'), 'alwaysshow', 2],
    [ts('Hide Smart Groups'), 'hide', 3],
  ]);
