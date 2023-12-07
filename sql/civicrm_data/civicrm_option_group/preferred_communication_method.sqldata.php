<?php
return CRM_Core_CodeGen_OptionGroup::create('preferred_communication_method', 'a/0001')
  ->addMetadata([
    'title' => ts('Preferred Communication Method'),
  ])
  ->addValueTable(['label', 'name'], [
    [ts('Phone'), 'Phone'],
    [ts('Email'), 'Email'],
    [ts('Postal Mail'), 'Postal Mail'],
    [ts('SMS'), 'SMS'],
    [ts('Fax'), 'Fax'],
  ]);
