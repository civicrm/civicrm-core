<?php
return CRM_Core_CodeGen_OptionGroup::create('phone_type', 'a/0033')
  ->addMetadata([
    'title' => ts('Phone Type'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Phone'), 'Phone', 1],
    [ts('Mobile'), 'Mobile', 2],
    [ts('Fax'), 'Fax', 3],
    [ts('Pager'), 'Pager', 4],
    [ts('Voicemail'), 'Voicemail', 5],
  ]);
