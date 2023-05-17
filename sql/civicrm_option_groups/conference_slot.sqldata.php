<?php
return CRM_Core_CodeGen_OptionGroup::create('conference_slot', 'a/0063')
  ->addMetadata([
    'title' => ts('Conference Slot'),
  ])
  ->addValues(['label', 'name', 'value'], [
    [ts('Morning Sessions'), 'Morning Sessions', 1],
    [ts('Evening Sessions'), 'Evening Sessions', 2],
  ]);
