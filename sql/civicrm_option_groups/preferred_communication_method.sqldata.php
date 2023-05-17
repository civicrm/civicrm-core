<?php
return CRM_Core_CodeGen_OptionGroup::create('preferred_communication_method', 'a/0001')
  ->addMetadata([
    'title' => ts('Preferred Communication Method'),
  ])
  ->addValues(['label', 'name'], [
    ['Phone', 'Phone'],
    ['Email', 'Email'],
    ['Postal Mail', 'Postal Mail'],
    ['SMS', 'SMS'],
    ['Fax', 'Fax'],
  ]);
