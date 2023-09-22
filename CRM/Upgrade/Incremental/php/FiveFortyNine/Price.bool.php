<?php
return [
  'civicrm_price_set' => [
    'is_active' => "DEFAULT 1 COMMENT 'Is this price set active'",
    'is_quick_config' => "DEFAULT 0 COMMENT 'Is set if edited on Contribution or Event Page rather than through Manage Price Sets'",
    'is_reserved' => "DEFAULT 0 COMMENT 'Is this a predefined system price set  (i.e. it can not be deleted, edited)?'",
  ],
  'civicrm_price_field' => [
    'is_enter_qty' => "DEFAULT 0 COMMENT 'Enter a quantity for this field?'",
    'is_display_amounts' => "DEFAULT 1 COMMENT 'Should the price be displayed next to the label for each option?'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this price field active'",
    'is_required' => "DEFAULT 1 COMMENT 'Is this price field required (value must be > 1)'",
  ],
  'civicrm_price_field_value' => [
    'is_default' => "DEFAULT 0 COMMENT 'Is this default price field option'",
    'is_active' => "DEFAULT 1 COMMENT 'Is this price field value active'",
  ],
];
