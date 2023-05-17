<?php
return CRM_Core_CodeGen_OptionGroup::create('currencies_enabled', 'a/0048')
  ->addMetadata([
    'title' => ts('Currencies Enabled'),
  ])
  ->addValueTable(['label', 'name', 'value'], [
    ['USD ($)', 'USD', 'USD', 'is_default' => 1],
    ['CAD ($)', 'CAD', 'CAD'],
    ['EUR (€)', 'EUR', 'EUR'],
    ['GBP (£)', 'GBP', 'GBP'],
    ['JPY (¥)', 'JPY', 'JPY'],
  ]);
