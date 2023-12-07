<?php
return CRM_Core_CodeGen_OptionGroup::create('individual_prefix', 'a/0006')
  ->addMetadata([
    'title' => ts('Individual contact prefixes'),
    'description' => ts('CiviCRM is pre-configured with standard options for individual contact prefixes (Ms., Mr., Dr. etc.). Customize these options and add new ones as needed for your installation.'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    [ts('Mrs.'), 'Mrs.', 1],
    [ts('Ms.'), 'Ms.', 2],
    [ts('Mr.'), 'Mr.', 3],
    [ts('Dr.'), 'Dr.', 4],
  ]);
