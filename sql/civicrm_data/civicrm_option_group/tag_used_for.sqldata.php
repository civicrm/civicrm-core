<?php
return CRM_Core_CodeGen_OptionGroup::create('tag_used_for', 'a/0046')
  ->addMetadata([
    'title' => ts('Tag Used For'),
    'is_locked' => 1,
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Contacts'), 'Contact', 'civicrm_contact'],
    [ts('Activities'), 'Activity', 'civicrm_activity'],
    [ts('Cases'), 'Case', 'civicrm_case'],
    [ts('Attachments'), 'File', 'civicrm_file'],
  ]);
